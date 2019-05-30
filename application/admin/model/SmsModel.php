<?php 
namespace app\admin\model;

use think\Model;
use think\facade\Env;

class SmsModel extends Model
{
    /*
     * app端预约
	 * 是否发送短信
     */
	public function remind_sms($unitid=0,$mobile,$falg=true)
	{
		$rs = db("sms_config")->where("unitid=$unitid")->find();
		if($rs){
            if($rs['status']==1 && $rs['number']>$rs['used'] && $rs['mark_ok']>0){
                $result = db("despeak")->where("mobile=$mobile")->order("despeak_id desc")->find();
                if($result){            
                    $content = $this->getContent($rs['mark_ok'],$result);
                    if($content){
    				    $this->send($unitid,$mobile,$content);
                    }
                }
			}
		}
	}
    /*
     * app端预约
     * 取消预约发送短信
     */
    public function cancel_sms($id){
        $result = db("despeak")->where("despeak_id",$id)->find();
        if($result){
            $rs = db("sms_config")->where("unitid",$result['unitId'])->find();
            if($rs['status']==1 && $rs['number']>$rs['used'] && $rs['mark_cancel']>0){
                $content = $this->getContent($rs['mark_cancel'],$result);
                if($content){
                    $this->send($result['unitId'],$result['mobile'],$content);
                }
            }
        }
    }
    // 替换变量
    public function getContent($id,$result){
        $content = db("sms_temp")->where('id',$id)->value("content");
        if(!empty($content)){            
            $content = str_replace("[name]",$result['name']?$result['name']:'',$content);
            $content = str_replace("[date]",$result['despeakDate'].' '.$result['time_Part_S'].'~'.$result['time_Part_O'],$content);
            $content = str_replace("[code]",($result['noChar']?$result['noChar']:'').($result['queNo']?$result['queNo']:''),$content);
            $content = str_replace("[hall]",$result['hallName']?$result['hallName']:'',$content);
            $content = str_replace("[doctor]",$result['queName']?$result['queName']:'',$content);
        }
        return $content;
    }
    // 替换变量
    public function replaceContent($content,$result){
        if(!empty($content)){            
            $content = str_replace("[name]",$result['name']?$result['name']:'',$content);
            $content = str_replace("[date]",(isset($result['despeakDate'])?$result['despeakDate']:'').' '.(isset($result['time_Part_S'])?$result['time_Part_S']:'').'~'.(isset($result['time_Part_O'])?$result['time_Part_O']:''),$content);
            $content = str_replace("[code]",(isset($result['noChar'])?$result['noChar']:'').(isset($result['queNo'])?$result['queNo']:''),$content);
            $content = str_replace("[hall]",isset($result['hallName'])?$result['hallName']:'',$content);
            $content = str_replace("[doctor]",isset($result['queName'])?$result['queName']:'',$content);
        }
        return $content;
    }
	// 发送短信
	public function send($unitid,$mobile,$content){
        $sms = new \alisms\SendSms;
        
        $signName = '中科易达';
        $templateCode = 'SMS_137780004';        

        $data['mobile']  = $mobile;
        $data['temp']    = $templateCode;
        $data['sign']    = $signName;
        $data['content'] = $content;
        $data['unitid']  = $unitid;
        $data['addtime'] = time();
        $id = db("sms_log")->insertGetId($data);

        $templateParam = array("code"=>$content);
        $m = $sms::sendSms($mobile,$signName,$templateCode,$templateParam);       
        $data = array();
        $rs = 0;
        $js = json_encode($m);
        $arr = json_decode($js,1);
        if($arr['Message']=="OK"){
            $rs = db("sms_log")->where("id",$id)->update(['status'=>1]);
            $rss = db('sms_config')->where('unitid', $unitid)->setInc('used');
        }
        return $rs;
	}
    // 判断短信数量是否足够
    public function checkSmsNum($unit_id=0){
        $num = 0;
        $result = db("sms_config")->where("unitid",$unit_id)->find();
        if($result){
            $num = $result['number'] - $result['used'];
        }
        return $num;
    }

    // 增加短信记录
    public function smsAddLog($userid,$unit_id,$number,$note){
        $data['manager_id'] = $userid;
        $data['unit_id']    = $unit_id;
        $data['number']     = $number;
        $data['note']       = $note;
        $data['add_time']   = time();
        $rs = db("sms_add_log")->insertGetId($data);
        return $rs;
    }
}