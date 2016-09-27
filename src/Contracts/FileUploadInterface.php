<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/18
 * Time: 16:53
 */
namespace Simon\Upload\Contracts;

interface FileUploadInterface
{

    public function config(array $config) : FileUploadInterface;

    public function upload() : FileUploadInterface;

    public function getFiles() : array;

}