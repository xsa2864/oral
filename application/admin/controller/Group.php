<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Group extends Base
{
	public function listdata()
    {    	
    	$where = array();
        if($this->userid!=1){
            $where['y.unitId'] = $this->unitid;
        }
        $list_n = array();
    	$list = db("yygroup")->alias("y")
                ->field("y.*,u.unitname")
                ->leftJoin("unit u","u.UnitId=y.unitId")
                ->order("u.UnitId asc,y.DispName asc")
                ->where($where)
                ->select();  
        if(!empty($list)){            
            foreach ($list as $key => $value) {
                unset($cwh);
                $cwh[] = ["yygroup_id",'=',$value['id']];
                $cwh[] = ["unitid",'=',$value['unitId']];
                $value['number'] = db("manager")->where($cwh)->count();
                $list_n[] = $value;
            }
            $list = $list_n;
        }
    	$this->assign('list',$list);
        // 模板输出
        return $this->fetch('listdata');
    }
    // 编辑部门
    public function groupEdit(){
    	$id = input('id',0);

        $where = array();
        $wh    = array();
        if($this->userid!=1){
            $where['unitId'] = $this->unitid;
        }
        $unit = db("unit")->where($where)->select();
        $wh['id'] = $id;
        $list = db("yygroup")->where($wh)->find();
        $this->assign("list",$list);
        $this->assign("unit",$unit);
        return $this->fetch('groupedit');
    }
    // 保存信息
    public function saveGroup(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';

	    $data['unitId'] 		= input('unitId',0);	    
	    $data['HallName'] 		= input('hallname','');
	    $data['DispName'] 		= input('hallname','');
        $data['EnableFlag'] 	= input('EnableFlag',0);
        $data['SerInterface'] 	= input('intfaces','');

        if(empty($data['HallName'])){
            $re_msg['msg'] = '预约组名不能为空';
            echo json_encode($re_msg);exit;
        }

        if(empty($data['unitId'])){
            $re_msg['msg'] = '请选择所属单位';
            echo json_encode($re_msg);exit;
        }

  		if($data['unitId']>0){
  			$rs = db("unit")->where("UnitId",$data['unitId'])->column('unitname');
  			$data['UnitName'] = $rs[0];
  		}

	    $id = input('id',0);
	    $flag = 0;
	    if($id > 0){
	    	$flag = db("yygroup")->where("id",$id)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
	    }else{
	    	$data['addtime'] = time();
	    	$flag = db("yygroup")->insert($data);
    	    if($flag){
    	    	$re_msg['success'] = 1;
            	$re_msg['msg'] = '保存成功';
    	    }
        }
	    echo json_encode($re_msg);
    }
    // 删除数据
    public function groupDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$result = db("yygroup")->where("id",$id)->find();
    	if($result){
    		$flag = db("yygroup")->where("id",$id)->delete();
    		if($flag){
    			$re_msg['success'] = 1;
       	 		$re_msg['msg'] = '删除成功';
    		}
    	}else{
    		$re_msg['msg'] = '该数据已经不存在了';
    	}
    	echo json_encode($re_msg);
    }
    // 预约人员配置
    public function memberlist(){
         
        $where = array();
        if($this->userid!=1){
            $where['m.unitId'] = $this->unitid;
        }
        $where['m.Types'] = 2;
        $list = db("manager")->alias("m")
                ->field("m.UserId,m.Sex,m.FullName,m.BodNo,m.UserName,g.title,y.DispName,u.unitname,h.HallName")
                ->leftJoin('auth_group_access ga','ga.uid = m.UserId')
                ->leftJoin('auth_group g','g.id = ga.group_id')
                ->leftJoin('yygroup y','y.id=m.yygroup_id')
                ->leftJoin('unit u','u.UnitId=m.unitid')
                ->leftJoin('hall h','h.HallNo=m.hallid')
                ->order("u.UnitId",'asc')
                ->order("y.DispName",'asc')
                ->where($where)
                ->paginate(20);
        $page = $list->render();

        $this->assign("page",$page);
        $this->assign('list',$list);
        // 模板输出
        return $this->fetch('memberlist');
    }
}