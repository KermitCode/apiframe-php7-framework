<?php

/*
 * Note:模型核心底层类 
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace lib\core;

use lib\core\Dao;
use lib\core\Service as Service;

class Model
{
    
    //关联的表及主键标识
    public $primaryKey = 'id';
    public $table='';
    
    //默认使用读库连接:对应配置文件中的default配置
    private static $_defaultDb = 'default';
    
    //当前连接资源及资源池:默认使用db.
    protected $db;
    protected static $sourceArr = array();
    
    //表前缀
    const PRE_CASH = 'btt_cash_';
    const PRE_DISCIPLE= 'btt_disciple_';
    const PRE_GOLD = 'btt_gold_';
    const PRE_MONEY = 'btt_money_';
    static $tableArr = array();
    
    //模型属性
    public $Attributes=array();
    
    //模型取数据时的条件
    private $Conditions=array();

    //模型实例化查询时必须指定表
    private function __checkTable()
    {
        if(!$this->table)
        {
            exit("no table assign in ModelClass:".get_called_class());
        }
    } 
    
    //实例化模型
    public function __construct($db = null)
    {
        $this->__checkTable();

        !$db && $db = self::$_defaultDb;
        
        if(!isset(self::$sourceArr[$db]))
        {
            self::$sourceArr[$db] = Dao::__getInstance($db);
        }
        
        foreach(self::$sourceArr as $dbkey => $dbobj)
        {
            if($dbkey == self::$_defaultDb)
            {
                $this->db = &self::$sourceArr[$dbkey];
            }else{
                $this->$dbkey = &self::$sourceArr[$dbkey];
            }
        }
  
        $this->primary && $this->primaryKey = $this->primary;
    }
    
    //取得model：避免重复加载
    protected function getModel( $model = 'Normal' )
    {
        if(!empty(Service::$ModelPool[$model]))
        {
            return Service::$ModelPool[$model];
        }

        return Service::createModel( $model );
    }
    
    //通用取得用户对应的数据表,$table_pre参数必须使用self::PRE_GOLD格式传入
    public function getTable($table_pre, $userid)
    {
        //已经得出过用户的数据表
        if(!empty(self::$tableArr[$userid][$table_pre]))
        {
            return self::$tableArr[$userid][$table_pre];
        }
        
        return self::$tableArr[$userid][$table_pre] = $table_pre.substr($userid, -1);
    }
    
    //初始化写库链接
    public function initDb($dbkey='db', $alsoWrite = false)
    {
        if(!isset(self::$sourceArr[$dbkey]))
        {
            self::$sourceArr[$dbkey] = Dao::__getInstance($dbkey);
        }
       
        foreach(Service::$ModelPool as $model=>$status)
        {
            empty(Service::$ModelPool[$model]->$dbkey) && Service::$ModelPool[$model]->$dbkey = self::$sourceArr[$dbkey];
        }
        
        return;
    }

    //直接读取DB进行查询
    public static function DB($dbkey = null)
    {
        if(!isset(self::$sourceArr[$dbkey]))
        {
            self::$sourceArr[$dbkey] = Dao::__getInstance($dbkey);
        }
        
        return self::$sourceArr[$dbkey];
    }

    //直接执行原始查询SQL
    public function querySql($sql)
    {
            return $this->db->querySql($sql);
    }

    //直接执行原始增改SQL
    public function execSql($sql)
    {
            return $this->db->executeSql($sql);
    }
    
    //通过ID取一条记录:
    public function getOneById($id, $field='*')
    {
        $this->__checkTable();
        
        $data = $this->db->select($this->table, array($this->primaryKey => $id), array('field'=>$field) );
        
        return $data?current($data):array();
    }

    //获取一行数据
    public function getOne($conditions, $fields='*')
    {
        $this->__checkTable();

        $data = $this->db->select($this->table, $conditions, array('field'=>$fields) );

        return $data?current($data):array();

    }
    
    //获取全部数据
    public function getAll()
    {
        $this->__checkTable();

        return $this->db->select($this->table);
    }

    //获取数据
    public function select($conditions=array(), $extend=array())
    {

        $this->__checkTable();

        return $this->db->select($this->table, $conditions, $extend );

    }

    //更新模型数据
    public function update($data, $conditions= array())
    {

        $this->__checkTable();

        return $this->db->update($this->table, $data, $conditions );

    }
    
    //更新模型数据
    public function insertDuplicateUpdate($data, $update)
    {

        $this->__checkTable();

        return $this->db->insertDuplicateUpdate($this->table, $data, $update);

    }
    
    //通过ID更新一条记录:
    public function updateById($id, $data)
    {

        $this->__checkTable();

        return $this->db->update($this->table, $data, array($this->primaryKey => $id) );

    }

    //删除模型数据
    public function delete($conditions)
    {

        $this->__checkTable();

        return $this->db->delete($this->table, $conditions);

    }
    
    //删除模型数据
    public function deleteById($id)
    {

        $this->__checkTable();

        return $this->db->delete($this->table, array($this->primaryKey => $id) );

    }

    //模型插入数据
    public function insert($data)
    {

        $this->__checkTable();

        return $this->db->insert($this->table, $data );

    }

    //数据自增
    public function increment($field, $conditions, $add=1)
    {

        $this->__checkTable();

        return $this->db->increment($this->table, $field, $conditions, $add);

    }

    //查询数量
    public function count($conditions=array(), $field='')
    {

        $this->__checkTable();

        return $this->db->count($this->table, $conditions, $field);

    }

    //表字段求和
    public function sum($field='*', $conditions=array())
    {

        $this->__checkTable();

        return $this->db->sum($this->table, $field, $conditions);

    }
    
    //读出SQL日志
    public function getQueryed($db=null)
    {
        return $this->db->getQueryed($db);
    }
	
}
