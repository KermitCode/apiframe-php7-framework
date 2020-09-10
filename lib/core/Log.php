<?php

/*************************************************
 * Author:04007.cn
 * Date:2019-04-13
 * Note:日志处理类
 * ***********************************************/

namespace lib\core;

//必须先定义日志目录才能进入此
!defined('LOG_PATH') && exit('undefine:LOG_PATH');
define('STARTTIME', microtime(true) * 1000 & 0x7FFFFFFF);

//定义全局日志ID
$microTime = gettimeofday();
define('TIMESTAMP', $microTime['sec']);
define('DAYSTART', strtotime(date("Y-m-d"), TIMESTAMP));
define('YMDHIS', date('Y-m-d H:i:s', TIMESTAMP));
define('YMD', date("ymd", TIMESTAMP));
define('LOG_ID', Log::getLogid($microTime['sec'], $microTime['usec']));

/*
 * 日志类的使用说明：
 * 日志默认记到项目同级目录的data目录下，例如线上就是/opt/data/wireless_logs/下面
 * 三种级别[debug,trace,error]日志类调用方法:
 * log::debug(..) 主要用于调试或线上排查时用，数组、对象会以友好形式展示，此方法主要用于调试展示输入的日志内容
 * log::trace(..) 默认的日志级别，会记录在哪个地方调用了日志处理及日志信息以追踪发生位置，执行用时
 * log::error(..) 错误日志，详细记录请求URL和数据页面执行时间，以及2层错误追踪，主要用于页面执行发生异常时自动调用以记录异常。
 * 三个方法参数一样，示例：log:debug(日志内容,文件前缀=null,新目录名=null,分割方式=null);
 * 关于日志的文件：
 * 不传文件名会记录到 日志目录/php_级别_年月.log文件里。
 * 如使用自定义文件名则会记录到 日志目录/文件名_级别.log里
 * 只有在使用自定义文件名时按年月分割参数才可用，比如传入文件名test,分割参数用month
 * 则会日志会记录在 日志目录/test_级别_年月.log 文件
 * 调用示例：
 * Log::trace('记录日志');  记录在/opt/data/wireless_logs/php_trace_201611.log中
 * Log::error('记录日志, 详细记录请求及调用追踪信息');记录在/opt/data/wireless_logs/php_error_201611.log中
 * Log::debug(array('test'=>'调试以数组友好展示形式展示'),'test');记录在/opt/data/wireless_logs/test_debug.log中
 */
class Log
{
    
    //全局日志ID
    private static $logId = null;
    
    /*
     * 日志级别：不同级别的日志，记录的信息详细程序不同,
     * 日志详细程度：最低级别 调试日志 < 追踪日志 < 最高级别错误日志
     * 默认日志级别追踪日志:日志间隔使用一个空隔,以便使用AWK分析日志(trace和error日志可适于awk分析；debug调试日志数组以友好形式展现可能不适于)
     */
    private static $logFormat=array(
        //调试日志:时间 日志ID 请求URI 日志信息
        'LOG_DEBUG'=>'[DEBUG] %s %s %s [log]%s',
   
        //追踪日志:时间 日志ID 请求URI [调用追踪] 日志信息 
        'LOG_TRACE' => '[TRACE] %s %s %s %s [log]%s',
    
        //错误日志:时间 日志ID 页面执行用时(ms) [调用追踪] 客户端IP 请求HOST 请求URI POST数据[json可能为空] 日志信息
        'LOG_ERROR' => "[ERROR] %s %s %s %s IP:%s %s %s [post]%s [log]%s",
        );
    
    //用户的IP地址
    public static $ip=null;
    
