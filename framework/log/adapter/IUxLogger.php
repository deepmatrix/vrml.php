<?php
/**
 * @brief 日志接口文件
 * @author webning
 * @date 2010-12-09
 * @version 0.6
 */
interface IUxLogger
{
    /**
     * @brief 实现日志的写操作接口
     * @param array  $logs 日志的内容
     */
    public function write($logs = array());
}
?>