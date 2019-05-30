<?php
namespace app\api\model;

use think\Db;
use think\Model;

class Log 
{	
	// 判断是否存在表
	public function is_tables(){
		$flag = false;
        $table_name = 't_log_'.date("Y-m-d",time());
        $sql = "SHOW TABLES LIKE '$table_name'";
        $rs = Db::query($sql);
        if(empty($rs)){
            $hql = "CREATE TABLE `$table_name` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `url` VARCHAR(50) NOT NULL COMMENT '请求url',
                            `request` VARCHAR(50) NOT NULL COMMENT '请求参数 ',
                            `result` VARCHAR(250) NOT NULL COMMENT '返回结果',
                            `addtime` INT(10) NOT NULL DEFAULT '0' COMMENT '请求时间',
                            PRIMARY KEY (`id`)
                        )
                        COMMENT='接口日志'
                        COLLATE='utf8_general_ci'
                        ENGINE=MyISAM;";
            Db::query($hql);
            $rs = Db::query($sql);
            if($rs){
                $flag = true;
            }
        }else{
            $flag = true;
        }
        return $flag;
	}

	// 保存日志
    public function save_log($result=''){
    	$flag = $this->is_tables();
    	if($flag){    		
	    	$data['url'] 		= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	    	$data['request']	= json_encode(input());
	    	$data['result']	 	= $result;
	    	$data['addtime'] 	= time();
	    	$table_name = 't_log_'.date("Y-m-d",time());
	        db("$table_name")->insert($data);
    	}
    }
}