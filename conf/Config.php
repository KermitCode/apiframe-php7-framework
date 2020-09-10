<?php
!defined('ROOT_PATH') && exit('NO DEFINE ROOT_PATH.');

//区分线上测试环境的配置类
class Config {

    //上线时必须调整为 false
    const DEBUG = true;
    const SIGNDEBUG = true;
    const TESTENV = true;

    //外部接口响应超时时间配置:测试环境加长点,线上要求1s超时.    
    const API_TIMEOUT = 2;
    const JUNIOR_PROJECT_PLAN_CATEGORY_ID = 60;    
    
    /************************************** MYSQL **************************************************************/
    public static $_MYSQL = array(
        //默认连接此配置：读库配置:支持多数据库配置:不要加key
        'default'=>array(
            array('h'=>'127.0.0.1', 'u'=>'username', 'pa'=>'password', 'd'=>'database', 'po'  => 3306),
            ),
        //其它数据库或者写库配置:和上面一样可配置多个数据库发址
        'wirteDb'=>array(
            array('h'=>'127.0.0.1', 'u'=>'username', 'pa'=>'password', 'd'=>'database', 'po'  => 3306),
            //array('h'=>'127.0.0.1', 'u'=>'username', 'pa'=>'yhepassword', 'd'=>'database', 'po'  => 3307),
            ),
    );

    /************************************** REDIS **************************************************************/
    public static $_REDIS = array(
        array('host' => '127.0.0.1', 'port' => 6379, 'auth'=>'password'),
    );

}
