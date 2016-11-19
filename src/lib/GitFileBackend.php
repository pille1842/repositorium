<?php
namespace Repositorium;

class GitFileBackend extends PlainFileBackend implements Interfaces\FileBackend
{
    protected $executablePath;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->executablePath = $container->get('settings')['gitPath'];
    }
    public function getFileHistory($file)
    {
        $history = new History($file);
        $output = array();
        $status = 0;
        $this->executeCommand('log '.escapeshellarg($file), $output, $status);
        if ($status != 0) {
            return false;
        }
        $commit = '';
        $author = '';
        $date = '';
        $commitmsg = '';
        $inCommit = false;
        foreach ($output as $l) {
            if (substr($l, 0, 6) == 'commit') {
                if ($inCommit) {
                    $history->addVersion(substr($commit, 0, 8), $commitmsg, strtotime($date));
                    $inCommit = false;
                    $commit = '';
                    $author = '';
                    $date = '';
                    $commitmsg = '';
                }
                $commit = trim(substr($l, 7));
                $inCommit = true;
            } elseif (substr($l, 0, 7) == 'Author:') {
                $author = trim(substr($l, 8));
            } elseif (substr($l, 0, 5) == 'Date:') {
                $date = trim(substr($l, 6));
            } else {
                if (trim($l) != '') {
                    $commitmsg .= trim($l)."\n";
                }
            }
        }
        if ($inCommit) {
            $history->addVersion(substr($commit, 0, 8), $commitmsg, strtotime($date));
        }

        return $history;
    }

    public function getFileVersion($file, $version)
    {
        $output = array();
        $status = $this->executeCommand('show '.escapeshellarg("$version:$file"), $output);

        if ($status) {
            return implode("\n", $output);
        } else {
            return false;
        }
    }

    public function getVersionMtime($file, $version)
    {
        $output = array();
        $status = $this->executeCommand('log '.escapeshellarg($version), $output);
        if ($status) {
            $date = trim(substr($output[2], 8));
            return strtotime($date);
        } else {
            return false;
        }
    }

    public function getFileDiff($file, $range)
    {
        $output = array();
        $status = $this->executeCommand('diff '.escapeshellarg($range).' '.escapeshellarg($file), $output);

        if ($status) {
            return implode("\n", $output);
        } else {
            return false;
        }
    }

    public function storeFile($file, $content, $commitmsg)
    {
        $status = parent::storeFile($file, $content, $commitmsg);

        if ($status) {
            $status = $this->executeCommand('add '.escapeshellarg($file), $output);
            if ($status) {
                $message = escapeshellarg($commitmsg);
                $status = $this->executeCommand('commit -m '.$message, $output);
            }
        }

        return $status;
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

        $status = $this->executeCommand('mv '.escapeshellarg($file).' '.escapeshellarg($target), $output);
        if ($status) {
            $message = "Move $file to $target";
            $status = $this->executeCommand('commit -m '.escapeshellarg($message), $output);
        }

        return $status;
    }

    public function restoreFileVersion($file, $version)
    {
        $output = array();

        $status = $this->executeCommand('checkout '.escapeshellarg($version).' '.escapeshellarg($file), $output);
        if ($status) {
            $message = "Restore $version of $file";
            $status = $this->executeCommand('commit -m '.escapeshellarg($message), $output);
        }

        return $status;
    }

    public function deleteFile($file)
    {
        $output = array();

        $status = $this->executeCommand('rm '.escapeshellarg($file), $output);
        if ($status) {
            $commitmsg = "Delete $file";
            $status = $this->executeCommand('commit -m '.escapeshellarg($commitmsg), $output);
        }

        return $status;
    }

    public function commitUploadedFile($file, $target, $commitmsg)
    {
        parent::commitUploadedFile($file, $target, $commitmsg);

        $output = array();
        $status = $this->executeCommand('add '.escapeshellarg($target), $output);
        if ($status) {
            $status = $this->executeCommand('commit -m '.escapeshellarg($commitmsg), $output);
        }

        return $status;
    }

    private function executeCommand($command, &$output)
    {
        $command = escapeshellcmd($command);
        $cwd = getcwd();
        chdir($this->storageDir);
        $this->container->get('logger')->addInfo("Executing command: ".$this->executablePath." $command");
        exec($this->executablePath.' '.$command, $output, $status);
        $this->container->get('logger')->addInfo("Return status: $status, length of return output: ".count($output)." lines");
        chdir($cwd);

        return $status == 0;
    }

    private function genFullFileName($file)
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . $file;
    }
}