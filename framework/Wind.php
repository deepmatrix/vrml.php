<?php

/* 框架版本信息 */
define('WIND_VERSION', '2.3.0');
/* 路径相关配置信息 */
define('WIND_PATH', dirname(__FILE__));
!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
/*
 * 二进制:十进制 模式描述 00: 0 关闭 01: 1 window 10: 2 log 11: 3 window|log
 */
!defined('WIND_DEBUG') && define('WIND_DEBUG', 0);

/**
 *
 * @author Qiong Wu <papa0924@gmail.com> 2011-10-9
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id: Wind.php 3859 2012-12-18 09:25:51Z yishuo $
 */
class Wind {

    private static $_extensions = '.php';

    /**
     * @brief 控制器所在位置
     */
    public static $_classes = array('application.controller.*', 'application.model.*');
    public static $enableIncludePath = true;
    private static $classMap = array();
    private static $_aliases = array('system' => WIND_PATH); // alias => path
    private static $_imports = array(); // alias => class name or directory
    private static $_includePaths = array(); // list of include paths
    private static $_coreClasses = array();
    private static $_isAutoLoad = true;
    
    private static $_app = null;
    private static $_tables;
    private static $_memory;

    /**
     * 获取应用对象
     * 
     * @param string $config
     * @return WebApplication 应用
     */
    public static function app($config = '') {
        if (!is_object(self::$_app)) {
            self::$_app = new WebApplication();
        }
        return self::$_app;
    }

    /**
     * 获取系统组件
     * 
     * @param string $alias        
     * @param array $args        
     * @return Ambigous <NULL, multitype:, WindClassProxy, WindModule, unknown,
     *         mixed>
     */
    public static function getComponent($alias, $args = array()) {
        return WindFactory::_getInstance()->getInstance($alias, $args);
    }

    /**
     * 注册系统组建
     * <code>
     * 对象方式注册:
     * $converter = new WindGeneralConverter();
     * Wind::registeComponent($converter,'windConverter',singleton);
     * 定义方式注册:
     * Wind::registeComponent(array('path' =>
     * 'WIND:convert.WindGeneralConverter', 'scope' => 'singleton'),
     * 'windConverter');</code>
     * 
     * @param object|array $componentInstance        
     * @param string $componentName        
     * @param string $scope        
     * @return boolean
     */
    public static function registeComponent($componentInstance, $componentName, $scope = 'application') {
        if (is_array($componentInstance)) {
            isset($componentInstance['scope']) || $componentInstance['scope'] = $scope;
            WindFactory::_getInstance()->loadClassDefinitions(
                    array($componentName => $componentInstance));
        } elseif (is_object($componentInstance)) {
            WindFactory::_getInstance()->registInstance($componentInstance, $componentName, $scope);
        }
        else
            throw new WindException('registe component fail, array or object is required', WindException::ERROR_PARAMETER_TYPE_ERROR);
    }

    /**
     * @brief 实现系统类的自动加载
     * @param String $className 类名称
     * @return bool true
     */
    public static function autoLoad($className) {
        if (isset(self::$_coreClasses[$className])) {
            include(WIND_PATH . DIRECTORY_SEPARATOR . self::$_coreClasses[$className] . self::$_extensions);
            return true;
        } else {
            if (!preg_match('|^\w+$|', $className)) {
                return FALSE;
            }
            foreach (self::$_includePaths as $classPath) {
                $filePath = $classPath . DIRECTORY_SEPARATOR . $className . self::$_extensions;
                if (is_file($filePath)) {
                    include($filePath);
                    return true;
                }
            }
        }

        if (defined('DISCUZ_ROOT')) {
            self::autoLoadDz($className);
        }
    }

    public static function autoLoadDz($class) {
        $class = strtolower($class);
        if (strpos($class, '_') !== false) {
            list($folder) = explode('_', $class);
            $file = 'class/' . $folder . DS . substr($class, strlen($folder) + 1);
        } else {
            $file = 'class/' . $class;
        }

        try {
            self::importDz($file);
            return true;
        } catch (Exception $exc) {
            $trace = $exc->getTrace();
            foreach ($trace as $log) {
                if (empty($log['class']) && $log['function'] == 'class_exists') {
                    return false;
                }
            }
            discuz_error::exception_error($exc);
        }
    }

