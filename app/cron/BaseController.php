<?php

/*
 * Note:定时任务基础控制器
 * Author:04007.cn
 * Date:2019-04-13
 */

namespace app\cron;

use \Config, \Baseset;
use \Hashids\Hashids;
use \lib\{core\Controller, ext\RedisClient};

class BaseController extends Controller
{
    public static $_REDIS;
    public $hashids;
    	
    //基础控制器
    public function __construct()
    {
    	if (php_sapi_name() !== 'cli') die('run in cli mode');
    }

    //初始化hashid
    public function _initHashids()
    {
        $this->hashids = new Hashids(Baseset::APPNAME);
    }

    //显示实时进度
    public function screen($message)
    {
        echo date('Y-m-d H:i:s ').$message."\n";
    }

	//初始化$this->_Redis
    protected function _initRedis()
    {
        self::$_REDIS = RedisClient::getInstance();
    }

    //通用将数据写入PHP文件，并加上return格式
    public function writeArray($file, $data)
    {
        $content = "<?php\nreturn " . var_export($data, true).";";
        file_put_contents($file, $content);
    }
    
    //通用将原数据直接写入file文件
    public function writeJson($file, $data)
    {
        file_put_contents($file, $data);
    }

}
