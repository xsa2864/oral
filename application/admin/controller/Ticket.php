<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Ticket extends Base
{
	// 预约数据
    public function signdata(){
        $wh['idcards']  = $idcards    = input('idcards');
        $wh['mobile']   = $mobile     = input('mobile');
        $wh['hallname'] = $hallname   = input('hallname');
        $wh['quename']  = $quename    = input('quename');
        $wh['name']     = $name       = input('name','');
        $wh['stime']    = $stime      = input('stime',date("Y-m-d",time()));
        $wh['etime']    = $etime      = input('etime',date("Y-m-d",time()));
        $wh['status']   = $status     = input('status',1);
        $wh['remark']   = $remark     = input('remark','');

        $where = array();
        $hwh   = array();
        $swh   = array();
        if($this->userid!=1){
            $where[] = ['d.unit_id','=',$this->unitid];
            $hwh[]   = ['UnitId','=',$this->unitid];
            $swh[]   = ['UnitId','=',$this->unitid];
            if($this->user['yygroup_id']){                
                $lists = $this->getNextUid();
                $where[] = ['d.manager_id','in',$lists];            
            }
        }

        if(!empty($mobile)){
            $where[] = ['d.mobile','like','%'.$mobile.'%'];
        }
        if(!empty($idcards)){
            $where[] = ['d.idcard','like','%'.$idcards.'%'];
        }
        if(!empty($remark)){
            $where[] = ['d.remark','like','%'.$remark.'%'];
        }
        if(!empty($hallname)){
            $where[] = ['h.HallName','like','%'.trim($hallname).'%'];
        }
        if(!empty($quename)){
            $where[] = ['s.QueName','like','%'.trim($quename).'%'];
        }
        $stime = date("Y-m-d",strtotime($stime));
        $etime = date("Y-m-d",strtotime("+1 day",strtotime($etime)));

        if (!empty($stime) && !empty($etime)) {
            // $where[] = ['d.despeakTime','between time',[strtotime($stime),strtotime($etime)]];
            $where[] = ['d.add_time','>=',strtotime($stime)];
            $where[] = ['d.add_time','<',strtotime($etime)];
        }else if(!empty($stime)){
            $where[] = ['d.add_time','>=',strtotime($stime)];
        }else if (!empty($etime)) {
           $where[] = ['d.add_time','<',strtotime($etime)];
        } 
        if($status!==""){            
        	$where[] = ['d.status','=',$status];
        }
        
        $result = db("z_ticket")->alias("d")
                    ->field("d.*,h.HallName,s.QueName")
                    ->leftJoin("hall h","h.HallNo=d.hall_id")
                    ->leftJoin("serque s","s.QueId=d.doctor_id")
                    ->order("d.sort desc,d.add_time asc")
                    ->where($where)
                    ->paginate(20,false,[
                        'type'     => '\page\Page',
                        'var_page' => 'page',
                        'query'    => $wh,
                    ]);
        $page = $result->render();

        $this->assign("wh",$wh);
        $this->assign("page",$page);
        $this->assign("list",$result);
        $list_hall = db("hall")->where($hwh)->select();
        $this->assign("list_hall",$list_hall);
        $list_d = db("serque")->where($swh)->select();
        $this->assign("list_d",$list_d);
        return $this->fetch('signdata');
    }
    // 编辑票号
    public function ticketEdit()
    {
    	$id = input("id",0);
    	$list = db("z_ticket")->where("id",$id)->find();
    	$this->assign("list",$list);
    	return $this->fetch("ticketEdit");
    }
    // 保存票号
    public function ticketSave(){
    	$re_msg['success'] 	= 0;
    	$re_msg['msg'] 		= '更新失败';
    	$id = input("id",0);
    	$data['status'] = input("status",0);
    	$data['sort']	= input("sort",0);
    	$rs = db("z_ticket")->where("id",$id)->update($data);
    	if($rs!==false){
    		$re_msg['success'] 	= 1;
    		$re_msg['msg'] 		= '更新成功';
    	}
    	echo json_encode($re_msg);
    }
}