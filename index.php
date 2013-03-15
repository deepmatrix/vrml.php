<?php
error_reporting(E_ALL);
define('IN_DISCUZ', true);
define('DISCUZ_ROOT', dirname(dirname(__FILE__)).'/');//需要带斜杠
define('DISCUZ_CORE_DEBUG', true);
define('DISCUZ_DEBUG', true);

//在App创建前，需要被定义
define('APPTYPEID', 1000);//TODO:当前应用的id
define('CURSCRIPT', '?');//TODO:当前应用名称

define('APP_PATH', dirname(__FILE__) . '/');//必须定义应用所在路径

require dirname(__FILE__) .'/framework/Wind.php';

define('URL_MODE', Router::URL_MODE_NATIVE);

//echo  Router::URL_MODE_NATIVE;
//echo Router::createUrl('/member/login');
//echo Router::parseUrl();
//var_dump(class_exists('discuz_application'));
/*$app = new WebApplication();*/
Wind::app("config")->run();