    public static function importDz($name, $folder = '', $force = true) {
        $key = $folder . $name;
        if (!isset(self::$_imports[$key])) {
            $path = DISCUZ_ROOT . '/source/' . $folder;
            if (strpos($name, DS) !== false) {
                $pre = basename(dirname($name));
                $filename = dirname($name) . DS . $pre . '_' . basename($name) . self::$_extensions;
            } else {
                $filename = $name . self::$_extensions;
            }

            if (is_file($path . DS . $filename)) {
                self::$_imports[$key] = true;
                return include $path . DS . $filename;
            } elseif (!$force) {
                return false;
            } else {
                throw new Exception('Oops! System file lost: ' . $filename);
            }
        }
        return true;
    }

    public static function t($name) {
        $pluginid = null;
        if ($name[0] === '#') {
            list(, $pluginid, $name) = explode('#', $name);
        }
        $classname = 'table_' . $name;
        if (!isset(self::$_tables[$classname])) {

            if (!class_exists($classname, false)) {
                self::importDz(($pluginid ? 'plugin/' . $pluginid : 'class') . '/table/' . $name);
            }
            self::$_tables[$classname] = new $classname;
        }

        return self::$_tables[$classname];
    }

    public static function memory() {
        if (!self::$_memory) {
            self::$_memory = new discuz_memory();
            self::$_memory->init(self::app()->config['memory']);
        }
        return self::$_memory;
    }

    /**
     * 初始化框架
     */
    public static function init() {
        function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');

        if (!self::$_isAutoLoad)
            return;
        self::_loadBaseLib();

        if (function_exists('spl_autoload_register'))
            spl_autoload_register('Wind::autoLoad');
        else
            self::$_isAutoLoad = false;

    }

    /**
     * @brief 用户自定义类的注册入口
     * @param array $classes 如:array('system.net.load.*','system.net.ftp.*');
     */
    public static function setClasses($classes) {
        if (is_string($classes))
            self::import($classes);
        if (is_array($classes)) {
            foreach ($classes as $class) {
                self::import($class);
            }
        }
    }

    /**
     * 导入单个类或者整个目录
     *
     * 导入类类似于include相关类文件，导入目录则等同于将目录添加到PHP include路径
     *
     * 采用路径别名的方式书写，例如：
     * <ul>
     *   <li><code>application.components.GoogleMap</code>: 导入<code>GoogleMap</code> 类.</li>
     *   <li><code>application.components.*</code>: 导入<code>components</code> 目录.</li>
     * </ul>
     *
     * @param string $alias path alias to be imported
     * @param boolean $forceInclude 是否立刻包含该类文件，否则只有在类被真正使用时才被包含
     * @return string 
     * @throws WindException 
     */
    public static function import($alias, $forceInclude = false) {
        if (isset(self::$_imports[$alias]))  // previously imported
            return self::$_imports[$alias];

        if (class_exists($alias, false) || interface_exists($alias, false))
            return self::$_imports[$alias] = $alias;

        if (($pos = strrpos($alias, '\\')) !== false) { // a class name in PHP 5.3 namespace format
            $namespace = str_replace('\\', '.', ltrim(substr($alias, 0, $pos), '\\'));
            if (($path = self::getPathOfAlias($namespace)) !== false) {
                $classFile = $path . DIRECTORY_SEPARATOR . substr($alias, $pos + 1) . self::$_extensions;
                if ($forceInclude) {
                    if (is_file($classFile))
                        require($classFile);
                    else
                        throw new WindException('Alias "' . $alias . '" is invalid. Make sure it points to an existing PHP file and the file is readable.');
                    self::$_imports[$alias] = $alias;
                }
                else
                    self::$classMap[$alias] = $classFile;
                return $alias;
            }
            else
                throw new WindException('Alias "' . $alias . '" is invalid. Make sure it points to an existing directory.');
        }

        if (($pos = strrpos($alias, '.')) === false) {  // a simple class name
            if ($forceInclude && self::autoload($alias))
                self::$_imports[$alias] = $alias;
            return $alias;
        }

        $className = (string) substr($alias, $pos + 1);
        $isClass = $className !== '*';

        if ($isClass && (class_exists($className, false) || interface_exists($className, false)))
            return self::$_imports[$alias] = $className;

        if (($path = self::getPathOfAlias($alias)) !== false) {
            if ($isClass) {
                if ($forceInclude) {
                    if (is_file($path . self::$_extensions))
                        require($path . self::$_extensions);
                    else
                        throw new WindException('Alias "' . $alias . '" is invalid. Make sure it points to an existing PHP file and the file is readable.');
                    self::$_imports[$alias] = $className;
                }
                else
                    self::$classMap[$className] = $path . self::$_extensions;
                return $className;
            }
            else {  // a directory
                if (self::$_includePaths === null) {
                    self::$_includePaths = array_unique(explode(PATH_SEPARATOR, get_include_path()));
                    if (($pos = array_search('.', self::$_includePaths, true)) !== false)
                        unset(self::$_includePaths[$pos]);
                }

                array_unshift(self::$_includePaths, $path);

                if (self::$enableIncludePath && set_include_path('.' . PATH_SEPARATOR . implode(PATH_SEPARATOR, self::$_includePaths)) === false)
                    self::$enableIncludePath = false;

                return self::$_imports[$alias] = $path;
            }
        }
        else
            throw new WindException('Alias "' . $alias . '" is invalid. Make sure it points to an existing directory or file.');
    }

