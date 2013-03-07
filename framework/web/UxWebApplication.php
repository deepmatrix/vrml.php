<?php
/**
 * @brief web应用类
 * @author webning
 * @date 2010-12-10
 * @version 0.6
 * @note
 */
class UxWebApplication extends UxAbstractApplication {
    const CONTROLLER_SUFFIX = 'Controller';
    
    public $controller;
    
    private $_defaultController = 'site';

    /**
     * @brief 请求执行方法，是application执行的入口方法
     */
    public function execRequest() {
        UxRouter::beginUrl();
        UxInterceptor::run("onCreateController");
        $this->controller = $this->createController();
        $this->controller->run();
        UxInterceptor::run("onFinishController");
    }

    /**
     * @brief 创建当前的Controller对象
     * @return object Controller对象
     */
    public function createController() {
        $ctrl_id = UxRouter::getInfo("controller");
        if($ctrl_id === null) {
            if(isset($this->config['defaultController']))
                $ctrl_id = $this->config['defaultController'];
            if(!$ctrl_id)
                $ctrl_id = $this->_defaultController;
        }
        
        $controller = ucwords($ctrl_id) . self::CONTROLLER_SUFFIX;

        if (!class_exists($controller))//控制器不存在，则无法被其他类所获取
            throw new UxHttpException('Controller ' . $ctrl_id . ' not existed.', 404);
        
        $this->controller = new $controller($this, $ctrl_id);
        return $this->controller;
    }

    /**
     * @brief 取得当前的Controller
     * @return object Controller对象
     */
    public function getController() {
        return $this->controller;
    }

}