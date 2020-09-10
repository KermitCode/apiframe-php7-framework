<?php

/*
 * Note:简单后台控制器
 * Author:04007.cn
 * Date:2019-06-04
 */

namespace app\controller;

use \Config, \Baseset;
use \lib\core\{Log};
use \lib\{ext\RedisClient, util\Params};

class AdminController extends BaseController
{

    //基础属性:
    public static $source = null;
    const PASSWORD_SALT = 'kre&&63klKHJR';
    public $message = '';
    
    //调用所有控制器都必须先验证相关的东西
    public function __construct()
    {
        //全局URL以及资源相对路径
        $this->domain = getenv('REQUEST_SCHEME') .'://'. getenv('HTTP_HOST');
        $this->sourceUrl = '/static/';
        
        //一些固定数据
        $this->title = '-apiFrame简单后台';
        $this->action = $_REQUEST['a'];
        $this->serverIP = $_SERVER['SERVER_ADDR'];
        $this->userIP = Log::getClientIp();
        
        //初始化菜单
        $this->_initMenu();

        //接受基本参数
        $this->uid = Params::getRequestArg('uid');
        $this->url = isset($_POST['url'])?$_POST['url']:'';
        
        if($this->action != 'login')
        {
            $this->checkPass();
        }
    }

    //登录控制器
    public function loginAction()
    {
        if(isset($_GET['logout']) && $_GET['logout']=='yes')
        {
           setcookie("_admin", "", time() - 3600);
        }

        //判断自动登录
        if(isset($_COOKIE["_admin"]) && $_COOKIE["_admin"]) $this->checkPass(null, true);

        //验证判断
        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $username = Params::getPost('username', '');
            $password = Params::getPost('password', '');
            if($username == 'admin' && $password)
            {
                $this->checkPass($password, false);
            }
            $this->message = '用户名或密码有误.';
        }
        $this->view('home/login');
        exit;
    }

    public function __call($method, $ages)
    {
        $var = '';
	foreach ($ages as $value)
        {
            $var .= $value.',';
	}
	$this->view('home/waiting');
    }
    
    //首页
    public function indexAction()
    {
       $this->view('home/index');
    }

 
}



