<?php

/*
 * Note:各服务层核心底层
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\core;
use app\cron\BaseController as CronController;
use app\controller\BaseController as Controller;

abstract class Service
{
    //初始化核心共用属性数据
    public static $instance = null;
    
    //全局所有模型定义
    public static $ModelPool = array();
    
    //全局事务日志记录文件
    const TRANS_ERROR_KEY = 'trans_error';
    
    //将全局的参数传递进来
    public static function initService($instance)
    {
        self::$instance = $instance;
    }
    
    //提取属性:一些移过来的代码中使用的$this->调用。但尽量不要调用到这里.影响效率.
    public function __get($key)
    {
        return self::$instance->$key ?? null;
    }
    
    //测试显示数据
    public function debug( ...$data)
    {
        self::$instance->debug( ...$data );
    }

    //查看SQL调用记录
    public function getQueryed()
    {
        return self::$NormalModel->getQueryed();
    }
    
    //取得redis
    public function getCronRedis()
    {
        if( PHP_SAPI == "cli")
        {
            return CronController::$_REDIS;
        }else{
            return Controller::$_REDIS;
        }
    }

    //取得model：避免重复加载
    protected function getModel( $model = 'Normal' )
    {
        if(!empty(self::$ModelPool[$model]))
        {
            return self::$ModelPool[$model];
        }
        
        return self::createModel( $model );
    }
    
    //加载要有的服务模块
    public static function createModel( $model )
    {
        if(!$model) return null;
        $model = ucfirst(strtolower($model));

        $modelClass = MODEL_SPEACE . $model . MODEL_SUFFIX;
        if(!class_exists($modelClass))
        {
            exit($modelClass." file not exists.");
        }
        
        self::$ModelPool[$model] = new $modelClass();
        return self::$ModelPool[$model];
    }

    //取得服务模块：避免重复加载
    protected function getService($service = 'Normal')
    {        
        if(!empty(Controller::$ServicePool[$service]))
        {
            return Controller::$ServicePool[$service];
        }
        
        return Controller::createService($service);
    }

    //显示实时进度
    public function screen($message)
    {
        echo date('Y-m-d H:i:s ').$message."\n";
    }
	
}
