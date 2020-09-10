<?php

/*
 * Note:Redis核心类 
 * Author:04007.cn
 * Date:2019-04-14
 */

namespace lib\ext;

use \Config;
use \Redis;
use \lib\core\Log;

class RedisClient
{
    //过期时间秒
    private $_expire = 3600;
    private $_timeout = 5000;
    private static $userid = null;
    private $_type, $_configid = null, $_serverInfo = null;
    
    //连接资源标识
    private static $_source = array(), $_clients = array(), $_records = array();

    //禁止直接使用new实例化此类
    private function __construct()
    {
    }
    
    //获取REDIS类：共享对REDIS的连接:0连接无线业务，1连接DT数据
    public static function getInstance($type=0)
    {
        if(isset(self::$_source[$type])) return self::$_source[$type];
        switch($type)
        {
            case 0:$config = Config::$_REDIS;
                break;
        }
        
        $redis = new RedisClient();
        $redis->_serverInfo = $config;
        $redis->_type = $type;
        
        return self::$_source[$type] = $redis;
    }
    
    //取得redisInfo
    public function getCurRedis()
    {
        if($this->_configid!==null)
        {
            return $this->_serverInfo[$this->_configid];
        }
        return array();
    }
    
    //更换userid以进行新的查询(新查询时将会更换具体执行的redis资源)
    public function resetUserid($userid=false)
    {
        $userid && self::$userid = $userid;
        return $userid;
    }
    
    //连接服务器
    private function _conn($key)
    {
        //读取连接第几个REDIS
        $this->_configid = $this->_getServerId();
        $id = $this->_type.'_'.$this->_configid;
        
        //调试状态记录redis的操作次数
        if(Config::DEBUG)
        {
            $records = debug_backtrace();
            $records = $records[1];
            foreach($records['args'] as $key=>$value)
            {
                if(is_array($value)) $records['args'][$key]=implode(',', $value);
            }
            self::$_records[$id][$records['function']][] = implode(' ',$records['args']);
        }

        //连接redis
        if (!isset(self::$_clients[$id]) || !(self::$_clients[$id] instanceof Redis))
        {
            self::$_clients[$id] = new Redis();
            self::$_clients[$id]->server = $this->_serverInfo[$this->_configid];
            $server = $this->_serverInfo[$this->_configid];
            $rs = self::$_clients[$id]->pconnect($server['host'], $server['port'], $this->_timeout);
            if(!$rs) Log::error("redis_connect_failed:{$server['host']}:{$server['port']}-", 'redisErr');
            if(!empty($server['auth']))
            {
                $rs = self::$_clients[$id]->auth($server['auth']);
                if(!$rs) Log::error("redis_auth_fail:{$server['host']}:{$server['port']}-", 'redisErr');
            }
        }

        return self::$_clients[$id];
    }
    
    //决定连接第几个REDIS
    private function _getServerId()
    {
        //根据当前请求的REDIS类型返回连接第几个REDIS
        switch($this->_type)
        {
            case 0:$id = 0;
                break;
        }
        return $id;
    }

    //测试显示资源
    public function showStatic()
    {
        echo '-------------source-------------------------<pre>';
        print_r(self::$_source);
        echo '-------------clients-------------------------<pre>';
        print_r(self::$_clients);
        echo '-------------records-------------------------<pre>';
        print_r(self::$_records);
    }

    //实现REDIS的set方法
    public function set($k, $v, $expire=null, $continue=false)
    {
        $exp = $expire===null?$this->_expire:$expire;
        $redis = $this->_conn($k);
        $ttl = $redis->ttl($k);
        if($ttl >1  && $continue)
        {
            $exp = $ttl;
        }
        $redis->setex($k, $exp, $v);
        return true;
    }
    
    //实现REDIS的del方法
    public function del($k)
    {
        $redis = $this->_conn($k);
        return $redis->del($k);
    }
    
    //查询REDIS值的剩余生存时间
    public function ttl($k)
    {
        $redis = $this->_conn($k);
        return $redis->ttl($k);
    }

    //值过期设置
    public function setExpire($seconds = null)
    {
        $seconds = intval($seconds);
        if ($seconds > 0) {
            $this->_expire = $seconds;
        } else {
            $h = date('H');
            if ($h < 2 || $h > 19) {
                $this->_expire = 60 * 60 + rand(0, 15 * 60); // 晚间高峰期增加缓存时间
            } elseif($h > 2 && $h < 12) {
                $this->_expire = 15 * 60 + rand(0, 5 * 60);  // 低谷期减少缓存时间
            } else {
                $this->_expire = 15 * 60 + rand(0, 10 * 60);
            }
        }
    }
    
    //读取值
    public function get($k)
    {
        $redis = $this->_conn($k);
        return $redis->get($k);
    }
    
    //读取队列
    public function lrange($k, $left, $length)
    {
        $redis = $this->_conn($k);
        return $redis->lrange($k, $left, $length);
    }
    
    //读取hash中的key
    public function hGet($h, $k)
    {
        $redis = $this->_conn($h);
        return $redis->hget($h, $k);
    }

