<?php
namespace app\admin\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
        'captcha|验证码'=>'require|captcha'
    ];

    protected $message = [
        'username' => '请输入登录账户!',
        'password' => '请输入登录密码!',
    ];
}