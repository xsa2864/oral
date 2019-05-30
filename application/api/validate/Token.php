<?php
namespace app\api\validate;

use think\Validate;
/**
 * 生成token参数验证器
 */
class Token extends Validate
{
	
	protected $rule = [
        'appid'       =>  'require',
    ];

    protected $message  =   [
        'appid.require'    => 'appid不能为空',
    ];
}