<?php
namespace Simon\Upload;
use Simon\Upload\Exceptions\TypeErrorException;
use Simon\Upload\Exceptions\SizeException;
use Simon\Upload\Exceptions\UploadException;
use Simon\Upload\Traits\Directory;
use Simon\Upload\Traits\ExtensionTrait;
use Simon\Upload\Traits\MimeTrait;
use Simon\Upload\Traits\RenameTrait;
use Simon\Upload\Traits\SizeTrait;
use Simon\Upload\File;

class FileUpload
{

    use SizeTrait,MimeTrait,ExtensionTrait,Directory,RenameTrait;

	/**
	 * 获取的新文件
	 * @var array
	 * @author simon
	 */
	protected $files = [];
	
	/**
	 * File\Uploads\File 对象
	 * @var object
	 * @author simon
	 */
	protected $file = null;
	
	/**
	 * 文件上传路径
	 * @var string
	 * @author simon
	 */
	protected $path = './uploads';

    /**
     * @var null
     */
    protected $filePath = null;
	
	/**
	 * 上传中的数据数组
	 * @var array
	 * @author simon
	 */
	protected $currentUpload = [];



	
	public function __construct($path = null)
	{
		$this->setPath($path);
	}
	
	/**
	 * 全局配置，可使用setXX方法覆盖config方法
	 * @param array $config
	 * @author simon
	 */
	public function config(array $config) : static
	{
		$allowPrototype = ['filesize','extensions','checkExtension','checkMime','rename','hashDirLayer','path'];
		
		foreach ($config as $key=>$value)
		{
			in_array($key, $allowPrototype,true) && $this->$key = $value;
		}
		
		return $this;
	}


	/**
	 * 文件上传
	 * 
	 * @author simon
	 */
	public function upload()
	{
		foreach ($this->formatFiles() as $file)
		{
			$this->setUploading($file);

			//验证上传
			$this->checkUpload();
			
			//开始上传
			$this->handleUpload();
		}
	}
	
	/**
	 * 获取上传的文件
	 * @author simon
	 */
	public function getFiles()
	{
		return $this->files;
	}
	
	/**
	 * 存储当前上传的临时信息数组
	 * @param array $upload
	 * @author simon
	 */
	protected function setUploading(array $upload) : static
	{
	    $upload['extension'] = pathinfo($upload['name'],PATHINFO_EXTENSION);

		$this->currentUpload = $upload;
        $this->file = new File($this->currentUpload['tmp_name']);

        return $this;
	}
	
	/**
	 * 验证文件上传
	 * @throws UploadException
	 * @throws SizeException
	 * @throws TypeErrorException
	 * @author simon
	 */
	protected function checkUpload()
	{
		//验证是否是正常上传文件
		$this->checkUploadedFile();
	
		//验证PHP自身upload检测
		$this->checkUploadSelf();
	
		//验证文件大小
		$this->checkFileSize();
	
		//验证扩展名
		$this->checkFileExtension();
	
		//验证mime
		$this->checkFileMime();
	}
	
	/**
	 * 验证文件mime类型
	 * @throws TypeErrorException
	 * @author simon
	 */
	protected function checkFileMime() : bool
	{

	    if ($this->getCheckMime())
        {
            $mime = $this->getMimes($this->currentUpload['extension']);
            if ($mime !== $this->file->getFileMime())
            {
                throw new TypeErrorException($this->currentUpload['name'],'mime');
            }
        }

		return true;
	}
	
	/**
	 * 验证文件扩展名
	 * @throws TypeErrorException
	 * @return boolean
	 * @author simon
	 */
	protected function checkFileExtension() : bool
	{

        if ($this->getCheckExtension() && !in_array(strtolower($this->currentUpload['extension']), $this->getExtensions(),true))
        {
            throw new TypeErrorException($this->currentUpload['name'],'extension');
        }

		return true;
	}
	
	/**
	 * 验证upload自身上传错误
	 * @throws UploadException
	 * @author simon
	 */
	protected function checkUploadSelf() : bool
	{
		if ($this->currentUpload['error'] !== UPLOAD_ERR_OK)
		{
			throw new UploadException($this->currentUpload['name'],$this->currentUpload['error']);
		}
		return true;
	}
	
	/**
	 * 验证文件大小
	 * @param array $upload
	 * @throws SizeException
	 * @author simon
	 */
	protected function checkFileSize() : bool
	{
		//验证文件大小
		if ($this->getFileSize() < $this->file->getSize())
		{
			throw new SizeException($this->currentUpload['name']);
		}
		return true;
	}
	
	/**
	 * 验证是否是正常上传文件
	 * @param array $upload
	 * @throws UploadException
	 * @author simon
	 */
	protected function checkUploadedFile() : bool
	{
		if (!is_uploaded_file($this->currentUpload['tmp_name']))
		{
			throw new UploadException($this->currentUpload['name'], UploadException::IS_NOT_UPLOAD_FILE);
		}
		return true;
	}
	
	/**
	 * 移动文件
	 * @throws UploadException
	 * @author simon
	 */
	protected function moveUploadFile() : bool
	{
	    if (!move_uploaded_file($this->currentUpload['tmp_name'], $this->filePath))
	    {
	        throw new UploadException($this->currentUpload['name'], UploadException::MOVE_TMP_FILE_ERR);
	    }
	    return true;
	}
	

	/**
	 * 设置文件路径
	 * @param  string $path
	 * @author simon
	 */
	protected function setPath($path)
	{
		if ($path)
		{
			$this->path = $path;
		}
		
		$this->path = str_replace('\\', '/', realpath($this->path)).'/';
	}

