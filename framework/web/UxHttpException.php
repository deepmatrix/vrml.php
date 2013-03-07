<?php
/**
 * Http异常处理，只能在控制器中使用
 */
class UxHttpException extends UxException
{
    private $_messageData = array();
	/**
	 * 构造函数
	 * @param array $message 
	 * @param mixed $code 
	 */
	public function __construct($message = null, $code = 0)
	{
        if(!is_array($message)){
            $message['message'] = $message;
        }
        
        $this->_messageData = $message;
        $message = isset($message['message']) ? $message['message'] : null;
		
        parent::__construct($message, $code);
	}
    
	/**
	 * @brief 获取控制器
	 * @return object 控制器对象
	 */
	public function getController()
	{
		return Ux::$app->controller;
	}

	/**
	 * @brief 报错 [适合在逻辑(非视图)中使用,此方法支持数据渲染]
	 * @param string $httpNum   HTTP错误代码
	 * @param array  $errorData 错误数据
	 */
	public function show()
	{
		$httpNum = $this->getCode();
		$errorData = $this->_messageData;

		//初始化页面数据
		$showData   = array(
			'title'   => null,
			'heading' => null,
			'message' => null,
		);

		if(is_array($errorData))
		{
			$showData['title']   = isset($errorData['title'])   ? $errorData['title']   : null;
			$showData['heading'] = isset($errorData['heading']) ? $errorData['heading'] : $showData['title'];
			$showData['message'] = isset($errorData['message']) ? $errorData['message'] : null;
		}
		else
		{
			$showData['message'] = $errorData;
		}

		//检查用户是否定义了error处理类
		$config = isset( Ux::$app->config['exception_handler'] ) ? Ux::$app->config['exception_handler'] : 'Error' ;
		$flag = class_exists($config);
        
		if( $flag && method_exists($config,"error{$httpNum}") )
		{
            $response = new UxHttpResponse($showData['message']);
            $response->statusCode = $httpNum;
            $response->outputHeaders();
			$errorObj = new $config(Ux::$app,'error');
			call_user_func(array($errorObj,'error'.$httpNum),$errorData);
		}
		//是系统内置的错误机制，此时可能还没有对应的控制器初始化
		else {
            $controller = $this->getController();
            if (!is_object($controller)) {
                $response = new UxHttpResponse($showData['message']);
                $response->statusCode = 404;
                $response->render();
                return;
            }
            if (file_exists(UX_PATH . 'web/source/view/' . 'error' . $httpNum . $controller->extend)) {
                $controller->render(UX_PATH . 'web/source/view/' . 'error' . $httpNum, $showData, false, $httpNum);
            }
            //输出错误信息
            else {
                $controller->renderText($showData['message'], false, $httpNum);
            }
        }
		exit;
	}
}

