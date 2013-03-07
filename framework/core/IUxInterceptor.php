<?php

/**
 * 拦截器接口，在创建拦截器对象的时候实现此接口。
 */
interface IUxInterceptor
{
	public static function onCreateApp();
	public static function onFinishApp();
	public static function onCreateController();
	public static function onFinishController();
	public static function onCreateAction();
	public static function onFinishAction();
	public static function onCreateView();
	public static function onFinishView();
	public static function onPhpShutDown();
}
