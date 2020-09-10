<?php

/*
 * Note:token更新控制器
 * Author:04007.cn
 * Date:2019-04-16
 */

namespace app\cron;

use \Config;
use \lib\{util\Params};

class TokenController extends BaseController
{

    //update app_access_token
    public function updateAction()
    {
        $this->_initRedis();

        //cli模式下输出信息
        $this->screen('完成token更新.');
    }
}
