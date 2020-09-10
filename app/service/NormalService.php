<?php

/*
 * Note:通用数据服务层.
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace app\service;

use \Config, \Baseset;
use \lib\core\{Service, Log};
use \lib\{util\Params};

class NormalService extends Service
{
 
    //初始化类
    public function __construct()
    {
    }
    
    //读取app信息
    public function getData($appid)
    {
        //测试不操作
        return array();
        
        
        //直接通过getModel调用Model读取/更新/插入数据示例
        return $this->getModel('test')->update(
            array(
                'status'=>1,
                ),
            array(
                'syncid'=>$syncid,
                )
            ); 
        
        //插入数据
        return $this->getModel('test')->insert(array(
            'uid'=>$uid,
            'sendtime'=>time(),
            'message_id'=>$message_id,
        ));
        
        
    }

    
    
    
    
}
