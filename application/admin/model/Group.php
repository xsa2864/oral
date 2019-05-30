<?php
namespace app\admin\model;

use think\Model;

class Group extends Model
{
	public function GetCountNum($id='')
	{
		$count = db("manager")->where("yygroup_id",$id)->count();
		return $count;
	}
}