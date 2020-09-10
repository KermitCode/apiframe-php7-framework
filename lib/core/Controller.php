<?php

/*
 * Note:控制器核心底层类 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\core;

use \Config;
use \lib\util\Params;
use \lib\core\{Service, Log};

abstract class Controller
{
    public $c, $a, $ca;
    public $emptyDict, $filter;
    
    private static $baseService = 'Normal';
    public static $ServicePool = array();
    
    public function __beforeAction($class, $action)
    {
        //执行时间和内存占用
        if(Config::DEBUG)
        {
            Params::getMemeoryUsed('start');
            Params::getTimeCosted('start');
        }
       
        //初始化基础参数
        $this->c = $class;
        $this->a = ucfirst($action);
        $this->ca = $class.$this->a;
        $this->emptyDict = new \stdClass();
        
        //注册服务：基本要用的服务都在此注入.后面如果还要增加可随时再调用此方法.
        Service::initService($this);
        self::createService(self::$baseService);
    }
    
    //默认调用方法
    public function __call($method, $arg)
    {
        exit("{$method} not exists");
    }

    //默认控制器方法:
    public function index()
    {
        $name = get_class($this);
        echo "$name's index.\n";
    }
    
    //全局共用有效属性值校验方法
    protected function checkMustParam( ...$keyArr )
    {
        foreach($keyArr as $key)
        {
            if(@!$this->{$key})
            {
                $error = '无效参数';
                Log::error($error.":{$key}");
                $this->jsonError($error);
            }
        }
    }

    //测试显示数据
    public function debug( ...$data )
    {
        $order = 1;
        foreach($data as $value)
        {
            echo str_repeat('-', 30) . "<b>DebugValue:{$order}</b>" .str_repeat('-', 30).'<pre>';
            print_r($value);
            echo '</pre>';
            $order++;
        }
    }
    
    //查看SQL调用记录
    public function getQuerySql()
    {
        return self::$NormalService->getQueryed();
    }
    
    //查看Redis调用记录
    public function getQueryRedis()
    {
        return \lib\ext\RedisClient::$_records;
    }
    
    //json返回最后结果
    public function jsonOk($data, $extendArr = array())
    {
        $return = array(
            'status'=> "0",
            'msg'=> "OK",
            'requestid'=> $this->_DtCardsAttr['req_id'] ?? LOG_ID,
        );
        
        //如有扩展数据输出
        if($extendArr)  $return = $return + $extendArr;
        $return['result'] = $data;
        return json_encode($return);
    }
    
    //json输出错误
    public function jsonError($message = 'server error', $errno = 1)
    {
        $return = array(
            'status'=> strval($errno),
            'msg'=> $message,
            'requestid'=>LOG_ID,
            'result' => new \stdClass(),
        );
        $this->show(json_encode($return));
        exit;
    }
    
    //输出数据:指定是否让CDN缓存
    public function show($data, $cdnCache = false)
    {
        if(!$cdnCache)
        {
            //禁用CDN缓存
            header("HTTP/1.1 200 OK");
            header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        }
        
        //加进所以要加入header里的信息:以后要新加全局字段都加在这里。
        header("X-Global: requestId=".LOG_ID);
        echo $data;
    }
    
    //显示页面执行时间和内存占用统计
    public function getStat()
    {
        //执行时间和内存占用
        $m = Params::getMemeoryUsed('start', 'end');
        $t = Params::getTimeCosted('start','end');
        return "function: {$this->c}->{$this->a} time-cost: {$t} memory-used: {$m}";
    }
    
    //显示当前连接的所有redis实例信息
    public function redisInfo()
    {
        if($this->_REDIS)
        {
            return $this->_REDIS->showStatic();
        }elseif($this->_PAYREDIS){
            return $this->_PAYREDIS->showStatic();
        }
    }
    
    //批量显示整个请求中重要指标
    public function debugAll()
    {
        $this->debug($this->getStat(), $this->getQuerySql(), $this->getQueryRedis(), $this->redisInfo());
        exit;
    }
    
    //记录错误:线上记录错误日志.
    public function recordError($error, $file = NULL)
    {
        if(Config::DEBUG)
        {
            $this->debug($error);
        }else{
            Log::error($error, $file);
        }
    }
    
    //记录追踪日志
    public function recordMess($message, $file = NULL)
    {
        Log::record(date('Y-m-d H:i:s ').$message."\n", $file);
    }

    //取得服务模块：避免重复加载
    protected function getService($service = 'Normal')
    {        
        if(!empty(self::$ServicePool[$service]))
        {
            return self::$ServicePool[$service];
        }
        
        return self::createService($service);
    }
    
    //加载要有的服务模块
    public static function createService( $service )
    {
        if(!$service) return null;
        $service = ucfirst(strtolower($service));

        $serviceClass = SERVICE_SPEACE . $service . SERVICE_SUFFIX;

        if(!class_exists($serviceClass))
        {
            exit($serviceClass." file not exists.");
        }
        
        self::$ServicePool[$service] = new $serviceClass();
        
        return self::$ServicePool[$service];
    }
    
    //所有的方法执行完成后需执行的操作
    public function __afterAction()
    {
        //调试状态记录执行用时及内存占用情况
        if(Config::DEBUG)
        {
            //Log::debug($this->getStat(), 'stat');
        }
    }
    
    //加载视图
    public function view($template,$data=false, $isReturn = false)
    {
        if (!empty($data) && is_array($data))
        {
            extract($data, EXTR_OVERWRITE);
        }
        
        ob_start();
        $templatePath = VIEW_PATH.$template.'.php';
        if (file_exists($templatePath))
        {
            include_once($templatePath);
        }else{
            $this->recordError("File:{$templatePath} not exists.");
        }
        
        if ( $isReturn === TRUE )
        {
            $buffer = ob_get_contents();
            @ob_end_clean();
            return $buffer;
        }
        
        ob_end_flush();
        exit;
    }

}
