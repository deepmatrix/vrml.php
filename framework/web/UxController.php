<?php
/**
 * @brief 控制器类,控制action动作,渲染页面
 * 
 * @author $Id: UxController.php 288 2012-11-30 04:11:36Z jimmy $
 * @package uxf.web
 */
class UxController extends UxComponent {

    public $theme = 'default';          //主题方案
    public $skin = 'default';          //风格方案
    public $layout = 'main';             //布局文件
    public $extend = '.php';            //模板扩展名
    public $defaultExecuteExt = '.php';             //默认编译后文件扩展名
    protected $accessRules = array();            //需要校验的action动作
    protected $lang = 'zh_sc';            //语言包方案
    protected $module = null;               //隶属于模块的对象
    protected $ctrlId = null;               //控制器ID标识符
    
    /**
     * 输出到视图的标题
     * @var string 
     */
    protected $title = null;               
    protected $defaultViewPath = 'views';            //默认视图目录
    protected $defaultLayoutPath = 'layouts';          //默认布局目录
    protected $defaultLangPath = 'language';         //默认语言目录
    protected $defaultSkinPath = 'skin';             //默认皮肤目录
    
    private $action;                                   //当前action对象
    private $CURD = array('del', 'add', 'update', 'get'); //系统CURD
    protected $defaultAction = 'index';                  //默认执行的action动作
    private $renderData = array();                  //渲染的数据
    private $_viewPath = '';

    /**
     * @brief 构造函数
     * @param string $ctrlId 控制器ID标识符
     * @param string $module 控制器所包含的模块
     */

    public function __construct($module, $controllerId) {
        $this->module = $module;
        $this->ctrlId = $controllerId;

        //初始化theme方案
        if (isset($this->module->config['theme']) && $this->module->config['theme'] != null) {
            $this->theme = $this->module->config['theme'];
        }

        //初始化skin方案
        if (isset($this->module->config['skin']) && $this->module->config['skin'] != null) {
            $this->skin = $this->module->config['skin'];
        }

        //初始化lang方案
        if (isset($this->module->config['lang']) && $this->module->config['lang'] != null) {
            $this->lang = $this->module->config['lang'];
        }
    }

    /**
     * @brief 生成验证码
     * @return image图像
     */
    public function getCaptcha() {
        //清空布局
        $this->layout = '';

        //配置参数
        $width = intval(UxHttpRequest::get('w')) == 0 ? 130 : UxHttpRequest::get('w');
        $height = intval(UxHttpRequest::get('h')) == 0 ? 45 : UxHttpRequest::get('h');
        $wordLength = intval(UxHttpRequest::get('l')) == 0 ? 5 : UxHttpRequest::get('l');
        $fontSize = intval(UxHttpRequest::get('s')) == 0 ? 25 : UxHttpRequest::get('s');

        //创建验证码
        $ValidateObj = new UxCaptcha();
        $ValidateObj->width = $width;
        $ValidateObj->height = $height;
        $ValidateObj->maxWordLength = $wordLength;
        $ValidateObj->minWordLength = $wordLength;
        $ValidateObj->fontSize = $fontSize;
        $ValidateObj->CreateImage($text);

        //设置验证码
        UxSafeUtil::set('UxCaptcha', $text);
    }

    /**
     * @brief 权限校验拦截
     * @param string $ownRight 用户的权限码
     * @return bool true:校验通过; false:校验未通过
     */
    public function checkRights($ownRight) {
        $actionId = $this->action->id;

        //是否需要权限校验 true:需要; false:不需要
        $isCheckRight = false;
        if (!$this->accessRules || $this->accessRules == 'all') {
            $isCheckRight = true;
        } else if (is_array($this->accessRules)) {
            if(isset($this->accessRules['deny']) && is_array($this->accessRules['deny'])){
                if(in_array($actionId, $this->accessRules['deny'])){
                    return false;
                } else {
                    $isCheckRight = true;
                }
                
            } elseif (isset($this->accessRules['allow']) && ($this->accessRules['allow'] == 'all')) {
                if(in_array($actionId, $this->accessRules['allow'])){
                    return true;
                } else {
                    $isCheckRight = true;
                }
                
            } else {
                $isCheckRight = true;
            }
        }

        //需要校验权限
        if ($isCheckRight == true) {
            if(!$ownRight)
                return true;
            $rightCode = $this->ctrlId . '@' . $actionId; //拼接的权限校验码
            $ownRight = ',' . trim($ownRight, ',') . ',';

            if (stripos($ownRight, ',' . $rightCode . ',') === false)
                return false;
            else
                return true;
        }
        else
            return true;
    }

