<?php
/*
 * Note:项目入口
 * Author:04007.cn
 * Date:2019-04-10
 */

PHP_VERSION_ID < 70000 && exit('php version must php7+.');
!defined('ROOT_PATH') && define('ROOT_PATH', dirname(dirname( __FILE__ )) );

define('APP_PATH', ROOT_PATH.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
define('VIEW_PATH',APP_PATH.'view'.DIRECTORY_SEPARATOR);
define('STATIC_PATH', ROOT_PATH .DIRECTORY_SEPARATOR. 'www'.DIRECTORY_SEPARATOR);
define('CACHE_PATH', ROOT_PATH .DIRECTORY_SEPARATOR. 'cache'.DIRECTORY_SEPARATOR);
define('LOG_PATH', ROOT_PATH.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR);

define('CONTROLLER_SPEACE','\app\controller\\');
define('CRON_SPEACE','\app\cron\\');
define('CONTROLLER_SUFFIX', 'Controller');
define('ACTION_SUFFIX', 'Action');
define('MODEL_SPEACE','\app\model\\');
define('MODEL_SUFFIX', 'Model');
define('SERVICE_SPEACE','\app\service\\');
define('SERVICE_SUFFIX', 'Service');

date_default_timezone_set( 'PRC' );
header( "Content-type: text/html; charset=utf-8" );

require_once ROOT_PATH.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (\Config::DEBUG)
{
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors','on');
}else{
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
}

register_shutdown_function(array('lib\core\Log', 'checkFinished'));
if (PHP_SAPI == "cli")
{
    $WORK_SPEACE = CRON_SPEACE;
    $_REQUEST = $_SERVER['argv'];
    $_REQUEST['c'] = empty($_REQUEST[1])? 'home':$_REQUEST[1];
    $_REQUEST['a'] = empty($_REQUEST[2])? 'index':$_REQUEST[2];
}else{
    $WORK_SPEACE = CONTROLLER_SPEACE;
    $_REQUEST['c'] = empty($_REQUEST['c'])? 'home':$_REQUEST['c'];
    $_REQUEST['a'] = empty($_REQUEST['a'])? 'index':$_REQUEST['a'];
}

$class = ucfirst(strtolower(trim($_REQUEST['c'])));
$action = ltrim(strtolower(trim($_REQUEST['a'])), '_') . ACTION_SUFFIX;
$classFile = $WORK_SPEACE . $class . CONTROLLER_SUFFIX;
if(!class_exists($classFile))
{
    exit("class not exists.");
}

$controller = new $classFile($class, $action);
$controller->__beforeAction($class, $action);
$controller->$action();
$controller->__afterAction();
