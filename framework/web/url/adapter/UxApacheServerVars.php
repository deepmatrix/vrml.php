<?php

class UxApacheServerVars implements IUxServerVars
{
	public function __construct($server_type)
	{}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		return $_SERVER['PHP_SELF'];
	}
}