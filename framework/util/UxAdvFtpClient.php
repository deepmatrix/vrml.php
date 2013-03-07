<?php
/**
 * 支持tls方式访问ftp的封装，基于curl扩展
 *
 * @author jimmy
 */
class UxAdvFtpClient {
    /**
      ABOR 中断数据连接程序
      ACCT <account> 系统特权帐号
      ALLO <bytes>  为服务器上的文件存储器分配字节
      APPE <filename> 添加文件到服务器同名文件
      CDUP <dir path> 改变服务器上的父目录
      CWD <dir path> 改变服务器上的工作目录
      DELE <filename> 删除服务器上的指定文件
      HELP <command> 返回指定命令信息
      LIST <name> 如果是文件名列出文件信息，如果是目录则列出文件列表
      MODE <mode> 传输模式（S=流模式，B=块模式，C=压缩模式）
      MKD <directory> 在服务器上建立指定目录
      NLST <directory> 列出指定目录内容
      NOOP 无动作，除了来自服务器上的承认
      PASS <password> 系统登录密码
      PASV 请求服务器等待数据连接
      PORT <address> IP 地址和两字节的端口 ID
      PWD 显示当前工作目录
      QUIT 从 FTP 服务器上退出登录
      REIN 重新初始化登录状态连接
      REST <offset> 由特定偏移量重启文件传递
      RETR <filename> 从服务器上找回（复制）文件
      RMD <directory> 在服务器上删除指定目录
      RNFR <old path> 对旧路径重命名
      RNTO <new path> 对新路径重命名
      SITE <params> 由服务器提供的站点特殊参数
      SMNT <pathname> 挂载指定文件结构
      STAT <directory> 在当前程序或目录上返回信息
      STOR <filename> 储存（复制）文件到服务器上
      STOU <filename> 储存文件到服务器名称上
      STRU <type> 数据结构（F=文件，R=记录，P=页面）
      SYST 返回服务器使用的操作系统
      TYPE <data type> 数据类型（A=ASCII，E=EBCDIC，I=binary）
      USER <username>> 系统登录的用户名
     */

    const MKDIR = 'MKD';
    const DELETE_FILE = 'DELE';
    const REMOVE_DIR = 'RMD';
    const CD = 'CWD';
    const LS = 'LIST';

    static public $STATUS = array(
        '110' => '新文件指示器上的重启标记',
        '120' => '服务器准备就绪的时间（分钟数）',
        '125' => '打开数据连接，开始传输',
        '150' => '打开连接',
        '200' => '成功',
        '202' => '命令没有执行',
        '211' => '系统状态回复',
        '212' => '目录状态回复',
        '213' => '文件状态回复',
        '214' => '帮助信息回复',
        '215' => '系统类型回复',
        '220' => '服务就绪',
        '221' => '退出网络',
        '225' => '打开数据连接',
        '226' => '结束数据连接',
        '227' => '进入被动模式（IP 地址、ID 端口）',
        '230' => '登录因特网',
        '250' => '文件行为完成',
        '257' => '路径名建立',
        '331' => '要求密码',
        '332' => '要求帐号',
        '350' => '文件行为暂停',
        '421' => '服务关闭',
        '425' => '无法打开数据连接',
        '426' => '结束连接',
        '450' => '文件不可用',
        '451' => '遇到本地错误',
        '452' => '磁盘空间不足',
        '500' => '无效命令',
        '501' => '错误参数',
        '502' => '命令没有执行',
        '503' => '错误指令序列',
        '504' => '无效命令参数',
        '530' => '未登录网络',
        '532' => '存储文件需要帐号',
        '550' => '文件不可用',
        '551' => '不知道的页类型',
        '552' => '超过存储分配',
        '553' => '文件名不允许'
    );
    private $_url;
    private $_protocol = 'ftp';
    private $_username;
    private $_password;
    private $_port = 21;
    private $_passive = FALSE; # not used yet
    private $_ssl = FALSE;
    private $_ssl_version = FALSE;
    private $_ssl_auth = FALSE;
    private $_curl;
    private $_last_curlinfo;
    private $_last_command;
    private $_last_error;
    private $protocols = array(
        'ftp' => TRUE,
        'ftps' => TRUE,
        'sftp' => FALSE
    );
    private $ssl_version = array(
        'sslv3' => 3,
        'sslv2' => 2,
        'tlsv1' => 1
    );
    private $ssl_auth = array(
        'ssl' => CURLFTPAUTH_SSL,
        'tls' => CURLFTPAUTH_TLS
    );

    public function __construct($params = array()) {
        $this->setOptions($params);
        $this->init(TRUE);
    }

    public function __destruct() {
        if (!empty($this->_curl)) {
            curl_close($this->_curl);
        }
    }

