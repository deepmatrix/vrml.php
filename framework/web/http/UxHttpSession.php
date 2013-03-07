<?php

/**
 * @brief session机制处理类
 * @author webning
 * @date 2011-02-24
 * @version 0.6
 */
//开启session
//session_start();

class UxHttpSession {

    //session前缀
    private static $pre = 'ux_';
    //安全级别
    private static $level = 'normal';
    //引擎
    private static $onCache = false;

    /**
     * Variable that defines if session started
     *
     * @var boolean
     */
    protected static $_sessionStarted = false;

    /**
     * Variable that defines if session destroyed
     *
     * @var boolean
     */
    protected $_sessionDestroyed = false;
    
    private static $_cacheObj = null;

    //获取配置的前缀
    private static function getPre() {
        if (isset(Ux::$app->config['safePre'])) {
            return Ux::$app->config['safePre'];
        } else {
            return self::$pre;
        }
    }

    //获取当前的安全级别
    private static function getLevel() {
        if (isset(Ux::$app->config['safeLevel'])) {
            return Ux::$app->config['safeLevel'];
        } else {
            return self::$level;
        }
    }

    /**
     * @brief 设置session数据
     * @param string $name 字段名
     * @param mixed $value 对应字段值
     */
    public static function set($name, $value = '') {
        self::_start();
        self::$pre = self::getPre();
        if (self::checkSafe() == -1)
            $_SESSION[self::$pre . 'safecode'] = self::sessionId();
        $_SESSION[self::$pre . $name] = $value;
    }

    /**
     * @brief 获取session数据
     * @param string $name 字段名
     * @return mixed 对应字段值
     */
    public static function get($name) {
        self::_start();
        self::$pre = self::getPre();
        $is_checked = self::checkSafe();

        if ($is_checked == 1) {
            return isset($_SESSION[self::$pre . $name]) ? $_SESSION[self::$pre . $name] : null;
        } else if ($is_checked == 0) {
            self::clear(self::$pre . 'safecode');
        }
        return null;
    }

    /**
     * @brief 清空某一个Session
     * @param mixed $name 字段名
     */
    public static function clear($name) {
        self::_start();
        self::$pre = self::getPre();
        unset($_SESSION[self::$pre . $name]);
    }

    /**
     * @brief 清空所有Session
     */
    public static function clearAll() {
        self::_start();
        return session_destroy();
    }

    /**
     * @brief Session的安全验证
     * @return int 1:通过验证,0:未通过验证
     */
    private static function checkSafe() {
        self::_start();
        self::$pre = self::getPre();
        if (isset($_SESSION[self::$pre . 'safecode'])) {
            if ($_SESSION[self::$pre . 'safecode'] == self::sessionId()) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    /**
     * @brief 得到session安全码
     * @return String  session安全码
     */
    private static function sessionId() {
        $level = self::getLevel();
        if ($level == 'none') {
            return '';
        } else if ($level == 'normal') {
            return md5(UxHttpClient::getIP());
        }
        return md5(UxHttpClient::getIP() . $_SERVER["HTTP_USER_AGENT"]);
    }

    /**
     * Start session
     *
     * @return void
     */
    private static function _start() {
        // check if session is started if it is return
        if (self::$_sessionStarted) {
            return;
        }
        self::$onCache = isset(Ux::$app->config['session']['onCache']) 
                    ? Ux::$app->config['session']['onCache'] 
                    : self::$onCache;
        if(self::$onCache){
            UxCacheSession::installOnCache(Ux::$app->config['cache']['type'], self::$pre);
        } //else {
            session_start();
        //}
        self::$_sessionStarted = true;
    }

    /**
     * Keeping a session open for a long operation causes subsequent requests from
     * a user of that session having to wait for session's file to be freed.
     * Therefore if you do not need the session anymore you can call this function
     * to store the session and close the lock it has
     */
    public function stop() {
        if (self::$_sessionStarted) {
            session_write_close();
            self::$_sessionStarted = false;
        }
    }

}

?>
