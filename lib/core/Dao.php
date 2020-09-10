<?php

/*
 * Note:数据库连接操作核心底层 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\core;

use \PDO;
use \Config;
use \PDOException;
use \lib\core\Log;

final class Dao
{

    //MYSQL连接资源及资源名称
    private static $_Instance;

    //查询时间:连接资源名称
    private $initTime, $sourceName= null;
    
    //SQL及查询时间统计
    public static $sqlTimeArr = array();
    
    const CORELOGERR = 'coreError';

    //引入MYSQL配置
    private static function getConfig($db)
    {   
        $c = empty($c=Config::$_MYSQL[$db])?array():$c;
        
        //从连接配置中随机取出一个配置:注写库应该只有一个连接配置
        $c && $c = $c[rand(0, count($c)-1)];

        if(empty($c['h']) || empty($c['d'])  || empty($c['u']))
        {
            Log::record("mysql config:{$db} error!\n", self::CORELOGERR);
            
            exit("mysql config:{$db} error!");
        }
        
        return $c;
    }
    
    //取得连接资源
    public static function __getInstance($db)
    {
        //已连接资源直接返回
        if(isset(self::$_Instance[$db])) return self::$_Instance[$db];
        
        //未连接资源有需要时进行连接
        self::$_Instance[$db] = self::init($db, self::getConfig($db));
        
        $Dao = new self();
        
        $Dao->sourceName = $db;

        return $Dao;

    }

    //资源连接数据
    private static function init($link, $c)
    {
        //连接配置处理
        $port = empty($c['po'])?'3306':$c['po'];
        
        $db_dsn=sprintf("mysql:host=%s;port=%s;dbname=%s", $c['h'], $port, $c['d']);
        
        //执行连接
        try{
            
            $db = new PDO($db_dsn, $c['u'], $c['pa'], array(PDO::ATTR_PERSISTENT =>false, PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));

            $db->exec('set names utf8mb4');
           
        }catch(PDOException $e){
            
            Log::record("db:{$link} link fail, more:".$e->getMessage()."\n", self::CORELOGERR);

            exit("db:{$link} link fail, error, see ".self::CORELOGERR.".log");
            
        }

            return $db;
        
	}
 
    //初始查询时间 
    private function initTime()
    {
        $this->initTime = microtime(true);
    }
    
    //检查SQL中的危险字符：未使用prepare过滤
    private function checkSqlSafe($sql)
    {
        return $sql;
        
        $sql = rtrim(trim($sql) , ';');
        
        //SQL中禁用;#-待补充
        if( strpos($sql,';') !==false || strpos($sql,'#') !==false )
        {
            Log::record('dangerout char IN SQL:'.$sql."\n", self::CORELOGERR);

            throw (new PDOException('dangerout char IN SQL:'.$sql, '8088'));
            
            return false;
        }
        
    }

    //底层执行数据查询
    public function querySql($sql)
    {
		
	$this->initTime();
        
        $this->checkSqlSafe($sql);

	$result = @self::$_Instance[$this->sourceName]->query($sql, PDO::FETCH_ASSOC);

        $this->checkError($sql);

        $this->__affectedRows = 0;

        return $result->fetchAll();
		
    }

    //底层执行数据查询
    public function fetchOne($sql)
    {
        
        $this->initTime();
        
        $this->checkSqlSafe($sql);

        $result = @self::$_Instance[$this->sourceName]->query($sql, PDO::FETCH_ASSOC);

        $this->checkError($sql);

        $this->__affectedRows = 0;

        return $result->fetch();
        
    }
    
    //记录SQL操作记录
    private function logQuey($sql)
    {
        //非开发调试模式不记录这些数据
        if(Config::DEBUG)
        {
            $costTime = round(10000 * ( microtime(true) - $this->initTime ), 4);
            
            self::$sqlTimeArr[$this->sourceName][] = array($sql, $costTime);
        }
        
        return true;
        
    }
    
    //返回所有操作SQL及用时记录(需开启调试)
    public function getQueryed($db=null)
    {
        if($db)
        {
            return empty(self::$sqlTimeArr[$db])?array():self::$sqlTimeArr[$db];
        }
        
        return self::$sqlTimeArr;
    }

    //销毁连接资源
    public static function destructDao()
    {
        foreach(self::$_Instance as $key=>$db) self::$_Instance[$key] = NULL;
    }
    
    //异常处理
    protected function checkError($sql)
    {
        if(self::$_Instance[$this->sourceName]->errorCode() != '00000')
        {
            $error = self::$_Instance[$this->sourceName]->errorInfo();

            $logChar = 'SQL ERROR:'.$error[1].':'.$error[2]. ', IN SQL:'.$sql;
            
            Log::record($logChar."\n", self::CORELOGERR);

            throw (new PDOException($logChar, '8089'));

            return false;
            
        }
        
        $this->logQuey($sql);

    }

    //底层删/改执行
    public function executeSql($sql)
    {
        
	$this->initTime();
        
        $this->checkSqlSafe($sql);

	$result = self::$_Instance[$this->sourceName]->exec($sql);

	$this->checkError($sql);

	return $this->__affectedRows = $result;
	
    }
	
    //底层插入执行：返回自增ID
    public function insertSql($sql)
    {
        $this->initTime();
        
        $this->checkSqlSafe($sql);

        self::$_Instance[$this->sourceName]->query($sql);

        $this->checkError($sql);

        return self::$_Instance[$this->sourceName]->lastInsertId();
    }

    //条件拼接MYSQL类获取表结构
    private function buildCondition($conditions = array())
    {
        //空字符串及字符串时的返回
        if(!$conditions) return '';

        if($conditions && is_string($conditions)) return ' where '.$conditions .' ';
	
        //传入数组时进行处理
        foreach($conditions as $key=>$value)
        {
			
            $key=$this->safeChar($key);

            is_string($value) && $value=$this->safeChar($value);

            if(strpbrk($key, '<=>') !== false)
            {

                $arrCondition[] = is_numeric($value)?"{$key}{$value}":"{$key}'{$value}'";

            }elseif(strpos($key, ' like') !== false){

                $arrCondition[] = "{$key} '%{$value}%'";

            }elseif(strpos($key, ' in') !== false){

                $value = is_array($value)?implode("','", $value):$value;

                $arrCondition[] = "{$key} ('{$value}')";

            }else{

                $arrCondition[] = is_numeric($value)?"`{$key}`={$value}":"`{$key}`='{$value}'";

            }

        }
		
	    return ' where '.implode(' and ', $arrCondition) .' ';
		
    }
    
    //安全过滤
    private  function safeFilter($data)
    {
        if(is_string($data)) return $this->safeChar($data);
        
        foreach($data as $key => $val)
        {
            $data[$key] = $this->safeChar($val);
        }
        
        return $data;
    }

    //MYSQL数组数据转为字符中SQL
    private function buildArray($data)
    {

        $sql_array = array();

        foreach($data AS $key=>$value)
        { 
            if($value !== null)
            {
                $sql_array[] = '`'.$this->safeChar($key)."`='" . $this->safeChar($value) ."'";
            }else{
                $sql_array[] = '`'.$this->safeChar($key)."`= null ";
            }    
        }

        return implode(',',$sql_array);

    }
    
    //数据安全过滤
    private function safeChar($data)
    {
        //return @mysql_real_escape_string(rtrim($data));
        return rtrim($data);
    }

    //数据查询:只适用于简单的查询条件,$conditions可传数组或者字符串，排序，设置字段，限制条数:_order_field_limit_groupby
    public function select($table, $conditions=array(), $extend=array())
    {
    /*调用示例：
     * ->select('table', array('id'=>2), array('order'=>'id desc', 'limit'=>1, 'field'=>'id,name'))
     */
        
	$conditions=$this->buildCondition($conditions);
        
        $extend = $this->safeFilter($extend);
        
        $groupby = empty($extend['groupby'])?'' : " group by {$extend['groupby']} ";
        
        $order = empty($extend['order'])?'' : " order by {$extend['order']} ";

        $limit = empty($extend['limit'])?'' : " limit {$extend['limit']} ";

        $field = empty($extend['field'])?'*' : $extend['field'];

        //拼接SQL并执行
        $sql="select {$field} from {$table} {$conditions} {$groupby} {$order} {$limit}";

        return $this->querySql($sql);
	
    }

    //数据更新:
    public function update($table, $data, $conditions=array())
    {
    /*调用示例：
     * ->update('table', array('name'=>'john'), array('id'=>2))
     */
        
        $conditions = $this->buildCondition($conditions);

        $sql="update {$table} set ".$this->buildArray($data)." {$conditions}";

        $sql_array = NULL;

        return $this->executeSql($sql);
	
    }
    
    //数据插入更新:
    public function insertDuplicateUpdate($table, $data, $update)
    {
        $sql = "insert into {$table} set ".$this->buildArray($data)
                
               ." on duplicate key update ". $this->buildArray($update);

        $sql_array = NULL;

        return $this->executeSql($sql);
    }

    //删除表中所据
    public function delete($table, $conditions=array())
    {
		
        $conditions = $this->buildCondition($conditions);

        $sql="delete from {$table} {$conditions}";

        return $this->executeSql($sql);
		
    }

    //MYSQL表插入数组:
    public function insert($table, $data, $ignore=false)
    { 
        
        $ignorechar = $ignore?'ignore':'';
        
        $sql="insert {$ignorechar} into {$table} set ".$this->buildArray($data);

        $this->executeSql($sql);

        return self::$_Instance[$this->sourceName]->lastInsertId();
    
    }

    //取得记录总数:只用于简单的条件
    public function count($table, $conditions=array(), $field='')
    {
        
        $field == '' && $field = '*';
        
        $condition = $this->buildCondition($conditions);

        $field = $this->safeChar($field);
	
        $sql = "select count({$field}) as allnum from {$table} {$condition}";

        $result = $this->querySql($sql);

        return $result[0]['allnum'];
		
    }
	
    //取得记录总数:select sum($sumField) from $table where $con
    public function sum($table, $sumField='*', $conditions=array())
    {
        
        $condition = $this->buildCondition($conditions);

        $sql = "select sum({$sumField}) as allnum from {$table} {$condition} {$groupby}";

        $result = $this->querySql($sql);

        return $result[0]['allnum'];

    }

    //字段自增
    public function increment($table, $field, $conditions, $add=1)
    {	
        $add = intval($add);
        
        if(!$add) return false;
        
        $condition = $this->buildCondition($conditions);

        $add = $add>0?"+{$add}":"-{$add}";

        $sql="update {$table} set {$field}={$field}{$add} {$condition}";

        return $this->executeSql($sql);

    }
    
    //多字段自增:$incrArr=>array('field'=>1, 'field2'=>2).默认自增量为1
    public function incrementMany($table, $incrArr, $conditions)
    {
        if(!$incrArr) return false;
        
        $array = array();
        
        foreach($incrArr as $field=>$val)
        {
            $val = intval($val);
            
            !$val && $val =1;
            
            $array[] = "{$field}={$field}".($val>0?"+{$val}":"-{$val}");
        }
        
        $condition = $this->buildCondition($conditions);

        $sql="update {$table} set ". implode(',', $array)." {$condition}";

        return $this->executeSql($sql);
		
    }
    
    //事务相关的方法：开始
    public function beginTransaction()
    {
        self::$_Instance[$this->sourceName]->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        
        $startResult = self::$_Instance[$this->sourceName]->beginTransaction();
        
        if(!$startResult)
        {
            throw (new PDOException('start Transaction error', 8090));
        }
        
        return $startResult;
  
    }
    
    //事务相关的方法：提交
    public function commit()
    { 
        $comitRs = self::$_Instance[$this->sourceName]->commit();
        
        self::$_Instance[$this->sourceName]->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        
        if(!$comitRs)
        {
            throw (new PDOException('commit Transaction error', 8999));
        }
        
        return $comitRs;
    }
    
    //事务相关的方法：回滚
    public function rollBack()
    { 
        $rollRs = self::$_Instance[$this->sourceName]->rollBack();
        
        self::$_Instance[$this->sourceName]->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        
        if(!$rollRs)
        {
            throw (new PDOException('rollBack Transaction error', 8999));
        }
        
        return $rollRs;
    }

}