    /**
     * @brief 获取当前控制器的id标识符
     * @return 控制器的id标识符
     */
    public function getId() {
        return $this->ctrlId;
    }

    /**
     * @brief 初始化controller对象
     */
    public function init() {
        
    }

    /**
     * @brief 过滤函数
     * @return array 初始化
     */
    public function filters() {
        return array();
    }

    /**
     * @brief 获取当前action对象
     * @return object 返回当前action对象
     */
    public function getAction() {
        return $this->action;
    }
    
    public function getAccessRules(){
        return $this->accessRules;
    }

    /**
     * @brief 设置当前action对象
     * @param object $actionObj 对象
     */
    public function setAction($actionObj) {
        $this->action = $actionObj;
    }

    /**
     * @brief 执行action方法
     */
    public function run() {
        //开启缓冲区
        ob_start();
        ob_implicit_flush(false);

        //初始化控制器
        $this->init();

        //创建action对象
        $actionObj = $this->createAction();
        UxInterceptor::run("onCreateAction");
        $actionObj->run();
        UxInterceptor::run("onFinishAction");
        flush();
        Ux::$app->end(0);
    }

    /**
     * @brief 创建action动作
     * @return object 返回action动作对象
     */
    public function createAction() {
        //获取action的标识符
        $actionId = UxRouter::getInfo('action');

        //设置默认的action动作
        if ($actionId === null)
            $actionId = $this->defaultAction;

        /* 创建action对象流程
         * 1,控制器内部动作
         * 2,CURD系统动作
         * 3,配置动作
         * 4,视图动作 */

        //1,控制器内部动作
        if (method_exists($this, $actionId))
            $this->action = new UxInlineAction($this, $actionId);

        //2,CURD系统动作
        else if (method_exists($this, 'curd') && in_array($actionId, $this->CURD))
            $this->action = new UxCurdAction($this, $actionId);

        //3,配置动作
        else if (($actions = $this->actions()) && isset($actions[$actionId])) {
            //自定义类名
            $className = $actions[$actionId]['class'];
            $this->action = new $className($this, $actionId);
        }

        //4,视图动作
        /*else{
            $this->action = new UxViewAction($this, $actionId);
        }*/
        
        else{
         throw new UxHttpException(array('heading' => '您访问的动作不存在', 'message' =>'动作 '.$actionId.' 不存在.'), 404);
        }

        return $this->action;
    }

    /**
     * @brief 预定义的action动作
     * @return array 动作信息
     */
    public function actions() {
        return array();
    }

    /**
     * @brief 渲染
     * @param string $view 要渲染的视图文件
     * @param string or array 要渲染的数据
     * @param bool $return 渲染类型
     * @return 渲染出来的数据
     */
    public function render($view, $data = null, $return = false, $statusCode = 200) {
        $this->_initHeaders($statusCode);
        
        $output = $this->renderView($view, $data);
        if ($return)
            return $output;
        else
            echo $output;
    }

    /**
     * @brief 渲染出静态文字
     * @param string $text 要渲染的静态数据
     * @param bool $return 输出方式 值: true:返回; false:直接输出;
     * @return string 静态数据
     */
    public function renderText($text, $return = false, $statusCode = 200) {
        $this->_initHeaders($statusCode);
        
        $text = $this->tagResolve($text);
        if ($return)
            return $text;
        else
            echo $text;
    }
    
    private function _initHeaders($statusCode = 200){
        $reponse = new UxHttpResponse('', "text/html;charset=" . $this->module->getCharset());
        $reponse->statusCode = $statusCode;
        $reponse->outputHeaders();
    }

