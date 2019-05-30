<?php 
namespace app\pavilion\model;

use think\Model;
use think\facade\Env;

class Sentence extends Model
{
	/*
	 * 综合显示屏 处理队列
	 */
	public function hallList($data='')
	{
		$queue = array();
		if(cache("queue")){
			$json = cache("queue");
            $queue = json_decode($json,1);            
		}
		$queue[$data['hall_id']]['now'] = isset($data['now'])?$data['now']:'';
		$queue[$data['hall_id']]['list'][$data['que_id']]['title'] 		= $data['title'];
		$queue[$data['hall_id']]['list'][$data['que_id']]['name'] 		= $data['name'];
		$queue[$data['hall_id']]['list'][$data['que_id']]['que_name']	= $data['que_name'];
		$queue[$data['hall_id']]['list'][$data['que_id']]['room_name'] 	= $data['room_name'];
		$queue[$data['hall_id']]['list'][$data['que_id']]['items'] 		= $data['items'];
		$jqueue = json_encode($queue);
		cache('queue',$jqueue);
		return $queue;
	}
	/*
	 *  综合显示屏 队列注销
	 */
	public function unsetHallList($hall_id=0,$que_id=array())
	{
		$queue = array();
		if(cache("queue")){
			$json = cache("queue");
            $queue = json_decode($json,1);  
            if($que_id){
            	foreach ($que_id as $key => $value) {
		            if(isset($queue[$hall_id]['list'][$value])){
		            	unset($queue[$hall_id]['list'][$value]);
		            }     
            	}
            }
            cache('queue',json_encode($queue));     
		}
	}

	/*
	 * 综合显示屏 处理队列
	 */
	public function hallLists($data='')
	{
		$queue = array();
		if(cache("queue")){
			$json = cache("queue");
            $queue = json_decode($json,1);
            if(!empty($data)){            	
	            foreach ($queue as $key => $value) {
	            	if($value['doctor_id']==$data['doctor_id']){
	            		unset($queue[$key]);
	            	}
	            }            
	            array_unshift($queue,$data);
            }
		}else{
			$queue[0]['doctor_id'] = $data['doctor_id'];
			$queue[0]['now'] 	= $data['now'];
			$queue[0]['title'] 	= $data['title'];
			$queue[0]['name'] 	= $data['name'];
		}
		$jqueue = json_encode($queue);
		cache('queue',$jqueue);
		return $queue;
	}

	/*
	 * 语句整理[ type 0=显示屏 1=语音] [screen_type 0=诊室屏 1=综合屏] hall_id 区域ID
	 * return string
	 */
	public function houseString($result,$hall_id=0,$screen_type=0,$type=0)
	{
		$wh = array();
        $wh[] = ['hall_id','=',$hall_id];
        $wh[] = ['type','=',$type];
        if($type==0){
        	$wh[] = ['screen_type','=',$screen_type];
        }
        $voice = db("z_voice")->where($wh)->find();
        $rule = $voice['rule'];
        if($voice){
        	$order = '';
			if($result['order']==1 && $type==0){
				$order = '<span>(预约)</span>';
			}
            $rule = str_replace("[hall]",isset($result['title'])?$result['title']:'',$rule);
			$rule = str_replace("[queue]",isset($result['room_name'])?$result['room_name']:'',$rule);
            $rule = str_replace("[code]",isset($result['code'])?$result['prefix'].$result['code'].$order:'',$rule);
            $rule = str_replace("[num]",isset($result['seat_name'])?$result['seat_name']:'',$rule);
			$rule = str_replace("[note]",isset($result['note'])?$result['note']:'',$rule);
			$rule = str_replace("[doctor]",isset($result['doctor'])?$result['doctor']:'',$rule);
			$rule = str_replace("[status]",isset($result['status_name'])?$result['status_name']:'',$rule);
			
			if($type==1){
				$ve['str'] = str_replace("[name]",isset($result['name'])?$result['name']:'',$rule);
				$ve['addr_id'] = $voice['addr'];
				$rule = $ve;
			}else{
            	$rule = str_replace("[name]",isset($result['name'])?$this->pregName($result['name']):'',$rule);
			}

        }else if($type==3){
        	// 诊室屏就诊中的显示
        	$rule .= isset($result['prefix']) ? $result['prefix']:'';
        	$rule .= isset($result['code']) ? $result['code']:'';        
        	$rule .= isset($result['name']) ? $this->pregName($result['name']):'';
        }else if($type!=1){
        	$rule .= isset($result['name'])?$this->pregName($result['name']):'';
        	$rule .= isset($result['code'])?$result['code']:'';
        	$rule .= isset($result['doctor'])?$result['doctor']:'';
        	$rule .= isset($result['status_name'])?$result['status_name']:'';
        }
        return $rule;
	}
	/*
	 * 语句整理 
	 * return string
	 */
	public function houseStr($result)
	{
		$order = '';
		if($result['order']==1){
			$order = '<span style="font-size: 1.1vw;">(预约)</span>';
		}
        $rule = $result['prefix'].$result['code'].$order."&nbsp;".$this->pregName($result['name']);        
        return $rule;
	}

	/*
	 * 判断是否包含中文字符 用户名用*号处理：
	 * return string
	 */
	public function pregName($str='')
	{
		if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
			//按照中文字符计算长度
			$len = mb_strlen($str, 'UTF-8');
			//echo '中文';
			if($len >= 3){
				//三个字符或三个字符以上掐头取尾，中间用*代替
				$str = mb_substr($str, 0, 1, 'UTF-8') . ' * ' . mb_substr($str, 2, $len-2, 'UTF-8');
			} elseif($len == 2) {
				//两个字符
				$str = mb_substr($str, 0, 1, 'UTF-8') . ' * ';
			}
		} else {
			//按照英文字串计算长度
			$len = strlen($str);
			//echo 'English';
			if($len >= 3) {
				//三个字符或三个字符以上掐头取尾，中间用*代替
				$str = substr($str, 0, 1) . ' * ' . substr($str, -1);
			} elseif($len == 2) {
				//两个字符
				$str = substr($str, 0, 1) . ' * ';
			}
		}
		return $str;
	}
}