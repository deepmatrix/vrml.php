<?php
/**
 * @brief 异常处理
 * @author walu 
 * @date 2011-07-22
 * @version 0.1
 */
if( !class_exists('Exception') )
{
	
	/**
	 * 在php5.1之前，通过php代码中模拟Exception类
	 */
	class Exception
	{
		/**
		 * 异常信息
		 * @var mixed
		 */
		protected $message = 'Unknown exception';
		
		/**
		 * 用户自定义的异常代码
		 * @var mixed
		 */
		protected $code = 0;
		
		/**
		 * 发生异常的文件名
		 * @var string
		 */
		protected $file;
		
		/**
		 * 发生异常的代码行号
		 * @var int
		 */
		protected $line;

		private $backtrace;

		/**
		 * 返回异常信息
		 * @return mixed
		 * @final
		 */
	    final function getMessage()
		{
			return $this->message;
		}

		/**
		 * 返回异常代码
		 * @return mixed
		 * @final
		 */
	    final function getCode()
		{
			return $this->code;
		}

		/**
		 * 返回发生异常的文件名
		 * @return string
		 * @final
		 */
	    final function getFile()
		{
			return $this->file;
		}

		/**
		 * 返回发生异常的代码行号
		 * 如果php version小于5.1，则只能返回抛出异常的行号
		 * @return int
		 * @final
		 */
	    final function getLine()
		{
			return $this->line;
		}

	    /**
		 * 返回完整的debug_backtrace数组
		 * @return array
		 * @final
		 */
		final function getTrace()
		{
			return $this->backtrace;
		}

		/**
		 * 返回已经格式化为html字符串的debug_backtrace信息
		 * @return string
		 * @final
		 */
	    final function getTraceAsString()
		{}
	}
}


class UxException extends Exception
{
	private static $logPath = false;
	private static $debugMode = false;

	/**
	 * 构造函数
	 * @param string $message 
	 * @param mixed $code 
	 */
	public function __construct($message = null, $code = 0)
	{
		$bt = debug_backtrace();
		//抛弃这一层，用上一层的
		//foreach($bt as $key=>$value)
		//{
		//	//unset($bt[$key]);
		//	break;
		//}
		$info = reset($bt);

		if($info !== false)
		{
			$this->message = $message;
			$this->code = $code;
			$this->file = $info['file'];
			$this->line = $info['line'];
		}
		$this->backtrace = $bt;
	}

	/**
	 * 直接输出异常信息，不用catch
	 */
	public static function phpException($e)
	{
		if( !($e instanceof UxException) && $e instanceof Exception  )
		{
			if( self::$debugMode )
			{
				echo $e->getMessage();
			}
			self::logError($e->getMessage());
			return;
		}
		$e->show();
	}

	public static function phpError($errno , $errstr,$errfile=false ,$errline=false,$errcontext=false   )
	{
		$errfile = self::pathFilter($errfile);
		$re = "";
		$re .= "\n\n\n<pre>------\n";
		$re .= "errID:{$errno}\n";
		$re .= "errStr:{$errstr}\n";
		$re .= "errFile:{$errfile}\n";
		$re .= "errLine:{$errline}\n";
		$re .= "errTime:".date("y-m-d H:i:s")."\n";
		if(is_array($errcontext))
		{
			$re .= "##出错时变量的值：##\n";
			$re .= var_export($errcontext,true)."\n";
		}
		$re .= "-------</pre>\n";
		
		self::logError($re);
		if( self::$debugMode )
		{
			echo $re;
			exit;
		}
	}
	
	public static function pathFilter($path)
	{
		$uxf_path = UX_PATH;
		$app_path = Ux::$app->getBasePath();
		
		$count = 1;

		if( strpos($path, $uxf_path) ===0  )
		{
			$path = str_replace(UX_PATH , "UX_PATH/",$path , $count);
		}
		elseif( strpos($path, $app_path) === 0  )
		{
			$path = str_replace($app_path , "APP_PATH/" , $path ,$count );
		}
		return $path;
	}

	public function show()
	{
		$bt = $this->getTrace();
		$re = "\n\n\n<pre>----------\n";
		$re .= sprintf("Mess: %s\n",$this->getMessage());
		$re .= sprintf("Line: %s\n",$this->getLine());
		$re .= sprintf("File: %s\n",self::pathFilter( $this->getFile() ) );
		$re .= sprintf("##Debug_backtrace:##\n");
		foreach($bt as $value)
		{
			$value['file'] = self::pathFilter($value['file']);
			$re .= sprintf("\tFunc:%-15s\tClass:%-15s\tType:%-5s\tLine:%-5s\tFile:%s\n" ,
				isset($value['function'])?$value['function']: "" ,
				isset($value['class'])?$value['class']:"",
				isset($value['type'])?$value['type']:"",
				isset($value['line'])?$value['line']:"",
				$value['file'] );
		}
		$re .= "------------</pre>";
		
		if( ! $this instanceof UxHttpException )
			self::logError($re);

		if( self::$debugMode )
		{
			echo $re;
			exit;
		}
	}

	public static function setLogPath($path)
	{
		self::$logPath = $path;
	}

	public static function setDebugMode($mode)
	{
		self::$debugMode = $mode;
	}

	public static function logError($str)
	{
		if( self::$logPath)
		{
			$dir = dirname( self::$logPath  );
			if( !file_exists(self::$logPath) && !file_exists( $dir ) )
			{
				$b = mkdir($dir,0777,true);
				if(!$b)
				{
					return;
				}
			}
			$fp = fopen(self::$logPath,"ab");
			if($fp !== false)
			{
				fwrite($fp,$str);
			}
		}
	}
}