	/**
	 * 设置当前文件
	 * @param string $name
	 */
	protected function setFile()
	{
		$this->file = new File($this->uploading['tmp_name']);
		
		//设置新的文件名
		$this->file->setNewName($this->getNewName());
		
		//设置新的路径
		$this->file->setNewPath($this->path.'/'.$this->file->getHashDir($this->uploading['name']).$this->file->getNewName());
	}
	
	/**
	 * 处理文件上传
	 * @param array $upload
	 * @author simon
	 */
	protected function handleUpload()
	{
		//自动创建目录
        $this->createDir($this->filePath);
			
		$this->moveUploadFile();
		
		$this->setUploadInfo();
	}

	protected function setFilePath()
    {
        //获取当前目录
        $dirs = $this->getHashDir($this->currentUpload['name']);

        $this->filePath = $this->path.$dirs.'/'.$this->getNewName($this->currentUpload['name']);
    }
	
	/**
	 * 获取上传信息
	 * @author simon
	 */
	protected function setUploadInfo()
	{
	    $file = [];
	    $file['new_name'] = pathinfo($this->filePath,PATHINFO_BASENAME);
	    $file['hash'] = sha1($this->filePath);
	    $file['old_name'] = $this->currentUpload['name'];
	    $file['save_path'] = pathinfo($this->path,PATHINFO_DIRNAME);
	    $file['full_path'] = $this->filePath;
	    $file['full_root'] = str_replace($this->path, '', $this->filePath);
	    $file['extension'] = pathinfo($this->path,PATHINFO_EXTENSION);
	    $file['mime_type'] = $this->file->getFileMime();
	    $file['filesize'] = $this->file->getSize();
	    //完成上传时间
	    $file['complete_time'] = time();
	    //完成上传的微秒时间，用于大并发
	    list($usec, $sec) = explode(" ", microtime());
	    $file['complete_microtime'] = (float)$usec + (float)$sec;
	    /* //合并上传信息
	     //$file = array_merge($upload,$file); */
	    return $file;

        $this->files[] = $file;
	}
	
	/**
	 * 处理需要上传的文件
	 * @author simon
	 */
	protected function formatFiles()
	{
		$files = [];
		if (!empty($_FILES))
		{
			$temp = [];
			foreach ($_FILES as $key=>$values)
			{
				if (is_array($values['name']))
				{
					foreach ($values['name'] as $k=>$vo)
					{
						//去除未添加上传的文件
						if (empty($vo))
						{
							continue;
						}
						$temp['name'] = $vo;
						$temp['type'] = $values['type'][$k];
						$temp['tmp_name'] = $values['tmp_name'][$k];
						$temp['error'] = $values['error'][$k];
						$temp['size'] = $values['size'][$k];
						$temp['__name'] = $key;
						$files[] = $temp;
					}
				}
				else
				{
					//去除未添加上传的文件
					if (empty($values['name']))
					{
						continue;
					}
					$values['__name'] = $key;
					$files[] = $values;
				}
			}
		}
		
		return $files;
	}
	

// 	protected function getNewName($name)
// 	{
// 		if ($this->rename)
// 		{
// 			$this->newName = sha1(uniqid('simon_')).mt_rand(1,999).'.'.$this->file->getExtension($name);
// 		}
// 		else 
// 		{
// 			$this->newName = $name;
// 		}
		
// 		return $this->newName;
// 	}
	
// 	/**
// 	 * 文件名相同时是否覆盖
// 	 * @param boolean $isCoverFile
// 	 * @author simon
// 	 */
// 	public function setCoverFile($isCoverFile)
// 	{
// 		$this->coverFile = $isCoverFile;
// 		return $this;
// 	}
	
// 	protected function coverFile($filepath)
// 	{
// 		//重命名文件
// 		if (!$this->coverFile && file_exists($filepath))
// 		{
// 			$filepath = pathinfo($filepath);
// 			$filepath = $filepath['dirname'].'/'.$nameInfo['filename'].'_2.'.$nameInfo['extension'];
// 		}
// 		return $filepath;
// 	}
	
	/**
	 * 设置HashDir
	 * @param string $name
	 * @return string
	 * @author simon
	 */
// 	protected function getHashDir($name)
// 	{
// 		$dirs = '';
		
// 		if ($this->hashDirLayer === 0)
// 		{
// 			return $dirs;
// 		}
		
// 		$name = sha1($name);
// 		$length = strlen($name);
		
// 		for($i=0;$i<$length;$i++)
// 		{
// 			if ($i+1 > $this->hashDirLayer)
// 			{
// 				break;
// 			}
// 			$dirs .= substr($name, $i,1).'/';
// 		}
		
// 		return $dirs;
// 	}
	
	/**
	 * 设置最后的文件路径
	 * @param string $dir
	 * @param string $name
	 * @return string
	 * @author simon
	 */
// 	protected function setFilePath($name)
// 	{
// 		$this->fullPath = $this->path.'/'.$this->file.$name;
// 		return $this->fullPath;
// 	}
	
// 	/**
// 	 * 创建目录
// 	 * @param string $dir
// 	 * @param number $mode
// 	 * @return boolean|bool
// 	 */
// 	protected function mkDir($dir, $mode = 0755)
// 	{
// 		if (is_dir($dir) || @mkdir($dir, $mode)) return true;
// 		if (!@$this->mkDir(dirname($dir), $mode)) return false;
// 		return mkdir($dir, $mode);
// 	}
	
}