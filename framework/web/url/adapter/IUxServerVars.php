<?php

interface IUxServerVars
{
	/**
	 * 获取REQUEST_URI
	 * 标准：真实的从浏览器地址栏体现出来的url，不包括#锚点
	 */
	public function requestUri();

	/**
	 * 最终呈现给服务器的url
	 * 标准：伪静态前与request_uri相同，伪静态后是最后一条规则调整后的url
	 */
	public function realUri();
}