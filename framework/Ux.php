<?php
//内核路径
define('UX_PATH', dirname(__file__) . DIRECTORY_SEPARATOR);

/**
 * @brief 引用内核入口文件
 * @author webning
 * @date 2010-12-02
 * @version 0.6
 */

class Ux {

    /**
     * @brief 当前应用的对象
     */
    public static $app;

    /**
     * @brief 控制器所在位置
     */
    public static $_classes = array('application.controllers.*');

    public static $enableIncludePath=true;

    private static $classMap=array();
    private static $_aliases=array('system' => UX_PATH); // alias => path
    private static $_imports=array();	// alias => class name or directory
    private static $_includePaths; // list of include paths

    /**
     * @brief 创建Application应用
     * @param string $className
     * @param array $config
     * @return object Application对象
     */
    public static function createApp($className, $config) {
        $app = new $className($config);
        return $app;
    }

    /**
     * @brief 创建WebApplication应用
     * @param array $config
     * @return object Application对象
     */
    public static function createWebApp($config = null) {
        self::$app = self::createApp('UxWebApplication', $config);
        return self::$app;
    }

    /**
     * @brief 实现系统类的自动加载
     * @param String $className 类名称
     * @return bool true
     */
    public static function autoload($className) {
        if (isset(self::$_coreClasses[$className])) {
            include(UX_PATH . self::$_coreClasses[$className]);
        } else {
            if (!preg_match('|^\w+$|', $className)) {
                return true;
            }
            foreach (self::$_includePaths as $classPath) {
                $filePath = $classPath.DIRECTORY_SEPARATOR . $className . '.php';
                if (is_file($filePath)) {
                    include($filePath);
                    return true;
                }
            }
        }
        return true;
    }

    /**
     * @brief 用户自定义类的注册入口
     * @param array $classes 如:array('system.net.load.*','system.net.ftp.*');
     */
    public static function setClasses($classes) {
        if (is_string($classes))
            self::import($classes);
        if (is_array($classes)){
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
     * @throws UxException 
     */
    public static function import($alias,$forceInclude=false)
    {
            if(isset(self::$_imports[$alias]))  // previously imported
                    return self::$_imports[$alias];

            if(class_exists($alias,false) || interface_exists($alias,false))
                    return self::$_imports[$alias]=$alias;

            if(($pos=strrpos($alias,'\\'))!==false) // a class name in PHP 5.3 namespace format
            {
                    $namespace=str_replace('\\','.',ltrim(substr($alias,0,$pos),'\\'));
                    if(($path=self::getPathOfAlias($namespace))!==false)
                    {
                            $classFile=$path.DIRECTORY_SEPARATOR.substr($alias,$pos+1).'.php';
                            if($forceInclude)
                            {
                                    if(is_file($classFile))
                                            require($classFile);
                                    else
                                            throw new UxException('Alias "'.$alias.'" is invalid. Make sure it points to an existing PHP file and the file is readable.');
                                    self::$_imports[$alias]=$alias;
                            }
                            else
                                    self::$classMap[$alias]=$classFile;
                            return $alias;
                    }
                    else
                            throw new UxException('Alias "'.$alias.'" is invalid. Make sure it points to an existing directory.');
            }

            if(($pos=strrpos($alias,'.'))===false)  // a simple class name
            {
                    if($forceInclude && self::autoload($alias))
                            self::$_imports[$alias]=$alias;
                    return $alias;
            }

            $className=(string)substr($alias,$pos+1);
            $isClass=$className!=='*';

            if($isClass && (class_exists($className,false) || interface_exists($className,false)))
                    return self::$_imports[$alias]=$className;

            if(($path=self::getPathOfAlias($alias))!==false)
            {
                    if($isClass)
                    {
                            if($forceInclude)
                            {
                                    if(is_file($path.'.php'))
                                            require($path.'.php');
                                    else
                                            throw new UxException('Alias "'.$alias.'" is invalid. Make sure it points to an existing PHP file and the file is readable.');
                                    self::$_imports[$alias]=$className;
                            }
                            else
                                    self::$classMap[$className]=$path.'.php';
                            return $className;
                    }
                    else  // a directory
                    {
                            if(self::$_includePaths===null)
                            {
                                    self::$_includePaths=array_unique(explode(PATH_SEPARATOR,get_include_path()));
                                    if(($pos=array_search('.',self::$_includePaths,true))!==false)
                                            unset(self::$_includePaths[$pos]);
                            }

                            array_unshift(self::$_includePaths,$path);

                            if(self::$enableIncludePath && set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR,self::$_includePaths))===false)
                                    self::$enableIncludePath=false;

                            return self::$_imports[$alias]=$path;
                    }
            }
            else
                    throw new UxException('Alias "'.$alias.'" is invalid. Make sure it points to an existing directory or file.');
    }

