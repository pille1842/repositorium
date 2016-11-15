<?php
namespace Repositorium;

class PlainFileBackend implements Interfaces\FileBackend
{
    protected $container;
    protected $storageDir;

    public function __construct($container)
    {
        $this->container = $container;
        $this->storageDir = rtrim($container->get('settings')['pathToRepository'],
                                    DIRECTORY_SEPARATOR);
    }

    public function fileExists($file)
    {
        return file_exists($this->genFullFileName($file));
    }

    public function isDirectory($file)
    {
        return is_dir($this->genFullFileName($file));
    }

    public function isBinary($file)
    {
        return substr($this->getFileMimetype($file), 0, 4) != 'text';
    }

    public function versionIsBinary($file, $version)
    {
        return substr($this->getFileVersionMimetype($file, $version), 0, 4) != 'text';
    }

    public function getDirectoryFiles($directory)
    {
        return glob($this->genFullFileName($directory) . DIRECTORY_SEPARATOR
                                                       . '*', GLOB_MARK);
    }

    public function getFileMtime($file)
    {
        return filemtime($this->genFullFileName($file));
    }

    public function getFileMimetype($file)
    {
        $finfo = finfo_open(FILEINFO_MIME);

        return finfo_file($finfo, $this->genFullFileName($file));
    }

    public function getFileVersionMimetype($file, $version)
    {
        $finfo = finfo_open(FILEINFO_MIME);

        return finfo_file($finfo, $this->genFullFileName($file));
    }

    public function getFileContent($file)
    {
        return file_get_contents($this->genFullFileName($file));
    }

    public function getFileHistory($file)
    {
        $history = new History($file);
        $history->addVersion('0', 'Plain file backend does not support versioning.',
                                $this->getFileMtime($file));

        return $history;
    }

    public function getFileVersion($file, $version)
    {
        return $this->getFileContent($file);
    }

    public function getVersionMtime($file, $version)
    {
        return $this->getFileMtime($file);
    }

    public function getFileDiff($file, $range)
    {
        return 'diff --git a/helloworld.txt b/helloworld.txt
index e4f37c4..557db03 100644
--- a/helloworld.txt
    b/helloworld.txt
@@ -1  1 @@
-Hello India
 Hello World';
    }

    public function storeFile($file, $content, $commitmsg)
    {
        $arrPath = explode(DIRECTORY_SEPARATOR, $file);
        foreach ($arrPath as $key => $value) {
            $tmpPath = '';
            for ($i = 0; $i < $key; $i++) {
                $tmpPath .= DIRECTORY_SEPARATOR . $arrPath[$i];
            }
            $tmpPath = trim($tmpPath, DIRECTORY_SEPARATOR);
            if (!$this->fileExists($this->genFullFileName($tmpPath))) {
                @mkdir($this->genFullFileName($tmpPath));
            } elseif (!$this->isDirectory($tmpPath)) {
                $this->container->logger->addWarning("Could not create directory $tmpPath ".
                                                     "because it's already a file.");
                return false;
            }
        }
        return file_put_contents($this->genFullFileName($file), $content);
    }

    public function moveFile($file, $target)
    {
        if ($this->fileExists($target)) {
            $this->container->logger->addWarning("Could not move $file because $target already exists.");
            return false;
        }
        $arrPath = explode(DIRECTORY_SEPARATOR, $target);
        foreach ($arrPath as $key => $value) {
            $tmpPath = '';
            for ($i = 0; $i < $key; $i++) {
                $tmpPath .= DIRECTORY_SEPARATOR . $arrPath[$i];
            }
            $tmpPath = trim($tmpPath, DIRECTORY_SEPARATOR);
            if (!$this->fileExists($this->genFullFileName($tmpPath))) {
                @mkdir($this->genFullFileName($tmpPath));
            } elseif (!$this->isDirectory($tmpPath)) {
                $this->container->logger->addWarning("Could not create directory $tmpPath ".
                                                     "because it's already a file.");
                return false;
            }
        }
        return @rename($this->genFullFileName($file), $this->genFullFileName($target));
    }

    public function restoreFileVersion($file, $version)
    {
        return true;
    }

    public function deleteFile($file)
    {
        return @unlink($this->genFullFileName($file));
    }

    public function getStreamInterface($file)
    {
        return new \GuzzleHttp\Psr7\LazyOpenStream($this->genFullFileName($file), 'r');
    }

    public function getVersionStreamInterface($file, $version)
    {
        return new \GuzzleHttp\Psr7\LazyOpenStream($this->genFullFileName($file), 'r');
    }

    private function genFullFileName($file)
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . $file;
    }
}