<?php

namespace MDS\Collivery\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;

class ConfigDataChange
{
    private $file;
    private $filesystem;

    /**
     * ConfigDataChange constructor.
     *
     * @param File       $file
     * @param Filesystem $filesystem
     */
    public function __construct(File $file, Filesystem $filesystem)
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
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
        $newUsername = $subject->getDataByKey('groups')['collivery']['fields']['username']['value'];

        $databasePassword = $subject->getConfigDataValue('carriers/collivery/password');
        $newPassword = $subject->getDataByKey('groups')['collivery']['fields']['password']['value'];

        //if password or username changed by admin user delete authentication file
        if ($databaseUsername !== $newUsername || $databasePassword !== $newPassword) {
            $filename = 'collivery.auth';
            $filepath = $this->filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath('cache/mds_collivery');

            if ($this->file->isExists("$filepath/$filename")) {
                $this->file->deleteFile("$filepath/$filename");
            }
        }

        return $proceed();
    }
}
