<?php

/**
 * @brief 数据库工厂类
 * @author chendeshan
 * @date 2010-12-3
 * @version 0.6
 */
class UxDbManager {

    //数据库对象
    public static $instance = NULL;
    //默认的数据库连接方式
    private static $defaultDB = 'mysql';

    /**
     * @brief 创建对象
     * @return object 数据库对象
     */
    public static function getDbo() {
        //单例模式
        if (self::$instance != NULL && is_object(self::$instance)) {
            return self::$instance;
        }

        //获取数据库配置信息
        if (!isset(Ux::$app->config['db']) || Ux::$app->config['db'] == null) {
            throw new UxHttpException('can not find DB info in config.inc.php', 1000);
        }
        $dbinfo = Ux::$app->config['db'];

        //数据库类型
        $dbType = isset($dbinfo['type']) ? $dbinfo['type'] : self::$defaultDB;

        switch ($dbType) {
            default:
                return self::$instance = new UxMysqlDbDriver;
                break;
        }
    }

    private function __construct() {
        
    }

    private function __clone() {
        
    }

}
