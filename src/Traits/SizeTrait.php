<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/18
 * Time: 17:30
 */

namespace Simon\Upload\Traits;


trait SizeTrait
{

    protected $fileSize = 1024*1024*2;

    /**
     *
     * 文件大小转换为字节
     * @param numeric $size => Beat
     * @return numeric
     */
    protected function sizeToByte() : int
	{

		if(is_numeric($this->fileSize)) return $this->fileSize;

		//获取单位
		$unit = strtoupper(substr($this->fileSize,-2,2));
		//获取数值
        $this->fileSize = rtrim($this->fileSize,$unit);

		switch($unit)
		{
			case 'KB' : $this->fileSize = $this->fileSize * pow(2,10); break;
			case 'MB' : $this->fileSize = $this->fileSize * pow(2,20); break;
			case 'GB' : $this->fileSize = $this->fileSize * pow(2,30); break;
			case 'TB' : $this->fileSize = $this->fileSize * pow(2,40); break;
			case 'PB' : $this->fileSize = $this->fileSize * pow(2,50); break;
			default	  : $this->fileSize = 0;
		}

        return $this->fileSize;
    }

    public function setFileSize(int $fileSize) : self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    protected function getFileSize() : int
    {
        return $this->fileSize;
    }

}