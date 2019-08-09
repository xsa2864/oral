<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;

class OperationLog extends Model
{
	public function writeLog($unit_id=0,$manager_id=0,$request='',$url='')
	{
		if($url == 'admin/Operation/index'){
			return true;
		}
		unset($request["pic"]);
		unset($request["ClassesTime"]);
		unset($request['subtitle']);
		unset($request['content']);

		$data['unit_id'] 	= $unit_id;
		$data['manager_id'] = $manager_id;
		$data['url']		= $url;
		$data['op_name']	= db("auth_rule")->where("name",$url)->value("title");
		$data['param']		= json_encode($request);
		$data['add_time']	= time();
		$table_name = "op_log_".date("Y-m",time());
		self::makeTables($table_name);
		$rs = db($table_name)->insert($data);
	}

	public static function makeTables($table_name){
		$sql = "CREATE TABLE IF NOT EXISTS `t_".$table_name."` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `unit_id` INT(11) NOT NULL DEFAULT '0',
				  `manager_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
				  `url` varchar(50) NOT NULL COMMENT '操作路径',
				  `op_name` varchar(30) NULL DEFAULT NULL COMMENT '操作名称',
				  `param` TEXT NOT NULL COMMENT '参数',
				  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
				  PRIMARY KEY (`id`),
				  INDEX `unit_id` (`unit_id`)
				) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='操作日志';";
		$rs = Db::query($sql);
	}

	public function writeHisLog($arr=[])
	{
		$data['type']	  = $arr['type'];
		$data['note']	  = $arr['note'];
		$data['msg']	  = $arr['msg'];
		$data['status']	  = $arr['status'];
		$data['add_time'] = time();

		$table_name = "his_log_".date("Y-m",time());
		self::makeHisTables($table_name);
		$rs = Db::name($table_name)->insert($data);
	}

	public static function makeHisTables($table_name){
		$sql = "CREATE TABLE IF NOT EXISTS `t_".$table_name."` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '类型 1=医生 2=排班 3=患者',
					`note` TEXT NULL COMMENT '内容',
					`msg` VARCHAR(250) NULL DEFAULT NULL COMMENT '提示',
					`status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '转态 0=失败 1=成功',
					`dis_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
					`add_time` BIGINT(12) NOT NULL COMMENT '添加时间',
					PRIMARY KEY (`id`)
				)
				COMMENT='日志' COLLATE='utf8mb4_general_ci' ENGINE=MyISAM AUTO_INCREMENT=7;";
		$rs = Db::query($sql);
	}
}