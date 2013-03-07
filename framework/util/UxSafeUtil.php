<?php
/**
 * @brief 安全机制session或者cookie数据操作
 * @author chendeshan
 * @date 2011-02-24
 * @version 0.6
 */

class UxSafeUtil
{
	/**
	 * @brief 设置数据
	 * @param string $key  键名;
	 * @param mixed  $val  值;
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function set($key,$val,$type = '')
	{
		$className = self::getSafeClass($type);
		call_user_func(array($className, 'set'),$key,$val);
	}

	/**
	 * @brief 获取数据
	 * @param string $key  要获取数据的键名
	 * @param string $type 安全方式:cookie or session;
	 * @return mixed 键名为$key的值;
	 */
	public static function get($key,$type = '')
	{
		$className = self::getSafeClass($type);
		$value = call_user_func(array($className, 'get'),$key);

		//cookie续写
		if($value != null && $className == 'UxHttpCookie')
		{
			self::set($key,$value);
		}

		return $value;
	}

	/**
	 * @brief 清除safe数据
	 * @param string $name 要删除的键值
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function clear($name = null,$type = '')
	{
		$className = self::getSafeClass($type);
		call_user_func(array($className, 'clear'),$name);
	}

	/**
	 * @brief 清除所有的cookie或者session数据
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function clearAll($type = '')
	{
		$className = self::getSafeClass($type);
		call_user_func(array($className, 'clearAll'));
	}

	/**
	 * @brief 获取cookie或者session对象
	 * @param  string $type 安全方式:cookie or session;
	 * @return object cookie或者session操作对象
	 */
	public static function getSafeClass($type = '')
	{
		$mappingConf = array('cookie'=>'UxHttpCookie','session'=>'UxHttpSession');
		if($type != '' && isset($mappingConf[$type]))
		{
			return $mappingConf[$type];
		}
		else if(isset(Ux::$app->config['safe']) && Ux::$app->config['safe'] == 'session')
		{
			return $mappingConf['session'];
		}
		else
		{
			return $mappingConf['cookie'];
		}
	}
}
?>