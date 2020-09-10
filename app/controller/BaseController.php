<?php

/*
 * Note:基础控制器 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace app\controller;

use \Config, \Baseset;
use \Hashids\Hashids;
use \lib\{core\Controller, ext\RedisClient, util\Params, core\Log, util\Page};

abstract class BaseController extends Controller
{
    public $appid, $sign, $timestamp;
    public $hashids;
    public $app;
    public $pagesize = 15;
    
    public static $_REDIS;

    //所有接口实例化时的基础参数获取及简单处理
    public function __construct($controller, $action)
    {
        $this->appid = Params::getRequestArg('appid', 0, true);
        $this->timestamp = Params::getRequestArg('timestamp', 0, true);
        $this->sign = Params::getRequestArg('sign', '');
        
        //基础参数
        $filterAction = array('ticketAction', 'oemuticketAction');
        if(!in_array($action, $filterAction))
        {
            //必要参数检查
            $this->checkMustParam('appid', 'timestamp', 'sign');
        }
        
        //公共参数校验及处理
        $this->checkSignature($controller, $action);
        
        $this->_initRedis();
        $this->_initHashids();
    }
    
    //初始化$this->_Redis
    protected function _initRedis()
    {
        self::$_REDIS = RedisClient::getInstance();
    }

    //初始化hashids
    protected function _initHashids()
    {
        return $this->hashids = new Hashids(Baseset::APPNAME);
    }

    //签名校验
    protected function checkSignature($c, $a)
    {
        //测试环境不检验签名
        $whiteAction = array('debugAction', 'ticketAction');
        if(Config::SIGNDEBUG || in_array(strtolower($c), array('admin')) || in_array($a, $whiteAction))
        {
            return true;
        }

        //请求时间要求
        if( abs(time() - $this->timestamp) > 180)
        {
            $this->jsonError("已过期timestamp");
        }
        
        //签名生成
        $signArr = $_GET;
        if($_SERVER['REQUEST_METHOD'] == 'POST') $signArr = $_POST;
        
        unset($signArr['sign'], $signArr['a'], $signArr['c']);
        ksort($signArr);

        $sign = array();
        foreach($signArr as $key=>$value)
        {
            $sign[] = "{$key}={$value}";
        }
        $signori = implode('&', $sign). $this->app['sercet'];
        $rightSign = md5($signori);

        //签名判断
        if($this->sign != $rightSign)
        {
            Log::error("Sign Error:{$this->sign}, right sign:".$rightSign);
            $this->jsonError('签名错误.');
        }
        
        return true;
    }
    
    //简单后台的登录判断
    protected function checkPass($pass=null, $loginpage=false)
    {
        $rightVal = md5($this->userIP.'_'.(Config::TESTENV?Baseset::TEST_CHECK:Baseset::SIMPLE_CHECK).date('Y-m-d'));
        $thisVal = md5($this->userIP.'_'.md5($pass).date('Y-m-d'));

        if($pass===null)
        {
            $pass = @$_COOKIE["_admin"];
            if($pass == $rightVal)
            {
                return true;
            }else{
                if(!$loginpage) $this->loginAction();
                else return false;
                exit;
            }
        }elseif($pass){
            if($rightVal != $thisVal) return false;          
            $ctime = time()+ 86400;
            setcookie('_admin', $rightVal, $ctime);
            $this->view('home/index');
            exit;
        }
        return;
    }
    
    //功能菜单
    protected function _initMenu()
    {
        $this->menu = array(
            'admin/index'=>array(
                'name'=>'管理后台首页',
            ),
            '#1'=>array(
                'name'=>'用户管理',
                'stats'=>'opeded',
                'children'=>array(
                    'list'=>'用户列表',
                    'add'=>'用户新增',
                ),
            ), 
            '#2'=>array(
                'name'=>'日志管理',
                'stats'=>'closed',
                'children'=>array(
                    'user'=>'日志管理',
                    'list'=>'日志管理',
                    'add'=>'日志管理',
                ),
             ),
           
        );
    }
    
    public function getUrl($route, $params=array())
    {
        return Page::getUrl($route, $params);
    }
    
    public function getUri()
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return $url['path'];
    }
    
    public function getParams($params = array())
    {
        $get = $_GET;
        unset($get['c'], $get['a']);
        return array_merge($get, $params);
    }
    
    /*
     * Author:04007.cn@04007.cn
     * Note:通用分页html字符串方法
     */
    public function getPageHtml($request, $records, $params = array(), $pagenum=0)
    {
        //current page & uri
        $baseUrl = $this->getUri();
        $params = $this->getParams($params);
        !$pagenum && $pagenum = $this->pagesize;
        $all = ceil( $records / $pagenum );
        $page = Params::getRequestArg('page', 1, true);
        $page < 1 && $page = 1;
        $page > $all && $page = $all;
        if( $all < 2 ) return '';
        
        //page html
        $htmlchar = '<nav class="admin-pagination text-center"><ul class="pagination">';
        $start = ( $page - 5 >0 )? ($page - 5):1;
        $end = ( $page + 5 > $all) ? $all:($page + 5);

        //to first
        if($page > 5 )
        {
            $params['page'] = 1;
            $htmlchar.="<li><a href='{$baseUrl}?".http_build_query($params)."'><<</a></li>";
            $params['page'] = $page-1;
            $htmlchar.="<li><a href='{$baseUrl}?".http_build_query($params)."'><</a></li>";
        }
        
        //middle pagehtml
        for($i = $start; $i<=$end; $i++)
        {
            $params['page'] = $i;
            if($i==$page)
            {
                $htmlchar.="<li class='active'><a href='#'>{$i}</a></li>";
            }else{
                $htmlchar.="<li><a href='{$baseUrl}?".http_build_query($params)."'>{$i}</a></li>";
            }
        }
        
        //to end
        if($page < $all)
        {
            $params['page'] = $page+1;
            $htmlchar.="<li><a href='{$baseUrl}?".http_build_query($params)."'>></i></a></li>";
            $params['page'] = $all;
            $htmlchar.="<li><a href='{$baseUrl}?".http_build_query($params)."'>>></a></li>";
        }

        $htmlchar.= "<li><span class='page-num'> 共{$all}页 / {$records}条记录</span></li></ul></nav>";
        return array($page, $htmlchar);
    }
}
