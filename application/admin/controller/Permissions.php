<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Permissions extends Base
{
    // 用户组数据列表
    public function listdata()
    {       
        $where = array();
        if($this->userid!=1){
            $where['unitId'] = $this->unitid;
        }
        $list = db("auth_group")->where($where)->select();
        $this->assign("lists",$list);
        return $this->fetch('listdata');
    }
    // 编辑用户组
    public function editPer(){
        $id = input("id",0);
        $result = db("auth_group")->where("id=".$id)->find();

        $wh[] = ['status','=',1];
        $wh[] = ['pid','=',0];
        $wh[] = ['id','<>',$id];
        if($this->userid!=1){
            $wh[] = ['unitid','=',$this->unitid];
        }
        $group_list = db("auth_group")->where($wh)->select();

        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }
        $where['EnableFlag'] = 1;
        $unit = db("unit")->where($where)->select();
        $this->assign('unit',$unit);  

        $this->assign("list",$result);
        $this->assign("group_list",$group_list);
        return $this->fetch("editper");
    }
    // 修改用户组
    public function groupSave(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';
        $data['title']      = input('title','');
        $data['pid']        = input('pid','')?'|'.input('pid','').'|':0;
        $data['status']     = input('status',1);
        // $data['unitid']     = $this->unitid;
        $data['unitid']     = input('unitid',1);
        $id = input('id',0);
        $flag = 0;
        if($id > 0){
            $flag = db("auth_group")->where("id",$id)->update($data);
            if($flag!==false){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '更新成功';
            }
        }else{
            $flag = db("auth_group")->insert($data);
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '新增成功';
            }
        }
        echo json_encode($re_msg);
    }
    // 删除用户组
    public function perDel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        $result = db("auth_group")->where("id",$id)->find();
        if($result){
            $flag = db("auth_group")->where("id",$id)->delete();
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }else{
            $re_msg['msg'] = '该数据已经不存在了';
        }

        echo json_encode($re_msg);
    }
    // 分配权限
    public function groupEdit(){
        $id = input('id',0);
        $arr = array();
        $rs = db("auth_group")->where("id",$id)->find();

        if(!empty($rs)){
            $arr = explode(',', $rs['rules']);
        }

        $list = db("auth_rule")->order('sort desc')->select();
        $list = $this->getc_Rule($list,0,$arr);
        
        $this->assign('id',$id);
        $this->assign("list",$list);
        return $this->fetch('groupedit');
    }
    // 编辑权限细则
    public function groupRules(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';
        $rules     = input('checkid/a','');
        $data['rules'] = '';
        if(!empty($rules)){
            $data['rules'] = implode(',', $rules);
        }
        $id = input('id',0);
        $flag = 0;
        if($id > 0){
            $flag = db("auth_group")->where("id",$id)->update($data);
        }
        if($flag != false){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '更新成功';
        }
        echo json_encode($re_msg);
    }
    // 权限列表
    public function listrule(){        
        $list = db("auth_rule")->order('sort desc')->select();
        $list = $this->getc_Rule($list);
        $this->assign("list",$list);
        return $this->fetch('listrule');
    }
    
    // 规则管理
    public function ruleEdit(){
        $id = input("id",0);
        $list = db("auth_rule")->where("id=".$id)->find();
      
        $this->assign("list",$list);
        $wh = array();
        $wh[] = ["id","NEQ",$id];
        $wh[] = ["ismenu","=",1];
        $rs = db("auth_rule")->where($wh)->select();
        $this->assign("rs",$rs);
        $this->assign("id",$id);
        return $this->fetch('editrule');
    }
    // 保存信息
    public function ruleSave(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';
        $data['pid']    = input("pid",0);
        $data['title']  = input("title",0);
        $data['name']   = input("name",0);
        $data['status'] = input("status",0);
        $data['ismenu'] = input("ismenu",0);
        $data['sort']   = input("sort",0);
        $data['condition'] = input("condition",'');
        $id = input('id',0);
        $flag = 0;

        if($id > 0){
            $flag = db("auth_rule")->where("id",$id)->update($data);
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '更新成功';
            }
        }else{
            $result = db("auth_rule")->where("name",$data['name'])->select();
            if($result){
                $re_msg['msg'] = '该操作方法以及存在';
            }else{                
                $flag = db("auth_rule")->insert($data);
                if($flag){
                    $re_msg['success'] = 2;
                    $re_msg['msg'] = '新增成功';
                }
            }
        }
        
        echo json_encode($re_msg);
    }
    // 删除权限
    public function ruleDel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        $result = db("auth_rule")->where("id",$id)->find();
        if($result){
            if($result['pid']==0){
                $rs = db("auth_rule")->where("pid",$result['id'])->select();
                if($rs){
                    $re_msg['msg'] = '还有子元素不能删除';
                    echo json_encode($re_msg);exit;
                }
            }
            $flag = db("auth_rule")->where("id",$id)->delete();
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }else{
            $re_msg['msg'] = '该数据已经不存在了';
        }

        echo json_encode($re_msg);
    }
}