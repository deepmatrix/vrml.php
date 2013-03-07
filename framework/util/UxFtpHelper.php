<?php
/**
 *
 * FTP操作类
 */

class UxFtpHelper {

    /**
     * FTP 连接 ID
     *
     * @var object
     */
    private $_linkId;


    /**
     * 构造函数
     *
     * @return void
     */
    public function __construct() {

    }

    /**
     * 连接FTP
     *
     * @param string $server
     * @param integer $port
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function connect($server, $port = 21, $username = '', $password = '') {

        //参数分析
        if (!$server || !$username || !$password) {
            return false;
        }

        $this->_linkId = ftp_connect($server, $port) or die('FTP server connetc failed!');
        ftp_login($this->_linkId, $username, $password) or die ('FTP server login failed!');
        //打开被动模拟
        ftp_pasv($this->_linkId, 1);

        return true;
    }

    /**
     * FTP-文件上传
     *
     * @param string  $localFile 本地文件
     * @param string  $ftpFile Ftp文件
     * @return bool
     */
    public function upload($localFile, $ftpFile) {

        if (!$localFile || !$ftpFile) {
            return false;
        }

        $ftpPath = dirname($ftpFile);
        if (!empty($ftpPath)) {
            //创建目录
            $this->makeDir($ftpPath);
            @ftp_chdir($this->_linkId, $ftpPath);
            $ftpFile = basename($ftpFile);
        }

        $ret = ftp_nb_put($this->_linkId, $ftpFile, $localFile, FTP_BINARY);
        while ($ret == FTP_MOREDATA) {
            $ret = ftp_nb_continue($this->_linkId);
           }

        if ($ret != FTP_FINISHED) {
            return false;
        }

        return true;
    }

    /**
     * FTP-文件下载
     *
     * @param string  $localFile 本地文件
     * @param string  $ftpFile Ftp文件
     * @return bool
     */
    public function download($localFile, $ftpFile) {

        if (!$localFile || !$ftpFile) {
            return false;
        }

        $ret = ftp_nb_get($this->_linkId, $localFile, $ftpFile, FTP_BINARY);
        while ($ret == FTP_MOREDATA) {
               $ret = ftp_nb_continue ($this->_linkId);
        }

        if ($ret != FTP_FINISHED) {
            return false;
        }

        return true;
    }

    /**
     * FTP-创建目录
     *
     * @param string  $path 路径地址
     * @return bool
     */
    public function makeDir($path) {

        if (!$path) {
            return false;
        }

           $dir  = explode("/", $path);
           $path = ftp_pwd($this->_linkId) . '/';
           $ret  = true;
           for ($i=0; $i<count($dir); $i++) {
            $path = $path . $dir[$i] . '/';
            if (!@ftp_chdir($this->_linkId, $path)) {
                if (!@ftp_mkdir($this->_linkId, $dir[$i])) {
                    $ret = false;
                    break;
                }
            }
            @ftp_chdir($this->_linkId, $path);
         }

        if (!$ret) {
            return false;
        }

         return true;
    }

    /**
     * FTP-删除文件目录
     *
     * @param string  $dir 删除文件目录
     * @return bool
     */
    public function deleteDir($dir) {

        $dir = $this->checkpath($dir);
        if (@!ftp_rmdir($this->_linkId, $dir)) {
            return false;
        }

        return true;
    }

    /**
     * FTP-删除文件
     *
     * @param string  $file 删除文件
     * @return bool
     */
    public function deleteFile($file) {

        $file = $this->checkpath($file);
        if (@!ftp_delete($this->_linkId, $file)) {
            return false;
        }

        return true;
    }

    /**
     * FTP-FTP上的文件列表
     *
     * @param string $path 路径
     * @return bool
     */
    public function nlist($path = '/') {

        return ftp_nlist($this->_linkId, $path);
    }

    /**
     * FTP-改变文件权限值
     *
     * @param string $file 文件
     * @param string $val  值
     * @return bool
     */
    public function chmod($file, $value = 0777) {

        return @ftp_chmod($this->_linkId, $value, $file);
    }

    /**
     * FTP-返回文件大小
     *
     * @param string $file 文件
     * @return bool
     */
    public function fileSize($file) {

        return ftp_size($this->_linkId, $file);
    }

    /**
     * FTP-文件修改时间
     *
     * @param string $file 文件
     * @return bool
     */
    public function mdtime($file) {

        return ftp_mdtm($this->_linkId, $file);
    }

    /**
     * FTP-更改ftp上的文件名称
     *
     * @param string $oldname 旧文件
     * @param string $newname 新文件名称
     * @return bool
     */
    public function rename($oldname, $newname) {

        return ftp_rename ($this->_linkId, $oldname, $newname);
    }

    /**
     * 析构函数
     *
     * @return void
     */
    public function __destruct() {

        if ($this->_linkId) {
            ftp_close($this->_linkId);
        }
    }
}
