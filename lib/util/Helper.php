<?php

/*
 * Note:辅助公用类 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\util;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;

class Helper
{

    /**
     * 将一个二维数组按照多个列进行排序，类似 SQL 语句中的 ORDER BY
     *
     * 用法：
     * @code php
     * $rows = sortByMultiCols($rows, array(
     *     'parent' => SORT_ASC, 
     *     'name' => SORT_DESC,
     * ));
     * @endcode
     *
     * @param array $rowset 要排序的数组
     * @param array $args 排序的键
     *
     * @return array 排序后的数组
     */
    static function sortByMultiCols($rowset, $args)
    {
        $sortArray = array();
        $sortRule = '';
        foreach ($args as $sortField => $sortDir)
        {
            foreach ($rowset as $offset => $row)
            {
                $sortArray[$sortField][$offset] = $row[$sortField];
            }
            $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
        }
        if (empty($sortArray) || empty($sortRule)) { return $rowset; }
        eval('array_multisort(' . $sortRule . '$rowset);');
        return $rowset;
    }

    
    //计算两个时间的日/时/分/秒差
    static function timediff($begintime, $endtime)
    {
        if($begintime >= $endtime)
        {
            return array("day" => 0,"hour" => 0,"min" => 0,"sec" => 0);
        }

        $timediff = $endtime-$begintime;
        $days = intval($timediff/86400);
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        $remain = $remain%3600;
        $mins = intval($remain/60);
        $secs = $remain%60;

        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        return $res;
    }

    static function curl_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    


    // Encrypt Function
    static function encrypt($encrypt, $key) {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $passcrypt = trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt, MCRYPT_MODE_ECB, $iv));
        $encode = base64_encode($passcrypt);
        return $encode;
    }

    // Decrypt Function
    static function decrypt($decrypt, $key) {
        $decoded = base64_decode($decrypt);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, trim($decoded), MCRYPT_MODE_ECB, $iv);
        return $decrypted;
    }

    static function sign($query_string)
    {
        ksort($query_string, SORT_STRING);
        $string = http_build_query($query_string);
        $sign = sha1(\Config::PAY_MCRYPT_KEY.$string);
        return $sign;
    }
    static function md5sign($queryArr)
    {
        ksort($queryArr, SORT_STRING);
        $string = http_build_query($queryArr);
        $sign = md5($string.'&key='.\Config::PAY_WUXIAN_KEY);
        return $sign;
    }
    static function openurl($url)
    {
        $opts = array(
        'http'=>array(
        'method'=>"GET",
        'timeout'=>2,
        )
        );
        $context = stream_context_create($opts);
        $html = file_get_contents($url, false, $context);
        return $html;
    }
    /**
     * post data to url
     *
     * @param string $host
     * @param int $port
     * @param array $data
     * //要post的数据
        $data = array(
        'User_id'=>$userid,
        'Order_id'=>$orderid,
        'Service_type'=>$Service_type,
        'Time_quota'=>$Time_quota,
        'Data_quota'=>$Data_quota,
        'Valid_since'=>$Valid_since,
        'Valid_through'=>$Valid_through,
        'Expire_at'=>strtotime("+2 year"),//##Mark##这个要和产品商量
        'Create_at'=>$orders['update_time'],
        );
     * @return string
     */
    static function posturl($host, $port, $path, $timeout, $data, $logfile)
    {
        $flag = 0;
        $post = '';
        $errno = '';
        $errstr = '';
        $response = '';

        $post = urldecode(http_build_query($data));
        $length = strlen($post);
        //创建socket连接
        $fp = fsockopen($host,$port,$errno,$errstr,$timeout);
        if ($fp) {
            error_log(date("Y-m-d H:i:s").__LINE__."\n",3,$logfile);
            stream_set_blocking($fp, FALSE);
            stream_set_timeout($fp,$timeout);
            error_log(date("Y-m-d H:i:s").__LINE__."\n",3,$logfile);
            //构造post请求的头
            $header  = "POST {$path} HTTP/1.1\r\n";
            $header .= "HOST:".\Config::$FEE_POSTORDER."\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: ".$length."\r\n";
            $header .= "Connection: close\r\n\r\n";
            //添加post的字符串
            $header .= $post."\r\n";
            //发送post的数据
            fputs($fp,$header);
            $info = stream_get_meta_data($fp);
            if ($info['timed_out']) {
                fclose($fp);
                $response = -1;
                error_log(date("Y-m-d H:i:s")."::Connection Timed Out! \n",3,$logfile);
            }
            $inheader = 1;
            $countEmpty = 0;
            while (!feof($fp)) {
                $response .= fgets($fp, 1024);
                if (empty($response)) {
                    $countEmpty++;
                }
                $info = stream_get_meta_data($fp);
                if ($info['timed_out']) {
                    fclose($fp);
                    $response = -1;
                    error_log(date("Y-m-d H:i:s")."::Connection Timed Out! \n",3,$logfile);
                    fclose($fp);
                    return $response;
                }
                //读取一定次数后报错退出
                if ($countEmpty>10) {
                    $response = -3;
                    error_log(date("Y-m-d H:i:s")."::一直返回空值，请查询对方服务器状况 \n",3,$logfile);
                    fclose($fp);
                    return $response;
                }
                @ob_flush();
                @flush();
            }
            $str = json_encode($data);
            error_log(date("Y-m-d H:i:s")."::{$str} \n",3,$logfile);
            error_log(date("Y-m-d H:i:s")."::{$response} \n",3,$logfile);

            fclose($fp);
        } else {
            $response = -2;
            error_log(date("Y-m-d H:i:s")."::ERRNO:{$errno} ## {$errstr} \n",3,$logfile);
        }
        return $response;
    }
    
    public static function getRealIp() {
        $pattern = '/^(\d{1,3}\.){3}\d{1,3}/';
        if (isset ( $_SERVER ["HTTP_X_FORWARDED_FOR"] ) && preg_match_all ( $pattern, $_SERVER ['HTTP_X_FORWARDED_FOR'], $mat )) {
            foreach ( $mat [0] as $ip ) {
                //得到第一个非内网的IP地址
                if ((0 != strpos ( $ip, '192.168.' )) && (0 != strpos ( $ip, '10.' )) && (0 != strpos ( $ip, '172.16.' ))) {
                    return $ip;
                }
            }
            return $ip;
        } else {
            if (isset ( $_SERVER ["HTTP_CLIENT_IP"] ) && preg_match ( $pattern, $_SERVER ["HTTP_CLIENT_IP"] )) {
                return $_SERVER ["HTTP_CLIENT_IP"];
            } else {
                return $_SERVER ['REMOTE_ADDR'];
            }
        }
    }

    public static function zip($fromfile, $tofile) {
        $zip = new \ZipArchive();
        if ($zip->open($tofile, \ZIPARCHIVE::CREATE)!==TRUE) {
            return false;
        }
        $zip->addFile($fromfile);
        $zip->close();
        return true;
    }

    public static function curl_open_url($url,$connecttimeout=1000,$executetimeout=1000,$logfile)
    {
        Helper::log(' Start:'.$url,$logfile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connecttimeout);//尝试连接等待的时间
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $executetimeout);//设置cURL允许执行的最长毫秒数
        $head = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($httpCode < 400)
        {
            $header = '';
            if ($httpCode == '200') {
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($head, 0, $headerSize);
                $body = substr($head, $headerSize);
            }

            if (empty($body)) {
                if(curl_errno($ch))
                {
                    Helper::log(curl_error($ch),$logfile);
                } else {
                    Helper::log($header,$logfile);
                }
                curl_close($ch);
                Helper::log(' End;',$logfile);
                return false;
            } else {
                curl_close($ch);
                Helper::log($body,$logfile);
                Helper::log(' End;',$logfile);
                return $body;
            }
        } else {
            if(curl_errno($ch))
            {
                Helper::log(curl_error($ch),$logfile);
            } else {
                Helper::log($httpCode,$logfile);
            }
            Helper::log(' End;',$logfile);
            return false;
        }
    }

    /**
     * 微信支付工具函数
     */
    
    public static function paraFilter(Array $params) {
        $result=array();
        $flag=$params[\Config::TRADE_FUNCODE_KEY];
        foreach($params as $key => $value){
            if (($flag==\Config::TRADE_FUNCODE)&&!($key==\Config::TRADE_FUNCODE_KEY||$key==\Config::TRADE_DEVICETYPE_KEY
            ||$key==\Config::TRADE_SIGNTYPE_KEY||$key==\Config::TRADE_SIGNATURE_KEY)){
                $result[$key]=$value;
                continue;
            }
            if(($flag==\Config::NOTIFY_FUNCODE||$flag==\Config::FRONT_NOTIFY_FUNCODE)&&!($key==\Config::SIGNTYPE_KEY||$key==\Config::SIGNATURE_KEY)){
                $result[$key]=$value;
                continue;
            }
            if (($flag==\Config::QUERY_FUNCODE)&&!($key==\Config::TRADE_SIGNTYPE_KEY||$key==\Config::TRADE_SIGNATURE_KEY
            ||$key==\Config::SIGNTYPE_KEY||$key==\Config::SIGNATURE_KEY)) {
                $result[$key]=$value;
                continue;
            }
        }
        return $result;
    }

    public static function buildSignature(Array $para){

        $prestr=self::createLinkString($para, true, false);
        $prestr.=\Config::TRADE_QSTRING_SPLIT.md5(\Config::$secure_key);
        return md5($prestr);
    }
    public static function createLinkString(Array $para,$sort,$encode) {
        if ($sort) {
            $para=self::argSort($para);
        }
        $linkStr = '';
        foreach ($para as $key => $value){
            if ($encode) {
                $value=urlencode($value);
            }
            $linkStr.=$key.\Config::TRADE_QSTRING_EQUAL.$value.\Config::TRADE_QSTRING_SPLIT;
        }
        $linkStr=substr($linkStr, 0,count($linkStr)-2);
        return $linkStr;
    }
    private static function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 发送信息
     *
     * @param type $req_content 请求字符串
     * @param type $url 请求地址
     * @return type 应答消息
     */
    static function sendMessage($req_content,$url) {
        if(function_exists("curl_init")){
            $curl=  curl_init();
            $option=array(
            CURLOPT_POST=>1,
            CURLOPT_POSTFIELDS=>$req_content,
            CURLOPT_URL=>$url,
            CURLOPT_RETURNTRANSFER=>1,
            CURLOPT_HEADER=>0,
            CURLOPT_SSL_VERIFYPEER=>  \Config::VERIFY_HTTPS_CERT,
            CURLOPT_SSL_VERIFYHOST=>  \Config::VERIFY_HTTPS_CERT
            );
            curl_setopt_array($curl, $option);
            $resp_data=  curl_exec($curl);
            if($resp_data==FALSE){
                curl_close($curl);
            }else{
                curl_close($curl);
                return $resp_data;
            }
        }
    }

    /**
     * 发送邮件
     * @param $subject 邮件标题
     * @param $message  邮件内容
     * @param array $tos 接收用户数组 array('xxx@qq.com','xxxxxx@qq.com',....);
     * @return \Nette\Mail\SmtpException
     */
    public static function sendMail($subject, $message, $tos=array()){
        try{
            if(empty($tos) || !is_array($tos) || empty($subject) || empty($message))
                return false;
            $mail = new Message;
            $mail->setFrom(\Config::$_MAIL['username']);
            foreach ($tos as $username)
                $mail->addTo($username);
            $mail->setSubject($subject)->setBody($message);
            (new SmtpMailer(\Config::$_MAIL))->send($mail);
        }
        catch(\Nette\Mail\SmtpException $e){
            throw $e;
        }

    }

    /**
     * 高效率计算文件行数
     * @param $file
     * @return int
     */
    public static function countLine($file){
        if(!file_exists($file))
            return 0;
        $fp=fopen($file, "r");
        $i=0;
        while(!feof($fp)) {
            //每次读取2M
            if($data=fread($fp,1024*1024*2)){
                //计算读取到的行数
                $num=substr_count($data,"\n");
                $i+=$num;
            }
        }
        fclose($fp);
        return $i;
    }
    
        /**
     * 检查敏感词
     * 精确匹配
     */
    static function checkSensitive($str) {
        $words = @include(__DIR__ . "/tables/sensitive_words.php");
        if (!empty($words) && isset($words[trim($str)])) {
            return true;
        }
        return false;
    }

    static function recursiveUnset($arr, $key) {
        if (!is_array($arr)) return $arr;
        foreach ($arr as $k => & $v) {
            if ($k === $key) {
                unset($arr[$k]);
            } else {
                $v = self::recursiveUnset($v, $key);
            }
        }
        return $arr;
    }
    
    /**
     * 将搜索词按alt_table里的映射进行替换
     * 具体的映射规则在/web/dataSource/tables/alt_table.php里可以看到
     *
     * homophone_table(.mini).php是比alt_table更全的表，包含对同音多音字的转换，如果有需要可以使用（但是文件更大，转换和搜索效率会更低）
     *
     * str:
     *      要进行转换的字符串
     * return_as_array:
     *      返回一个数组还是字符串。默认false返回字符串
     * brace_polyphone_remove_bar:
     *      去除多音字value中的竖线(|)，并为其加上{}。这个参数仅在建索引时用到，且只有使用同音多音字转换($use_homophone=true)时才有效
     */
    static function altSearchStr($str, $return_as_array=false, $brace_polyphone_remove_bar=false) {
        // 是否启用同音多音字转换。默认不启用(false)，若要启用就改成true
        // 改完后搜索词会按新规则转换，而索引字段alt的转换只有修改了索引机器上的该变量并跑完索引之后才会生效
        $use_homophone = true;

        if ($use_homophone) {
            $table_name = 'homophone_table.incomplete.mini';
        } else {
            $table_name = 'alt_table';
        }
        $table = @include(__DIR__ . "/tables/{$table_name}.php");
        if (!$table || !is_array($table)) {
            throw new Exception("table is empty! - {$table_name}", -1);
        }

        // return str_replace(array_keys($table), array_values($table), $str); // 效率太低
        $mapped = array();

        $chars  = self::utf8StrSplit($str);
        foreach ($chars as $char) {
            if (isset($table[$char])) {
                if ($use_homophone && $brace_polyphone_remove_bar && strpos($table[$char], '|')) {
                    $mapped[] = "{" . str_replace('|', '', $table[$char]) . "}";
                } else {
                    $mapped[] = $table[$char];
                }
            } else {
                $mapped[] = $char;
            }
        }
        
        if ($return_as_array) {
            return $mapped;
        } else {
            return implode('', $mapped);
        }
    }
    
        /**
     * 汉字转拼音（汉字编码是utf-8）
     *
     * str:
     *      要进行转换的字符串
     * fuzzy:
     *      是否启用转模糊音，默认开启
     * strict:
     *      是否启用严格模式，默认开启。在严格模式下，任何没有匹配到拼音的字符都被忽略，不出现在结果中；否则会把这些字符按原样添加到结果里
     * py_table:
     *      使用指定的拼音表，既可以是数组，也可以是一个php文件的路径。格式见默认的 /web/dataSource/tables/py_table.php
     */
    static function pinyin($str, $fuzzy=true, $strict=true, $py_table=array()) {
        if (!$py_table) {
            $py_table = @include(__DIR__ . '/tables/py_table.php'); // 用require的话，当文件不存在时会引发一个 fatal error
        } else {
            if (!is_array($py_table)) {
                $py_table = @include($py_table);
            }
        }
        if (!$py_table || !is_array($py_table)) {
            throw new Exception('pinyin table is empty!', -1);
        }

        $py_list = array();
        $py_temp = array();
        $chars = self::utf8StrSplit($str);

        foreach ($chars as $char) {
            $char_pys = array();
            if (isset($py_table[$char])) {
                $char_pys = explode(',', $py_table[$char]);
            } else {
                if ($strict) {
                    continue;
                } else {
                    $char_pys = array($char);
                }
            }

            foreach ($char_pys as $py) {
                if (empty($py_list)) {
                    $py_temp[] = $py;
                } else {
                    foreach ($py_list as $v) {
                        $py_temp[] = "{$v}{$py}";
                    }
                }
            }

            $py_list = $py_temp;
            $py_temp = array();
        }

        return $py_list;
    }
    
    static function getUrlContents($url, $timeout=1, $headers=array()) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $contents = trim(curl_exec($curl));
        curl_close($curl);
        return $contents;
    }
    
        static function utf8StrExtendSpace($str) {
        return implode(' ', self::utf8StrSplit($str));
    }

    static function utf8StrSplit($str) {
        $split = 1;
        $array = array();
        for($i = 0; $i < strlen($str); ) {
            $value = ord($str[$i]);
            if ($value < 128) {
                $split = 1;
            } else {
                if ($value >= 192 && $value <= 223) {
                    $split = 2;
                } elseif ($value >= 224 && $value <= 239) {
                    $split = 3;
                } elseif ($value >= 240 && $value <= 247) {
                    $split = 4;
                }
            }
            $key = NULL;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array,$key);
        }
        return $array;
    }
    
    //-----------------------------------------以下方法为未使用-------------------------

    //uid 校验
    static function isUuid($uid)
    {
        if (preg_match("/^[0-9A-Z]{8}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{12}$/", trim($uid,'{}'))) {
            return true;
        } else {
            return false;
        }
    }

    //
    static function getArrayArg($array, $name, $default = null){
        return isset($array[$name])? trim($array[$name]) : $default;
    }

    //-----------------------------------------用户中心加解密算法-------------------------
    //token加密算法
    static function tokenDecode($str)
    {
        $arrResult = [];
        $text = substr($str,0,-20);
        $iv   = substr($str,-20,-4);
        if (strlen($iv) != 16) { // iv必须是16位
            return $arrResult;
        }
        $encryptedData = base64_decode(strtr($text, '-_', '+/'));
        $result = openssl_decrypt($encryptedData ,'aes-128-cbc' ,\Config::$_SSO['key'], OPENSSL_RAW_DATA, $iv);
        if (!$result) {
            return $arrResult;
        } 
        $arrResult = array_combine(array('userid','username','login_ip','ctime','dev_id','client','appid'), explode('||', $result));
        if(!preg_match('/^\d*$/', $arrResult['userid']))  return [];
        return $arrResult;
    }

    //调转
    static function redirect($url)
    {
        header('HTTP/1.1 302 Moved Temporarily');
        header('Location: ' . $url);
        exit;
    }
}
