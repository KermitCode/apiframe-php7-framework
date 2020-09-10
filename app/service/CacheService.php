<?php

/*
 * Note:cache服务层.
 * Author:04007.cn
 * Date:2019-04-16
 */

namespace app\service;

use \Config, \Baseset;
use \lib\core\{Service, Log};
use \lib\{util\Params};

class CacheService extends Service
{
    public static $cacheData;
 
    //初始化类
    public function __construct()
    {
        
    }
    
    //保存数据
    public function save($data, $fileName)
    {
        $content = var_export($data, true);
        
        if(strpos($fileName, '.') === false)
        {
            $fileName = $fileName.'.php';
        }
        
        return $this->saveToFile($content, $fileName);
    }

    //取得缓存数据
    public function get($fileName)
    {
        
        if(isset(self::$cacheData[$fileName]))
        {
            return self::$cacheData[$fileName];
        }
        
        $file = strpos($fileName, '.') === false? ($fileName.'.php'):$fileName;
        
        if(!file_exists(CACHE_PATH. $file))
        {
            Log::error("file:".CACHE_PATH. $file." not exists.");
            
            return null;
        }
        
        self::$cacheData[$fileName] = require(CACHE_PATH. $file);
        
        return self::$cacheData[$fileName];
    }
    
    //取得文件最后修改时间
    public function getModifyTime($fileName)
    {
        $file = strpos($fileName, '.') === false? ($fileName.'.php'):$fileName;

        if(!file_exists(CACHE_PATH. $file))
        {
            return 0;
        }

		return filemtime(CACHE_PATH. $file);
    }


    protected function saveToFile($content, $file)
    {
        if(!$content || !$file)
        {
            return false;
        }
        
        $str = "<?php\n#CreateTime:".date('Y-m-d H:i:s')."\nreturn {$content};";
        
        return file_put_contents( CACHE_PATH.$file , $str);
    }
    
    public function saveStreamFile($stream, $filename, $dir=null)
    {
        $pathfile = $dir?(CACHE_PATH . $dir . '/' . $filename):(CACHE_PATH . $filename);
        if($dir && !is_dir(CACHE_PATH.$dir))
        {
             mkdir(CACHE_PATH.$dir, true);
        }
        
        $fp = fopen($pathfile, "w+");
        fwrite($fp, $stream);
        fclose($fp);
        
        return true;
    }


    
    
    
    
}
