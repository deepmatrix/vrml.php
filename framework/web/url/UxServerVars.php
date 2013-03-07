<?php
//$_SERVER里与url有关的两个量的兼容处理方案
//这是个不成熟的解决方案，可能仅适用于本框架

class UxServerVars implements IUxServerVars
{
	public static function factory($server_type)
	{
		$obj = null;
		$type = array(
			'apache' => 'UxApacheServerVars',
			'iis'	=> 'UxIisServerVars_IIS' ,
			'nginx' => 'UxNginxServerVars'
		);

		foreach($type as $key=>$value)
		{
			if(stripos($server_type,$key) !== false )
			{
				$obj = new $value($server_type);
				break;
			}
		}

		if($obj === null)
		{
			return new UxServerVars();
		}
		else
		{
			return $obj;
		}
	}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		return $_SERVER['REQUEST_URI'];
	}
}