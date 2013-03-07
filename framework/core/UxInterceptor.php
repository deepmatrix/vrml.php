<?php
/**
 * @brief 内核拦截器
 * @author walu
 * @date 2011-07-15
 * @version 0.1
 */

/**
 * 内核拦截器
 * 
 * 在app中使用这个类，需要在config.php里配置interceptor
 * 'interceptor'=>array(
 *		'classname', //将classname类注册到所有位置 或者
 *		'classname1@onFinishApp', //将classname1类注册到onFinishApp这个位置
 * );
 * 
 * <ul>
 *	<li>onPhpShutDown一旦注册，便肯定会执行，即使程序中调用了die和exit</li>
 * </ul>
 *
 * 在使用拦截器时，建议一个拦截器只完成一方面的工作，比如Blog@onFinishApp,User@onCreateApp
 * 虽然Blog和User的逻辑可以写在一类里，但为了以后维护的方便，建议拆分开
 *
 * @author walu
 */
class UxInterceptor
{
	/**
	 * @brief 系统中预定的位置
	 */
	private static $_validPosition = array(
		'onCreateApp' , 'onFinishApp' ,
		'onCreateController' , 'onFinishController',
		'onCreateAction' , 'onFinishAction',
		'onCreateView' , 'onFinishView',
		'onPhpShutDown'
	);

	private static $obj = array();

	/**
	 * 向系统中的拦截位置注册类
	 * @param string|array $value 可以为 "iclass_name","class_name@position",也可以是由他们组成的数组
	 */
	public static function reg($value)
	{
		if( is_array($value) )
		{
			foreach($value as $v)
			{
				self::reg($v);
			}
		}
		else
		{
			$tmp = explode("@",trim($value));
			if( count($tmp) == 2  )
			{
				self::regIntoPosition($tmp[0] , $tmp[1]);
			}
			else
			{
				foreach(self::$_validPosition as $value)
				{
					self::regIntoPosition($tmp[0] , $value);
				}
			}
		}
	}
	
	/**
	 * 直接向某位置注册类
	 * @param string $className
	 * @param string $position
	 */
	public static function regIntoPosition($className,$position)
	{
		$validPos = in_array( $position,self::$_validPosition);
		$haveDone = isset(self::$obj[$position]) &&  in_array( $className,self::$obj[$position]);
		if( $validPos && !$haveDone  )
		{
			self::$obj[$position][] = $className;
		}
	}

	/**
	 * 调用注册到某个位置的拦截器
	 * @param string $position 位置
	 */
	public static function run($position)
	{
		if( !isset(self::$obj[$position]) || !in_array($position , self::$_validPosition ) )
		{
			return;
		}
		foreach( self::$obj[$position] as $value  )
		{
			call_user_func( array($value,$position) );
		}
	}

	/**
	 * 删除某个位置的所有拦截器，如果$className!=null,则只删除它一个
	 * @param string $position
	 * @param string|null $className
	 */
	public static function del($position,$className = null)
	{
		if(!isset(self::$obj[$position]))
		{
			if($className!==null)
			{
				foreach(self::$obj[$position] as $key=>$value)
				{
					if( $className==$value )
					{
						unset(self::$obj[$position][$key]);
						break;
					}
				}
			}
			else
			{
				unset(self::$obj[$position]);
			}
		}
	}
	
	/**
	 * 清空所有拦截器
	 */
	public static function delAll()
	{
		self::$obj = array();
	}
	
	/**
	 * 调用所有的onFinishApp拦截器
	 */
	public static function shutDown()
	{
		self::run("onPhpShutDown");
	}
}