    public function setOptions($params = array()) {
        if (empty($params)) {
            return FALSE;
        }

        if (isset($params['url'])) {
            $this->setUrl($params['url']);
        }

        if (isset($params['username'])) {
            $this->setUsername($params['username']);
        }

        if (isset($params['password'])) {
            $this->setPassword($params['password']);
        }

        if (isset($params['port'])) {
            $this->setPort($params['port']);
        }

        if (isset($params['protocol'])) {
            $this->setProtocol($params['protocol']);
        }

        if (isset($params['use_ssl'])) {
            $this->useSsl($params['use_ssl']);
        }

        if (isset($params['ssl_version'])) {
            $this->setSslVersion($params['ssl_version']);
        }

        if (isset($params['ssl_auth'])) {
            $this->setSslAuthMethod($params['ssl_auth']);
        }

        return TRUE;
    }

    public function setUrl($url) {
        $pos = strpos($url, '://');

        if ($pos === FALSE) {
            $this->_url = trim($url, '/');
        } else {
            preg_match("@(ftp|http|https|ftps|sftp(?=:\/\/))?(?::\/\/)?([^:\/]+)(?(?=:):([\d]+))@i", $url, $match);

            var_dump($match);
            /*
              if(! empty($match[1]))
              {
              $this->_protocol = $match[1];
              }
              $this->_url = $match[2];
             */
        }
    }

    public function setPort($port) {
        $this->_port = (int) $port;
    }

    public function setUsername($username) {
        $this->_username = $username;
    }

    public function setPassword($password) {
        $this->_password = $password;
    }

    public function setProtocol($protocol) {
        $protocol = strtolower($protocol);

        if (isset($this->protocols[$protocol]) && $this->protocols[$protocol] == TRUE) {
            $this->_protocol = $protocol;
        }
    }

    public function useSsl($switch = FALSE) {
        if ($switch) {
            $this->_ssl = TRUE;
        } else {
            $this->_ssl = FALSE;
        }

        return $this->_ssl;
    }

    public function setSslVersion($version = FALSE) {
        if ($version == FALSE) {
            $this->_ssl_version = FALSE;
        } else {
            $version = strtolower($version);

            if (isset($this->ssl_version[$version]) && $this->ssl_version[$version] != FALSE) {
                $this->_ssl_version = $version;
            }
        }

        return $this->_ssl_version;
    }

    public function setSslAuthMethod($method = FALSE) {
        if ($method == FALSE || !isset($this->ssl_auth[strtolower($method)])) {
            $this->_ssl_auth = FALSE;
        } else {
            $this->_ssl_auth = strtolower($method);
        }

        return $this->_ssl_auth;
    }

    public function getOptions() {
        return array(
            'url' => $this->_url,
            'protocol' => $this->_protocol,
            'username' => $this->_username,
            'password' => $this->_password,
            'port' => $this->_port,
            'use_ssl' => $this->_ssl,
            'ssl_version' => $this->_ssl_version,
            'ssl_auth' => $this->_ssl_auth
        );
    }

    public function getLastError() {
        return $this->_last_error;
    }

    public function getLastCommand() {
        return $this->_last_command;
    }

    public function init($new_connect = FALSE) {
        $retVal = TRUE;

        if (empty($this->_url)) {
            $retVal = FALSE;
        } else if (!($new_connect || empty($this->_curl))) {
            $retVal = TRUE;
        } else {
            $curl_handler = $this->_curl;

            if (!empty($curl_handler)) {
                $this->_curl = NULL;
                curl_close($curl_handler);
            }

            $curl_handler = $this->_curl = curl_init();

            $port = (empty($this->_port) && $this->_port !== 0) ? 21 : $this->_port;

            curl_setopt($curl_handler, CURLOPT_PORT, $port);
            curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl_handler, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handler, CURLOPT_FTP_USE_EPRT, TRUE);
            curl_setopt($curl_handler, CURLOPT_FTP_USE_EPSV, TRUE);
            curl_setopt($curl_handler, CURLOPT_USERPWD, "{$this->_username}:{$this->_password}");

            $ssl = ($this->_ssl == TRUE) ? CURLFTPSSL_ALL : CURLFTPSSL_NONE;
            curl_setopt($curl_handler, CURLOPT_FTP_SSL, $ssl);

            if ($this->_ssl && $this->_ssl_version != FALSE) {
                curl_setopt($curl_handler, CURLOPT_SSLVERSION, $this->ssl_version[$this->_ssl_version]);
            }

            if ($this->_ssl && $this->_ssl_auth != FALSE) {
                $me = $this->_ssl_auth == 'ssl' ? CURLFTPAUTH_SSL : CURLFTPAUTH_TLS;
                curl_setopt($curl_handler, CURLOPT_FTPSSLAUTH, $me);
            }
        }

