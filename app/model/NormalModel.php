<?php

/*
 * Note:通用模型
 * Author:04007.cn
 * Date:2019-04-10
 */

namespace app\model;

use lib\core\Model;

//基础通用模型，包括使用方法示例
class NormalModel extends Model
{
    
    //此模型为公用模型，一些表只需要进行简单的读取，可不用建立模型文件，都在此处调用实现
    public $table = 'normal';
    public $primary = 'id';

    
    //初始化其它数据库
    public function initDefaultdb()
    {
        $this->initDb('default');
    }
    
    //MODE层操作示例
    public function getAuthorizes($uid)
    {
        //测试不返回
        return array();
        
        //直接通过条件select查询
        return $this->select(
            array('id'=>$id),
            array('order'=>'id desc')
            );
        
        //可以直接getOne读取单条记录
        return $this->getOne(array('id'=>$id));
        
        //可以直接写SQL操作读取，$this->db中的db可根据上方initDb中初始化的数据随意切换MYSQL
        $sql = "select * from test";
        return $this->db->querySql($sql);
        
        //复杂条件查询，包括in查询等。
        $conditions = array(
            'deadline in'=>$dateArr,
            'typeid'=>1,
            'status'=>0,
            );
        $extend = array(
            'field'=>'id,name',
            );
        return $this->select($conditions, $extend);
        
        //直接删除
        return $this->delete(
            array(
                'id'=>$id,
            )
        );
        
        //大小比较操作及in操作删除
        return $this->delete(
            array(
                "UNIX_TIMESTAMP(probation_deadline)<"=>strtotime("-6months"),
                "syncid not in"=>$whiteList,
                )
            );
        
        //直接通过ID取数据
        $data = $this->getOneById(1);
        //查询SQL
        $data = $this->querySql("select * from btt_test where id=2");
        $data = $this->select(array('id'=>1));
        //直接取表记录条伯
        $data = $this->count();
        
        //插入SQL并返回自增ID
        $insert_id = $this->writedb->insertSql("insert into btt_test(name) values('第4条测试数据')");
        $data = $this->getOne(array('id'=>$insert_id));
        
        //执行删除SQL
        $data = $this->writedb->executeSql("delete from btt_test where id=".$insert_id);
        
        //更新操作
        $modify = array('number'=>999);
        $data = $this->writedb->update('btt_test', $modify, array('id'=>9));

        //字段自增操作
        $this->writedb->increment('btt_test', 'number', array('id'=>111), 2);
        return $this->writedb->update('btt_device', $data, $conditions);
        return $this->writedb->insert('btt_device', $data);
        
        //表直接插入数组数据
        $this->insert($table, $data);
        
        //自增1
        $this->increment($field, $conditions, $add=1);
        //更多的方法可以查看直接model底层类中的方法
    }


}
