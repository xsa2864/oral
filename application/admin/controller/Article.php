<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Article extends Base
{
	// 文章列表
    public function articleList(){
    	$where = array();
        if($this->userid!=1){
            $where['a.unitid'] = $this->unitid;
        }
        $list = db("article")->alias("a")
        		->field("a.*,u.unitname,ac.name")
        		->leftJoin("article_cate ac","ac.id=a.cate_id")
                ->leftJoin("unit u","u.UnitId=ac.unitid")
        		->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
    	return $this->fetch('articleList');
    }
	// 文章管理
    public function article()
    {    
    	$id = input("id",0);
    	$where = array();
        $wh = array();
        if($this->userid!=1){
            $where['unitid'] = $this->unitid;
            $wh[] = ['ac.unitid','=',$this->unitid];
        }
    	$where['id'] = $id;
    	$result = db("article")->where($where)->find();
    	$cate = db("article_cate")
                ->alias("ac")
                ->field("ac.*,u.unitname")
                ->leftJoin("unit u","u.UnitId=ac.unitid")
                ->where($wh)
                ->order("u.unitid ace")
                ->select();
    	$this->assign("cate",$cate);
    	$this->assign("list",$result);
    	return $this->fetch('articleEdit');
    }
    // 保存文章
    public function articleSave(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

    	$id = input("id",0);
    	$data['title']     = input("title","");
    	$data['subtitle']  = input("subtitle","");
    	$data['cate_id']   = input("cate_id",0);
        $data['head_img']  = input("head_img","");
    	$data['content']   = input("content","");
    	$data['status']    = input("status",0);

        if(empty($data['title'])){
            $re_msg['msg'] = '文章标题不能为空';
            echo json_encode($re_msg);exit;
        }

        if(empty($data['cate_id'])){
            $re_msg['msg'] = '请选择文章分类';
            echo json_encode($re_msg);exit;
        }

    	if($id>0){
    		$rs = db("article")->where("id",$id)->update($data);
    		if($rs){
    			$re_msg['success'] = 1;
        		$re_msg['msg'] = '更新成功';
    		}else{
    			$re_msg['msg'] = '更新失败';
    		}
    	}else{
	    	$data['unitid'] = $this->unitid;
	    	$data['manager_id'] = $this->userid;
    		$data['addtime'] = time();
    		$rs = db("article")->insertGetId($data);
    		if($rs){
    			$re_msg['success'] = 2;
        		$re_msg['msg'] = '添加成功';
    		}
    	}
    	echo json_encode($re_msg);
    }
    // 文章分类列表
    public function cateList(){
    	$where = array();
        if($this->userid!=1){
            $where['a.unitid'] = $this->unitid;
        }
        $list = db("article_cate")->alias("a")
        		->field("a.*,u.unitname")
        		->leftJoin("unit u","u.UnitId=a.unitid")
        		->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
    	return $this->fetch('cateList');
    }
    // 编辑分类
    public function cateEdit(){
    	$id = input("id",0);
        $where = array();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }
        $unit = db("unit")->where($where)->select();
        $list = db("article_cate")->where("id=$id")->find();
        $this->assign("unit",$unit);
    	$this->assign("list",$list);
    	return $this->fetch('cateEdit');
    }
    // 文章分类保存
    public function cateSave(){
		$re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

    	$id = input("id",0);
    	$data['name'] = input("name","");
    	$data['status'] = input("status",0);
        $data['unitid'] = input("unitid",0);
        
        if($this->userid!=1){            
            if(empty($data['unitid'])){
                $re_msg['msg'] = '请选择单位';
                echo json_encode($re_msg);exit;
            }
        }

        if(empty($data['name'])){
            $re_msg['msg'] = '分类名称不能为空';
            echo json_encode($re_msg);exit;
        }
    	if($id>0){
    		$rs = db("article_cate")->where("id",$id)->update($data);
    		if($rs!==false){
    			$re_msg['success'] = 1;
        		$re_msg['msg'] = '更新成功';
    		}else{
    			$re_msg['msg'] = '更新失败';
    		}
    	}else{
	    	// $data['unitid'] = $this->unitid;
    		$rs = db("article_cate")->insertGetId($data);
    		if($rs){
    			$re_msg['success'] = 2;
        		$re_msg['msg'] = '添加成功';
    		}
    	}
    	echo json_encode($re_msg);
    }
    // 删除文章
    public function articleDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$where = array();
        if($this->userid!=1){
            $where['unitid'] = $this->unitid;
        }
        $where['id'] = $id;
    	$rs = db("article")->where($where)->delete();
    	if($rs){
    		$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
    	}
    	echo json_encode($re_msg);
    }
    // 删除分类
    public function cateDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$where = array();
        if($this->userid!=1){
            $where['unitid'] = $this->unitid;
        }
        $where['id'] = $id;
    	$rs = db("article_cate")->where($where)->delete();
    	if($rs){
    		$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
    	}
    	echo json_encode($re_msg);
    }
}