    /**
     * 将别名转换为文件路径
     * 注意，该方法不检测文件路径是否存在，仅检测根别名是否可用
     * @param string $alias alias (如system.web.Controller)
     * @return mixed 
     */
    public static function getPathOfAlias($alias) {
        if (isset(self::$_aliases[$alias]))
            return self::$_aliases[$alias];
        else if (($pos = strpos($alias, '.')) !== false) {
            $rootAlias = substr($alias, 0, $pos);
            if (isset(self::$_aliases[$rootAlias]))
                return self::$_aliases[$alias] = rtrim(self::$_aliases[$rootAlias] . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, substr($alias, $pos + 1)), '*' . DIRECTORY_SEPARATOR);
            /* else if(self::$_app instanceof UxWebApplication)
              {
              if(self::$_app->findModule($rootAlias)!==null)
              return self::getPathOfAlias($alias);
              } */
        }
        return false;
    }

    /**
     * Create a path alias.
     * Note, this method neither checks the existence of the path nor normalizes the path.
     * @param string $alias alias to the path
     * @param string $path the path corresponding to the alias. If this is null, the corresponding
     * path alias will be removed.
     */
    public static function setPathOfAlias($alias, $path) {
        if (empty($path))
            unset(self::$_aliases[$alias]);
        else
            self::$_aliases[$alias] = rtrim($path, '\\/');
    }

    /**
     * 加载核心层库函数
     * 
     * @return void
     */
    private static function _loadBaseLib() {
        self::$_coreClasses = array(
            //core
            //'IWindApplication' => 'core/IWindApplication',
            //'IWindFactory' => 'core/IWindFactory',
            'IWindController' => 'core/IWindController',
            //'IWindRequest' => 'core/IWindRequest',
            //'IWindResponse' => 'core/IWindResponse',
            'WindException' => 'core/WindException',
            //'WindFactory' => 'core/WindFactory',
            'WindUtility' => 'core/WindUtility',
            //'WindModule' => 'core/WindModule',
            //'WindHandlerInterceptor' => 'core/filter/WindHandlerInterceptor',
            //'WindHandlerInterceptorChain' => 'core/filter/WindHandlerInterceptorChain',
            //util
            //'WindUrlHelper' => 'utility/WindUrlHelper',
            //web
            'WebApplication' => 'web/WebApplication',
            'Router' => 'web/Router',
            'Controller' => 'web/Controller',
        );
    }

}

Wind::init();

class DB extends discuz_database {
    
}

class C extends Wind {
    
}