<?php
/**
 * @brief 数据库底层抽象类
 * @author chendeshan
 * @date 2010-12-3
 * @version 0.6
 */

abstract class UxAbstractDbDriver
{
	//数据库写操作连接资源
	private static $wTarget = NULL;

	//数据库读操作连接资源
	private static $rTarget = NULL;

	//SQL类型
	protected static $sqlType = NULL;

	/**
	* @brief 获取SQL语句的类型,类型：select,update,insert,delete
	* @param string $sql 执行的SQL语句
	* @return string SQL类型
	*/
	private function getSqlType($sql)
	{
		$strArray = explode(' ',trim($sql),2);
		return strtolower($strArray[0]);
	}

	/**
	 * @brief 设置数据库读写分离并且执行SQL语句
	 * @param string $sql 要执行的SQL语句
	 * @return int or bool SQL语句执行的结果
	 */
    public function getAll($sql)
    {
		//取得SQL类型
        self::$sqlType = $this->getSqlType($sql);

		//读方式
        if(self::$sqlType=='select' || self::$sqlType=='show')
        {
            if(self::$rTarget == NULL || !is_resource(self::$rTarget))
            {
				//多数据库支持并且读写分离
                if(isset(Ux::$app->config['db']['read']))
                {
					//获取ip地址
					$ip = UxHttpClient::getIP();

                    $this->connect(UxHashHelper::hash(Ux::$app->config['db']['read'],$ip));
                }
                else
                {
                	$this->connect(Ux::$app->config['db']);
                }
                self::$rTarget = $this->linkRes;
            }
        }
        //写方式
        else
        {
            if(self::$wTarget == NULL || !is_resource(self::$wTarget))
            {
				//多数据库支持并且读写分离
                if(isset(Ux::$app->config['db']['write']))
                {
                	$this->connect(Ux::$app->config['db']['write']);
                }
                else
                {
                	$this->connect(Ux::$app->config['db']);
                }
                self::$wTarget = $this->linkRes;
            }
        }

        if(is_resource($this->linkRes))
        {
        	return $this->doSql($sql);
        }
        else
        {
        	return false;
        }

    }

	//数据库连接
    abstract public function connect($dbinfo);

	//执行sql通用接口
    abstract public function doSql($sql);
    
    /**
     * 字段和表名处理添加`
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        $key   =  trim($key);
        if(!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
           $key = '`'.$key.'`';
        }
        return $key;
    }
}
