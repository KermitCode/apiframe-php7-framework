<?php

/*
 * Note: 加密解决方法
 * Author:04007.cn
 * Date:2019-05-07
 */

namespace lib\util;

use Config, Baseset;
use lib\core\Log;

class Deal 
{

    private static $key = 'letter' ;
	private static $cryptkey = " !_#$%&'()*+,-.ABCDEFGHIJKLMNOP?@/0123456789:;<=>QRSTUVWXYZ[\\]^\"`nopqrstuvwxyzabcdefghijklm{|}~";

	public static function encrypt($string)
	{
		$string = urlencode($string);
		$len    = strlen($string);
		$str    = '';
		for($i=0; $i<$len; ++$i) {
		    $ch = $string[$i];
		    $index = strpos(self::$cryptkey, $ch);
		    if( $index !== false) {
		        $key = $index + 32;
		        $ch  = chr($key);
		    } else {
		        $ch = $string[$i];
		    }
		    
		    $str .= $ch;
		}
		return $str;
	}

	public static function decrypt($string)
	{
		$len    = strlen($string);
		$str    = '';
		for($i=0; $i<$len; ++$i) {
		    $ch = ord($string[$i]);
		    if($ch >= 32 && $ch <= 126) {
		        $key = $ch-32;
		        $ch  = self::$cryptkey[$key];
		    } else {
		        $ch = $string[$i];
		    }
		    $str .= $ch;
		}

		return self::replace($str);
    }

	
	public static function replace($string)
	{
	    $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
	    $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
	    return str_replace($entities, $replacements, $string);
	}

}
