<?php
namespace app\api\model;


/*==呼叫器操作==*/

class UserInfo 
{	
	public function getName(){
	    return "菜鸟教程";
	}

	public function getUrl(){
	    return "www.runoob.com";
	}

	public function add($a=0,$b=0)
	{
		return $a+$b;
	}
}
