<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/18
 * Time: 17:03
 */

namespace Simon\FileUpload\Traits;


trait ExtensionTrait
{

    /**
     * 允许的文件扩展名
     * @var array
     * @author simon
     */
    protected $extensions = ['jpg','jpeg','gif','png','bmp'];//ok

    /**
     * 是否验证文件扩展名
     * @var boolean
     * @author simon
     */
    protected $checkExtension = true;

    /**
     * 是否验证扩展名
     * @param boolean $isCheckExtension
     * @return \Simon\File\Uploads\FileUpload
     * @author simon
     */
    public function setCheckExtension(bool $isCheck) : ExtensionTrait
    {
        $this->checkExtension = $isCheck;

        return $this;
    }

    /**
     *
     *
     * @author simon
     */
    public function getCheckExtension() : bool
    {
        return $this->checkExtension;
    }

    /**
     * 允许的文件扩展名
     * @param array $extensions
     * @return \Simon\File\Uploads\FileUpload
     * @author simon
     */
    public function setExtensions(array $extensions) : ExtensionTrait
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     *
     *
     * @author simon
     */
    public function getExtensions() : array
    {
        return $this->extensions;
    }

}