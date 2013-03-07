<?php
/**
 * @brief 系统JS包加载类文件
 * @author webning
 * @date 2010-12-22
 * @version 0.6
 */

 class UxClientScript
 {
     //系统JS注册表
     private static $_jsPackages=array(
	     'jquery'=>'jquery-1.4.4.min.js',
	     'form'=>'form.js',
	     'dialog'=>'artdialog/artDialog.min.js',
	     'validate'=>array(
		     'js'=>'autovalidate/validate.js',
		     'css'=>'autovalidate/style.css'
	     ),
	     'my97date' =>'my97date/wdatepicker.js'
     );
     /**
      * @brief 加载系统的JS方法
      * @param mixed $name
      * @return String
	  */
	 private static $createfiles = array();
     
     public static function load($name,$charset='UTF-8')
	 {
		 if(isset(self::$_jsPackages[$name]))
		{
			if(!isset(self::$createfiles[$name]))
			{
				$is_file = false;
				$file = null;
				if(is_string(self::$_jsPackages[$name]))
				{
					if(stripos(self::$_jsPackages[$name],'/')===false)
					{
						$is_file = true;
						$file = self::$_jsPackages[$name];
					}
					else $file = dirname(self::$_jsPackages[$name]);
				}
				else
				{
					if(is_array(self::$_jsPackages[$name]['js'])) $file = dirname(self::$_jsPackages[$name]['js'][0]);
					else $file = dirname(self::$_jsPackages[$name]['js']);
				}
				if(!file_exists(Ux::$app->getRuntimePath().'systemjs/'.$file))
				{
					self::$createfiles[$name] = true;
					UxFileHelper::xcopy(UX_PATH.'web/source/js/'.$file,Ux::$app->getRuntimePath().'systemjs/'.$file);
				}
			}
			$webjspath = Ux::$app->getWebRunPath().'/systemjs/';
			if(is_string(self::$_jsPackages[$name])) return '<script charset="'.$charset.'" src="'.$webjspath.self::$_jsPackages[$name].'"></script>';
			else if(is_array(self::$_jsPackages[$name]))
			{
				$str='';
				if(isset(self::$_jsPackages[$name]['css']))
				{
					if(is_string(self::$_jsPackages[$name]['css'])) $str .= '<link rel="stylesheet" type="text/css" href="'.$webjspath.self::$_jsPackages[$name]['css'].'"/>';
					else if(is_array(self::$_jsPackages[$name]['css']))
					{
						foreach(self::$_jsPackages[$name]['css'] as $css)
						{
							$str .= '<link rel="stylesheet" type="text/css" href="'.$webjspath.$css.'"/>';
						}
					}
				}
				if(isset(self::$_jsPackages[$name]['js']))
				{
					if(is_array(self::$_jsPackages[$name]['js']))
					{
						foreach(self::$_jsPackages[$name]['js'] as $js)
						{
							$str .= '<script charset="'.$charset.'" src="'.$webjspath.$js.'"></script>';
						}
					}
					else
					{
						$str .= '<script charset="'.$charset.'" src="'.$webjspath.self::$_jsPackages[$name]['js'].'"></script>';
					}
				}

				return $str;
			}
		}
		 else return '';
	 }
 }

 ?>
