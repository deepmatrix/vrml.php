<?php

/**
 * @brief 应用的基本类文件
 * @author webning
 * @date 2010-12-10
 * @version 0.6
 */
abstract class UxAbstractApplication extends UxComponent {

    //应用的名称
    public $name = 'My Application';
    //用户的编码
    private $_charset = 'UTF-8';
    //用户的语言
    private $_language = 'zh_sc';
    //应用的config信息
    private $_conf;
    //应用的要目录
    private $_basePath;
    //id应用的唯一标识
    private $_id;
    //运行时的路径
    private $_runtimePath;
    //运行时的web目录
    private $_webRunPath;
    //默认时区
    private $_timezone = 'Asia/Shanghai';
    //渲染时的数据
    private $_renderData = array();

    /**
     * @brief 构造函数
     * @param array or string $config 配置数组或者配置文件名称
     */
    public function __construct($config) {
        $this->_conf = new UxConfigure($config);

        //设为if true为了标注以后要再解决cli模式下的basePath
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
            } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
                $_SERVER['DOCUMENT_ROOT'] = dirname(rtrim($_SERVER['PATH_TRANSLATED'], "/\\"));
            }
        }

        if ($web = true) {
            $script_dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            if ($script_dir != "") {
                $script_dir .="/";
            }

            $basePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/" . $script_dir;
            $this->_conf->append('basePath', $basePath);
            $this->setBasePath($basePath);
            echo $this->getBasePath();
        }

        if (isset($this->_conf->name)) {
            $this->name = $this->_conf->name;
        }

        if (isset($this->_conf->charset)) {
            $this->setCharset($this->_conf->charset);
        }

        //设置核心的路径别名
        Ux::setPathOfAlias('application',$this->getBasePath());
        Ux::setPathOfAlias('webroot',dirname($_SERVER['SCRIPT_FILENAME']));
        Ux::setPathOfAlias('ext',$this->getBasePath().'extensions');
        Ux::setClasses(Ux::$_classes);
        if (isset($this->_conf->classes)) {
            Ux::setClasses($this->_conf->classes);
        }
        
        if (isset($this->_conf->timezone)) {
            date_default_timezone_set($this->_conf->timezone);
        } else {
            date_default_timezone_set($this->_timezone);
        }

        $debugMode = (isset($this->_conf->debug) && $this->_conf->debug) ? true : false;
        $this->setDebugMode($debugMode);

        $this->disableMagicQuotes();

        if (!isset($this->_conf->upload))
            $this->_conf->append('upload', 'upload');
        
        //开始向拦截器里注册类
        if (isset($this->_conf->interceptor) && is_array($this->_conf->interceptor)) {
            UxInterceptor::reg($this->_conf->interceptor);
            register_shutdown_function(array('UxInterceptor', "shutDown"));
        }
    }

    //执行请求
    abstract public function execRequest();

    /**
     * @brief 应用运行的方法
     * @return Void
     */
    public function run() {
        UxInterceptor::run("onCreateApp");
        $this->execRequest();
        UxInterceptor::run("onFinishApp");
    }

    /**
     * @brief 实现应用的结束方法
     * @param int $status 应该结束的状态码
     */
    public function end($status = 0) {
        exit($status);
    }

    public function setId($id) {
        $this->_id = $id;
    }

    public function getId() {
        return $this->_id;
    }

    /**
     * @brief 取消魔法转义
     */
    public function disableMagicQuotes() {
        if (get_magic_quotes_gpc()) {
            $_POST = $this->_stripSlash($_POST);
            $_GET = $this->_stripSlash($_GET);
            $_COOKIE = $this->_stripSlash($_COOKIE);
        }
    }

    /**
     * @brief 辅助disableMagicQuotes();
     */
    private function _stripSlash($arr) {
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                $arr[$key] = $this->_stripSlash($value);
            }
            return $arr;
        } else {
            return stripslashes($arr);
        }
    }

    /**
     * @brief 设置调试模式
     * @param $flag true开启，false关闭
     */
    private function setDebugMode($flag) {
        $basePath = $this->getBasePath();

        if (function_exists("ini_set")) {
            ini_set("display_errors", $flag ? "on" : "off");
        }

        if ($flag === true) {
            error_reporting(E_ALL | E_STRICT);
            UxException::setDebugMode(true);
        } else {
            error_reporting(0);
            UxException::setDebugMode(false);
        }

        set_error_handler("UxException::phpError", E_ALL | E_STRICT);
        set_exception_handler("UxException::phpException");
        UxException::setLogPath($basePath . "backup/errorLog/" . date("y-m-d") . ".log");
    }

    /**
     * @brief 设置应用的基本路径
     * @param string  $basePath 路径地址
     */
    public function setBasePath($basePath) {
        $this->_basePath = $basePath;
    }

    /**
     * @brief 取得应用的路径
     * @return String 路径地址
     */
    public function getBasePath() {
        return $this->_basePath;
    }

    /**
     * @brief 设置运行时的路径
     * @param mixed $runtimePath 路径地址
     */
    public function setRuntimePath($runtimePath) {
        $this->_runtimePath = $runtimePath;
    }

    /**
     * @brief 得到当前的运行路径
     * @return String 路径地址
     */
    public function getRuntimePath() {
        if ($this->_runtimePath === null) {
            $this->_runtimePath = $this->getBasePath() . 'runtime' . DIRECTORY_SEPARATOR;
        }
        return $this->_runtimePath;
    }

    /**
     * @brief 得到当前的运行URL路径
     * @return String 路径地址
     */
    public function getWebRunPath() {
        if ($this->_webRunPath === null)
            $this->_webRunPath = UxRouter::creatUrl('') . str_replace(array(dirname(realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . "/" . ltrim($_SERVER['SCRIPT_NAME'], '/\\'))) . DIRECTORY_SEPARATOR, '\\'), array('', '/'), realpath($this->getRuntimePath()));
        return $this->_webRunPath;
    }

    /**
     * @brief 设置渲染数据
     * @param array $data 数组的形式存储，渲染后键值将作为变量名。
     */
    public function setRenderData($data) {
        if (is_array($data)) {
            $this->_renderData = array_merge($this->_renderData, $data);
        }
    }

    /**
     * @brief 取得应用级的渲染数据
     * @return array
     */
    public function getRenderData() {
        return $this->_renderData;
    }

    /**
     * @brief 设置应用的语言
     * @param string  $language 语言名称
     */
    public function setLanguage($language) {
        $this->_language = $language;
    }

    /**
     * @brief 得到应用的语言名
     * @return String 语言名称
     */
    public function getLanguage() {
        return $this->_language;
    }

    /**
     * @brief 设置字符集编码
     * @param String $charset 字符编码
     */
    public function setCharset($charset) {
        $this->_charset = $charset;
    }

    /**
     * @brief 获取字符集
     * @return String 字符集编码
     */
    public function getCharset() {
        return $this->_charset;
    }
    
    public function getConf() {
        return $this->_conf;
    }
    
    public function getConfig() {
        return $this->_conf->getAllConf();
    }
}

?>
