<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/18
 * Time: 16:53
 */
namespace Simon\FileUpload\Contracts;

interface FileUploadInterface
{

    public function setUploadConfig(array $config) : FileUploadInterface;

    public function upload() : FileUploadInterface;

    public function getUploadFiles() : array;

}