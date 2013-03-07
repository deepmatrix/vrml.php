<?php
/**
 * @brief 日志接口文件
 * @author webning
 * @date 2010-12-09
 * @version 0.6
 */

class UxLogger
{
    private static $log      = null;         //日志对象
    private static $logClass = array('file' => 'UxFileLogger' , 'db' => 'UxDbLogger');

    /**
     * @brief   生成日志处理对象，包换各种介质的日志处理对象,单例模式
     * @logType string $logType 日志类型
     * @return  object 日志对象
     */
    public static function getInstance($logType = 'file')
    {
        if(!$logType || !isset(Ux::$app->config['logger']['type']) || !isset(self::$logClass[$logType])){
            $logType = 'file';
        }
    	$className = self::$logClass[$logType];
    	if(!class_exists($className)) {
    		throw new UxException('the log class is not exists',403);
    	}
        
        if ('file' == $logType) {//file
            $params = !isset(Ux::$app->config['logger']['path']) 
                    ? 'backup/log' : Ux::$app->config['logger']['path'];
            
        } else if ('db' == $logType) {
            $params = !isset(Ux::$app->config['logger']['table']) 
                    ? 'system_log' : Ux::$app->config['logger']['table'];
        }
 
    	if(!self::$log instanceof $className)
    	{
    		self::$log = new $className($params);
    	}
    	return self::$log;
    }

    public function __construct(){}
    public function __clone(){}
}
?>
