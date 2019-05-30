<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;

class OperationLog extends Model
{
	public function writeLog($manager_id=0,$request='',$url='')
	{
		if($url == 'admin/Operation/index'){
			return true;
		}
		unset($request["pic"]);
		unset($request["ClassesTime"]);
		unset($request['subtitle']);
		unset($request['content']);

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
				  `manager_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
				  `url` varchar(50) NOT NULL COMMENT '操作路径',
				  `op_name` varchar(30) NULL DEFAULT NULL COMMENT '操作名称',
				  `param` TEXT NOT NULL COMMENT '参数',
				  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='操作日志';";
		$rs = Db::query($sql);
	}
}