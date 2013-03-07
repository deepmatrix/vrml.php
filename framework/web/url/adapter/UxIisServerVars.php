<?php
class UxIisServerVars implements IUxServerVars
{
	public function __construct($server_type)
	{}

	public function requestUri()
	{
		$re = "";
		if(isset($_SERVER['REQUEST_URI']))
		{
			$re = $_SERVER['REQUEST_URI'];
		}
		elseif( isset($_SERVER['HTTP_X_REWRITE_URL']) )
		{
			//不取HTTP_X_REWRITE_URL
			$re = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		elseif(isset($_SERVER["SCRIPT_NAME"] ) && isset($_SERVER['QUERY_STRING']) )
		{
			$re = $_SERVER["SCRIPT_NAME"] .'?'. $_SERVER['QUERY_STRING'];
		}
		return $re;
	}

	public function realUri()
	{
		$re= "";
		if( isset($_SERVER['HTTP_X_REWRITE_URL'])  )
		{
			$re = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : $_SERVER['HTTP_X_REWRITE_URL'];
		}
		elseif(isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != "" )
		{
			$re = $_SERVER['PATH_INFO'];
		}
		elseif(isset($_SERVER["SCRIPT_NAME"] ) && isset($_SERVER['QUERY_STRING']) )
		{
			$re = $_SERVER["SCRIPT_NAME"] .'?'. $_SERVER['QUERY_STRING'];
		}
		return $re;
	}

}