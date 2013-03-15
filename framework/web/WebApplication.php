<?php

/**
 * 整合Discuz Core与Application两个类的功能，同时增加的特性：
 * 1) 加入路由解析的逻辑，形成MVC代码模式；
 * 2) MVC代码结构：
 * <pre>
 *      app_path/
 *           index.php
 *           framework/
 *           source/
 *               conf/
 *               controller/
 *               model/
 *               view/
 *               class/
 *               function/
 *               module/
 *               language/
 * </pre>
 *                      
 * 3) 支持到Module级别的URL解析与控制器调度，形如 /app_path/index.php/member-user/login
 */
class WebApplication extends discuz_application {

    /**
     * 控制器后缀，可以重载来改变
     * @var string 
     */
    protected $ctrlSuffix = 'Controller';
    
    /**
     * 默认控制器名称
     * @var string
     */
    protected $defaultCtrlName = 'site';
    
    /**
     * 默认动作名称
     * @var string
     */
    protected $defaultActionName = 'index';
    
    private $_controllerId = '';


    /**
     * 当前控制器对象
     * @var Controller 
     */
    protected $ctrlObj = null;
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * 应用程序正式运行
     */
    public function run(){
        //必须在init初始化之前，先进行Url参数解析
        Router::parseUrl();
        
        if(defined('DISCUZ_CORE_DEBUG') && DISCUZ_CORE_DEBUG) {
            set_error_handler(array(__CLASS__, 'handleError'));
            set_exception_handler(array(__CLASS__, 'handleException'));
            register_shutdown_function(array(__CLASS__, 'handleShutdown'));
        }
        //discuz_application的init过程
        $this->init();
        //分派请求
        $this->dispatch();
    }
    
    /**
     * 根据请求分派响应的控制器与动作
     */
    protected function dispatch(){
        //设置核心的路径别名
        Wind::setPathOfAlias('application',$this->getBasePath());
        Wind::setClasses(Wind::$_classes);
        /*if (isset($this->_conf->classes)) {
            Ux::setClasses($this->_conf->classes);
        }*/
        
        $this->createController();
    }
    
    /**
     * 创建控制器并运行，支持动作
     */
    protected function createController(){
        $controller_class_name = ucfirst($this->getControllerId()) . $this->ctrlSuffix;
        if(!class_exists($controller_class_name)){
            throw new WindException($controller_class_name . ' not found');
        }
        
        $this->ctrlObj = new $controller_class_name();

        $action_name = $this->_getActionId();

        $this->ctrlObj->doAction($action_name);
    }
    
    /**
     * 获取控制器ID
     */
    public function getControllerId(){
        if(!$this->_controllerId){
            $controller_id = getgpc(Router::URL_KEY_CTRL);
            if(null == $controller_id)
                $controller_id = $this->defaultCtrlName;       
            
            $this->_controllerId = $controller_id;
        }
        
        return $this->_controllerId;
    }

    /**
     * 获取动作id
     * @return string
     */
    private function _getActionId(){
        $action_id = getgpc(Router::URL_KEY_ACTION);

        if(null == $action_id)
            $action_id = $this->defaultActionName;
        
        return $action_id;
    }
    
    /**
     * 供服务器端程序使用的应用根路径
     * @return string
     */
    public function getBasePath(){
        return defined('APP_PATH') ? APP_PATH : dirname(WIND_PATH) . '/';
    }


    public static function handleException($exception) {
		discuz_error::exception_error($exception);
	}

    /**
     * 错误日志，处理错误时，只记录，不显示
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
	public static function handleError($errno, $errstr, $errfile, $errline) {
		if($errno & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($errstr, false, true, true);
		}
	}

	public static function handleShutdown() {
		if(($error = error_get_last()) && $error['type'] & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($error['message'], false, true, true);
		}
	}
}