<?php

/*
 * Note:辅助参数处理类
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\util;

use Config, Baseset;
use lib\core\Log;

class Params 
{
    //内存时间占用.
    public static $memory = array(), $time = array();
    
    //是否保存调用外部URL的调用数据记录
    public static $apiRecord = array();

    //读取参数
    static function getRequestArg($name, $default = null, $int=false)
    {
        //提取参数
        $val = trim($_REQUEST[$name]??'');
        $val && $val = htmlspecialchars($val);

        //平台机型值处理
        if($name =='platf' && !in_array($val, self::$platfs)) $val = false;
        if($name =='mtype' && !in_array($val, self::$mtypes)) $val = false;

        //值处理及默认值修复
        $int && $val = intval($val);
        !$val && $val = $default;
        
        return $val;
    }
    
    //读取post参数
    static function getPost($name, $default, $int=false)
    {
        //提取参数
        $val = trim($_POST[$name]??'');
        $val && $val = htmlspecialchars($val);
        
        //值处理及默认值修复
        $int && $val = intval($val);
        !$val && $val = $default;
        
        return $val;
    }
 
    //内存占用的情况
    public static function getMemeoryUsed($skey, $ekey = false)
    {
        if($ekey !== false)
        {
            $end = isset(self::$memory[$ekey])?self::$memory[$ekey]:memory_get_usage();
            return Params::roundSize($end - self::$memory[$skey]);
        }else{
            self::$memory[$skey] = memory_get_usage();
        }
    }
    
    //程序时间执行情况
    public static function getTimeCosted($skey, $ekey = false)
    {
        if($ekey !== false)
        {
            $end = isset(self::$time[$ekey])?self::$time[$ekey]: microtime(true);
            return round( ($end - self::$time[$skey]), 2) * 1000 .' ms';
        }else{
            self::$time[$skey] = microtime(true);
        }
    }
    
    //大小显示
    public static function roundSize($size)
    {
        $kb=1024;
        $mb=$kb*1024;
        $gb=$mb*1024;
        $tb=$gb*1024;

        if($size<$kb)	return $size." byte";
        if($size>=$kb and $size<$mb)	return round($size/$kb,2)." KB";
        if($size>=$mb and $size<$gb)	return round($size/$mb,2)." MB";
        if($size>=$gb and $size<$tb)	return round($size/$gb,2)." GB";
        if($size>=$tb)	return round($size/$tb,2)." TB";
    }

    //curl请求封装
    static function http_get($url, $headers = array(), $timeout = 1)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = array();
        $r['content'] = curl_exec($ch);
        $r['http_info'] =curl_getinfo($ch);
        $r['http_info']['http_error'] = curl_error($ch);
        $r['http_info']['http_errno'] = curl_errno($ch);
        self::$filter && self::$apiRecord[] = array('url'=>$url, 'result'=>$r['content']);
        curl_close($ch);
        return $r;
    }

    static function http_post($url, $params = array(), $headers = array()) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        
        $r = array();
        $r['content'] = curl_exec($ch);
        $r['http_info'] =curl_getinfo($ch);
        $r['http_info']['http_error'] = curl_error($ch);
        $r['http_info']['http_errno'] = curl_errno($ch);
        self::$filter && self::$apiRecord[] = array('url'=>$url, 'result'=>$r['content']);
        curl_close($ch);
        return $r;
    }

    //取完整URL
    public static function getFullUrl()
    {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    //过滤数组中的空字符串内容
    public static function filterSpace($data)
    {
        //字符串过滤
        if(is_array($data))
        {
            $return = array();
            foreach($data as $key=>$value)
            {
                $return[$key] = self::filterSpace($value);
            }
            return array_filter($return);
        }else{
            return trim($data);
        }
    }
    
    //从给定的数组中删除指定值元素:$valueMix可以是单个值，也可以是一个数组.
    public static function array_remove($array, $valueMix)
    {
        foreach( $array as $k=>$v)
        {
            if( (is_array($valueMix) && in_array($v, $valueMix)) || (!is_array($valueMix) && $v == $valueMix) )
            {
                unset($array[$k]);
            }
        }
        return $array;
    }

    //数组重置键值
    public static function changeKey(array $arr, $field = 'id') {
        $arrNew = [];
        foreach ($arr as $k => $v) {
            $arrNew[$v[$field]] = $v;
        }

        return $arrNew;
    }

    public static function getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return sprintf('%.0f', (floatval($usec) + floatval($sec)) * 1000);
    }
    
    //去除换行、空格、回车等无意义字符
    public static function DeleteHtml($str) 
    { 
        $str = trim($str);
        $str = preg_replace("/\t/","",$str); 
        $str = preg_replace("/\r\n/","",$str); 
        $str = preg_replace("/\r/","",$str); 
        $str = preg_replace("/\n/","",$str); 
        $str = preg_replace("/ /","",$str);
        $str = preg_replace("/  /","",$str); 
        return trim($str);
    }

}