<?php
/**
 * @brief 对象基本类
 * @author jimmy
 * @date 2012-08-03
 * @version 0.6
 */

class UxObject
{
	private $property = array();

	/**
	 * @brief __set函数
	 * @param string $name
	 * @param mixed  $value
	 */
	public function __set($name,$value)
	{
		$this->property[$name] = $value;
	}

	/**
	 * @brief __get函数
	 * @param string $name
	 */
	public function __get($name)
	{
		if(isset($this->property[$name]))
		{
			return $this->property[$name];
		}
		else
		{
			return null;
		}
	}

	/**
	 * @brief __call函数
	 * @param string $name
	 * @param array 参数列表
	 */
	public function __call($name,$params)
	{

	}

	/**
	 * @brief 检测数据是否存在
	 * @param string $name
	 * @return bool true:存在; false:不存在;
	 */
	public function __isset($name)
	{
		if(isset($this->property[$name]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 卸载数据
	 * @param string $name
	 * @return bool true:卸载成功; false:卸载失败;
	 */
	public function __unset($name)
	{
		if(isset($this->property[$name]))
		{
			unset($this->property[$name]);
			return true;
		}
		else
		{
			return false;
		}
	}
    /**
     * @brief 最得所有属性值数组。
     * @return array 数据
     */
    public function getProperty()
    {
        return $this->property;
    }
}

?>