        return $retVal;
    }

    public function browse($dir = '') {
        if (empty($this->_curl)) {
            $this->init(TRUE);
        }

        $url = $this->_protocol . '://' . $this->_url . "/";
        if (!empty($dir)) {
            $dir = trim($dir, '/');
            $url .= $dir . '/';
        }
        $this->_last_command = "BROWSE {$url}";

        $curl_handler = $this->_curl;
        curl_setopt($curl_handler, CURLOPT_URL, $url);
        $data = curl_exec($curl_handler);
        $info = curl_getinfo($curl_handler);

        $retVal = array(
            'success' => ($info['http_code'] >= 200 && $info['http_code'] < 300),
            'status' => (isset(self::$STATUS[$info['http_code']])) ? self::$STATUS[$info['http_code']] : $info['http_code'],
            'data' => $data
        );

        return $retVal;
    }

    public function upload($src, $dir, $dest_name) {

        if (empty($src) || empty($dest_name)) {
            return FALSE;
        }

        $dir = trim($dir, '/');
        $dir = empty($dir) ? '' : "/{$dir}";

        $file = fopen($src, 'r');
        $size = filesize($src);

        if (empty($this->_curl)) {
            $this->init(TRUE);
        }

        $curl_handler = $this->_curl;

        $dest = $this->_protocol . '://' . $this->_url;
        $dest .= $dir;
        $dest .= "/{$dest_name}";

        curl_setopt($curl_handler, CURLOPT_URL, $dest);
        curl_setopt($curl_handler, CURLOPT_UPLOAD, TRUE);
        curl_setopt($curl_handler, CURLOPT_INFILE, $file);
        curl_setopt($curl_handler, CURLOPT_INFILESIZE, $size);

        $data = curl_exec($curl_handler);

        $this->_last_command = 'UPLOAD {$src} TO {$dest}';

        $info = curl_getinfo($curl_handler);

        $retVal = array(
            'success' => ($info['http_code'] >= 200 && $info['http_code'] < 300),
            'status' => (isset(self::$STATUS[$info['http_code']])) ? self::$STATUS[$info['http_code']] : $info['http_code'],
            'data' => $data
        );

        curl_setopt($curl_handler, CURLOPT_UPLOAD, FALSE);
        return $retVal;
    }

    public function mkdir($newdir, $dir = '') {
        if (empty($newdir) || strpos($newdir, '/') != FALSE) {
            return FALSE;
        } else {
            $newdir = trim($newdir, '/');
        }
        $url = $this->_protocol . '://' . $this->_url . '/';
        if (!empty($dir)) {
            $dir = trim($dir, '/');
            $url .= $dir . '/';
        }
        $commands = array();
        $commands[] = self::MKDIR . " {$newdir}";
        $this->_last_command = implode('; ', $commands);
        $curl_handler = $this->_curl;

        curl_setopt($curl_handler, CURLOPT_URL, $url);
        curl_setopt($curl_handler, CURLOPT_POSTQUOTE, $commands);

        $data = curl_exec($curl_handler);
        $info = curl_getinfo($curl_handler);

        $retVal = array(
            'success' => ($info['http_code'] >= 200 && $info['http_code'] < 300),
            'status' => (isset(self::$STATUS[$info['http_code']])) ? self::$STATUS[$info['http_code']] : $info['http_code'],
            'data' => $data
        );

        return $retVal;
    }

    public function delete($dir, $toDelete, $type = 'f') {
        if (empty($toDelete) || ($type != 'd' && $type != 'f')) {
            return FALSE;
        }

        $url = $this->_protocol . '://' . $this->_url . '/';
        if (!empty($dir)) {
            $dir = trim($dir, '/');
            $url .= $dir . '/';
        }

        $commands = array();
        $command = ($toDelete == 'f') ? self::DELETE_FILE : self::REMOVE_DIR;
        $file = trim($toDelete, '/');
        $command = "{$command} {$toDelete}";
        $commands[] = $command;

        $curl_handler = $this->_curl;

        curl_setopt($curl_handler, CURLOPT_URL, $url);
        curl_setopt($curl_handler, CURLOPT_POSTQUOTE, $commands);

        $this->_last_command = $command;

        $data = curl_exec($curl_handler);
        $info = curl_getinfo($curl_handler);

        $retVal = array(
            'success' => ($info['http_code'] >= 200 && $info['http_code'] < 300),
            'status' => (isset(self::$STATUS[$info['http_code']])) ? self::$STATUS[$info['http_code']] : $info['http_code'],
            'data' => $data
        );

        return $retVal;
    }

    public function getCommandHelp($command) {
        
    }

}