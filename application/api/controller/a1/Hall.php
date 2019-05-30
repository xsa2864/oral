<?php
namespace app\api\controller\a1;

use think\Controller;
use think\Request;
use think\DB;
use think\facade\Cookie;

class Hall extends Controller
{
	public function selectList()
	{
		$unitid = input("unitid",0);
        if(Cookie::has('unitid') && $unitid==0){
            $unitid = Cookie::get('unitid');
        }
        $page = input("page",1);
		$page_size = 10;
        $where = array();
        $lists = array();
        $data = array();
        if($unitid){
            $where[] = ['UnitId',"=",$unitid];
            $list = DB::name("hall")->field("HallNo,HallName as name")->where($where)->page($page,$page_size)->select();
            if($list){
                foreach ($list as $key => $value) {                    
                    $value['url'] = url('/app/hall/getQueues',['id'=>$value['HallNo']]);
                    $lists[] = $value;
                }
            }
            $count 			= DB::name('hall')->where($where)->count();
			$max_page 		= ceil($count/$page_size);
			$data['list']  = $lists;
            $data['title'] = "科室列表";
            $data['pages'] = $max_page;
        }else{
            $where['EnableFlag'] = 1;
            $list 		= DB::name("unit")->field("UnitId,unitname as name")->where($where)->page($page,$page_size)->select();
            if($list){
                foreach ($list as $key => $value) {                    
                    $value['url'] = url('/app/hall/selectList',['unitid'=>$value['UnitId']]);
                    $lists[] = $value;
                }
            }
            $count 		= DB::name('unit')->where($where)->count();
            $max_page 	= ceil($count/$page_size);
            $data['list']  = $lists;
            $data['title'] = "医院列表";
            $data['pages'] = $max_page;
        }
        return json($data);
	}
	// 队列列表
	public function getQueues()
	{
		$hall_id 	= input("id",0);
		$page 	 	= input("page",1);
		$page_size 	= 10;
		$flag 		= 1;
		$lists 		= array();
		$where[] = ['HallNo','=',$hall_id];
		$where[] = ['EnableFlag','=',1];
		$result = DB::name("serque")->where($where)->page($page,$page_size)->select();
		$max_page = 1;
		if($result){
			$count 	  = DB::name("serque")->where($where)->count();
			$max_page = ceil($count/$page_size);

			if($flag){				
				foreach ($result as $key => $value) {
					$value['url'] = url('/app/hall/getDoctors',['que_id'=>$value['QueId']]);
					$lists[] = $value;
				}
			}else{
				$lists = $result;
			}
		}
		$data['title'] 	= '队列列表';
		$data['flag']	= $flag;
		$data['list'] 	= $lists;
 		$data['pages']  = $max_page;
		return json($data);
	}
	// 医生列表
	public function getDoctors()
	{
		$que_id 	= input("que_id",0);
		$page 	 	= input("page",1);
		$page_size 	= 10;
		$lists 		= array();
		$max_page   = 1;
		$where[] = ['c.que_id','=',$que_id];
		$where[] = ['d.status','=',1];
		$result = DB::name("z_doctor")->alias("d")
					->field("d.*")
					->leftJoin("z_doctor_class c","c.doctor_id=d.id")
					->where($where)
					->page($page,$page_size)
					->select();
		if($result){
			$count 	= DB::name("z_doctor")->alias("d")
					->leftJoin("z_doctor_class c","c.doctor_id=d.id")
					->where($where)
					->count();
			$max_page 		= ceil($count/$page_size);
			foreach ($result as $key => $value) {
				$value['url'] = url('/app/hall/getOrder',['que_id'=>$que_id,'doctor_id'=>$value['id']]);
				$lists[] = $value;
			}
		}
		$data['title'] 	= '医生列表';
		$data['list'] 	= $lists;
 		$data['pages'] = $max_page;
		return json($data);
	}
}