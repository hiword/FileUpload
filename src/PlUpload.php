<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2016/9/27
 * Time: 10:29
 */

namespace Simon\Upload;


use Simon\Upload\Contracts\FileUploadInterface;
use Simon\Upload\Exceptions\UploadException;

class PlUpload extends FileUpload implements FileUploadInterface
{
    /**
     * 当前块
     * @var integer
     * @author simon
     */
    protected $chunk = 0;

    /**
     * 总块数
     * @var integer
     * @author simon
     */
    protected $chunks = 0;

    /**
     * 上传的字节数据，也检测是否上传完成
     * @var integer
     * @author simon
     */
    protected $status = 0;

    /**
     * 设置上传文件新的文件路径
     */
    protected function setFilePath()
    {
        //获取当前目录
        $dirs = $this->getHashDir($this->currentUpload['name']);

        $this->filePath = $this->path.$dirs.$_POST['name'];
    }

    /**
     * 分块上传大小设置
     */
    protected function setChunking()
    {
        // Chunking might be enabled
        $this->chunk = isset($_POST["chunk"]) ? intval($_POST["chunk"]) : 0;
        $this->chunks = isset($_POST["chunks"]) ? intval($_POST["chunks"]) : 0;
    }

    /**
     * 清理目录临时文件
     */
//    private function clearFile() {
//        $filelists = scandir($this->_savePath);
//        foreach ($filelists as $fvalue) {
//            if ($fvalue === '.' || $fvalue==='..') continue;
//            $tmpfilePath = $this->_savePath . DIRECTORY_SEPARATOR . $fvalue;
//            // If temp file is current file proceed to the next
//            if ($tmpfilePath === "{$this->__newFileName}.part") {
//                continue;
//            }
//            // Remove temp file if it is older than the max age and is not the current file
//            if (preg_match('/\.part$/', $fvalue) && (filemtime($tmpfilePath) < time() - $this->_maxFileAge)) {
//                @unlink($tmpfilePath);
//            }
//        }
//    }

    protected function setUploading(array $upload)
    {
        $upload['name'] = $_POST['oldname'];

        parent::setUploading($upload);
    }

    protected function moveUploadFile() : bool
    {
        //块上传大小
        $this->setChunking();

        //读取并写入数据流
        if (!(boolean) $fpIn = fopen($this->currentUpload['tmp_name'], "rb"))
        {
            throw new UploadException($this->currentUpload['name'], UploadException::READ_FILE_STREAM_ERR);
        }

        //获取文件路径
        $file = $this->getTempFile();

        //读取文件
        if (!(boolean) $fpOut = fopen($file, $this->chunks ? "ab" : "wb"))
        {
            //关闭文件流
            fclose($fpIn);
            throw new UploadException($this->currentUpload['name'], UploadException::WRITER_ERR_NO_TMP_DIR);
        }

        //循环按照指定字节读取文件
        while ((boolean)$buff = fread($fpIn, 4096))
        {
            $this->status = fwrite($fpOut, $buff);
        }

        //关闭文件流
        fclose($fpOut);
        fclose($fpIn);

        return true;
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

        //上传完成
        if ($this->status && (!$this->chunks || $this->chunk == $this->chunks - 1))
        {
            $file = $this->getTempFile();

            rename($file, $this->filePath);

            $this->setFile($this->filePath);

            $this->setUploadInfo();
        }
    }

    /**
     * 临时文件名
     * @author simon
     */
    protected function getTempFile()
    {
        return $this->filePath.'.part';;
    }

}