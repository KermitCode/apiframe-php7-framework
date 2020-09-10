<?php

/*
 * Note:辅助分页处理类
 * Author:04007.cn
 * Date:2019-06-05
 */

namespace lib\util;

use Config, Baseset;
use lib\core\Log;

class Page
{
    
    public static $baseUrl = null;
    
    public static function getBaseurl()
    {
        
        if(self::$baseUrl) return self::$baseUrl;
        
        self::$baseUrl = getenv('REQUEST_SCHEME') .'://'. getenv('HTTP_HOST');
  
        return self::$baseUrl;
    }
    
    //生成URL
    public static function getUrl($route, $params=array())
    {
        return self::getBaseurl().DIRECTORY_SEPARATOR.$route.'?'.http_build_query($params);
    }
    

}