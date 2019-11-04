<?php
require __DIR__ . '/app/bootstrap.php';
defined('BP') || define('BP', str_replace('\\', '/', dirname(dirname(dirname((__DIR__))))));

use Codeception\Exception\ConnectionException;
use Magento\Framework\Model\ResourceModel\Db\Context;

class UninstallMDSModule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    private $moduleName = 'MDS_Collivery';

    public function __construct(Context $context, $connectionName = null)
    {
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->runUninstall();
    }

    public function runUninstall()
    {
        $commands = [
            "module:disable $this->moduleName --clear-static-content",
            'setup:upgrade',
            'setup:di:compile',
            'indexer:reindex',
            'cache:flush'

        ];
        foreach ($commands as $command) {
            $this->executeCommand('php -f ' . BP . '/bin/magento ' . $command);
        }
        $this->removeDatabaseEntries();
        $this->removeModuleDirectory(BP . '/app/code/MDS');
    }

    /**
     * @param $cmd
     *
     * @return array
     */
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

    /**
     * @return $this
     */
    public function removeDatabaseEntries()
    {
        $connection = $this->getConnection();
        $eavTable = $this->getTable('eav_attribute');
        $connection->beginTransaction();
        try {
            /**
             * remove ('location', 'town', 'suburb') in eav_attributes coz they keep appearing on checkout even though
             * the module is removed,
             */
            $this->getConnection()->delete(
                $eavTable,
                "attribute_code in ('location', 'town', 'suburb')"
            );

            /**
             *  remove module in setup_module so when we run we want reInstall module nextime it inserts a new
             *  entry and recreate eav attributes again, if theres no record of module in setup_module it runs installData and
             * installScheme
             */
            $this->getConnection()->delete(
                $this->getTable('setup_module'),
                "module = '$this->moduleName'"
            );
            echo 'Done Uninstalling MDS Collivery Shipping Module';
            $connection->commit();
        } catch (ConnectionException $e) {
            $connection->rollBack();
        }

        return $this;
    }

    /**
     * @param $path
     */
    public function removeModuleDirectory($path)
    {
        if (is_dir($path)) {
            $files = glob($path . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                $this->removeModuleDirectory($file);
            }
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
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
    $objectManager = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
    $context = $objectManager->create(Magento\Framework\Model\ResourceModel\Db\Context::class);
    new UninstallMDSModule($context);
    ?>
</div>
</body>
</html>


