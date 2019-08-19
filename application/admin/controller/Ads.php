<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Ads extends Base
{
	// 广告列表
    public function adsList(){  
    	$where = array();
        if($this->userid!=1){
            // $where['a.unitid'] = $this->unitid;
            if($this->hallid){
                $where['a.hall_id'] = $this->hallid;
            }
            $where['u.UnitId'] = $this->unitid;
        }
        $list = db("ads")->alias("a")
        		->field("a.*,u.unitname,t.title as titles,h.HallName")
                ->leftJoin("ads_type t","t.id=a.type")
        		->leftJoin("unit u","u.UnitId=a.unitid")
                ->leftJoin("hall h","h.HallNo=a.hall_id")
        		->where($where)->order(["hall_id","form"=>"asc"])->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
    	return $this->fetch('adsList');
    }
	// 广告管理
    public function adsEdit()
    {    
    	$id = input("id",0);
    	$where  = array();
        $wh     = array();
        $whu    = array();
        $attr   = array();
        if($this->userid!=1){
            $whu[]      = ['UnitId','=',$this->unitid];
            if($this->hallid){
                $wh[] = ['HallNo','=',$this->hallid];
                $where['hall_id'] = $this->hallid;
            }
            $wh[] = ['UnitId','=',$this->unitid];
        }
        $hall = db("hall")->where($wh)->select();
        $where['id'] = $id;
        $result = db("ads")->where($where)->find();
        if($result){
            $attr = json_decode($result['attr'],1);
        }
        $type = db("ads_type")->select();        
        $this->assign("hall",$hall);
        $this->assign("type",$type);
    	$this->assign("attr",$attr);
    	$this->assign("list",$result);

        $unit  = db("unit")->where($whu)->select();
        $this->assign("unit",$unit);
        $this->assign("user_id",$this->userid);
    	return $this->fetch('adsEdit');
    }
    // 保存广告
    public function adsSave(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

    	$id = input("id",0);
    	$data['title']  = input("title","");
        $data['type']   = input("type",0);
    	$pic            = input("pic/a","");
    	$url            = input("url/a","");
        $data['horizontal']  = input("horizontal",0);
        $data['status']      = input("status",0);
        if($this->userid==1){
            $data['unitid'] = input("unit_id",0);
        }else{
            $data['unitid'] = $this->unitid;
        }
        $data['hall_id'] = input("hall_id",0);
        $data['form']    = input("form",0);

        // if(empty($data['title'])){
        //     $re_msg['msg'] = '标题不能为空';
        //     echo json_encode($re_msg);exit;
        // }
        
        if(empty($data['type'])){
            $re_msg['msg'] = '广告类型不能为空';
            echo json_encode($re_msg);exit;
        }
        // if($this->userid!=1){
        //     if(empty($data['hall_id'])){
        //         $re_msg['msg'] = '请选择区域';
        //         echo json_encode($re_msg);exit;
        //     }
        // }

        $arr = array();
        if(!empty($pic)){
            foreach ($pic as $key => $value) {
                $arr[$key]['img'] = $value;
                $arr[$key]['url'] = isset($url[$key])?$url[$key]:'';
            }
        }
        if(!empty($arr)){
            $data['attr'] = json_encode($arr);
        }

    	if($id>0){
    		$rs = db("ads")->where("id",$id)->update($data);
    		if($rs!==false){
    			$re_msg['success'] = 1;
        		$re_msg['msg'] = '更新成功';
    		}else{
    			$re_msg['msg'] = '更新失败';
    		}
    	}else{
	    	$data['unitid'] = $this->unitid;
    		$data['addtime'] = time();
    		$rs = db("ads")->insertGetId($data);
    		if($rs){
    			$re_msg['success'] = 2;
        		$re_msg['msg'] = '添加成功';
    		}
    	}
    	echo json_encode($re_msg);
    }
    // 广告位分类列表
    public function cateList(){
    	$where = array();
        if($this->userid!=1){
            $where['a.unitid'] = $this->unitid;
        }
        $list = db("ads_type")->alias("a")
        		->field("a.*,u.unitname")
        		->join("unit u","u.UnitId=a.unitid","left")
        		->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
    	return $this->fetch('cateList');
    }
    // 编辑广告分类
    public function cateEdit(){
    	$id = input("id",0);
        $where = array();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }
        $unit = db("unit")->where($where)->select();
        $list = db("ads_type")->where("id=$id")->find();
        $this->assign("unit",$unit);
    	$this->assign("list",$list);
    	return $this->fetch('cateEdit');
    }
    // 广告类保存
    public function cateSave(){
		$re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

    	$id = input("id",0);
    	$data['title'] = input("title","");
        $data['unitid'] = input("unitid","");

        if($this->userid!=1){            
            if(empty($data['unitid'])){
                $re_msg['msg'] = '请选择单位';
                echo json_encode($re_msg);exit;
            }
        }

        if(empty($data['title'])){
            $re_msg['msg'] = '分类名称不能为空';
            echo json_encode($re_msg);exit;
        }

    	if($id>0){
    		$rs = db("ads_type")->where("id",$id)->update($data);
    		if($rs){
    			$re_msg['success'] = 1;
        		$re_msg['msg'] = '更新成功';
    		}else{
    			$re_msg['msg'] = '更新失败';
    		}
    	}else{
	    	$data['unitid'] = $this->unitid;
    		$rs = db("ads_type")->insertGetId($data);
    		if($rs){
    			$re_msg['success'] = 2;
        		$re_msg['msg'] = '添加成功';
    		}
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
        $rs = db("ads_type")->where($where)->delete();
        if($rs){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '删除成功';
        }
        echo json_encode($re_msg);
    }
    
    // 删除广告
    public function adsDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$where = array();
        if($this->userid!=1){
            $where['unitid'] = $this->unitid;   
        }
        $where['id'] = $id;
    	$rs = db("ads")->where($where)->delete();
    	if($rs){
    		$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
    	}
    	echo json_encode($re_msg);
    }

    // 上传图片
    public function upload_ads(){  
        $re_msg['success'] = 0; 
        $re_msg['msg']  = '上传失败';
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        if (!file_exists(Env::get('root_path') . 'public/uploads/ads')) {
            mkdir( Env::get('root_path') . 'public/uploads/ads', 0777, true );
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>1506780,'ext'=>'jpg,png,gif'])->move(Env::get('root_path') . 'public/uploads/ads');
        if($info){
            $re_msg['success'] = 1;

            $re_msg['msg']  = str_replace("\\","/",$info->getSaveName());; 
        }else{
            // 上传失败获取错误信息
            $re_msg['msg']  = $file->getError();
        }
        echo json_encode($re_msg);
    }

    // 上传图片
    public function upload(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '上传失败';

        $height = input("height",0);
        $width  = input("width",0);

        $file = request()->file('image');   
        $image = \think\Image::open($file);

        $path = Env::get('root_path').'public';
        $path_img = '/uploads/thumb/';
        if (!file_exists($path.$path_img)) {
            mkdir( $path.$path_img, 0777, true );
        }
        $img_name = time().rand(100,999).".".$image->type();
        $images = $path_img.$img_name;
  
        // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.png
        $image->thumb($width, $height,\think\Image::THUMB_SCALING)->save($path.$images);
       
        // 移动到框架应用根目录/public/uploads/ 目录下
        // $info = $file->validate(['size'=>506780,'ext'=>'jpg,png,gif'])->move(Env::get('root_path') . 'public/uploads/ads');
        if($image){
            $re_msg['success'] = 1;
            $re_msg['msg']  =  $images; 
        }else{
            // 上传失败获取错误信息
            $re_msg['msg']  = "上传失败";
        }
        echo json_encode($re_msg);
    }
    // 编辑器上传图片
    public function upload_e(){
        $re_msg['errno'] = 1;
        $re_msg['data']  = array();
        $files = request()->file();

        $imags = [];
        $errors = [];
        foreach($files as $file){
            $info = $file->move(Env::get('root_path') . 'public/uploads');
        if($info){
                $path = '/uploads/'.$info->getSaveName();
                array_push($imags,$path);
            }else{
                array_push($errors,$file->getError());
            }
        }
        if(!$errors){
            $re_msg['errno'] = 0;
            $re_msg['data'] = $imags;
        }        
        echo json_encode($re_msg);
    }
}