<?php
namespace app\api\validate;

use think\Validate;
/**
 * 预约信息
 */
class Patient extends Validate
{
	
	protected $rule = [
        'HOSPITAL_ID'       =>  'require',
        'HALL_CODE'         =>  'require',
        'QUE_CODE'          =>  'require',
        'ORIGINAL_ID'       =>  'require',
        'IDCARD'            =>  'require|idCard',
        'TEL'               =>  ['require','mobile'],
        'DOCTOR_CODE'       =>  'require',
        'DOCTOR_NAME'       =>  'require',
        'SEX_CODE'          =>  'require|between:0,2',
        'SD_DATE'           =>  ['require','regex'=>"/^\d{4}-\d{2}-\d{2}$/"],
        'START_DATE'        =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
        'END_DATE'          =>  ['require','regex'=>"/^\d{2}:\d{2}:\d{2}$/"],
    ];

    protected $message  =   [
        'HOSPITAL_ID.require'       => 'HOSPITAL_ID 不能为空',
        'HALL_CODE.require'         => 'HALL_CODE 不能为空',
        'QUE_CODE.require'          => 'QUE_CODE 不能为空',
        'ORIGINAL_ID.require'       => 'ORIGINAL_ID 不能为空',
        'IDCARD.require'            => true,
        'IDCARD.idCard'             => 'IDCARD 身份证号不正确',
        'TEL.require'               => true,
        'TEL.mobile'                 => 'TEL 手机号错误',
        'DOCTOR_CODE.require'       => 'DOCTOR_CODE 医生姓名不能为空',
        'DOCTOR_NAME.require'       => 'DOCTOR_NAME 医生姓名不能为空',
        'SEX_CODE.require'          => 'SEX_CODE 性别代码不能为空',
        'SEX_CODE.between'          => 'SEX_CODE 状态在0-1之间',
        'START_DATE.require'        => 'START_DATE 不能为空',
        'START_DATE.require'        => 'START_DATE 不能为空',
        'SD_DATE.require'           => 'SD_DATE 不能为空',
        'START_DATE.regex'          => 'START_DATE 时间格式错误',
        'END_DATE.regex'            => 'END_DATE 时间格式错误',
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