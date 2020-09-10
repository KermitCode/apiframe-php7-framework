<?php

/*
 * Note: 加密解决方法
 * Author:04007.cn
 * Date:2019-05-07
 */

namespace lib\util;

use Config, Baseset;
use lib\core\Log;

class Encrypt 
{
    private static $key = 'smb_letter' ;

	public static function encrypt($data, $key=null)
	{
        echo $data.'-=-';
		$key === null && $key = self::$key;

        $key = md5($key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        for($i = 0; $i<$len; $i++)
		{
            if($x == $l){
                $x = 0;
            }
            $char .= $key{$x};
            $x++;
        }

        for($i=0;$i<$len;$i++)
		{
            $str .= chr(ord($data{$i}) + ord($char{$i}));
        }

        return base64_encode($str);
    }

	public static function decrypt($data,$key=null)
	{
		$key === null && $key = self::$key;

        $char = $str = '';
        $key = md5($key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        for($i=0;$i<$len;$i++)
		{
            if($x == $l){
                $x = 0;
            }
            $char .= substr($key,$x,1);
            $x++;
        }
        for($i = 0;$i<$len;$i++){
            $str .= chr(ord($data{$i}) - ord($char{$i}));
        }
        return $str;

    }

}
