<?php
/**
 * @brief 处理 $_GET,$_POST 数据
 * @author jimmy
 */
class UxHttpRequest
{
    
	/**
	 * 访问的端口号
	 *
	 * @var int
	 */
	protected $_port = null;
	/**
	 * 请求路径信息
	 *
	 * @var string
	 */
	protected $_hostInfo = null;
	/**
	 * 客户端IP
	 *
	 * @var string
	 */
	protected $_clientIp = null;
	
	/**
	 * 语言
	 *
	 * @var string
	 */
	protected $_language = null;
	
	/**
	 * 路径信息
	 *
	 * @var string
	 */
	protected $_pathInfo = null;
	
	/**
	 * 请求参数信息
	 *
	 * @var array
	 */
	protected $_attribute = array();
    
	/**
	 * 初始化Request对象
	 *
	 */
	public function __construct() {
		$this->normalizeRequest();
	}
   
	/**
	 * 初始化request对象
	 *
	 * 对输入参数做转义处理
	 */
	protected function normalizeRequest() {
		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
			if (isset($_GET)) $_GET = $this->_stripSlashes($_GET);
			if (isset($_POST)) $_POST = $this->_stripSlashes($_POST);
			if (isset($_REQUEST)) $_REQUEST = $this->_stripSlashes($_REQUEST);
			if (isset($_COOKIE)) $_COOKIE = $this->_stripSlashes($_COOKIE);
		}
	}
    
	/**
	 * @brief 获取键为$key的 $_GET 和 $_POST 传送方式的数据
	 * @param string $key $_GET 或 $_POST 的键
	 * @param string $type 传送方式 值: false:默认(先get后post); get:get方式; post:post方式;
	 * @return string $_GET 或者 $_POST 的值
	 * @note 优先获取 $_GET 方式的数据,如果不存在则获取 $_POST 方式的数据
	 */
	public static function get($key, $type=false)
	{
		//默认方式
		if($type==false)
		{
			if(isset($_GET[$key])) return $_GET[$key];
			else if(isset($_POST[$key])) return $_POST[$key];
			else return null;
		}

		//get方式
		else if($type=='get' && isset($_GET[$key]))
			return $_GET[$key];

		//post方式
		else if($type=='post' && isset($_POST[$key]))
			return $_POST[$key];

		//无匹配
		else
			return null;

	}

	/**
	 * @brief 设置键为$key 的$_GET 或者 $_POST 的变量值
	 * @param string $key $_GET 或者 $_POST 键
	 * @param string $value 设置的值
	 * @param string $type 设置的类型 值: get:默认,get方式; post:post方式
	 */
	public static function set($key, $value, $type='get')
	{
		//get方式
		if($type=='get')
			$_GET[$key] = $value;

		//post方式
		else if($type=='post')
			$_POST[$key] = $value;
	}
    
	/**
	 * 返回该请求是否为ajax请求
	 * 
	 * 如果是ajax请求将返回true,否则返回false
	 * @return boolean 
	 */
	public static function isAjax() {
		return !strcasecmp(self::getServer('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest');
	}

	/**
	 * 请求是否使用的是HTTPS安全链接
	 * 
	 * 如果是安全请求则返回true否则返回false
	 * @return boolean
	 */
	public static function isSecure() {
		return !strcasecmp(self::getServer('HTTPS'), 'on');
	}

	/**
	 * 返回请求是否为GET请求类型
	 * 
	 * 如果请求是GET方式请求则返回true，否则返回false
	 * @return boolean 
	 */
	public static function isGet() {
		return !strcasecmp(self::getRequestMethod(), 'GET');
	}

	/**
	 * 返回请求是否为POST请求类型
	 * 
	 * 如果请求是POST方式请求则返回true,否则返回false
	 * 
	 * @return boolean
	 */
	public static function isPost() {
		return !strcasecmp(self::getRequestMethod(), 'POST');
	}

	/**
	 * 返回请求是否为PUT请求类型
	 * 
	 * 如果请求是PUT方式请求则返回true,否则返回false
	 * 
	 * @return boolean
	 */
	public static function isPut() {
		return !strcasecmp(self::getRequestMethod(), 'PUT');
	}

	/**
	 * 返回请求是否为DELETE请求类型
	 * 
	 * 如果请求是DELETE方式请求则返回true,否则返回false
	 * 
	 * @return boolean
	 */
	public static function isDelete() {
		return !strcasecmp(self::getRequestMethod(), 'Delete');
	}
    
	/**
	 * 获得请求的方法
	 * 
	 * 将返回POST\GET\DELETE等HTTP请求方式
	 * @return string 
	 */
	public static function getRequestMethod() {
		return strtoupper(self::getServer('REQUEST_METHOD'));
	}
	
	/* (non-PHPdoc)
	 * @see IWindRequest::getAttribute()
	 */
	public static function getAttribute($key, $defaultValue = '') {
        if (isset($_GET[$key]))
			return $_GET[$key];
		else if (isset($_POST[$key]))
			return $_POST[$key];
		else if (isset($_COOKIE[$key]))
			return $_COOKIE[$key];
		else if (isset($_REQUEST[$key]))
			return $_REQUEST[$key];
		else if (isset($_ENV[$key]))
			return $_ENV[$key];
		else if (isset($_SERVER[$key]))
			return $_SERVER[$key];
		else
			return $defaultValue;
	}

	/**
	 * 获得用户请求的数据
	 * 
	 * 返回$_GET,$_POST的值,未设置则返回$defaultValue
	 * @param string $key 获取的参数name,默认为null将获得$_GET和$_POST两个数组的所有值
	 * @param mixed $defaultValue 当获取值失败的时候返回缺省值,默认值为null
	 * @return mixed
	 */
	public static function getRequest($key = null, $defaultValue = null) {
		if (!$key) return array_merge($_POST, $_GET);
		if (isset($_GET[$key])) return $_GET[$key];
		if (isset($_POST[$key])) return $_POST[$key];
		return $defaultValue;
	}

	/**
	 * 获取请求的表单数据
	 * 
	 * 从$_POST获得值
	 * @param string $name 获取的变量名,默认为null,当为null的时候返回$_POST数组
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认为null
	 * @return mixed
	 */
	public static function getPost($name = null, $defaultValue = null) {
		if ($name === null) return $_POST;
		return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
	}

	/**
	 * 获得$_GET值
	 * 
	 * @param string $name 待获取的变量名,默认为空字串,当该值为null的时候将返回$_GET数组
	 * @param string $defaultValue 当获取的变量不存在的时候返回该缺省值,默认值为null
	 * @return mixed
	 */
	public static function getGet($name = '', $defaultValue = null) {
		if ($name === null) return $_GET;
		return (isset($_GET[$name])) ? $_GET[$name] : $defaultValue;
	}

	/**
	 * 返回cookie的值
	 * 
	 * 如果$name=null则返回所有Cookie值
	 * @param string $name 获取的变量名,如果该值为null则返回$_COOKIE数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public static function getCookie($name = null, $defaultValue = null) {
		if ($name === null) return $_COOKIE;
		return (isset($_COOKIE[$name])) ? $_COOKIE[$name] : $defaultValue;
	}

	/**
	 * 返回session的值
	 * 
	 * 如果$name=null则返回所有SESSION值
	 * @param string $name 获取的变量名,如果该值为null则返回$_SESSION数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public static function getSession($name = null, $defaultValue = null) {
		if ($name === null) return $_SESSION;
		return (isset($_SESSION[$name])) ? $_SESSION[$name] : $defaultValue;
	}

	/**
	 * 返回Server的值
	 * 
	 * 如果$name为空则返回所有Server的值
	 * @param string $name 获取的变量名,如果该值为null则返回$_SERVER数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public static function getServer($name = null, $defaultValue = null) {
		if ($name === null) return $_SERVER;
		return (isset($_SERVER[$name])) ? $_SERVER[$name] : $defaultValue;
	}

	/**
	 * 返回ENV的值
	 * 
	 * 如果$name为null则返回所有$_ENV的值
	 * @param string $name 获取的变量名,如果该值为null则返回$_ENV数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public static function getEnv($name = null, $defaultValue = null) {
		if ($name === null) return $_ENV;
		return (isset($_ENV[$name])) ? $_ENV[$name] : $defaultValue;
	}
}