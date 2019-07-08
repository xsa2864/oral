<?php
namespace app\api\validate;

use think\Validate;
/**
 * 预约信息
 */
class Doctor extends Validate
{
	
	protected $rule = [
        'HOSPITAL_ID'       =>  'require',
        'ORIGINAL_ID'       =>  'require',
        'TEL'               =>  ['require','mobile'],
        'DOCTOR_NAME'       =>  'require',
        'SEX_CODE'          =>  'require|between:0,2',
        'APPELLATION'       =>  'require',
        'WORKER_GS_TIME'    =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
        'WORKER_GE_TIME'    =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
        'WORKER_AS_TIME'    =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
        'WORKER_AE_TIME'    =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
        'INTRO'             =>  'require|max:80',
        'STATUS'            =>  'between:0,1',
    ];

    protected $message  =   [
        'HOSPITAL_ID.require'       => 'HOSPITAL_ID 不能为空',
        'ORIGINAL_ID.require'       => 'ORIGINAL_ID 不能为空',
        'TEL.require'               => true,
        'TEL.mobile'                => 'TEL 手机号错误!',
        'APPELLATION.require'       => 'APPELLATION 职务不能为空',
        'SEX_CODE.require'          => 'SEX_CODE 性别代码不能为空',
        'SEX_CODE.between'          => 'SEX_CODE 性别代码在0-2之间',
        'DOCTOR_NAME.require'       => 'DOCTOR_NAME 医生姓名不能为空',
        'WORKER_GS_TIME.require'    => true,
        'WORKER_GE_TIME.require'    => true,
        'WORKER_AS_TIME.require'    => true,
        'WORKER_AE_TIME.require'    => true,
        'WORKER_GS_TIME.regex'      => 'WORKER_GS_TIME 时间格式错误',
        'WORKER_GE_TIME.regex'      => 'WORKER_GE_TIME 时间格式错误',
        'WORKER_AS_TIME.regex'      => 'WORKER_AS_TIME 时间格式错误',
        'WORKER_AE_TIME.regex'      => 'WORKER_AE_TIME 时间格式错误',
        'INTRO.require'             => true,
        'INTRO.max'                 => 'INTRO 介绍说明最多不能超过240个字符',
        'SEX_CODE.between'          => 'SEX_CODE 状态在0-1之间',
    ];

    //验证身份证是否有效
    function checkIdCard($idcard){     
        // 只能是18位
        if(strlen($idcard)!=18){
            return false;
        }     
        // 取出本体码
        $idcard_base = substr($idcard, 0, 17);     
        // 取出校验码
        $verify_code = substr($idcard, 17, 1);     
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);     
        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for($i=0; $i<17; $i++){
            $total += substr($idcard_base, $i, 1)*$factor[$i];
        }     
        // 取模
        $mod = $total % 11;     
        // 比较校验码
        if($verify_code == $verify_code_list[$mod]){
            return true;
        }else{
            return false;
        }     
    }
}