<?php
/**
 * 路由构造与解析
 * 基于模块的方式，但入口文件由模块自己控制，即每个模块一个入口
 *
 */
class Router {
    
    const URL_MODE_NATIVE = 1; //原生的Url形式,指从index.php，比如index.php?controller=blog&action=read&id=100
    const URL_MODE_PATHINFO = 2; //pathinfo格式的Url,指的是：/blog/read/id/100
    const URL_MODE_DIY = 3; //经过urlRoute后的Url,指的是:/blog-100.html
    const URL_MODE_URL_PATHINFO = 4;//带入口文件的pathinfo， index.php/blog/read/id/100
    
    const URL_KEY_CTRL = 'controller';
    const URL_KEY_ACTION = 'action';
    const URL_KEY_MODULE = 'module';
    const URL_KEY_ANCHOR = "/#&"; //urlArray中表示锚点的索引
    const URL_KEY_QUESTION_MARK = "?"; // /site/abc/?callback=/site/login callback=/site/login部分在UrlArray里的key

    private static $_urlRoute = array(); //路由规则的缓存
    
    /**
     * @brief  接收基准格式的URL，将其转换为Config中设置的模式
     * @param  String $url      传入的url
     * @param array $paramters 请求参数
     * @return String $finalUrl url地址
     */
    public static function createUrl($url = '') {
        if (preg_match("!^[a-z]+://!i", $url)) {
            return $url;
        }

        $baseUrl = self::getIndexFile();
        if ($url == "") {
            return self::getScriptDir();
        } elseif ($url == "/") {
            return self::getScriptDir() . $baseUrl;
        }

        $rewriteRule = defined('URL_MODE') ? URL_MODE : self::URL_MODE_NATIVE;

        //判断是否需要返回绝对路径的url
        $baseDir = self::getScriptDir();

        $url = self::tidy($url);
        $tmpUrl = false;

        if ($rewriteRule == self::URL_MODE_PATHINFO) {
            $tmpUrl = self::convertUrl($url, self::URL_MODE_PATHINFO, self::URL_MODE_DIY);
        }
        
        if ($tmpUrl !== false) {
            $url = $tmpUrl;
        } else {
            switch ($rewriteRule) {
                case self::URL_MODE_NATIVE:
                    $url = self::convertUrl($url, self::URL_MODE_PATHINFO, self::URL_MODE_NATIVE);
                    break;
                case self::URL_MODE_URL_PATHINFO:
                    $url = "/" . self::getIndexFile() . "/" . $url;
                    break;
            }
        }
        $url = self::tidy($baseDir . $url);
        return $url;
    }
    
    /**
     * @brief 获取url参数
     * @param String url 需要分析的url，默认为当前url
     */
    public static function parseUrl($url = '') {
        //四种
        //native： /index.php?controller=blog&action=read&id=100
        //pathinfo:/blog/read/id/100
        //native-pathinfo:/index.php/blog/read/id/100
        //diy:/blog-100.html
        $obj = UxServerVars::factory($_SERVER['SERVER_SOFTWARE']);
        $url = !empty($url) ? $url : $obj->realUri();
        preg_match('/\.php(.*)/', $url, $phpurl);
        if (!isset($phpurl[1]) || !$phpurl[1]) {
            if ($url != "") {
                //强行赋值
                //todo：检测是否有bug
                $phpurl = array(1 => "?");
            } else {
                return;
            }
        }
        $url = $phpurl[1];
        $urlArray = array();
        $rewriteRule = defined('URL_MODE') ? URL_MODE : self::URL_MODE_NATIVE;
        if ($rewriteRule != self::URL_MODE_NATIVE) {
            $urlArray = self::decodeRouteUrl($url);
        }
        if ($urlArray == array()) {
            if ($url[0] == '?') {
                $urlArray = $_GET;
            } else {
                $urlArray = self::pathinfoToArray($url);
            }
        }

        if (isset($urlArray[self::URL_KEY_CTRL])) {
            $tmp = explode('-', $urlArray[self::URL_KEY_CTRL]);
            if (count($tmp) == 2) {
                $_GET[self::URL_KEY_MODULE] = $tmp[0];
                $_GET[self::URL_KEY_CTRL] = $tmp[1];
            } else {
                $_GET[self::URL_KEY_CTRL] = $urlArray[self::URL_KEY_CTRL];
            }
        }
        if (isset($urlArray[self::URL_KEY_ACTION])) {
            $_GET[self::URL_KEY_ACTION] =  $urlArray[self::URL_KEY_ACTION];
        }

        unset($urlArray[self::URL_KEY_CTRL], $urlArray[self::URL_KEY_ACTION], $urlArray[self::URL_KEY_ANCHOR]);
        foreach ($urlArray as $key => $value) {
            $_GET[$key] = $value;
        }
    }
    
