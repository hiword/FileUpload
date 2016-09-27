<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/26
 * Time: 20:42
 */

namespace Simon\Upload\Traits;


trait RenameTrait
{

    protected $isRename = true;

    public function setRename(bool $isRename) : self
    {
        $this->isRename = $isRename;
        return $this;
    }

    protected function getRename() : bool
    {
        return $this->isRename;
    }

    /**
     * 设置文件名
     * @param string $name
     * @return string
     * @author simon
     */
    protected function getNewName(string $name)
    {
        if ($this->isRename)
        {
            $name = sha1(uniqid('simon_')).mt_rand(1,999).'.'.pathinfo($name,PATHINFO_EXTENSION);
        }

        return $name;
    }
}