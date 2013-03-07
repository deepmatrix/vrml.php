<?php
/**
 * @brief 标签处理文件
 * @author webning
 * @date 2010-12-17
 * @version 0.6
 */
class UxViewTagParser
{
    //视图路径
	private $_viewPath;
    
    /**
     * @brief  解析给定的字符串
     * @param string $str 要解析的字符串
     * @param mixed $path 视图文件的路径
     * @return String 解析处理的字符串
     */
	public function resolve($str,$path=null)
	{
		$this->_viewPath = $path;
		return preg_replace_callback('/{(\/?)(\$|url|webroot|theme|include|require|js)\s*(:?)([^}]*)}/i', array($this,'translate'), $str);
	}
    /**
     * @brief 处理设定的每一个标签
     * @param array $matches
     * @return String php代码
     */
	public function translate($matches)
	{
		if($matches[1]!=='/')
		{
			switch($matches[2].$matches[3])
			{
				case '$':
                {
                    $str = trim($matches[4]);
                    $first = substr($str,0,1);
					if($first != '.' && $first != '(')
					{
						if(strpos($str,'(')===false)return '<?php echo isset($'.$str.')?$'.$str.':"";?>';
						else return '<?php echo $'.$str.';?>';
					}
                    else return $matches[0];
                }
                case 'js:': return UxClientScript::load($matches[4]);
				case 'url:': return '<?php echo UxRouter::creatUrl("'.$matches[4].'");?>';
                case 'webroot:': return '<?php echo UxRouter::creatUrl("")."'.$matches[4].'";?>';
                case 'theme:': return '<?php echo UxRouter::creatUrl("")."static/".$this->theme."/'.$matches[4].'";?>';
				case 'require:':
				case 'include:':
				{
					$fileName = trim($matches[4]);
					$viewfile = Ux::$app->controller->getViewPath().$fileName.Ux::$app->controller->extend;
                    
					$runfile= Ux::$app->getRuntimePath().$fileName.Ux::$app->controller->defaultExecuteExt;

					if(!file_exists($runfile) || filemtime($runfile)<filemtime($viewfile))
					{
						$file = new UxFileHelper($runfile,'w+');
						$template = file_get_contents($viewfile);
						$t = new UxViewTagParser();
						$tem = $t->resolve($template,dirname($viewfile));
						$file->write($tem);
                        $file->save();
					}
                    
					return "<?php require('$runfile')?>";
				}
				default:
				{
					 return $matches[0];
				}
			}
		}
		else
		{
			if($matches[2] =='code') return '?>';
			else return '<?php }?>';
		}
	}
    /**
     * @brief 分析标签属性
     * @param string $str
     * @return array以数组的形式返回属性值
     */
	public function getAttrs($str)
	{
		preg_match_all('/([a-zA-Z0-9_]+)\s*=([^=]+?)(?=(\S+\s*=)|$)/i', trim($str), $attrs);
		$attr = array();
		foreach($attrs[0] as $value)
		{
			$tem = explode('=',$value);
			$attr[trim($tem[0])] = trim($tem[1]);
		}
		return $attr;
	}
}
?>
