<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/18
 * Time: 16:58
 */

namespace Simon\Upload\Drives;


use Simon\FileUpload\Contracts\FileUploadInterface;

class FileUpload implements FileUploadInterface
{

    public function setUploadConfig(array $config) : FileUploadInterface
    {
        // TODO: Implement setUploadConfig() method.

        return $this;
    }

    public function upload() : FileUploadInterface
    {
        // TODO: Implement upload() method.

        return $this;
    }

    public function getUploadFiles() : array
    {
        // TODO: Implement getUploadFiles() method.
    }

}