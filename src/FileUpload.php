<?php
namespace Simon\Upload;
use Simon\Upload\Contracts\FileUploadInterface;
use Simon\Upload\Exceptions\TypeErrorException;
use Simon\Upload\Exceptions\SizeException;
use Simon\Upload\Exceptions\UploadException;
use Simon\Upload\File;
use Simon\Upload\Traits\Directory;
use Simon\Upload\Traits\ExtensionTrait;
use Simon\Upload\Traits\MimeTrait;
use Simon\Upload\Traits\RenameTrait;
use Simon\Upload\Traits\SizeTrait;

class FileUpload implements FileUploadInterface
{

    use SizeTrait,MimeTrait,ExtensionTrait,Directory,RenameTrait;

	/**
	 * 获取的新文件
	 * @var array
	 * @author simon
	 */
	protected $files = [];
	
	/**
	 * File\Upload\File 对象
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
     * 完整的文件路径
     * @var string
     */
    protected $filePath = '';
	
	/**
	 * 上传中的数据数组
	 * @var array
	 * @author simon
	 */
	protected $currentUpload = [];

    /**
     * FileUpload constructor.
     * @param string $path
     * @param array $config
     */
	public function __construct($path = '',$config = [])
	{

		$this->setPath($path);

        if ($config)
        {
            $this->config($config);
        }
	}

    public function config(array $config) : FileUploadInterface
    {
        // TODO: Implement config() method.
        $allowFunc = ['setFileSize','setRename','setCheckMime','setMimes','setCheckExtension','setExtensions','setHashDirLayer'];

        foreach ($config as $key=>$value)
        {
            if (in_array($key, $allowFunc,true))
            {
                call_user_func_array([$this,$key],[$value]);
            }
        }

        return $this;
    }

    public function upload() : FileUploadInterface
    {
        // TODO: Implement upload() method.
        foreach ($this->formatFiles() as $file)
        {
            $this->setUploading($file);

            //验证上传
            $this->checkUpload();

            //开始上传
            $this->handleUpload();
        }

        return $this;
    }

    public function getFiles() : array
    {
        // TODO: Implement getFiles() method.
        return $this->files;
    }

	/**
	 * 存储当前上传的临时信息数组
	 * @param array $upload
	 * @author simon
	 */
	protected function setUploading(array $upload)
	{
	    $upload['extension'] = pathinfo($upload['name'],PATHINFO_EXTENSION);

		$this->currentUpload = $upload;

        $this->setFile($this->currentUpload['tmp_name']);
	}

	protected function setFile($file)
    {
        $this->file = new File($file);
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
	protected function setPath(string $path)
	{
		if ($path)
		{
			$this->path = $path;
		}
		
		$this->path = str_replace('\\', '/', realpath($this->path)).'/';
	}

	/**
	 * 处理文件上传
	 * @param array $upload
	 * @author simon
	 */
	protected function handleUpload()
	{
	    $this->setFilePath();

		//自动创建目录
        $this->createDir(dirname($this->filePath));
			
		$this->moveUploadFile();

        $this->setFile($this->filePath);
		
		$this->setUploadInfo();
	}

    /**
     * 设置上传文件新的文件路径
     */
	protected function setFilePath()
    {
        //获取当前目录
        $dirs = $this->getHashDir($this->currentUpload['name']);

        $this->filePath = $this->path.$dirs.$this->getNewName($this->currentUpload['name']);
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
	    $file['save_path'] = pathinfo($this->filePath,PATHINFO_DIRNAME);
	    $file['full_path'] = $this->filePath;
	    $file['full_root'] = str_replace(dirname(getenv('SCRIPT_FILENAME')), '', $this->filePath);
	    $file['extension'] = pathinfo($this->filePath,PATHINFO_EXTENSION);
	    $file['mime_type'] = $this->file->getFileMime();
	    $file['file_size'] = $this->file->getSize();
	    //完成上传时间
	    $file['complete_time'] = time();
	    //完成上传的微秒时间，用于大并发
	    list($usec, $sec) = explode(" ", microtime());
	    $file['complete_microtime'] = (float)$usec + (float)$sec;
	    /* //合并上传信息
	     //$file = array_merge($upload,$file); */

        $this->files[] = $file;
	}
	
	/**
	 * 处理需要上传的文件
	 * @author simon
	 */
	protected function formatFiles() : array
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

}