    /**
     * @brief 获取当前主题下的视图路径
     * @return string 视图路径
     */
    public function getViewPath() {
        if (!$this->_viewPath) {
            $viewPath = isset($this->module->config['viewPath']) ? $this->module->config['viewPath'] : $this->defaultViewPath;
            $this->_viewPath = $this->module->getBasePath() . $viewPath . DIRECTORY_SEPARATOR . $this->theme . DIRECTORY_SEPARATOR;
        }
        return $this->_viewPath;
    }

    /**
     * @brief 获取当前主题下的皮肤路径
     * @return string 皮肤路径
     */
    public function getSkinPath() {
        if (!isset($this->_skinPath)) {
            $skinPath = isset($this->module->config['skinPath']) ? $this->module->config['skinPath'] : $this->defaultSkinPath;
            $this->_skinPath = $this->getViewPath() . $skinPath . DIRECTORY_SEPARATOR . $this->skin . DIRECTORY_SEPARATOR;
        }
        return $this->_skinPath;
    }

    /**
     * @brief 获取当前语言包方案的路径
     * @return string 语言包路径
     */
    public function getLangPath() {
        if (!isset($this->_langPath)) {
            $langPath = isset($this->module->config['langPath']) ? $this->module->config['langPath'] : $this->defaultLangPath;
            $this->_langPath = $this->module->getBasePath() . $langPath . DIRECTORY_SEPARATOR . $this->lang . DIRECTORY_SEPARATOR;
        }
        return $this->_langPath;
    }

    /**
     * @brief 获取layout文件路径(无扩展名)
     * @return string layout路径
     */
    public function getLayoutFile() {
        if ($this->layout == null)
            return false;

        return $this->getViewPath() . $this->defaultLayoutPath . DIRECTORY_SEPARATOR . $this->layout;
    }

    /**
     * @brief 取得视图文件路径(无扩展名)
     * @param string $viewName 视图文件名
     * @return string 视图文件路径
     */
    public function getViewFile($viewName) {
        $path = $this->getViewPath() . lcfirst($this->ctrlId) . DIRECTORY_SEPARATOR . $viewName;
        return $path;
    }

    /**
     * @brief 设置页面标题
     * @param string $value 标题值
     */
    public function setTitle($value) {
        $this->title = $value;
    }

    /**
     * @brief 获取页面标题
     * @return string 页面标题
     */
    public function getTitle() {
        if ($this->title !== null) {
            return $this->title;
        } else {
            return $this->ctrlId;
        }
    }

    /**
     * @brief 获取要渲染的数据
     * @return array 渲染的数据
     */
    public function getRenderData() {
        return $this->renderData;
    }

    /**
     * @brief 设置要渲染的数据
     * @param array $data 渲染的数据数组
     */
    public function setRenderData($data) {
        if (is_array($data))
            $this->renderData = array_merge($this->renderData, $data);
    }

    /**
     * @brief 视图重定位
     * @param string $next     下一步要执行的动作或者路径名,注:当首字符为'/'时，则支持跨控制器操作
     * @param bool   $location 是否重定位 true:是 false:否
     */
    public function redirect($nextUrl, $location = true, $data = null) {
        //获取当前的action动作
        $actionId = UxHttpRequest::get('action');
        if ($actionId === null) {
            $actionId = $this->defaultAction;
        }

        //分析$nextAction 支持跨控制器跳转
        $nextUrl = strtr($nextUrl, '\\', '/');

        if ($nextUrl[0] != '/') {
            //重定跳转定向
            if ($actionId != $nextUrl && $location == true) {
                $locationUrl = UxRouter::creatUrl('/' . $this->ctrlId . '/' . $nextUrl);
                header('location:' . $locationUrl);
                Ux::$app->end(0);
            }
            //非重定向
            else {
                $this->action = new UxViewAction($this, $nextUrl);
                $this->action->run();
            }
        } else {
            $urlArray = explode('/', $nextUrl, 4);
            $ctrlId = isset($urlArray[1]) ? $urlArray[1] : '';
            $nextAction = isset($urlArray[2]) ? $urlArray[2] : '';

            //重定跳转定向
            if ($location == true) {
                //url参数
                if (isset($urlArray[3])) {
                    $nextAction .= '/' . $urlArray[3];
                }
                $locationUrl = UxRouter::creatUrl('/' . $ctrlId . '/' . $nextAction);
                header('location:' . $locationUrl);
                Ux::$app->end(0);
            }
            //非重定向
            else {
                $nextCtrlObj = new $ctrlId($this->module, $ctrlId);

                //跨控制器渲染数据
                if ($data != null) {
                    $nextCtrlObj->setRenderData($data);
                }
                $nextCtrlObj->init();
                $nextViewObj = new UxViewAction($nextCtrlObj, $nextAction);
                $nextViewObj->run();
            }
        }
    }

