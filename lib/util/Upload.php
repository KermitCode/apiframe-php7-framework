<?php

/*
 * Note:上传辅助处理类
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\util;

use Config;
use lib\core\Log;

class Upload 
{
    //上传类型
    public static $type = 'jpg,jpeg,gif,png';
    //上传路径
    public static $path = STATIC_PATH . 'images/';
    
    /**
     * 执行上传
     * @param null $fieldName 字段名
     * @return array|bool
     * @throws Exception
     */
    static function doUpload($subPath, $uploadFiles)
    {
        if (!is_dir(self::$path.$subPath) && !mkdir(self::$path . $subPath, 0755, true)) {
            return ['status'=>0, 'error'=>"上传目录创建失败"];
        }

        $info = getimagesize($uploadFiles['tmp_name']);
        $uploadFiles["ext"] = str_replace('image/', '', $info['mime']);
        if(strtolower($uploadFiles['ext'])=='jpeg')
        {
            $uploadFiles["ext"] = 'jpg';
        }
        $checkStatus = self::checkFile($uploadFiles);

        if (!$checkStatus['status']) 
        {
            return $checkStatus;
        }
        return self::save($subPath, $uploadFiles);
    }
    /**
     * 储存文件
     *
     * @param string $file 储存的文件
     *
     * @return boolean
     */
    static function save($subPath, $file)
    {
        $fileName = md5(file_get_contents($file['tmp_name'])) . "." . $file['ext'];
        $filePath = self::$path . $subPath .'/'. $fileName;
        if (!move_uploaded_file($file['tmp_name'], $filePath) && is_file($filePath)) 
        {
            return ['status'=> 0, 'error'=>'移动临时文件失败','data'=>[]];
        }
        $img = ['time' => time(), 'size'=>$file['size'], 'ext'=>$file['ext'], 'file'=>$fileName];
        $img['url'] = sprintf('%s/%s/%s', Config::IMG_URL, $subPath, $fileName);
        return ['status'=> 1, 'error'=>'','data'=>$img];
    }

    /**
     * 文件合法性校验
     * @param $file
     * @return bool
     */
    static function checkFile($file)
    {
        if ($file['error'] != 0) 
        {
            return ['status'=> 0, 'error'=>$file['error']];
        }
        
        $types = [];
        if (!is_array(self::$type)) 
        {
            $types = explode(',', self::$type);
        }
        if (!in_array(strtolower($file['ext']), $types)) {
            return ['status'=> 0, 'error'=> '文件类型错误'];
        }
        if (strstr(strtolower($file['type']), "image") && !getimagesize($file['tmp_name']))
        {
            return ['status'=> 0, 'error'=> '上传内容不是一个合法图片'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['status'=> 0, 'error'=> '抱歉，图片错误！'];
        }
        return ['status'=>1, 'error'=>''];
    }
}