    /**
     * @brief  获取当前脚本所在文件夹
     * @return 脚本所在文件夹
     */
    public static function getScriptDir() {
        $re = trim(dirname($_SERVER['SCRIPT_NAME']), '\\');
        if ($re != '/') {
            $re = $re . "/";
        }
        return $re;
    }
    
    /**
     * @brief 获取入口文件名
     */
    public static function getIndexFile() {
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            return 'index.php';
        } else {
            return basename($_SERVER['SCRIPT_NAME']);
        }
    }
    
    public static function tidy($url) {
        return preg_replace("![/\\\\]{2,}!", "/", $url);
    }
    

    /**
     * @brief 将Url从一种Url格式转成另一个格式。
     * @param string $url 想转换的url
     * @param int $from 
     * @param int $to 
     * @return string 如果转换失败则返回false
     */
    public static function convertUrl($url, $from, $to) {
        if ($from == $to) {
            return $url;
        }

        $urlArray = "";
        $fun_re = false;
        switch ($from) {
            case self::URL_MODE_NATIVE :
                $urlTmp = parse_url($url);
                $urlArray = self::queryStringToArray($urlTmp);
                break;
            case self::URL_MODE_PATHINFO :
                $urlArray = self::pathinfoToArray($url);
                break;
            case self::URL_MODE_DIY :
                $urlArray = self::diyToArray($url);
                break;
            default:
                return $fun_re;
                break;
        }

        switch ($to) {
            case self::URL_MODE_NATIVE :
                $fun_re = self::urlArrayToNative($urlArray);
                break;
            case self::URL_MODE_PATHINFO :
                $fun_re = self::urlArrayToPathinfo($urlArray);
                break;
            case self::URL_MODE_DIY:
                $fun_re = self::urlArrayToDiy($urlArray);
                break;
        }
        return $fun_re;
    }
    
    /**
     * @brief 将controller=blog&action=read&id=100类的query转成数组的形式
     * @param string $url
     * @return array
     */
    public static function queryStringToArray($url) {
        if (!is_array($url)) {
            $url = parse_url($url);
        }
        $query = isset($url['query']) ? explode("&", $url['query']) : array();
        $re = array();
        foreach ($query as $value) {
            $tmp = explode("=", $value);
            if (count($tmp) == 2) {
                $re[$tmp[0]] = $tmp[1];
            }
        }
        $re = self::sortUrlArray($re);
        isset($url['fragment']) && ($re[self::URL_KEY_ANCHOR] = $url['fragment']);
        return $re;
    }

    /**
     * @brief 将/blog/read/id/100形式的url转成数组的形式
     * @param string $url
     * @return array
     */
    public static function pathinfoToArray($url) {
        //blog/read/id/100
        //blog/read/id/100?comment=true#abcde
        $data = array();
        preg_match("!^(.*?)?(\\?[^#]*?)?(#.*)?$!", $url, $data);
        $re = array();
        if (isset($data[1]) && trim($data[1], "/ ")) {
            $string = explode("/", trim($data[1], "/ "));
            $key = null;
            $i = 1;
            //前两个是ctrl和action，后面的是参数名和值
            foreach ($string as $value) {
                if ($i <= 2) {
                    $tmpKey = ($i == 1) ? self::URL_KEY_CTRL : self::URL_KEY_ACTION;
                    $re[$tmpKey] = $value;
                    $i++;
                    continue;
                }

                if ($key === null) {
                    $key = $value;
                    $re[$key] = "";
                } else {
                    $re[$key] = $value;
                    $key = null;
                }
            }
        }
        if (isset($data[2]) || isset($data[3])) {
            $re[self::URL_KEY_QUESTION_MARK] = ltrim($data[2], "?");
        }

        if (isset($data[3])) {
            $re[self::URL_KEY_ANCHOR] = ltrim($data[3], "#");
        }

        $re = self::sortUrlArray($re);
        return $re;
    }
    
    /**
     * @brief 将用户请求的url进行路由转换，得到urlArray
     * @param string  $url
     * @return array
     */
    public static function diyToArray($url) {
        return self::decodeRouteUrl($url);
    }
    
        /**
     * @brief 将请求的url通过路由规则解析成urlArray
     * @param $url string 要解析的url地址
     */
    private static function decodeRouteUrl($url) {
        $url = trim($url, '/');
        $urlArray = array(); //url的数组形式
        $routeList = self::getRouteCache();
        if (!$routeList) {
            return $urlArray;
        }

        foreach ($routeList as $level => $regArray) {
            foreach ($regArray as $regPattern => $value) {
                //解析执行规则的url地址
                $exeUrlArray = explode('/', $value);

                //判断当前url是否符合某条路由规则,并且提取url参数
                $regPatternReplace = preg_replace("%<\w+?:(.*?)>%", "($1)", $regPattern);
                if (strpos($regPatternReplace, '%') !== false) {
                    $regPatternReplace = str_replace('%', '\%', $regPatternReplace);
                }

                if (preg_match("%$regPatternReplace%", $url, $matchValue)) {
                    //是否完全匹配整个完整url
                    $matchAll = array_shift($matchValue);
                    if ($matchAll != $url) {
                        continue;
                    }

                    //如果url存在动态参数，则获取到$urlArray
                    if ($matchValue) {
                        preg_match_all("%<\w+?:.*?>%", $regPattern, $matchReg);
                        foreach ($matchReg[0] as $key => $val) {
                            $val = trim($val, '<>');
                            $tempArray = explode(':', $val, 2);
                            $urlArray[$tempArray[0]] = isset($matchValue[$key]) ? $matchValue[$key] : '';
                        }

                        //检测controller和action的有效性
                        if ((isset($urlArray[self::URL_KEY_CTRL]) && !preg_match("%^\w+$%", $urlArray[self::URL_KEY_CTRL]) ) || (isset($urlArray[self::URL_KEY_ACTION]) && !preg_match("%^\w+$%", $urlArray[self::URL_KEY_ACTION]) )) {
                            $urlArray = array();
                            continue;
                        }

                        //对执行规则中的模糊变量进行赋值
                        foreach ($exeUrlArray as $key => $val) {
                            $paramName = trim($val, '<>');
                            if (($val != $paramName) && isset($urlArray[$paramName])) {
                                $exeUrlArray[$key] = $urlArray[$paramName];
                            }
                        }
                    }

                    //分配执行规则中指定的参数
                    $paramArray = self::pathinfoToArray(join('/', $exeUrlArray));
                    $urlArray = array_merge($urlArray, $paramArray);
                    return $urlArray;
                }
            }
        }
        return $urlArray;
    }
    
    /**
     * 当设置URL模式为DIY后，需要先调用此方法设置diy规则
     * @param array $rules
     */
    public static function setRouteRules($rules){
        //if(is_array($rules))
        //    self::$_urlRoute = $rules;
        
        //存在路由的缓存信息
        if (self::$_urlRoute) {
            return self::$_urlRoute;
        }
        
        if(!is_array($rules))
            return null;

        $cacheRoute = array();
        foreach ($rules as $key => $val) {
            if (is_array($val)) {
                continue;
            }

            $tempArray = explode('/', trim($val, '/'), 3);
            if ($tempArray < 2) {
                continue;
            }

            //进行路由规则的级别划分,$level越低表示匹配优先
            $level = 3;
            if (($tempArray[0] != '<' . self::URL_KEY_CTRL . '>') && ($tempArray[1] != '<' . self::URL_KEY_ACTION . '>'))
                $level = 0;
            elseif (($tempArray[0] == '<' . self::URL_KEY_CTRL . '>') && ($tempArray[1] != '<' . self::URL_KEY_ACTION . '>'))
                $level = 1;
            elseif (($tempArray[0] != '<' . self::URL_KEY_CTRL . '>') && ($tempArray[1] == '<' . self::URL_KEY_ACTION . '>'))
                $level = 2;

            $cacheRoute[$level][$key] = $val;
        }

        if (empty($cacheRoute)) {
            self::$_urlRoute = false;
            return null;
        }

        ksort($cacheRoute);
        self::$_urlRoute = $cacheRoute;
    }

   /**
     * @brief 获取路由缓存
     * @return array
     */
    private static function getRouteCache() {
        //配置文件中不存在路由规则
        if (self::$_urlRoute === false) {
            return null;
        }
        
        return self::$_urlRoute;
    }
    
    /**
     * @brief 将urlArray用原生url形式表现出来
     * @access private
     */
    private static function urlArrayToNative($arr) {
        $re = "/";
        $re .= self::getIndexFile();
        $fragment = isset($arr[self::URL_KEY_ANCHOR]) ? $arr[self::URL_KEY_ANCHOR] : "";

        $questionMark = isset($arr[self::URL_KEY_QUESTION_MARK]) ? $arr[self::URL_KEY_QUESTION_MARK] : "";

        unset($arr[self::URL_KEY_ANCHOR], $arr[self::URL_KEY_QUESTION_MARK]);
        if (count($arr)) {
            $tmp = array();
            foreach ($arr as $key => $value) {
                $tmp[] = "{$key}={$value}";
            }
            $tmp = implode("&", $tmp);
            $re .= "?{$tmp}";
        }
        if (count($arr) && $questionMark != "") {
            $re .= "&" . $questionMark;
        } elseif ($questionMark != "") {
            $re .= "?" . $questionMark;
        }

        if ($fragment != "") {
            $re .= "#{$fragment}";
        }
        return $re;
    }
    
    /**
     * @brief 将urlArray用pathinfo的形式表示出来
     * @access private
     */
    private static function urlArrayToPathinfo($arr) {
        $re = "";
        $ctrl = isset($arr[self::URL_KEY_CTRL]) ? $arr[self::URL_KEY_CTRL] : '';
        $action = isset($arr[self::URL_KEY_ACTION]) ? $arr[self::URL_KEY_ACTION] : '';

        $ctrl != "" && ($re.="/{$ctrl}");
        $action != "" && ($re.="/{$action}");

        $fragment = isset($arr[self::URL_KEY_ANCHOR]) ? $arr[self::URL_KEY_ANCHOR] : "";
        $questionMark = isset($arr[self::URL_KEY_QUESTION_MARK]) ? $arr[self::URL_KEY_QUESTION_MARK] : "";
        unset($arr[self::URL_KEY_CTRL], $arr[self::URL_KEY_ACTION], $arr[self::URL_KEY_ANCHOR]);
        foreach ($arr as $key => $value) {
            $re.="/{$key}/{$value}";
        }
        if ($questionMark != "") {
            $re .= "?" . $questionMark;
        }
        $fragment != "" && ($re .= "#{$fragment}");
        return $re;
    }
    
    /**
     * @brief 将urlArray转成路由后的url
     * @access private
     */
    private static function urlArrayToDiy($arr) {
        if (!isset($arr[self::URL_KEY_CTRL]) || !isset($arr[self::URL_KEY_ACTION]) || !($routeList = self::getRouteCache())) {
            return false;
        }

        foreach ($routeList as $level => $regArray) {
            foreach ($regArray as $regPattern => $value) {
                $urlArray = explode('/', trim($value, '/'), 3);

                if ($level == 0 && ($arr[self::URL_KEY_CTRL] . '/' . $arr[self::URL_KEY_ACTION] != $urlArray[0] . '/' . $urlArray[1])) {
                    continue;
                } else if ($level == 1 && ($arr[self::URL_KEY_ACTION] != $urlArray[1])) {
                    continue;
                } else if ($level == 2 && ($arr[self::URL_KEY_CTRL] != $urlArray[0])) {
                    continue;
                }

                $url = self::parseRegPattern($arr, array($regPattern => $value));

                if ($url) {
                    return $url;
                }
            }
        }
        return false;
    }

   /**
     * @brief 根据规则生成URL
     * @param $urlArray array url信息数组
     * @param $regPattern array 路由规则
     * @return string or false
     */
    private static function parseRegPattern($urlArray, $regArray) {
        $regPattern = key($regArray);
        $value = current($regArray);

        //存在自定义正则式
        if (preg_match_all("%<\w+?:.*?>%", $regPattern, $customRegMatch)) {
            $regInfo = array();
            foreach ($customRegMatch[0] as $val) {
                $val = trim($val, '<>');
                $regTemp = explode(':', $val, 2);
                $regInfo[$regTemp[0]] = $regTemp[1];
            }

            //匹配表达式参数
            $replaceArray = array();
            foreach ($regInfo as $key => $val) {
                if (strpos($val, '%') !== false) {
                    $val = str_replace('%', '\%', $val);
                }

                if (isset($urlArray[$key]) && preg_match("%$val%", $urlArray[$key])) {
                    $replaceArray[] = $urlArray[$key];
                    unset($urlArray[$key]);
                } else {
                    return false;
                }
            }

            $url = str_replace($customRegMatch[0], $replaceArray, $regPattern);
        } else {
            $url = $regPattern;
        }

        //处理多余参数
        $paramArray = self::pathinfoToArray($value);

        $questionMarkKey = isset($urlArray[self::URL_KEY_QUESTION_MARK]) ? $urlArray[self::URL_KEY_QUESTION_MARK] : '';
        $anchor = isset($urlArray[self::URL_KEY_ANCHOR]) ? $urlArray[self::URL_KEY_ANCHOR] : '';
        unset($urlArray[self::URL_KEY_CTRL], $urlArray[self::URL_KEY_ACTION], $urlArray[self::URL_KEY_ANCHOR], $urlArray[self::URL_KEY_QUESTION_MARK]);
        foreach ($urlArray as $key => $rs) {
            if (!isset($paramArray[$key])) {
                $questionMarkKey .= '&' . $key . '=' . $rs;
            }
        }
        $url .= ($questionMarkKey) ? '?' . trim($questionMarkKey, '&') : '';
        $url .= ($anchor) ? '#' . $anchor : '';

        return $url;
    }

    /**
     * @brief 对Url数组里的数据进行排序
     * ctrl和action最靠前，其余的按key排序
     * @param array $re
     * @access private
     */
    private static function sortUrlArray($re) {
        $fun_re = array();
        isset($re[self::URL_KEY_CTRL]) && ($fun_re[self::URL_KEY_CTRL] = $re[self::URL_KEY_CTRL]);
        isset($re[self::URL_KEY_ACTION]) && ($fun_re[self::URL_KEY_ACTION] = $re[self::URL_KEY_ACTION]);
        unset($re[self::URL_KEY_CTRL], $re[self::URL_KEY_ACTION]);
        ksort($re);
        $fun_re = array_merge($fun_re, $re);
        return $fun_re;
    }
}

