<?php

namespace MDS\Collivery\Observer;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use MDS\Collivery\Model\Cache;

class ConfigDataChange
{
    private $file;
    private $filesystem;
    private $cache;
    private $path;

    /**
     * ConfigDataChange constructor.
     *
     * @param File       $file
     * @param Filesystem $filesystem
     * @param Cache      $cache
     */
    public function __construct(File $file, Filesystem $filesystem, Cache $cache)
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
        $this->cache = $cache;
        $this->path = 'cache/mds_collivery/collivery.auth';
    }

    /**
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure                     $proceed
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        $databaseUsername = $subject->getConfigDataValue('carriers/collivery/username');
        if (isset($subject->getDataByKey('groups')['collivery'])) {
            $newUsername = $subject->getDataByKey('groups')['collivery']['fields']['username']['value'];

            $databasePassword = $subject->getConfigDataValue('carriers/collivery/password');
            $newPassword = $subject->getDataByKey('groups')['collivery']['fields']['password']['value'];

            //if password or username changed by admin user delete authentication file
            if ($databaseUsername !== $newUsername || $databasePassword !== $newPassword) {
                if ($this->cache->has('collivery.auth')) {
                    $this->file->deleteFile($this->path);
                }
            }
        }

        return $proceed();
    }
}
