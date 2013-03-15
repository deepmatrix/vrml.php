<?php

class Controller implements IWindController {
    /**
     * 
     * @var string 默认视图所在的文件夹
     */
    protected $viewPath = 'view';
    
    protected $viewSuffix = '.php';


    private $_actionId = '';
    

    /**
     * 执行动作
     * 
     * @param string $actionId
     * @throws WindException
     */
    public function doAction($actionId) {
        if(!$actionId || !is_string($actionId))
            return false;
        
        if(!method_exists($this, $actionId))
            throw new WindException($this->getId() . '.' . $actionId . ' not defined.');
        
        $this->_actionId = $actionId;

        call_user_func(array($this, $actionId));
    }
    
    /**
     * 获取控制器id
     * @return string
     */
    public function getId(){
        return Wind::app()->getControllerId();
    }
    
    /**
     * 渲染视图
     * 视图文件存放到存放在view目录下，子模块的视图则放到其子目录下
     * 
     * 每个控制器下的视图文件放到一个文件夹下，如'main/index'即放到view/main/index.php
     * 
     * @param string $view 指定视图文件
     * @param array $data 传到到视图中的数据
     */
    protected function render($view, $data = null){
        global $_G;
        //先将数据打散成全局变量
        if(is_array($data)){
            foreach ($data as $key => $value) {
                $$key = $value;
            }
        }
        include $this->_templatEx($view);
    }
    
    /**
     * 优行扩展的模板调用函数，直接将模板文件放在对应模块的tpl目录下
     * 
     * @param string $file
     * @param string $tpldir
     * @return string
     */
    private function _templatEx($file, $tpldir = '') {
        $tpldir = $this->getViewPath() . DS . $this->getId();
        return template($file, str_replace('/', '_', $tpldir), $tpldir);
    }
    
    protected function getViewPath(){
        return basename(Wind::app()->getBasePath()) . DS . $this->viewPath;
    }
    
    public function __get($name) {
        global $_G;
        
        if(!isset($_G[$name]))
            throw new WindException("Variables \$$name not defined in \$_G");
        
        return $_G[$name];
    }
}