//$_SERVER里与url有关的两个量的兼容处理方案
//这是个不成熟的解决方案，可能仅适用于本框架

class UxServerVars implements IUxServerVars
{
	public static function factory($server_type)
	{
		$obj = null;
		$type = array(
			'apache' => 'UxApacheServerVars',
			'iis'	=> 'UxIisServerVars_IIS' ,
			'nginx' => 'UxNginxServerVars'
		);

		foreach($type as $key=>$value)
		{
			if(stripos($server_type,$key) !== false )
			{
				$obj = new $value($server_type);
				break;
			}
		}

		if($obj === null)
		{
			return new UxServerVars();
		}
		else
		{
			return $obj;
		}
	}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		return $_SERVER['REQUEST_URI'];
	}
}

interface IUxServerVars
{
	/**
	 * 获取REQUEST_URI
	 * 标准：真实的从浏览器地址栏体现出来的url，不包括#锚点
	 */
	public function requestUri();

	/**
	 * 最终呈现给服务器的url
	 * 标准：伪静态前与request_uri相同，伪静态后是最后一条规则调整后的url
	 */
	public function realUri();
}

class UxApacheServerVars implements IUxServerVars
{
	public function __construct($server_type)
	{}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		return $_SERVER['PHP_SELF'];
	}
}

