<?php
namespace Repositorium;

class AckSearchProvider implements Interfaces\SearchProvider
{
    protected $container;
    protected $storageDir;
    protected $executablePath;

    public function __construct($container)
    {
        $this->container  = $container;
        $this->storageDir = rtrim($container->get('settings')['pathToRepository'],
                                  DIRECTORY_SEPARATOR);
        $this->executablePath = $container->get('settings')['ackPath'];
    }

    public function searchFor($keyword)
    {
        $output = array();
        $status = $this->executeCommand(escapeshellarg($keyword), $output);
        if ($status) {
            $results = array();
            foreach ($output as $line) {
                $arrLine = explode(':', $line);
                $file = $arrLine[0];
                $linenumber = $arrLine[1];
                if (count($arrLine) > 3) {
                    array_shift($arrLine);
                    array_shift($arrLine);
                    $snippet = implode(':', $arrLine);
                } else {
                    $snippet = $arrLine[2];
                }
                $results[$file][$linenumber] = $snippet;
            }
            return $results;
        } else {
            return false;
        }
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
}