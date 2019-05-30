<?php
namespace app\api\controller\a1;

use think\Controller;
use think\Request;
use think\DB;
use think\facade\Cookie;

class Article extends Controller
{
	// 获取文章列表
	public function getList()
	{
		$cate_id = input("cate_id",0);
		$page = input("page",1);
		$page_size = 10;
		$where[] = ['cate_id','=',$cate_id];
		$result = DB::name("article")->field("content",true)->where($where)->page($page,$page_size)->select();
		$count = DB::name("article")->where($where)->count();
		$max_page = ceil($count/$page_size);
		$data = array();
		$list = array();
		if($result){
			foreach ($result as $key => $value) {
				$value['url'] = url('app/article/detail',['id'=>$value['id']]);
				$value['addtime'] = date("Y-m-d H:i",$value['addtime']);
				$list[] = $value;
			}
		}
		$data['list'] = $list;
		$data['params'] = ['cate_id'=>$cate_id];
		$data['pages'] = $max_page;
		return json($data);
	}

	// 获取文章内容
	public function getDetail()
	{
		$id 		= input("id",0);
		$cate_id 	= input("cate_id",0);
		$where = array();
		if($id){
			$where[] 	= ['id','=',$id];
		}
		if($cate_id){
			$where[] 	= ['cate_id','=',$cate_id];
		}
		$result = DB::name("article")->where($where)->find();
		return json($result);
	}
}