class UxNginxServerVars implements IUxServerVars
{
	public function __construct($server_type){}
	public function requestUri()
	{
		$re = "";
		if(isset($_SERVER['REQUEST_URI']))
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		return $re;
	}
	public function realUri()
	{
		$re = "";
		if(isset($_SERVER['DOCUMENT_URI']) )
		{
			$re = $_SERVER['DOCUMENT_URI'];
		}
		elseif( isset($_SERVER['REQUEST_URI']) )
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		return $re;
	}
}

class UxIisServerVars implements IUxServerVars
{
	public function __construct($server_type)
	{}

	public function requestUri()
	{
		$re = "";
		if(isset($_SERVER['REQUEST_URI']))
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		elseif( isset($_SERVER['HTTP_X_REWRITE_URL']) )
		{
			//不取HTTP_X_REWRITE_URL
			$re = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		elseif(isset($_SERVER["SCRIPT_NAME"] ) && isset($_SERVER['QUERY_STRING']) )
		{
			$re = $_SERVER["SCRIPT_NAME"] .'?'. $_SERVER['QUERY_STRING'];
		}
		return $re;
	}

	public function realUri()
	{
		$re= "";
		if( isset($_SERVER['HTTP_X_REWRITE_URL'])  )
		{
			$re = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : $_SERVER['HTTP_X_REWRITE_URL'];
		}
		elseif(isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != "" )
		{
			$re = $_SERVER['PATH_INFO'];
		}
		elseif(isset($_SERVER["SCRIPT_NAME"] ) && isset($_SERVER['QUERY_STRING']) )
		{
			$re = $_SERVER["SCRIPT_NAME"] .'?'. $_SERVER['QUERY_STRING'];
		}
		return $re;
	}

}