    /**
     * @brief 渲染layout
     * @param string $viewContent view代码
     * @return string 解释后的view和layout代码
     */
    public function renderLayout($layoutFile, $viewContent) {
        if (file_exists($layoutFile)) {
            //在layout中替换view
            $layoutContent = file_get_contents($layoutFile);
            $content = str_replace('{viewcontent}', $viewContent, $layoutContent);
            return $content;
        }
        else
            return $viewContent;
    }

    /**
     * @brief 渲染处理
     * @param string $viewFile 要渲染的页面
     * @param string or array $rdata 要渲染的数据
     * @param bool 渲染的方式 值: true:缓冲区; false:直接渲染;
     */
    public function renderView($viewFile, $rdata = null) {
        UxInterceptor::run("onCreateView");
        if (stripos($viewFile, UX_PATH . 'web/source/view/') !== FALSE) {//错误提示视图
            if (!file_exists($viewFile. $this->extend)) {
                throw new UxHttpException('系统视图文件未找到.', 403);
            }
            
            $runtimeFile = $viewFile.$this->extend;
        } else {//正常视图
            //要渲染的视图
            $renderFile = $this->getViewFile($viewFile) . $this->extend;
            //检查视图文件是否存在
            if (!file_exists($renderFile)) {
                throw new UxHttpException(array('heading' => '文件不存在', 'message' =>'视图文件 '.UxException::pathFilter($renderFile).' 未找到.'), 403);
            }
            //生成文件路径
            $runtimeFile = str_replace($this->getViewPath(), $this->module->getRuntimePath(), $this->getViewFile($viewFile) . $this->defaultExecuteExt);

            //layout文件
            $layoutFile = $this->getLayoutFile() . $this->extend;

            if (!file_exists($runtimeFile) || (filemtime($renderFile) > filemtime($runtimeFile)) || (file_exists($layoutFile) && (filemtime($layoutFile) > filemtime($runtimeFile)))) {
                //获取view内容
                $viewContent = file_get_contents($renderFile);

                //处理layout
                $viewContent = $this->renderLayout($layoutFile, $viewContent);

                //标签编译
                $inputContent = $this->tagResolve($viewContent);

                //创建文件
                $fileObj = new UxFileHelper($runtimeFile, 'w+');
                $fileObj->write($inputContent);
                $fileObj->save();
                unset($fileObj);
            }
            
        }
        UxInterceptor::run("onFinishView");
        //引入编译后的视图文件
        $this->requireFile($runtimeFile, $rdata);
    }

    /**
     * @brief 引入编译后的视图文件
     * @param string $__runtimeFile 视图文件名
     * @param mixed  $rdata         渲染的数据
     * @return string 编译后的视图数据
     */
    public function requireFile($__runtimeFile, $rdata) {
        //渲染的数据
        if (is_array($rdata))
            extract($rdata, EXTR_OVERWRITE);
        else
            $data = $rdata;

        unset($rdata);

        //渲染控制器数据
        $__controllerRenderData = $this->getRenderData();
        extract($__controllerRenderData, EXTR_OVERWRITE);
        unset($__controllerRenderData);

        //渲染module数据
        $__moduleRenderData = $this->module->getRenderData();
        extract($__moduleRenderData, EXTR_OVERWRITE);
        unset($__moduleRenderData);

        require($__runtimeFile);
    }

    /**
     * @brief 编译标签
     * @param string $content 要编译的标签
     * @return string 编译后的标签
     */
    public function tagResolve($content) {
        $tagObj = new UxViewTagParser();
        return $tagObj->resolve($content);
    }

}
