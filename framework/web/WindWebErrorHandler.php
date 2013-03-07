<?php
Wind::import('WIND:base.WindErrorHandler');
/**
 * web mvc 错误句柄处理
 * errorDir错误页面所在目录，在web模式下默认的错误目录为‘WIND:web.view’，{@see
 * AbstractWindFrontController}configTemplate默认值，也可以通过配置当前应用的错误目录相改变这个默认配置。
 * 
 * @author Qiong Wu <papa0924@gmail.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package wind.web
 */
class WindWebErrorHandler extends WindErrorHandler {
	
	/*
	 * (non-PHPdoc) @see WindError::showErrorMessage()
	 */
	protected function showErrorMessage($message, $file, $line, $trace, $errorcode) {
		list($fileLines, $trace) = $this->crash($file, $line, $trace);
		
		if (WIND_DEBUG & 2) {
			$log = $message . "\r\n" . $file . ":" . $line . "\r\n";
			foreach ($trace as $key => $value) {
				$log .= $value . "\r\n";
			}
			Wind::getComponent('windLogger')->error($log, 'error', true);
		}
		$message = nl2br($message);
		$errDir = Wind::getRealPath($this->errorDir, false);
		if ($this->isClosed)
			$errPage = 'close';
		elseif (is_file($errDir . '/' . $errorcode . '.htm'))
			$errPage = $errorcode;
		else
			$errPage = 'error';
		
		$title = $this->getResponse()->codeMap($errorcode);
		$title = $title ? $errorcode . ' ' . $title : 'unknowen error';
		$title = ucwords($title);
		
		ob_start();
		$this->getResponse()->setStatus($errorcode);
		$this->getResponse()->sendHeaders();
		require $errDir . '/' . $errPage . '.htm';
		exit(ob_get_clean());
	}
}

?>