    /**
     * 将别名转换为文件路径
     * 注意，该方法不检测文件路径是否存在，仅检测根别名是否可用
     * @param string $alias alias (如system.web.Controller)
     * @return mixed 
     */
    public static function getPathOfAlias($alias)
    {
            if(isset(self::$_aliases[$alias]))
                    return self::$_aliases[$alias];
            else if(($pos=strpos($alias,'.'))!==false)
            {
                    $rootAlias=substr($alias,0,$pos);
                    if(isset(self::$_aliases[$rootAlias]))
                            return self::$_aliases[$alias]=rtrim(self::$_aliases[$rootAlias].DIRECTORY_SEPARATOR.str_replace('.',DIRECTORY_SEPARATOR,substr($alias,$pos+1)),'*'.DIRECTORY_SEPARATOR);
                    /*else if(self::$_app instanceof UxWebApplication)
                    {
                            if(self::$_app->findModule($rootAlias)!==null)
                                    return self::getPathOfAlias($alias);
                    }*/
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
    public static function setPathOfAlias($alias,$path)
    {
            if(empty($path))
                    unset(self::$_aliases[$alias]);
            else
                    self::$_aliases[$alias]=rtrim($path,'\\/');
    }

    //系统内核所有类文件注册信息
    public static $_coreClasses = array(
        //core
        'UxObject' => 'core/UxObject.php',
        'UxAbstractApplication' => 'core/UxAbstractApplication.php',
        'UxException' => 'core/UxException.php',
        'IUxInterceptor' => 'core/IUxInterceptor.php',
        'UxInterceptor' => 'core/UxInterceptor.php',
        'UxComponent' => 'core/UxComponent.php',
        'UxConfigure' => 'core/UxConfigure.php',
        //util
        'UxFileHelper' => 'util/UxFileHelper.php',
        'UxStringUtil' => 'util/UxStringUtil.php',
        'UxXmlUtil' => 'util/UxXmlUtil.php',
        'UxTimeHelper' => 'util/UxTimeHelper.php',
        'UxImageHelper' => 'util/UxImageHelper.php',
        'UxFileUploadAgent' => 'util/UxFileUploadAgent.php',
        'UxHashHelper' => 'util/UxHashHelper.php',
        'UxEnryptor' => 'util/UxEnryptor.php',
        'UxSafeUtil' => 'util/UxSafeUtil.php',
        'UxCaptcha' => 'util/UxCaptcha.php',
        'UxCommonValidateHelper' => 'util/UxCommonValidateHelper.php',
        'UxLangUtil' => 'util/UxLangUtil.php',
        'UxSmtpHelper' => 'util/UxSmtpHelper.php',
        'JSON' => 'vendor/JSON.php',
        //url
        'UxRouter' => 'web/url/UxRouter.php',
        'UxServerVars' => 'web/url/UxServerVars.php',
        'IUxServerVars' => 'web/url/adapter/IUxServerVars.php',
        'UxApacheServerVars' => 'web/url/adapter/UxApacheServerVars.php',
        'UxIisServerVars' => 'web/url/adapter/UxIisServerVars.php',
        'UxNginxServerVars' => 'web/url/adapter/UxNginxServerVars.php',
        //http
        'UxHttpServer' => 'web/http/UxHttpServer.php',
        'UxHttpClient' => 'web/http/UxHttpClient.php',
        'UxHttpRequest' => 'web/http/UxHttpRequest.php',
        'UxHttpCookie' => 'web/http/UxHttpCookie.php',
        'UxHttpSession' => 'web/http/UxHttpSession.php',
        'UxHttpResponse' => 'web/http/UxHttpResponse.php',
        //web
        'UxWebApplication' => 'web/UxWebApplication.php',
        'UxHttpException' => 'web/UxHttpException.php',
        'UxController' => 'web/UxController.php',
        'UxClientScript' => 'web/UxClientScript.php',
        'UxAbstractAction' => 'web/action/UxAbstractAction.php',
        'UxInlineAction' => 'web/action/UxInlineAction.php',
        'UxViewAction' => 'web/action/UxViewAction.php',
        'UxCurdAction' => 'web/action/UxCurdAction.php',
        'UxViewTagParser' => 'web/view/UxViewTagParser.php',
        //log
        'UxLogger' => 'log/UxLogger.php',
        'IUxLogger' => 'log/adapter/IUxLogger.php',
        'UxFileLogger' => 'log/adapter/UxFileLogger.php',
        'UxDbLogger' => 'log/adapter/UxDbLogger.php',
        //cache
        'UxCache' => 'cache/UxCache.php',
        'IUxCache' => 'cache/IUxCache.php',
        'UxFileCacheAdapter' => 'cache/adapter/UxFileCacheAdapter.php',
        'UxMemCacheAdapter' => 'cache/adapter/UxMemCacheAdapter.php',
        'UxApcCacheAdapter' => 'cache/adapter/UxApcCacheAdapter.php',
        'UxEacceleratorCacheAdapter' => 'cache/adapter/UxEacceleratorCacheAdapter.php',
        'UxDbCacheAdapter' => 'cache/adapter/UxDbCacheAdapter.php',
        'UxXcacheCacheAdapter' => 'cache/adapter/UxXcacheCacheAdapter.php',
        'UxCacheSession' => 'cache/UxCacheSession.php',
        //db
        'UxDbModel' => 'db/UxDbModel.php',
        'UxDbManager' => 'db/UxDbManager.php',
        'UxAbstractDbDriver' => 'db/driver/UxAbstractDbDriver.php',
        'UxMysqlDbDriver' => 'db/driver/UxMysqlDbDriver.php',
        'UxPagingHelper' => 'db/UxPagingHelper.php',
        'UxQueryBuilder' => 'db/UxQueryBuilder.php',
        'UxDb' => 'db/UxDb.php',
        'UxTodel' => 'db/UxTodel.php',
        'UxDbMysql' => 'db/driver/UxDbMysql.php',
    );

}

/**
 * @brief 实现系统内容所有类的自动加载
 * @param String $className
 */
function __autoload($className) {
    Ux::autoload($className);
}
