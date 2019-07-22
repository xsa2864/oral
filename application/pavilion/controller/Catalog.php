<?php
namespace app\pavilion\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;


class Catalog extends Controller
{
	public function index()
    {

        $show = input("show",0);
        if(request()->ip()=='192.168.0.239'||request()->ip()=='192.168.0.133' || true){        
            $screen = [
                ['op'=>url('pavilion/display/roomTone'),'title'=>'诊室屏(横)'],
                ['op'=>url('pavilion/display/roomVone'),'title'=>'诊室屏(竖)'],
                ['op'=>url('pavilion/display/hallTone'),'title'=>'综合显示屏(横)'],
                ['op'=>url('pavilion/display/largeVues2'),'title'=>'综合显示屏(横2)'],
                ['op'=>url('pavilion/display/hallVone'),'title'=>'综合显示屏(竖)'],
                ['op'=>url('pavilion/display/operationTone'),'title'=>'手术显示屏'],
            ];
            $download = [
                ['op'=>'/uploads/video/app.apk','title'=>'浏览窗口apk'],
                ['op'=>'/uploads/video/ChromeCore_1249_1.0.4.2.exe','title'=>'谷歌浏览器'],
                ['op'=>'/uploads/video/TTS.apk','title'=>'TTS语音apk'],
                ['op'=>'/uploads/video/iflytechvoiceengine_2013112101.apk','title'=>'语音引擎apk'],
                ['op'=>'/uploads/video/MIPS_DS_Basic_FREE_V4.0.0.apk','title'=>'信息发布apk'],
                ['op'=>'/uploads/video/webclient.rar','title'=>'PC客户端'],
                ['op'=>'/uploads/video/CLodop_Setup_for_Win32NT_https_3.080Extend.zip','title'=>'下载打印驱动'],
            ];
            $admin = [
                ['op'=>url('admin/login/index'),'title'=>'护士站'],
            ];
            $ter = [
                ['op'=>url('pavilion/login/index'),'title'=>'呼叫器'],
            ];
            $fetch = [
                ['op'=>url('pavilion/fetch/showCard'),'title'=>'取票端'],
            ];
            $class = [
                ['op'=>url('pavilion/schedule/showClass'),'title'=>'排班情况'],
            ];
            $code = [
                ['op'=>url('tool/token/getToken'),'title'=>'生成激活码'],
            ];
            $app = [
                ['op'=>'../../app/index/indexs','title'=>'预约通道'],
            ];
            $socket = [
                // ['op'=>'/oral/start.php','title'=>'启动通信'],
                ['op'=>url('pavilion/catalog/showStart'),'title'=>'启动通信服务说明'],
            ];
            $api = [
                ['op'=>url('pavilion/catalog/test'),'title'=>'webservice http api'],
                ['op'=>url('pavilion/catalog/screen'),'title'=>'呼叫 显示 api'],
            ];
            $list = [
                ['show'=>1,'op'=>'','logo'=>'&#xe629;','title'=>'显示屏','data'=>$screen],
                ['show'=>1,'op'=>'','logo'=>'&#xe665;','title'=>'护士站','data'=>$admin],
                ['show'=>1,'op'=>'','logo'=>'&#xe770;','title'=>'呼叫器','data'=>$ter],
                ['show'=>1,'op'=>'','logo'=>'&#xe638;','title'=>'取票端','data'=>$fetch],
                ['show'=>1,'op'=>'','logo'=>'&#xe663;','title'=>'排班情况','data'=>$class],
                ['show'=>1,'op'=>'','logo'=>'&#xe653;','title'=>'生成激活码','data'=>$code],                
                ['show'=>1,'op'=>'','logo'=>'&#xe601;','title'=>'下载','data'=>$download],
                ['show'=>1,'op'=>'','logo'=>'&#xe63c;','title'=>'预约通道','data'=>$app],
                ['show'=>1,'op'=>'','logo'=>'&#xe64c;','title'=>'api接口','data'=>$api],
                ['show'=>1,'op'=>'','logo'=>'&#xe628;','title'=>'启动通信','data'=>$socket],
            ];

            $this->assign("list",$list);
            return $this->fetch("index");
        }
    }
    // 启动服务通信说明
    public function showStart()
    {
        $path = str_replace("public","",$_SERVER['DOCUMENT_ROOT']);
        $this->assign("path",$path);
        return $this->fetch("start");
    }
    
    
	public function getLocalIP() {
	 	$preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
		//获取操作系统为win2000/xp、win7的本机IP真实地址
	 	exec("ipconfig", $out, $stats);
	 	if (!empty($out)) {
            $flag = false;
            foreach ($out AS $row) {
                if(strstr($row, "VirtualBox")){
                    $flag = true;
                }
                if (strstr($row, "IP") && strstr($row, ":") && !strstr($row, "IPv6")) {
                    if($flag){
                        $flag = false;
                        continue;
                    }

	 	 	 	 	$tmpIp = explode(":", $row);
	 	 	 	 	if (preg_match($preg, trim($tmpIp[1]))) {
	 	 	 	 	 	return trim($tmpIp[1]);
	 	 	 	 	}
	 	 	 	}
	 	 	}
	 	}
		//获取操作系统为linux类型的本机IP真实地址
		exec("ifconfig", $out, $stats);
		if (!empty($out)) {
		  	if (isset($out[1]) && strstr($out[1], 'addr:')) {
		  	 	$tmpArray = explode(":", $out[1]);
		  	 	$tmpIp = explode(" ", $tmpArray[1]);
		  	 	if (preg_match($preg, trim($tmpIp[0]))) {
		  	 	 	return trim($tmpIp[0]);
		  	 	}
		  	}
		}
		return '127.0.0.1';
	} 
    public function test()
    {     
        return $this->fetch("debug");
    }
    public function but()
    {     
        $list = [
            ['type'=>0,'url'=>url("api/v2.wsdl/httpSaveDoctor"),'title'=>'http 增加医生','data'=>'{"BODY":{"META":{"TOPIC_ID":" A203","APP_ID":" JQ_HIS "},"ROWS":{"ROW":{"HOSPITAL_ID":"单位ID","HOSPITAL_NAME":"单位名称","HALL_CODE":"诊区代码","HALL_NAME":"诊区名称","DOCTOR_CODE":"医生代码","DOCTOR_NAME":"医生名称 ","SOLELY_ID":"医生工号","QUE_CODE":"就诊队列代码","QUE_NAME":"就诊队列名称","APPELLATION":"职务","TEL":"手机号码 15344955587","INTRO":"介绍说明","PHOTO":"医生照片","SEX_CODE":"性别代码","SEX_NAME":"性别名称","HOUR_SUM":"每小时可以预约数量","NO_CHAR":"号前字符串","START_NO":"起始号 默认：1000","STEP":"步长","WORKER_GS_TIME":"上午上班时间 12:10:00","WORKER_GE_TIME":"上午下班时间 12:10:00","WORKER_AS_TIME":"下午上班时间 12:10:00","WORKER_AE_TIME":"下午下班时间 12:10:00","STATUS":"在职状态（1-在职，0-离职或其他）","OPERATION_STATUS":"操作状态（0=增加 1=修改 2=删除）","ORIGINAL_ID":"唯一值"}}}}'],
            ['type'=>0,'url'=>url("api/v2.wsdl/httpSaveClass"),'title'=>'http 增加排班','data'=>'{"BODY":{"META":{"TOPIC_ID":"A204","APP_ID":"JQ_HIS"},"ROWS":{"ROW":{"ORIGINAL_ID":"唯一值","HOSPITAL_ID":"单位ID","HOSPITAL_NAME":"单位名称","HALL_CODE":"诊区代码","HALL_NAME":"诊区名称","DOCTOR_CODE":"医生代码","DOCTOR_NAME":"医生名称","QUE_CODE":"队列代码","QUE_NAME":"队列名称","SECHEDUAL_DATE":"1,2,3,4,5","STATUS":"操作状态（1=添加或更新 0=删除"}}}}'],
            ['type'=>0,'url'=>url("api/v2.wsdl/httpSavePatient"),'title'=>'http 增加患者','data'=>'{"BODY":{"META":{"TOPIC_ID":"A201","APP_ID":"JQ_HIS"},"ROWS":{"ROW":{"HOSPITAL_ID":"单位ID","HOSPITAL_NAME":"单位名称","HALL_CODE":"诊区代码","HALL_NAME":"诊区名称","QUE_CODE":"队列代码 ","QUE_NAME":"队列名称","DOCTOR_CODE":"医生代码","DOCTOR_NAME":"医生名称","ORIGINAL_ID":"唯一值","CARD_NO":"卡号","IDCARD":"身份证号 350124198912282892","PREFIX":"票号前缀","QUE_NUM":"排队号码","PATIENT_ID":"患者流水号","LOCAL_ID":"患者标识号","NAME":"姓名","TEL":"手机号码 15355895580","SEX_CODE":"性别代码（1-男，2-女，0-未知的性别，9-未说明的性别） ","SEX_NAME":"性别名称","BIRTHDAY":"生日日期","OPERATION_STATUS":"0","STATUS":"操作状态（0=增加1=修改2=删除）","ORDERS":"是否预约 （0=否1=是） ","SIGN_IN":"签到状态（ 0=未签到1=已签到）","FETCH_STATUS":"叫号队列状态 （0=否 1=是，如果是，要就诊日期时间要填写当下日期时间） ","SD_DATE":"就诊日期 2019-06-16","QH_DATE":"取号时间 2019-06-169 09:30:11","START_DATE":"就诊开始时间 09:30:11","END_DATE":"就诊结束时间 09:30:11"}}}}'],
            ['type'=>0,'url'=>url("api/v2.wsdl/saveDoctor"),'title'=>'web service 增加医生','data'=>'<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                      <TOPIC_ID> A203</TOPIC_ID>
                      <APP_ID> JQ_HIS </APP_ID>                   
                    </META>
                    <ROWS>
                        <ROW>
                           <HOSPITAL_ID>单位ID</HOSPITAL_ID>
                            <HOSPITAL_NAME>单位名称</HOSPITAL_NAME>
                            <HALL_CODE>诊区代码</HALL_CODE>
                            <HALL_NAME>诊区名称</HALL_NAME>
                            <DOCTOR_CODE>医生代码</DOCTOR_CODE>
                            <DOCTOR_NAME>医生名称 </DOCTOR_NAME>
                            <SOLELY_ID>医生工号</SOLELY_ID>
                            <QUE_CODE>就诊队列代码</QUE_CODE>
                            <QUE_NAME>就诊队列名称</QUE_NAME>
                            <APPELLATION>职务</APPELLATION>
                            <TEL>手机号码 15344955587</TEL>
                            <INTRO>介绍说明</INTRO>
                            <PHOTO>医生照片</PHOTO>
                            <SEX_CODE>性别代码</SEX_CODE>
                            <SEX_NAME>性别名称</SEX_NAME>
                            <HOUR_SUM>每小时可以预约数量</HOUR_SUM>
                            <NO_CHAR>号前字符串</NO_CHAR>
                            <START_NO>起始号 默认：1000</START_NO>
                            <STEP>步长</STEP>
                            <WORKER_GS_TIME>上午上班时间 12:10:00</WORKER_GS_TIME>
                            <WORKER_GE_TIME>上午下班时间 12:10:00</WORKER_GE_TIME>
                            <WORKER_AS_TIME>下午上班时间 12:10:00</WORKER_AS_TIME>
                            <WORKER_AE_TIME>下午下班时间 12:10:00</WORKER_AE_TIME>
                            <STATUS>在职状态（1-在职，0-离职或其他）</STATUS>
                            <OPERATION_STATUS>操作状态（0=增加 1=修改 2=删除）</OPERATION_STATUS>
                            <ORIGINAL_ID>唯一值</ORIGINAL_ID>
                        </ROW>                       
                    </ROWS>
                  </BODY>
                </MESSAGE>'],
            ['type'=>0,'url'=>url("api/v2.wsdl/saveClass"),'title'=>'web service 增加排班','data'=>'<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                    <BODY>
                        <META>
                          <TOPIC_ID>A204</TOPIC_ID>                        
                          <APP_ID>JQ_HIS</APP_ID>                        
                        </META>
                        <ROWS>
                            <ROW>
                                <ORIGINAL_ID>唯一值</ORIGINAL_ID>
                                <HOSPITAL_ID>单位ID</HOSPITAL_ID>
                                <HOSPITAL_NAME>单位名称</HOSPITAL_NAME>
                                <HALL_CODE>诊区代码</HALL_CODE>
                                <HALL_NAME>诊区名称</HALL_NAME>
                                <DOCTOR_CODE>医生代码</DOCTOR_CODE>
                                <DOCTOR_NAME>医生名称</DOCTOR_NAME>
                                <QUE_CODE>队列代码</QUE_CODE>
                                <QUE_NAME>队列名称</QUE_NAME>
                                <SECHEDUAL_DATE>1,2,3,4,5</SECHEDUAL_DATE>
                                <STATUS>操作状态 1=添加或更新 0=删除</STATUS>
                            </ROW>
                        </ROWS>
                    </BODY>
                </MESSAGE>'],
            ['type'=>0,'url'=>url("api/v2.wsdl/savePatient"),'title'=>'web service 增加患者','data'=>'<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                      <TOPIC_ID>A201</TOPIC_ID>
                      <APP_ID>JQ_HIS</APP_ID>                   
                    </META>
                    <ROWS>
                        <ROW>
                            <HOSPITAL_ID>单位ID</HOSPITAL_ID>
                            <HOSPITAL_NAME>单位名称</HOSPITAL_NAME>
                            <HALL_CODE>诊区代码</HALL_CODE>
                            <HALL_NAME>诊区名称</HALL_NAME>
                            <QUE_CODE>队列代码 </QUE_CODE>
                            <QUE_NAME>队列名称</QUE_NAME>
                            <DOCTOR_CODE>医生代码</DOCTOR_CODE>
                            <DOCTOR_NAME>医生名称</DOCTOR_NAME>
                            <ORIGINAL_ID>唯一值</ORIGINAL_ID>
                            <CARD_NO>卡号</CARD_NO>
                            <IDCARD>身份证号 350124198912282892</IDCARD>
                            <PREFIX>票号前缀</PREFIX>
                            <QUE_NUM>排队号码</QUE_NUM>
                            <PATIENT_ID>患者流水号</PATIENT_ID>
                            <LOCAL_ID>患者标识号</LOCAL_ID>
                            <NAME>姓名</NAME>
                            <TEL>手机号码 15355895580</TEL>
                            <SEX_CODE>性别代码（1-男，2-女，0-未知的性别，9-未说明的性别） </SEX_CODE>
                            <SEX_NAME>性别名称</SEX_NAME>
                            <BIRTHDAY>生日日期</BIRTHDAY>
                            <OPERATION_STATUS>0</OPERATION_STATUS>
                            <STATUS>操作状态（0=增加1=修改2=删除）</STATUS>
                            <ORDERS>是否预约 （0=否1=是） </ORDERS>
                            <SIGN_IN>签到状态（ 0=未签到1=已签到）</SIGN_IN>
                            <FETCH_STATUS>叫号队列状态 （0=否 1=是，如果是，要就诊日期时间要填写当下日期时间） </FETCH_STATUS>
                            <SD_DATE>就诊日期 2019-06-16</SD_DATE>
                            <QH_DATE>取号时间 2019-06-169 09:30:11</QH_DATE>
                            <START_DATE>就诊开始时间 09:30:11</START_DATE>
                            <END_DATE>就诊结束时间 09:30:11</END_DATE>
                        </ROW>
                    </ROWS>
                  </BODY>
                </MESSAGE>'],
            ['type'=>1,'url'=>'/uploads/video/2019-06-25排队叫号接口文档2.doc','title'=>'下载接口入参说明文档'],            
        ];
        return json($list);
    }

    public function screen()
    {        
        return $this->fetch("screen");
    }

     public function buts()
    {     
        $list = [
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>1,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'呼叫','data'=>''],
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>2,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'重呼','data'=>''],
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>3,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'过号','data'=>''],
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>4,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'完成','data'=>''],
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>5,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'停诊','data'=>""],
            ['type'=>0,'url'=>url("api/v2.Quick/quickQueue",['type'=>6,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'保安','data'=>''],
            ['type'=>0,'url'=>url("api/v2.login/login",['username'=>1001,'password'=>"123456"]),'title'=>'获取TOKEN','data'=>''],
            ['type'=>0,'url'=>url("api/v2.login/getTerminal",['hall_id'=>2]),'title'=>'获取终端设备','data'=>''],
            ['type'=>0,'url'=>url("api/v2.login/setUp",['terminal_id'=>13,'ip'=>"68-F7-28-48-DE-99"]),'title'=>'绑定呼叫器终端','data'=>''],
            ['type'=>0,'url'=>url("api/v2.Call/operCall",['token'=>'1:1562924040:71623613dcd263d65bcf67f6b48f0e03','ip'=>'68-F7-28-48-DE-99']),'title'=>'推送信息','data'=>'[{"prefix":"w","code":"1002","name":"\u6797\u5c0f\u5170","status":"2","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1003","name":"\u9648\u519b","status":"1","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1004","name":"\u674e\u56db","status":"1","order":"0","title":"\u5916\u79d1"}]'],
            ['type'=>0,'url'=>url("api/v2.Call/setVoice",['token'=>'1:1562924040:71623613dcd263d65bcf67f6b48f0e03','ip'=>"68-F7-28-48-DE-99"]),'title'=>'语音播放','data'=>'[{"prefix":"w","code":"1002","name":"\u6797\u5c0f\u5170","status":"2","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1003","name":"\u9648\u519b","status":"1","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1004","name":"\u674e\u56db","status":"1","order":"0","title":"\u5916\u79d1"}]'],
            ['type'=>1,'url'=>'/uploads/video/中科易达接口api文档2019-07-03.doc','title'=>'下载接口入参说明文档'],            
        ];
        return json($list);
    }
    
}