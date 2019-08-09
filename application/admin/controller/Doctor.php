<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\facade\Cookie;
use think\facade\Env;

class Doctor extends Base
{
	// 医生数据列表
    public function listdata()
    {    	
    	$id = input("id",0);
        $where = array();
        if($this->userid!=1){
            $where['s.UnitId'] = $this->unitid;
            if($this->hallid){
                $where['s.HallNo'] = $this->hallid;
            }
        }
        if($id) {
            $where['s.UnitId'] = $id;
        }
        $hall_id = input("hall_id",'');
        if($hall_id){
            $where['s.HallNo'] = $hall_id;
            cookie("now_hall_id",$hall_id);
        }
    	$list = db("serque")->alias("s")
                ->field('s.*,u.unitname,h.HallName')
                ->join('unit u','u.UnitId=s.UnitId','LEFT')
                ->join('hall h','h.HallNo=s.HallNo','LEFT')
                ->where($where)->order("s.HallNo asc")->paginate(20);
        $page = $list->render();
    	$this->assign("lists",$list);
        $this->assign("page",$page);
        return $this->fetch('listdata');
    }
    // 医生详细信息
    public function editDoctor(){
    	$id = input("id",0);    
        $result = array();
        $work = array();

        $where = array();
        $wh[] = ["EnableFlag",'=',1];
        if($this->userid!=1){
            $wh[] = ["UnitId",'=',$this->unitid];
            if($this->hallid){
                $wh[] = ['HallNo','=',$this->hallid];
            }
        }
        $hall = db("hall")->where($wh)->select();
        $where['QueId'] = $id;
    	$result = db("serque")->where($where)->find();

        if(isset($result['ClassesTime'])){
            $work = explode('-', $result['ClassesTime']);
        }

        $where = array();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }
        $unit = db("unit")->where($where)->select();
        $now_hall_id = cookie("now_hall_id");
        $this->assign("now_hall_id",$now_hall_id);
        $this->assign("unit",$unit);
        $this->assign("hall",$hall);
    	$this->assign("list",$result);
        $this->assign("work",$work);
        $code = Db::name("serque")->where("UnitId",$this->unitid)->value("max(code)");
        $InterfaceID = Db::name("serque")->where("UnitId",$this->unitid)->value("max(InterfaceID)");
        $ser_num = Db::name("serque")->where("UnitId",$this->unitid)->value("max(ser_num)");
        // 模板输出
        $this->assign("code",$code+1);
        $this->assign("InterfaceID",$InterfaceID+1);
        $this->assign("ser_num",$ser_num+1);
    	return $this->fetch('editdoctor');
    }
    // 保存信息
    public function saveDoctor(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';

        $data['staff_code'] = input('staff_code','');
        $data['code']       = input('code','');
	    $data['HallNo']     = input('hall_id',0);
	    $data['UnitId'] 	= input('unitid',1);
        $data['NoChar']     = input('NoChar','');
        $data['ser_num']    = input('ser_num','');
        $data['StarNo']     = input('StarNo','Y');
        $data['quick_char'] = input('quick_char','V');
        $data['mobile']     = input('mobile','');
        $data['pic']        = input('pic','');
        $data['DispName']   = input('dispname','');
        $data['QueName']    = input('quename','');
        $data['EnableFlag'] = input('EnableFlag',0);
        $data['type']       = input('type',0);
        $data['step']       = input('step',1);
        $data['voice_addr'] = input('voice_addr',1);
        $data['QueForm']    = input('QueForm',0);
        $data['WorkTime1']  = input('stime1');
        $data['WorkTime2']  = input('etime1');
        $data['WorkTime3']  = input('stime2');
        $data['WorkTime4']  = input('etime2');
        $data['ClassesTime'] = implode('-', input('ClassesTime/a',array('')));
        $data['InterfaceID'] = input('intfaces','');
        $data['AlternateField1']    = input('AlternateField1','');
        $queid          = input('queid',0);
        $password       = input('password','');

        $cw[] = ["UnitId",'=',$data['UnitId']];
        $cw[] = ["code",'=',$data['code']];
        $result = db("serque")->where($cw)->find();
        if($result){
            $re_msg['msg'] = '已经存在队列编号，请更换';
            if($queid>0){
                if($result['QueId']!=$queid){
                    return json($re_msg);
                }
            }else{                
                return json($re_msg);
            }
        }
        if(empty($data['QueName'])){
            $re_msg['msg'] = '请填写队列名称';
            return json($re_msg);
        }
        if(empty($data['HallNo'])){
            $re_msg['msg'] = '请选择区域';
            return json($re_msg);
        }
        if(!empty($data['ser_num'])){
            $sw[] = ['UnitId','=',$data['UnitId']];
            $sw[] = ['ser_num','=',$data['ser_num']];
            $ser = Db::name("serque")->where($sw)->find();           
            if($ser){                
                if($ser['QueId']!=$queid){                
                    $re_msg['msg'] = '唯一编码已经存在';
                    return json($re_msg);
                }
            }
        }
        if($data['InterfaceID']){
            $ssw[] = ['UnitId','=',$data['UnitId']];
            $ssw[] = ['InterfaceID','=',$data['InterfaceID']];
            $sser = Db::name("serque")->where($ssw)->find();           
            if($sser){                
                if($sser['QueId']!=$queid){                
                    $re_msg['msg'] = '接口编码已经存在';
                    return json($re_msg);
                }
            }
        }

	    $flag = 0;
	    if($queid > 0){
            if($password){
                $data['password'] = md5($password);
            }
	    	$flag = db("serque")->where("QueId",$queid)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
	    }else{
            if(empty($password)){
                $data['password'] = md5('123456');
            }
	    	$data['InTime'] = date("Y-m-d H:i:s",time());
	    	$flag = db("serque")->insert($data);
    	    if($flag){
    	    	$re_msg['success'] = 1;
            	$re_msg['msg'] = '添加成功';
    	    }
        }
	    echo json_encode($re_msg);
    }
    // 删除数据
    public function delAll(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $list = input("list",'');
        if($list){
            $arr = explode(',', $list);
            $rs = Db::name("z_doctor")->delete($arr);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }
        echo json_encode($re_msg);
    }
    // 删除数据
    public function doctorDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$result = db("serque")->where("QueId",$id)->find();
    	if($result){
    		$flag = db("serque")->where("QueId",$id)->delete();
    		if($flag){
    			$re_msg['success'] = 1;
       	 		$re_msg['msg'] = '删除成功';
    		}
    	}else{
    		$re_msg['msg'] = '该数据已经不存在了';
    	}
    	echo json_encode($re_msg);
    }
    // 上传图片
    public function upload(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '上传失败';
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif'])->move(Env::get('root_path') . 'public/uploads');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            // echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getFilename();
            $re_msg['success'] = 1;
            $re_msg['msg']  =  str_replace("\\","/",$info->getSaveName()); 
        }else{
            // 上传失败获取错误信息
            $re_msg['msg']  = $file->getError();
        }
        echo json_encode($re_msg);
    }

    // 医生数据列表
    public function listdatas()
    {       
        $id = input("id",0);
        $hall_id  = input("hall_id",0);
        $wh['query'] = $query = input("query","");
        $where = array();
        if($this->userid!=1){
            $where[] = ['z.unit_id','=',$this->unitid];
        }else if($id!=0) {
            $where[] = ['z.unit_id', '=',$id];
        }
        if($hall_id){
            $where[] = ['z.hall_id', '=', $hall_id];
        }
        if(!empty($query)){
            $where[] = ['z.staff_code|z.QueName|s.QueName','like','%'.$query.'%'];
        }

        $list = db("z_doctor")->alias("z")
                ->field("z.*,group_concat(s.QueName) as quename")
                ->leftJoin('z_doctor_class dc','dc.doctor_id=z.id')            
                ->leftJoin('serque s','s.QueId=dc.que_id')
                ->where($where)
                ->group("z.id")
                ->paginate(20,false,[
                    'query'    => $wh,
                ]);
        $page = $list->render();
        $this->assign("lists",$list);
        $this->assign("page",$page);
        $this->assign("wh",$wh);
        return $this->fetch('listdatas');
    }
    // 医生详细信息
    public function editDoctors(){

        $id = input("id",0);    
        $result = array();
        $work = array();

        $wh     = array();
        $wha    = array(); 
        $whu    = array();
        $where  = array();
        if($this->userid!=1){
            $wha[] = ['UnitId','=',$this->unitid];
            $whu[]      = ['UnitId','=',$this->unitid];
            $wh['UnitId'] = $this->unitid;
            if($this->hallid){
                $wha[] = ['HallNo','=',$this->hallid];
            }
        }

        $unit = db("unit")->where($wh)->select();
        $que_id = array();
        $serque = db("serque")->where($wha)->select();
        $where['id'] = $id;
        $result = db("z_doctor")->where($where)->find();
        if($result){
            $que_id = array_filter(explode('|', $result['que_id']));
        }

        if(isset($result['ClassesTime'])){
            $work = explode('-', $result['ClassesTime']);
        }
        $config_fetch = Db::name("config_fetch")->where("unitid",$this->unitid)->find();
        $type_list = $config_fetch['degree'];
        if($type_list){
            $type_list = explode(',', $type_list);
        }
        $this->assign("type_list",$type_list);
        $this->assign("config_fetch",$config_fetch);

        $this->assign("que_id",$que_id);
        $this->assign("unit",$unit);
        $this->assign("serque",$serque);
        $this->assign("list",$result);
        $this->assign("work",$work);
        $unit  = db("unit")->where($whu)->select();
        $this->assign("unit",$unit);
        $this->assign("user_id",$this->userid);
        return $this->fetch('editdoctors');
    }
    // 保存信息
    public function saveDoctors(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';
        $mk_que_id          = input('mk_que_id');
        $data['staff_code'] = input('staff_code','');
        $data['hall_id']    = input('hall_id',0);
        $data['que_id']     = input('que_id','');
        $data['NoChar']     = input('NoChar','');
        $data['StarNo']     = input('StarNo','');
        $data['HourSum']     = input('HourSum',6);
        $data['mobile']     = input('mobile','');
        $data['pic']        = input('pic','');
        $data['DispName']   = input('dispname','');
        $data['url']        = input('url','');
        $data['QueName']    = input('quename','');
        $data['status']     = input('status',0);
        $data['original_id']= input('original_id',0);
        $data['type']       = input('type','');
        $data['step']       = input('step',1);
        $data['QueForm']    = input('QueForm',0);
        $data['WorkTime1']  = input('stime1');
        $data['WorkTime2']  = input('etime1');
        $data['WorkTime3']  = input('stime2');
        $data['WorkTime4']  = input('etime2');
        $data['ClassesTime'] = implode('-', input('ClassesTime/a',array('')));
        $data['InterfaceID'] = input('intfaces','');
        $data['AlternateField1']    = input('AlternateField1','');
        $id             = input('id',0);
        $password       = input('password','');
        if($this->userid==1){
            $data['unit_id'] = input("unit_id",0);
        }else{
            $data['unit_id'] = $this->unitid;
        }
        $data['mk_que_id'] = $mk_que_id?implode(',', $mk_que_id):'';

        if(empty($data['staff_code'])){
            $re_msg['msg'] = '请填写工号';
            echo json_encode($re_msg);exit;
        }
        $cw[] = ['staff_code','=',$data['staff_code']];
        $cw[] = ["unit_id",'=',$this->unitid];
        $find_rs = db("z_doctor")->where($cw)->find();
        if($find_rs){
            if($find_rs['id'] != $id){
                $re_msg['msg'] = '员工号已经存在请更换';
                echo json_encode($re_msg);exit;
            }
        }
        if(empty($data['QueName'])){
            $re_msg['msg'] = '请填写姓名';
            echo json_encode($re_msg);exit;
        }
        if(empty($data['unit_id'])){
            $re_msg['msg'] = '请选择单位';
            echo json_encode($re_msg);exit;
        }
        if($data['InterfaceID']){            
            $ih[] = ["InterfaceID",'=',$data['InterfaceID']];
            $ih[] = ["unit_id",'=',$this->unitid];
            $irs = Db::name("z_doctor")->where($ih)->find();
            if($irs){
                if($irs['id'] != $id){                
                    $re_msg['msg'] = '接口标识已经存在';
                    return json($re_msg);
                }
            }
        }
        if($data['original_id']){            
            $oh[] = ["original_id",'=',$data['original_id']];
            $oh[] = ["unit_id",'=',$this->unitid];
            $ors = Db::name("z_doctor")->where($oh)->find();
            if($ors){
                if($ors['id'] != $id){                
                    $re_msg['msg'] = '唯一值已经存在';
                    return json($re_msg);
                }
            }
        }
        $flag = 0;
        if($id > 0){
            if($password){
                $data['password'] = md5($password);
            }
            $flag = db("z_doctor")->where("id",$id)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
        }else{
            if(empty($password)){
                $data['password'] = md5('123456');
            }
            $id = db("z_doctor")->insertGetId($data);
            if($id){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '添加成功';
            }
        }
        // 添加排班表
        $cla = new \app\admin\model\ClassTime;
        $rs = $cla->makeQueue($data['que_id'],$id);
        echo json_encode($re_msg);
    }
    // 删除数据
    public function doctorDels(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        $result = db("z_doctor")->where("id",$id)->find();
        if($result){
            $flag = db("z_doctor")->where("id",$id)->delete();
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }else{
            $re_msg['msg'] = '该数据已经不存在了';
        }
        echo json_encode($re_msg);
    }
    // 获取排班信息
    public function getClassTime()
    {
        $re_msg['success'] = 0;
        $re_msg['msg'] = '还没配置排班时间';

        $que_id = input("que_id",0);
        $doctor_id = input("doctor_id",0);
        $where[] = ['que_id','=',$que_id];
        $where[] = ['doctor_id','=',$doctor_id];
        $result = db("z_doctor_class")->where($where)->find();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '获取排班时间';
            $cla = new \app\admin\model\ClassTime;
            $re_msg['data'] = $cla->arrangeClass($result['class']);
        }
        echo json_encode($re_msg);
    }
    // 排班页面
    public function classTime()
    { 
        $wh['id']       = $id = input("id",0);
        $wh['QueId']    = $QueId = input("QueId",0);
        $wh['name']     = $name = input("name",'');
        $where = array();
        $swh = array();
        $dwh = array();
        if($this->userid!=1){
            if($this->hallid){
                $where[] = ['s.HallNo','=',$this->hallid];
                $swh[]   = ['HallNo','=',$this->hallid];
            }
            $where[]    = ['h.UnitId','=',$this->unitid];
            $swh[]      = ['UnitId','=',$this->unitid];
            $dwh[]      = ['unit_id','=',$this->unitid];
        }
        if(!empty($name)){
            $where[] = ['d.QueName|s.QueName','like','%'.$name.'%'];
        }
        if(!empty($id)){
            $where[] = ['d.id','=',$id];
        }
        if(!empty($QueId)){
            $where[] = ['s.QueId','=',$QueId];
        }
        $where[] = ['c.id','>',0];
        $list = db("z_doctor_class")->alias("c")
                ->field("s.QueName as qname,d.*,c.*,h.HallName")
                ->leftJoin("serque s","s.QueId=c.que_id")
                ->rightJoin("z_doctor d","d.id=c.doctor_id")
                ->leftJoin("hall h","h.HallNo=s.HallNo")
                ->where($where)
                ->order("c.que_id asc")
                ->paginate(20,false,[
                    'query'    => $wh,
                ]);
        $cla = new \app\admin\model\ClassTime;
        $lists = array();
        foreach ($list as $key => $value) {
            $value['date'] = $cla->arrangeClass($value['class']);
            $lists[] = $value;
        }
        $this->assign("page",$list->render());
        $this->assign("wh",$wh);
        $this->assign("lists",$lists);
        $dlist = Db::name("z_doctor")->where($dwh)->order("QueName","asc")->select();
        $this->assign("dlist",$dlist);
        $qlist = Db::name("serque")->where($swh)->order("QueName","asc")->select();
        $this->assign("qlist",$qlist);
        return $this->fetch("classtime");
    }
    //修改排班页面
    public function editClass()
    {

        $id = input("id",'');
        $item = db("z_doctor_class")->alias("c")
                ->field("s.QueName as qname,c.*,d.staff_code,d.QueName")
                ->leftJoin("serque s","s.QueId=c.que_id")
                ->leftJoin("z_doctor d","d.id=c.doctor_id")
                ->where("c.id",$id)->find();
        $cla = new \app\admin\model\ClassTime;
        $class = $cla->arrangeClass($item['class']);
        $this->assign("class",$class);
        $this->assign("item",$item);
        return $this->fetch("classedit");
    }
    // 更新排班
    public function updateClass()
    {
        $re_msg['success'] = 0;
        $re_msg['msg'] = '更新失败';
        $id = input("id",0);
        $str = input("str",'');
        $calss = explode(',', $str);
        $cla = new \app\admin\model\ClassTime;
        $data['class'] = $cla->binDecClass($calss);
        $rs = db("z_doctor_class")->where("id",$id)->data($data)->update();
        if($rs!==false){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '更新成功';
        }
        echo json_encode($re_msg);
    }

    // 排班配置管理
    public function classEdit()
    {    
        $id = input("id",1);
        $where  = array();    
        $where[] = ['id','=',$id];
        $result = db("z_temp_config")->where($where)->find();
        $this->assign("item",$result);
        return $this->fetch('temp');
    }
    // 保存广告
    public function classSave(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

        $id = input("id",0);
        $data['title']  = input("title","");
        $data['tip']    = input("tip","");
        $data['img']    = input("pic","");
        $data['status'] = input("status",0);
       

        if(empty($data['title'])){
            $re_msg['msg'] = '标题不能为空';
            echo json_encode($re_msg);exit;
        }

        if($id>0){
            $rs = db("z_temp_config")->where("id",$id)->update($data);
            if($rs!==false){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '更新成功';
            }else{
                $re_msg['msg'] = '更新失败';
            }
        }else{
            $data['add_time'] = time();
            $rs = db("z_temp_config")->insertGetId($data);
            if($rs){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '添加成功';
            }
        }
        echo json_encode($re_msg);
    }

    // 导入医生
    public function importInfo()
    {
        $re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';
        $file = input("file.info");
        $info = $file->validate(['size'=>1048576,'ext'=>'xls,xlsx'])->move( './excel');
        if($info){
            //获取上传到后台的文件名
            $fileName = $info->getSaveName();
            //获取文件路径
            $filePath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'excel'.DIRECTORY_SEPARATOR.$fileName;
            //获取文件后缀
            $suffix = $info->getExtension();
            //判断哪种类型
            if($suffix=="xlsx"){
                $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                }else{
                $reader = PHPExcel_IOFactory::createReader('Excel5'); 
                }
            }else{
                $re_msg['msg'] = '文件过大或格式不正确导致上传失败-_-!';
                echo json_encode($re_msg);exit;
                // $this->error('文件过大或格式不正确导致上传失败-_-!');
            }
        //载入excel文件
        $excel = $reader->load("$filePath",$encode = 'utf-8');
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $data = []; //数组形式获取表格数据
        for ($i=2; $i <=$row_num; $i++) { 
            $data[$i]['staff_code'] = $excel->getActiveSheet()->getCell("A".$i)->getValue();
            $data[$i]['QueName']    = $excel->getActiveSheet()->getCell("B".$i)->getValue();
            $data[$i]['sex']        = $excel->getActiveSheet()->getCell("C".$i)->getValue();
            $data[$i]['mobile']     = $excel->getActiveSheet()->getCell("D".$i)->getValue();
            $data[$i]['NoChar']     = $excel->getActiveSheet()->getCell("E".$i)->getValue();
            $data[$i]['StarNo']     = $excel->getActiveSheet()->getCell("F".$i)->getValue();
            $data[$i]['type']       = $excel->getActiveSheet()->getCell("G".$i)->getValue();
            $data[$i]['AlternateField1']     = $excel->getActiveSheet()->getCell("H".$i)->getValue();
            $data[$i]['unit_id']    = $this->unitid;
            $data[$i]['password']   = md5('123456');
            $data[$i]['WorkTime1']    = '08:00:00';
            $data[$i]['WorkTime2']    = '12:00:00';
            $data[$i]['WorkTime3']    = '14:00:00';
            $data[$i]['WorkTime4']    = '18:00:00';
            $data[$i]['add_time']     = time();
            //将数据保存到数据库
        }
        $res = Db::name('z_doctor')->insertAll($data);
        if($res){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '添加成功';
        }
        echo json_encode($re_msg);exit;
    }

}