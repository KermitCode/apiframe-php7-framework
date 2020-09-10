<?php

/*
 * Note:Pheanstalk核心调用类 
 * Author:04007.cn
 * Date:2019-04-26
 */

namespace lib\ext;

use \Config;
use \Pheanstalk\Pheanstalk;
use \lib\core\Log;

class BeanstalkClient
{
    private static $instance;
    private $conf = array();
    
    //初始化实例
    private function __construct($index)
    {
        $index === null && $this->conf = current(Config::$_BEANSTALKD);
        
        try
        {
            $this->pheanstalk = new Pheanstalk($this->conf['host'], $this->conf['port']);  
            if (!$this->pheanstalk->getConnection()->isServiceListening())
            {
                $this->pheanstalk = false;
            }
        }catch (\Exception $e){
            Log::error("beanstalk server connection is null, {$this->conf['host']}:{$this->conf['port']}. error message:" . $e->getMessage());
        }
        
        if(!$this->pheanstalk)
        {
            Log::error("beanstalk server not connectionl, {$this->conf['host']}:{$this->conf['port']}");
        }

        return $this->pheanstalk;
    }
    
    //生成实例
    public static function getInstance($index=null)
    {
        if (empty(self::$instance) || !(self::$instance instanceof self))
        {
            self::$instance = new self($index);
        }
        return self::$instance;
    }
    
    //向队列添加数据
    public function producer($tubename, $data, $delayTime = 0, $priority = 0)
    {
        if (empty($tubename) || empty($data))
        {
            return false;
        }
        $data = is_array($data) ? json_encode($data) : $data;
        
        if (!is_string($data))
        {
            return false;
        }
        
        $id = 0;
        try 
        {
            if (!empty($delayTime) && $delayTime > 0)
            {
                $delayTime = intval($delayTime);
                $priority = empty($priority) ? 0 : intval($priority);
                $id = $this->pheanstalk->useTube($tubename)->put($data, $priority, $delayTime);
            } else {
                $id = $this->pheanstalk->useTube($tubename)->put($data);
            }
            
        } catch (\Exception $e){
            
            Log::error("beanstalk put error, {$this->conf['host']}:{$this->conf['port']}" . $e->getMessage());
            return;
            
        }
        
        return $id;
    }
    
    public function statusTube($tubeName)
    {
        return $this->pheanstalk->statsTube($tubeName);
    }
    
    //取得队列Job
    public function getJob($tubename)
    {
        $listTubes = $this->pheanstalk->listTubes();
        
        if (!in_array($tubename, $listTubes))
        {
            return false;
        }
        
        $timeout = $this->conf['timeout'] ?? 3;

        $job = $this->pheanstalk->watch($tubename)->ignore('default')->reserve($timeout);
        
        return empty($job) ? false : $job;
    }
    
    //取得Job数据
    public function getJobData($job)
    {
        if (empty($job))
        {
            return false;
        }
        
        $data = $job->getData();
        
        return empty($data) ? false : $data;
    }

    //释放job
    public function release($job)
    {
        if (empty($job))
        {
            return false;
        }
        
        $this->pheanstalk->release($job);
    }
    
    //清除job
    public function delete($job)
    {
        if (empty($job))
        {
            return false;
        }

        $this->pheanstalk->delete($job);
    }

}