    //日志ID
    public static function getLogid($second, $micro)
    {
        if(self::$logId)
        {
            return self::$logId;
        }
        
        //如果有外部传入日志ID,使用外部日志ID,以便可以此ID追踪日志记录:日志必须是全数字，且长度不能小于15位.
        if(!empty($_GET['Logid']) && is_numeric($_GET['Logid']) && strlen($_GET['Logid'])>15 )
        {
            self::$logId = $_GET['Logid'];
        }else{
            $uniPartId = abs(crc32(uniqid(md5(mt_rand(0,99999)),true)));
            $year = date('y')%9+1;
            self::$logId = $year.sprintf("%'.03d", date('z')).sprintf("%'.05d", $second % 86400)
                          .sprintf("%'.06d", $micro).sprintf("%'.04d", substr($uniPartId,0,5));
        }
        return self::$logId;
    }
    
    //信息内容格式化
    private static function format($message, $level)
    {
        if(is_array($message) or is_object($message))
        {
            //调试级别时数组、对象以友好形式展示；记录日志时以JSON记录
            if($level=='debug') $message = var_export($message, true);
            else $message = json_encode($message);
        }elseif(is_resource($message)){
            $message = strval($message);
        }
        return $message;
    }
    
    //日志文件路径及名称
    private static function initLogFile($dir, $file, $split, $level)
    {
        //判断是否写到子目录中:logs目录预先创建好
        $logDir = $dir?LOG_PATH."{$dir}/":LOG_PATH;
        !is_dir($logDir) && mkdir($logDir, true);

        /* 日志文件名:
         * 不传文件名则以php_级别_年月.log名称存储.
         * 如是自定义文件名:则日志文件为 文件名_级别.log,再根据所传参数确定是否分年月存储
         */
        $filePre = $file?"{$file}_{$level}" : "php_{$level}_".date('Ym');
        PHP_SAPI == "cli" && $filePre = PHP_SAPI.$filePre;
        //是否按年、月分割
        $fileExt = '';
        $file && $fileExt = $split?($split=='month'?date('_Ym'):date('_Y')):'';
        
        //日志文件名
        return $logDir.$filePre.$fileExt.'.log'; 
    }
    
    /*
     * 记录日志:所有的日志写在项目根目录同级的下logs文件夹中，
     * $message:日志内容，可为字符串、数组、对象等
     * $file:日志文件名；如果不传则日志文件名为php_年份.log
     * $dir:默认日志文件在日志目录下，如果要在新目录中则传入dir名称
     * $split:null,month,year;分别代表不分割，按月分割，按年分割。
     * 日志级别:也即调用的方法名比如：log::error($mess) 中的error.
     */
    private static function dolog($message, $file, $dir, $split, $level)
    {
        //日志文件
        $logFile = self::initLogFile($dir, $file, $split, $level);
        
        //日志信息格式化
        $logMessage = self::format($message, $level);
        
        //记录日志
        return self::writeLog($logFile, $logMessage, $level);
    }

    //记录调试日志
    public static function debug($message, $file=null, $dir=null, $split=null)
    {
        return self::dolog($message, $file, $dir, $split, __FUNCTION__);
    }
    
    //记录追踪日志
    public static function trace($message, $file=null, $dir=null, $split=null)
    {
        return self::dolog($message, $file, $dir, $split, __FUNCTION__);
    }
    
    //记录错误日志
    public static function error($message, $file=null, $dir=null, $split=null)
    {
        return self::dolog($message, $file, $dir, $split, __FUNCTION__);
    }
    
    //记录要用的数据到文件:默认文件名 $dir_$file; 如没有$dir.则不加$dir_
    public static function record($message, $file, $dir=null, $append=true)
    {
        //日志目录及文件
        $logDir = $dir?LOG_PATH."{$dir}/":LOG_PATH;
        !is_dir($logDir) && mkdir($logDir, true);
        $logFile = $logDir.$file.'.log';
       
        //写入日志
        return $append?file_put_contents($logFile, $message, FILE_APPEND):file_put_contents($logFile, $message);
    }
    
