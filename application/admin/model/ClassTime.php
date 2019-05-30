<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class ClassTime extends Model
{
	/*
	 * 医生看诊队列
	 * return boolse
	 */
	public static function makeQueue($str='',$doctor_id=0)
	{
		$arr = array();
		$num = 0;
		$flag = false;
		if(!empty($str) && $doctor_id){
			$arr = array_filter(explode('|', $str));
			$num = count($arr);
			// $where[] = ['que_id','in',$arr];
			$where[] = ['doctor_id','=',$doctor_id];
			$result = db("z_doctor_class")->where($where)->column("que_id");
			// if(count($result)<$num){
			// 	foreach ($arr as $key => $value) {
			// 		if(!in_array($value, $result)){
			// 			$data['doctor_id']  = $doctor_id;
			// 			$data['que_id']		= $value;
			// 			$rs = db("z_doctor_class")->insert($data);
			// 			if($rs && !$flag){
			// 				$flag = true;
			// 			}						
			// 		}
			// 	}
			// }
			if($result){
				foreach ($arr as $key => $value) {
					if(!in_array($value, $result)){
						$data['doctor_id']  = $doctor_id;
						$data['que_id']		= $value;
						$rs = db("z_doctor_class")->insert($data);
						if($rs && !$flag){
							$flag = true;
						}						
					}
				}
				foreach ($result as $key => $value) {
					if(!in_array($value, $arr)){
						$data['doctor_id']  = $doctor_id;
						$data['que_id']		= $value;
						$rs = db("z_doctor_class")->where($data)->delete();
						if($rs && !$flag){
							$flag = true;
						}						
					}
				}
			}else{
				foreach ($arr as $key => $value) {
					$data['doctor_id']  = $doctor_id;
					$data['que_id']		= $value;
					$rs = db("z_doctor_class")->insert($data);
					if($rs && !$flag){
						$flag = true;
					}	
				}
			}
		}
		return $flag;
	}

	/*
	 * 相应队列在线医生
	 * return Array
	 */
	public static function workDoctor($que_id=0)
	{
		$data = array();
		$wh[] = ['dc.que_id','=',$que_id];
		$drs = db("z_doctor")->alias("d")->field("d.id,d.QueName,dc.class")
				->leftJoin("z_doctor_class dc","dc.doctor_id=d.id")
				->where($wh)
				->select();
		foreach ($drs as $key => $value) {
			$flag = self::checkDetailTimes($value['class']);
			if($flag){
				$data[] = $value;
			}
		}
		return $data;
	}

	/*
	 * 分析10进制数值，判断是否是在上班时间内(上下午)
	 * reutrn boolse
	 */
    public static function checkDetailTimes($values=0){
        $da = date("w",time());
        $hour = date("H",time())>12?1:0;
    	$calssArr = self::arrangeClass($values,false);
    	$flag = false;
		foreach ($calssArr as $key => $value) {
			if($value>0){				
	        	if($key==0 || $key==1){
	        		$n = 1;
	        	}else if($key==2 || $key==3){
	        		$n = 2;
	        	}else if($key==4 || $key==5){
	        		$n = 3;
	        	}else if($key==6 || $key==7){
	        		$n = 4;
	        	}else if($key==8 || $key==9){
	        		$n = 5;
	        	}else if($key==10 || $key==11){
	        		$n = 6;
	        	}else if($key==12 || $key==13){
	        		$n = 0;
	        	}
		        if($n==$da){
					if($key%2==$hour){
			        	$flag = true;
					}
		        }
			}
		}
        return $flag;
    }

	/*
	 * 解析排班
	 * 排班时间处理 $value Int
	 * return array
	 */
	public static function arrangeClass($value=0,$flag=true)
	{
		$str = decbin($value);
        $arr = str_split($str);
        $arrs = array_reverse($arr);	//以相反的顺序返回数组。
        if($flag){        	
	        $num = count($arrs);
	        for ($i=0; $i < (14-$num); $i++) { 
	        	array_push($arrs, 0);
	        }
        }
		return $arrs;
	}
	/*
	 * 加密排班
	 * 排班时间处理 $arr Array 
	 * return int
	 */
	public static function binDecClass($arr='')
	{
        $str = '';
        if(empty($arr[0]) && $arr[0]==''){
        	$value = 0;
        }else{        	
	        for ($i=13; $i >= 0; $i--) { 
	        	if(in_array($i, $arr)){
	        		$str .= '1';
	        	}else{
	        		$str .= '0';
	        	}
	        }
			$value = bindec($str);
        }
		return $value;
	}

	/*
	 * 出上班时间
	 * rturn Array
	 */
    public static function checktime($doctor_id=0,$que_id=0){
    	$where[] = ['c.doctor_id','=',$doctor_id];
    	$where[] = ['c.que_id','=',$que_id];
    	$ser = db("z_doctor_class")->alias("c")
    			->field("c.*,d.*")
    			->leftJoin("z_doctor d","d.id=c.doctor_id")
    			->where($where)
    			->find();
        $date_w = array();
        $date_c = array();  
        $data_n = array();
        $date_week = array();
        $date_num  = array();
	    $arr_all   = array();
	    $all_num   = 0;
        $week = array('0'=>'日','1'=>'一','2'=>'二','3'=>'三','4'=>'四','5'=>'五','6'=>'六');
		
		if($ser){
	        // 获取上班时间 周几
	    	$calssArr = self::arrangeClass($ser['class']);			
	        // 一共可以预约名额
	        $m_time     = strtotime($ser['WorkTime2'])-strtotime($ser['WorkTime1']);
	        $a_time     = strtotime($ser['WorkTime4'])-strtotime($ser['WorkTime3']);
	        $all_num    = $m_time/3600*$ser['HourSum']+$a_time/3600*$ser['HourSum'];

	        foreach ($calssArr as $key => $value) {
	        	if($value>0){
	        		if($key==0 || $key==1){
	        			$n = 1;
	        		}else if($key==2 || $key==3){
	        			$n = 2;
	        		}else if($key==4 || $key==5){
	        			$n = 3;
	        		}else if($key==6 || $key==7){
	        			$n = 4;
	        		}else if($key==8 || $key==9){
	        			$n = 5;
	        		}else if($key==10 || $key==11){
	        			$n = 6;
	        		}else if($key==12 || $key==13){
	        			$n = 0;
	        		}
	        		$date_w[] = $n;
	        		// 统计诊断量
		        	if($key%2==0){
		                $count_all = $m_time/3600*$ser['HourSum'];	               
		        	}else{
		        		$count_all = $a_time/3600*$ser['HourSum'];
		        	}
		            $arr_all[$n] = isset($arr_all[$n])?($arr_all[$n]+$count_all):$count_all;
	        	}  
	        }       
	        $date_w = array_unique($date_w); //去重

	        $config     = db("config_fetch")->where("unitid",1)->find();
	        
	        // 判断上班时间具体日期
	        for ($i=1; $i <= 7; $i++) { 
	            if($config['today']){
	                $date = date("Y-m-d",strtotime("+".($i-1)." day")); 
	            }else{
	                $date = date("Y-m-d",strtotime("+".$i." day")); 
	            }
	            $da = date("w",strtotime($date));
	            if(in_array($da, $date_w)){
	                $date_week[] = $week[$da];
	                if(!empty($arr_all)){
	                    $da = $da==0?7:$da;
	                    $date_num[] = isset($arr_all[$da])?$arr_all[$da]:'';
	                }
	                $date_c[] = $date;
	                $data_n[] = self::getNumDespeak($date,'',$que_id,$doctor_id);
	            }            
	        }
		}    

        $data['data']       = $date_c;
        $data['data_n']     = $data_n;
        $data['date_num']   = $date_num;
        $data['all_num']    = $all_num;
        $data['arr_all']    = $arr_all;
        $data['date_week']  = $date_week;
        return $data;
    }
    /*
     * 查询预约人数
     * $day 按天查询  $time 具体时间
     */
    public static function getNumDespeak($day='',$time='',$que_id=0,$doctor_id=0){
        $where = "despeakDate = '".date("Y-m-d",strtotime($day))."' and status=1";
        if($time!=''){
            $where .= ' and time_Part_S="'.$time.'"';
        }
        if($que_id){
            $where .= ' and queId="'.$que_id.'"';
        }
        if($doctor_id){
            $where .= ' and doctor_id="'.$doctor_id.'"';
        }
        $sql = "select count(*) as num from t_despeak where $where";
        $result = Db::query($sql);
        return $result[0]['num'];
    }
    /*
     * 根据日期，获取上班时间点
     */
    public static function getCheckTimes($doctor_id=0,$que_id=0,$ndata=''){
    	$where[] = ['c.doctor_id','=',$doctor_id];
    	$where[] = ['c.que_id','=',$que_id];
    	$result = db("z_doctor_class")->alias("c")
    			->field("c.*,d.*")
    			->leftJoin("z_doctor d","d.id=c.doctor_id")
    			->where($where)
    			->find();

    	$data = array();
        $number = array();
        $time = array();
    	if(!empty($result)){    		
	    	$m_time = (strtotime($result['WorkTime2'])-strtotime($result['WorkTime1']))/3600;
	        $a_time = (strtotime($result['WorkTime4'])-strtotime($result['WorkTime3']))/3600;
            $num = self::checkDetailTime($result['class'],$ndata);
            if($num==1 || $num==3){                
    	        for ($i=0; $i < $m_time; $i++) { 
    	        	$time1 = date("H:i",strtotime($result['WorkTime1']." +".$i." hour"));
    	        	$time2 = date("H:i",strtotime($result['WorkTime1']." +".($i+1)." hour")); 
    	        	if(strtotime($ndata.' '.$time2) > (time()+10800)){
	    	            $time[] = $time1.'-'.$time2;
	    	            $number[] = self::getNumDespeak($ndata,date("H:i:s",strtotime($result['WorkTime1']." +".$i." hour")),0,$result['id']);
    	        	}
    	        }
            }
            if($num==2 || $num==3){    
    	        for ($n=0; $n < $a_time; $n++) { 
    	        	$time1 = date("H:i",strtotime($result['WorkTime3']." +".$n." hour"));
    	        	$time2 = date("H:i",strtotime($result['WorkTime3']." +".($n+1)." hour")); 
    	        	if(strtotime($ndata.' '.$time2) > (time()+10800)){
	    	            $time[] = $time1.'-'.$time2;
	    	            $number[] = self::getNumDespeak($ndata,date("H:i:s",strtotime($result['WorkTime3']." +".$n." hour")),0,$result['id']);
    	        	}
    	        }
            }

	        $data['HourSum'] = $result['HourSum'];
	        $data['time'] = $time;
	        $data['number'] = $number;
            $data['class'] = $num;
    	}
    	return $data;
    }
    // 上班具体时间
    public static function checkDetailTime($values=0,$date=''){
        // 获取上班时间 周几
        $num = 0;
        $da = date("w",strtotime($date));
    	$calssArr = self::arrangeClass($values,false);
		foreach ($calssArr as $key => $value) {
			if($value>0){
	        	if($key==0 || $key==1){
	        		$n = 1;
	        	}else if($key==2 || $key==3){
	        		$n = 2;
	        	}else if($key==4 || $key==5){
	        		$n = 3;
	        	}else if($key==6 || $key==7){
	        		$n = 4;
	        	}else if($key==8 || $key==9){
	        		$n = 5;
	        	}else if($key==10 || $key==11){
	        		$n = 6;
	        	}else if($key==12 || $key==13){
	        		$n = 0;
	        	}
		        if($n==$da){
					if($key%2==0){
			        	$num = $num+1;
					}else{
						$num = $num+2;
					}
		        }
			}
		}
        return $num;
    }
    /*
     * 队列是否在上班时间内
     *
     */
    public function queueValid($arr='')
    {
    	$q_list = array();
    	if($arr){
			$w = date("w",time())?date("w",time()):7;
			$h = date("H",time())>=12?2:1;
			foreach ($arr as $key => $value) {
				$arr = explode('-', $value['ClassesTime']);
				foreach ($arr as $keys => $val) {
					$sp = str_split($val);
					if($sp[0]==$w){
						if($sp[1]==$h){
							$q_list[] = $value;
						}
					}
				}
			}
		}
		return $q_list;
    }
}