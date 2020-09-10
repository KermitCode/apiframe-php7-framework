<?php

/*
 * Note:Home控制器 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace app\controller;

use \Config, \Baseset;
use \lib\{ext\RedisClient, util\Params};

class HomeController extends BaseController
{
    
    public function indexAction()
    {
        
        echo $this->jsonOk(
                array(
                    "data"=>array()
                )
            );
        
        //$this->jsonError("appid无效.");
        
        //调用service:默认调用NormalService，调用其它的传入参数即可。
        $this->getService();
    }
}


