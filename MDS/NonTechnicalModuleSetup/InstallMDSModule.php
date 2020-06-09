<?php

require __DIR__ . '/app/bootstrap.php';
defined('BP') || define('BP', str_replace('\\', '/', dirname(dirname(dirname((__DIR__))))));

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class InstallMDSModule
{
    private $objectManager;
    private $logger;
    private $resource;
    private $moduleName = 'MDS_Collivery';

    public function __construct()
    {
        $this->objectManager = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->logger = $this->objectManager->get(LoggerInterface::class);
    }

    public function runInstall()
    {
        $commands = [
           "module:enable $this->moduleName",
           'setup:upgrade',
           'setup:di:compile',
           'indexer:reindex',
           'cache:flush'
        ];
        $moduleDir = BP . '/app/code/MDS/Collivery';

        if (is_dir($moduleDir)) {
            foreach ($commands as $command) {
                $this->executeCommand('php -f ' . BP . '/bin/magento ' . $command);
            }
        } else {
            echo "Please extract the MDS Collivery zip file to /&lt;project&gt;/app/code/MDS";
        }
    }

    public function executeCommand($cmd)
    {
        while (@ ob_end_flush()); // end all output buffers if any
        $process = popen("$cmd 2>&1 ; echo ", 'r');
        $output = "";
        $completedOutput = "";

        while (!feof($process)) {
            $output = fread($process, 4096);
            $completedOutput = $completedOutput . $output;
            echo '<pre>';
            echo "$output<br>";
            echo '</pre>';
            @ flush();
        }
        pclose($process);

        // get exit status
        preg_match('/[0-9]+$/', $completedOutput, $matches);

        // return exit status and intended output
        $matches = isset($matches[0]) ?: '';
        return [
            'exit_status'  => intval($matches),
            'output'       => str_replace("Exit status : " . $matches, '', $completedOutput)
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        #console {
          background-color: black;
          color: white;
          padding: 50px;
          font-size: large;
        }
    </style>
</head>
<body>
<div id="console">
    <?php
    $class = new InstallMDSModule();
    $class->runInstall();
    ?>
</div>
</body>
</html>
