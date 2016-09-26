<?php
namespace Simon\File\Uploads;
use Simon\File\Uploads\Exceptions\TypeErrorException;
use Simon\File\Uploads\Exceptions\SizeException;
use Simon\File\Uploads\Exceptions\UploadException;
class FileUpload 
{
	
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
	 * 允许上传的文件大小[字节]
	 * @var numeric  
	 * @author simon
	 */
	protected $filesize = 2097152;//2MB//ok//2097152
	
	/**
	 * 文件上传路径
	 * @var string
	 * @author simon
	 */
	protected $path = './uploads';
	
	/**
	 * 上传中的数据数组
	 * @var array
	 * @author simon
	 */
	protected $uploading = [];
	

	

	
	/**
	 * 是否允许重命名
	 * @var boolean
	 * @author simon
	 */
	protected $rename = true;//ok
	
	/**
	 * 自动创建hash目录的层级数，当为0的时候不创建
	 * @var numeric
	 * @author simon
	 */
	protected $hashDirLayer = 2;//ok
	
	
	public function __construct($path = null)
	{
		$this->setPath($path);
	}
	
	/**
	 * 全局配置，可使用setXX方法覆盖config方法
	 * @param array $config
	 * @author simon
	 */
	public function config(array $config)
	{
		$allowPrototype = ['filesize','extensions','checkExtension','checkMime','rename','hashDirLayer','path'];
		
		foreach ($config as $key=>$value)
		{
			in_array($key, $allowPrototype,true) && $this->$key = $value;
		}
		
		return $this;
	}
	
	
	/**
	 * 设置hash目录的层级数
	 * @param numeric $layer
	 * @author simon
	 */
	public function setHashDirLayer($layer)
	{
		$this->hashDirLayer = $layer;
		return $this;
	}
	
	/**
	 * 获取hash目录的层级数
	 * @return \Simon\File\Uploads\numeric
	 * @author simon
	 */
	public function getHashDirLayer()
	{
		return $this->hashDirLayer;
	}
	
	/**
	 * 设置是否重命名文件
	 * @param boolean $isRename
	 * @return \Simon\File\Uploads\FileUpload
	 * @author simon
	 */
	public function setRename($isRename)
	{
		$this->rename = $isRename;
		return $this;
	}
	
	/**
	 * 
	 * @author simon
	 */
	public function getRename()
	{
		return $this->rename;
	}
	

	

	
	
	/**
	 * 文件大小
	 * @param numeric $size
	 * @return \Simon\File\Uploads\FileUpload
	 * @author simon
	 */
	public function setFilesize($size)
	{
		$this->filesize = is_numeric($size) ? $size : $this->filesize;
		return $this;
	}
	
	/**
	 * 
	 * 
	 * @author simon
	 */
	public function getFilesize()
	{
		return $this->filesize;
	}
	
	/**
	 * 文件上传
	 * 
	 * @author simon
	 */
	public function upload()
	{
		foreach ($this->setFiles() as $values)
		{
			
			$this->setUploading($values);
			
			//保存当前文件
		    $this->setFile();
		    
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
	protected function setUploading(array $upload)
	{
		$this->uploading = $upload;
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
	protected function checkFileMime()
	{
		if (!$this->checkMime)
		{
			return true;
		}
		
		$extension = $this->file->getExtension($this->uploading['name']);
		//jpg文件mime特例
		$extension === 'jpg' && $extension = 'jpeg';

		$mimeExtension = isset($this->mimes[$this->file->getFileMime()]) ? $this->mimes[$this->file->getFileMime()] : null;
		if ($mimeExtension !== strtolower($extension))
		{
			throw new TypeErrorException($this->uploading['name'],'mime');
		}
		return true;
	}
	
	/**
	 * 验证文件扩展名
	 * @throws TypeErrorException
	 * @return boolean
	 * @author simon
	 */
	protected function checkFileExtension()
	{
		if (!$this->checkExtension)
		{
			return true;
		}
		
		//验证扩展名
		$extension = $this->file->getExtension($this->uploading['name']);
		if (!in_array(strtolower($extension), $this->extensions,true))
		{
			throw new TypeErrorException($this->uploading['name'],'extension');
		}
		return true;
	}
	
	/**
	 * 验证upload自身上传错误
	 * @throws UploadException
	 * @author simon
	 */
	protected function checkUploadSelf()
	{
		if ($this->uploading['error'] !== UPLOAD_ERR_OK)
		{
			throw new UploadException($this->uploading['name'],$this->uploading['error']);
		}
		return true;
	}
	
	/**
	 * 验证文件大小
	 * @param array $upload
	 * @throws SizeException
	 * @author simon
	 */
	protected function checkFileSize()
	{
		//验证文件大小
		if ($this->filesize < $this->file->getSize())
		{
			throw new SizeException($this->uploading['name']);
		}
		return true;
	}
	
	/**
	 * 验证是否是正常上传文件
	 * @param array $upload
	 * @throws UploadException
	 * @author simon
	 */
	protected function checkUploadedFile()
	{
		if (!is_uploaded_file($this->uploading['tmp_name']))
		{
			throw new UploadException($this->uploading['name'], UploadException::IS_NOT_UPLOAD_FILE);
		}
		return true;
	}
	
	/**
	 * 移动文件
	 * @throws UploadException
	 * @author simon
	 */
	protected function moveUploadFile()
	{
	    if (!move_uploaded_file($this->uploading['tmp_name'], $this->file->getNewPath()))
	    {
	        throw new UploadException($this->uploading['name'], UploadException::MOVE_TMP_FILE_ERR);
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
		
		$this->path = str_replace('\\', '/', realpath($this->path));
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
		$this->file->mkDir(dirname($this->file->getNewPath()));
			
		$this->moveUploadFile();
		
		$this->files[] = $this->getUploadInfo();
	}
	
	/**
	 * 获取上传信息
	 * @author simon
	 */
	protected function getUploadInfo()
	{
		
		$filepath = $this->file->getNewPath();
		
	    $file = [];
	    $file['new_name'] = $this->file->getNewName();
	    $file['hash'] = sha1($filepath);
	    $file['old_name'] = $this->uploading['name'];
	    $file['save_path'] = $this->path;
	    $file['full_path'] = $filepath;
	    $file['full_root'] = str_replace($this->path, '', $filepath);
	    $file['extension'] = $this->file->getExtension($filepath);
	    $file['mime_type'] = $this->file->getFileMime($filepath);
	    $file['filesize'] = $this->file->getSize($filepath);
	    //完成上传时间
	    $file['complete_time'] = time();
	    //完成上传的微秒时间，用于大并发
	    list($usec, $sec) = explode(" ", microtime());
	    $file['complete_microtime'] = (float)$usec + (float)$sec;
	    /* //合并上传信息
	     //$file = array_merge($upload,$file); */
	    return $file;
	}
	
	/**
	 * 处理需要上传的文件
	 * @author simon
	 */
	protected function setFiles()
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
	
	/**
	 * 设置文件名
	 * @param string $name
	 * @return string 
	 * @author simon
	 */
	protected function getNewName()
	{
		$name = $this->uploading['name'];
		
		if ($this->rename)
		{
			$name = sha1(uniqid('simon_')).mt_rand(1,999).'.'.$this->file->getExtension($name);
		}
		
		return $name;
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