    //取得客户端的IP地址
    public static function getClientIp()
    {
        if(self::$ip) return self::$ip;
        
        $temp_ip = '';
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown'))
        {
            $temp_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            strpos($temp_ip, ',') && list($temp_ip) = explode(',', $temp_ip);
        } else if(!empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
            $temp_ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if(!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $temp_ip = $_SERVER['REMOTE_ADDR'];
        }
        
        self::$ip = $temp_ip;
        return self::$ip;
    }
    
    //记录执行到此用时
    private static function getCostTime()
    {
        return (microtime(true) * 1000) & 0x7FFFFFFF  - STARTTIME;
    }
    
    //得到追踪信息
    private static function getBacktrace($depth)
    {
        $traceChar = array();
        $trace = debug_backtrace();
        for($i = $depth; $i>=4; $i--)
        {
            if(empty($trace[$i])) continue;
            if($i == 4)
            {
                $traceChar[] = "{$trace[$i]['file']}:{$trace[$i]['line']} ";
            }else{
                //提取调用的文件路径行数参数
                $temp = '';
                !empty($trace[$i]['file']) && $temp.="{$trace[$i]['file']}:line:{$trace[$i]['line']} ";
                !empty($trace[$i]['class']) && $temp.="{$trace[$i]['class']} ";
                $args= !empty($trace[$i]['args'])?json_encode($trace[$i]['args']):'';
                !empty($trace[$i]['function']) && $temp.="{$trace[$i]['type']}{$trace[$i]['function']}({$args})";
                $traceChar[] = $temp;
            }
        }
        return implode('==>', $traceChar);
    }
    
    //错误日志时的详细信息
    private static function getMore()
    {
        return array(
            self::getClientIp(), 
            $_SERVER['HTTP_HOST'] ?? 'null',
            $_SERVER['REQUEST_URI'] ?? 'null',
            empty($_POST)?'':json_encode($_POST)
        );
    }
    
    //得到记录信息
    private static function getFormat($level)
    { 
        $formatArr = array(YMDHIS, LOG_ID);
        switch($level)
        {
            case 'debug':
                $formatArr = array_merge($formatArr, array($_SERVER['REQUEST_URI']?? ''));
                break;
            case 'trace': 
                $formatArr = array_merge($formatArr, array($_SERVER['REQUEST_URI']?? '', self::getBacktrace(6)));
                break;
            case 'error': 
                $formatArr = array_merge($formatArr, array(self::getBacktrace(5), self::getCostTime()), self::getMore());
                break;
            default : 
                break;
        }
        return $formatArr;
    }
    
    //记录到日志文件
    private static function writeLog($logFile, $logMessage, $level)
    {
        //提取格式化日志信息
        $formatArr = self::getFormat($level);
        $formatArr[] = $logMessage;

        //写进日志文件:暂未进行加锁写、视情况再看是否有必要.
        $key = 'LOG_'.strtoupper($level);
        $format_temp = self::$logFormat[$key].PHP_EOL;
        $log = vsprintf($format_temp, $formatArr);
        return file_put_contents($logFile, $log, FILE_APPEND);
    }
    
    //页面最终执行完毕时的错误处理
    public static function checkFinished()
    {
        //捕获页面执行最的严重错误
        $error = error_get_last();
        if ($error['type'] == E_ERROR)
        {
            //出现核心错误，返回异常给前端.通过LOG_ID查服务端日志.
            if (!headers_sent())
            {
                header('HTTP/1.1 502 Service Unavailable');
                echo json_encode(array('status' => -1, 'msg' => 'error occured!', 'requestid' => LOG_ID));
            }
            
            //重要错误进行记录日志并停止执行
            Log::error("{$error['file']}:{$error['line']}:{$error['message']}");
            exit;
            
        }elseif($error['type'] && $error['type'] != E_NOTICE && $error['type'] != E_WARNING){
            //对其它的非致命错误(不含E_NOTICE和E_WARNING)进行记录:但不终止程序执行.
            Log::error("{$error['file']}:{$error['line']}:{$error['message']}", 'phpLog');
        }
    }
    
}
