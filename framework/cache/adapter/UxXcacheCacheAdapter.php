<?php
/**
 *
 * @package cache
 * @since 1.0
 */


class UxXcacheCacheAdapter implements IUxCache {

    /**
 * 默认缓存时间
     *
     * 如果设置为 0 表示缓存总是失效，设置为 null 则表示不检查缓存有效期
     * @var integer
     */
    protected $_default_life_time = 3600;


    /**
     * 构造函数
     *
     * @access public
     * @return boolean
     */
    public function __construct() {

        //分析xcache扩展模块
        if (!extension_loaded('xcache')) {
            throw new UxHttpException('The xcache extension must be loaded before use!');
        }

        return true;
    }

    /**
     * 写入缓存
     *
     * @param string $key
     * @param mixted $value
     * @param integer $expire
     * @return boolean $expire
     */
     public function set($key, $value, $expire = null) {

         if (is_null($expire)) {
             $expire = $this->_options['life_time'];
         }

         return xcache_set($key, $value, $expire);
     }

     /**
      * 读取缓存，失败或缓存撒失效时返回 false
      *
      * @param string $id
      * @return mixted
      */
     public function get($id) {

         if (xcache_isset($id)) {
             return xcache_get($id);
         }

         return false;
     }

    /**
     * 删除指定的缓存
     *
     * @param string $id
     * @return void
     */
     public function del($id) {

         return xcache_unset($key);
     }
     
     public function flush() {
         ;
     }
}