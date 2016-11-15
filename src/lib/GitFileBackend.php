<?php
namespace Repositorium;

class GitFileBackend extends PlainFileBackend implements Interfaces\FileBackend
{
    public function getFileHistory($file)
    {
        $history = new History($file);
        $output = array();
        $status = 0;
        $this->executeCommand('log '.$file, $output, $status);
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
        $status = 0;
        $output = array();
        $this->executeCommand("show $version:$file", $output, $status);

        return implode("\n", $output);
    }

    public function getVersionMtime($file, $version)
    {
        $status = 0;
        $output = array();
        $this->executeCommand("log $version", $output, $status);
        $date = trim(substr($output[2], 8));

        return strtotime($date);
    }

    public function getFileDiff($file, $range)
    {
        $status = 0;
        $output = array();
        $this->executeCommand("diff $range $file", $output, $status);

        return implode("\n", $output);
    }

    public function storeFile($file, $content, $commitmsg)
    {
        $result = parent::storeFile($file, $content, $commitmsg);
        $this->executeCommand("add $file", $status, $output);
        $this->executeCommand('commit -m "'.$commitmsg.'"', $status, $output);

        return $result;
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
        $this->executeCommand("mv $file $target", $output, $status);
        $this->executeCommand('commit -m "Move '.$file.' to '.$target.'"', $output, $status);
        return $status == 0;
    }

    public function restoreFileVersion($file, $version)
    {
        $output = array();
        $status = 0;

        $this->executeCommand("checkout $version $file", $output, $status);
        $this->executeCommand('commit -m "Restore '.$version.' of '.$file.'"', $output, $status);

        return true;
    }

    public function deleteFile($file)
    {
        $output = array();
        $status = 0;

        $this->executeCommand("rm $file", $output, $status);
        $this->executeCommand('commit -m "Delete '.$file.'"', $output, $status);

        return $status == 0;
    }

    private function executeCommand($command, &$output, &$status)
    {
        $cwd = getcwd();
        chdir($this->storageDir);
        $this->container->get('logger')->addInfo("Executing command: /usr/bin/git $command");
        exec('/usr/bin/git '.$command, $output, $status);
        $this->container->get('logger')->addInfo("Return status: $status, length of return output: ".count($output)." lines");
        chdir($cwd);
    }
}