<?php

/**
 * @brief 视图动作
 * @author Ben
 * @date 2010-12-16
 * @version 0.6
 */
class UxViewAction extends UxAbstractAction {

    public $defaultView = 'index';
    public $viewPath;
    public $view;
    public $basePath;

    /**
     * @return
     * @brief 执行action
     */
    function run() {
        UxInterceptor::run("onCreateView");
        $controller = $this->getController();
        $this->resolveView($this->getView());
        $data = null;

        if (!$this->view || !file_exists($this->view . $controller->extend)) {
            $path = $this->view . $controller->extend;
            $path = UxException::pathFilter($path);
            $data = array(
                'title' => 'HTTP 404',
                'heading' => 'not found',
                'message' => "not found this view page($path)",
            );
            throw new UxException($data['message'], 404);
        } else {
            $controller->render($this->view, $data);
        }
        UxInterceptor::run("onFinishView");
    }

    /**
     * @return string 获取视图
     * @brief 获取视图
     */
    function getView() {
        if ($this->viewPath === null) {
            $action = $this->getId();
            if (!empty($action))
                $this->viewPath = $action;
            else
                $this->viewPath = $this->defaultView;
        }
        return $this->viewPath;
    }

    /**
     * @param string 视图名称
     * @return bool
     * @brief 解析视图路径
     */
    function resolveView($viewPath) {
        if (preg_match('/^\w[\w\-]*$/', $viewPath)) {
            //分割模板目录的层次
            $view = strtr($viewPath, '-', '/');
            $this->basePath = $this->getController()->getViewFile($view);

            if (!empty($this->basePath)) {
                $this->view = $this->basePath;
                return;
            }
        } else {
            $viewPath = UxException::pathFilter($viewPath);
            throw new UxHttpException("the view filename({$viewPath}) is wrong", 403);
        }
    }

}
