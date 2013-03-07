<?php
/**
 * @since 1.0
 */

class UxDbCacheAdapter implements IUxCache {

    /**
     * 缓存目录
     *
     * @var string
     */
     public $cache_dir;

     /**
      * 缓存有效周期
      *
      * @var integer
      */
     public $lifetime;


     /**
      * 构造函数,初始化变量
      *
      * @access public
      * @return boolean
      */
     public function __construct() {

         //设置缓存目录
         $this->cache_dir = CACHE_DIR . 'data' . DIRECTORY_SEPARATOR;
         //默认缓存周期为24小时
         $this->lifetime  = 86400;

         return true;
     }

    /**
     * 分析缓存文件的路径.
     *
     * @param string $file_name
     * @return string
     */
    protected function parse_cache_file($file_name) {

        return $this->cache_dir . $file_name . '_cache.db.php';
    }

    /**
     * 设置缓存周期.
     *
     * @param integer $life_time
     * @return $this
     */
    public function lifetime($life_time) {

        if (!$life_time) {
            return false;
        }

        $this->lifetime = (int)$life_time;

        return $this;
    }

    /**
     * 缓存分析,判断是否开启缓存重写开关
     *
     * @param string $cache_file
     * @return boolean
     */
    protected function parse_cache($cache_file) {

        if (!is_file($cache_file)) {
            return true;
        }

        return ($_SERVER['REQUEST_TIME'] - filemtime($cache_file) > $this->lifetime) ? true : false;
    }

    /**
     * 创建缓存文件
     *
     * @param string $cache_file
     * @param array  $cache_content
     * @return void
     */
    protected function create_cache($cache_file, $cache_content) {

        //分析缓存文件内容
        $contents = "<?php\r\nif (!defined('IN_DOIT')) exit();\r\nreturn ";
        $contents .= var_export($cache_content, true) . ';';

        //当缓存目录不存在时,自行创建目录。
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0777, true);
        } else if (!is_writeable($this->cache_dir)) {
            chmod($this->cache_dir, 0777);
        }

        //将缓存内容写入文件
        file_put_contents($cache_file, $contents, LOCK_EX);
    }

    /**
     * 加载缓存文件内容
     *
     * 本类中的主函数,当第二参数为空时,则默认数据表内全部数据表字段的数据
     * @param string $table_name
     * @param array  $filter
     * @return array
     */
    public function load($table_name, $filter = array()) {

        //参数分析
        if (empty($table_name)) {
            return false;
        }

        //分析缓存文件名
        $cache_file  = $this->parse_cache_file($table_name);

        //缓存文件内容需要更新
        if ($this->parse_cache($cache_file)) {
            //获取数据表内容
            $model   = Controller::model($table_name);
            $data    = $model->findAll();

            //分析当有数据表字段过滤时
            $cache_content = array();
            //当数据表有数据时
            if ($data) {
                 if ($filter && is_array($filter)) {
                    foreach ($data as $key=>$value) {
                        foreach ($filter as $column_name) {
                            $cache_content[$key][$column_name] = $value[$column_name];
                        }
                    }
                } else {
                    $cache_content = $data;
                }
            }

            //清空不必要的内容占用
            unset($data);

            //生成缓存文件
            $this->create_cache($cache_file, $cache_content);

            return $cache_content;
        }

        return include $cache_file;
    }

    /**
     * 加载设置信息缓存文件内容
     *
     * @access public
     * @param string $table_name
     * @param string $key
     * @param string $value
     * @return array
     */
    public function load_config($table_name, $key, $value) {

        //参数分析
        if (!$table_name || !$key || !$value) {
            return false;
        }

        //分析缓存文件名
        $cache_file     = $this->parse_cache_file($table_name);

        if ($this->parse_cache($cache_file)) {
            //获取数据表内容
            $model   = Controller::model($table_name);
            $data    = $model->findAll();

            //分析当有数据表字段过滤时
            $cache_content = array();
            //当数据表有数据时
            if ($data) {
                foreach ($data as $lines) {
                    $cache_content[$lines[$key]] = $lines[$value];
                }
            }

            //清空不必要的内容占用
            unset($data);

            //生成缓存文件
            $this->create_cache($cache_file, $cache_content);

            return $cache_content;
        }

        return include $cache_file;
    }

    /**
     * 删除缓存文件
     *
     * @param string $file_name
     * @return boolean
     */
    public function del($file_name) {

        //参数分析
        if (!$file_name) {
            return false;
        }

        //分析缓存文件名
        $cache_file     = $this->parse_cache_file($file_name);

        return is_file($cache_file) ? unlink($cache_file) : true;
    }
    
    public function flush() {
        ;
    }
}