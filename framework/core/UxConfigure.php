<?php

/**
 * 配置读取、操作等
 *
 * @author jim
 * @version $Id$
 */
class UxConfigure {
    private $_p = array();

    public function __construct($configFile) {
        if (!is_string($configFile) || !is_file($configFile))
            throw new UxHttpException('未指定配置文件！', 500);
        
        $config = require($configFile);
        
        if (!is_array($config))
            $this->_p = array();
        else
            $this->_p = $config;
    }
    
	/**
	 * @brief __get函数
	 * @param string $name
	 */
	public function __get($name) {
		return (isset($this->_p[$name]) ? $this->_p[$name] : null);
	}
    
    public function __isset($name) {
		return (isset($this->_p[$name]) ? true : false);
    }

        public function append($name, $value){
        if(!isset($this->_p[$name])){
            $this->_p[$name] = $value;
        }
    }
    
    /**
     * 获取全部配置数据，兼容老的配置访问方式
     * @return array 配置数据
     */
    public function getAllConf(){
        return $this->_p;
    }
}

?>
