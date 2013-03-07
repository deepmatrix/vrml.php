<?php
class UxNginxServerVars implements IUxServerVars
{
	public function __construct($server_type){}
	public function requestUri()
	{
		$re = "";
		if(isset($_SERVER['REQUEST_URI']))
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		return $re;
	}
	public function realUri()
	{
		$re = "";
		if(isset($_SERVER['DOCUMENT_URI']) )
		{
			$re = $_SERVER['DOCUMENT_URI'];
		}
		elseif( isset($_SERVER['REQUEST_URI']) )
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		return $re;
	}
}