    //hash读所有
    public function hGetall($h)
    {
        $redis = $this->_conn($h);
        $rtn = $redis->hgetall($h);
        return $rtn?$rtn:array();
    }
    
    //hash读所有new
    public function hGetallnew($h)
    {
        $redis = $this->_conn($h);
        $keys = $redis->hkeys($h);
        $data = array();
        if(!$keys) return $data;
        $keys = array_values($keys);
        $data = $redis->hmget($h, $keys);
        return $data; 
    }
    
    //批量读取REDIS数据:$keys为数组，以一个key计算redis存储位置
    public function mget($keys)
    {
        if(!$keys) return array();
        $redis = $this->_conn(current($keys));
        $rtn = $redis->mget($keys);
        return $rtn?$rtn:array();
    }
    
    //批量设置REDIS数据:$data为数组，$key=>$values
    public function mset($data, $expire=0)
    {
        $redis = $this->_conn(key($data));
        $rtn = $redis->mset($data);

        //循环设置过期时间
        if($expire)
        {
            foreach($data as $k=>$v)
            {
                $redis->expire($k, $expire);
            }
        }
        return $rtn?$rtn:array();
    }
    
    //设置hash的值
    public function hSet($h, $k, $v, $expire=0)
    {
        $expire = $expire?$expire:$this->_expire;
        try {
            $redis = $this->_conn($h);
            $redis->hSet($h, $k, $v);
            if ($redis->ttl($h) < 0)
            {
                $redis->expire($h, $expire);
            }
            return true;
        } catch (Exception $e) {
            // pass
        }
        return false;
    }
    
    //设置hash的值无需过期
    public function hSetnoExpire($h, $k, $v)
    {
        try {
            $redis = $this->_conn($h);
            $redis->hSet($h, $k, $v);
            return true;
        } catch (Exception $e) {
            // pass
        }
        return false;
    }
    
    //向list插入值
    public function rpush($k, $v)
    {
        try {
            $redis = $this->_conn($k);
            $redis->rpush($k, $v);
            return true;
        } catch (Exception $e) {
            // pass
        }
        return false;
    }
    
    //取出list中的值
    public function lpop($k)
    {
        try {
            $redis = $this->_conn($k);
            return $redis->lpop($k);
        } catch (Exception $e) {
            // pass
        }
        return false;
    }
    
    //删除hash的值
    public function hDel($h, $k) 
    {
        $redis = $this->_conn($h);
        return $redis->hDel($h, $k);
    }
    
    //给值设置过期时间
    public function expire($key ,$expire=0)
    {
        $expire = $expire?$expire:$this->_expire;
        $redis = $this->_conn($key);
        return $redis->expire($key, $expire);
    }
    
    //hash批量设置值
    public function hMSet($h, $arr, $expire=0, $continue=false)
    {
        $expire = $expire?$expire:$this->_expire;
        $redis = $this->_conn($h);
        if ($redis->hMSet($h, $arr))
        {
            if ($continue || $redis->ttl($h) < 0) $redis->expire($h, $expire);
            return true;
        }
        return false;
    }
    
    //取得集合数据
    public function smembers($key)
    {
        if(!$key) return array();
        $redis = $this->_conn($key);
        $rtn = $redis->smembers($key);
        return $rtn?$rtn:array();
    }
    
    //向集合添加数据
    public function sadd($k, $v, $expire=null)
    {
        $exp = $expire===null?$this->_expire:$expire;
        $redis = $this->_conn($k);
        $redis->sadd($k, $v);
        return $redis->expire($k, $exp);
    }

    //
    public function exists($key) {
        try {
            $redis = $this->_conn($key);
            return $redis->exists($key);
        } catch (Exception $e) {
            // pass
        }
        return false;
    }

    public function hSetHasExpire($h,$k,$v,$expire=null) {
        try {
            $redis = $this->_conn($h);
            $redis->hset($h,$k,$v);
            if (intval($expire) > 0 && $redis->ttl($h) < 0) {
                $redis->expire($h, intval($expire));
            }
            return true;
        } catch (Exception $e) {
            // pass
        }
        return false;
    }

    public function hMGet($h, $arr, $db_name='rough') {
        $rtn = array();
        try {
            $redis = $this->_conn($h, $db_name);
            $rtn = $redis->hMGet($h, $arr);
        } catch (Exception $e) {
            // pass
        }
        return $rtn;
    }
    
    public function hExists($h,$k,$db_name='rough') {
        try {
            $redis = $this->_conn($h);
            return $redis->hexists($h, $k);
        } catch (Exception $e) {
        }
        return 0;
    }
    
    public function hIncrBy($h,$k,$count=1,$db_name='rough') {
        try {
            $redis = $this->_conn($h);
            return $redis->hincrby($h, $k, $count);
        } catch (Exception $e) {
        }
        return false;
    }

    public function setex($k, $ttl, $v, $db_name='exact') {
        $rtn = false;
        try {
            $redis = $this->_conn($k, $db_name);
            if ($ttl <= 0) {
                $ttl = $this->getExpire();
            }
            $rtn = $redis->setex($k, $ttl, $v);
        } catch (Exception $e) {
            // pass
        }
        return $rtn;
    }
}
