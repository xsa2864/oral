<?php
/**
 * User: 李昊天
 * Date: 18/7/9
 * Time: 下午11:10
 * Email: haotian0607@gmail.com
 */

namespace app\admin\validate;


use think\Validate;

class AuthGroup extends Validate
{
    protected $rule = [
        'group_name' => 'require|max:30|unique:AuthGroup,group_name',
        'desc' => 'max:200',
        'is_super' => 'require|in:0,1'
    ];

    protected $message = [
        'group_name.require' => '请输入角色名称!',
        'group_name.max' => '角色名称不得大于:rule位字符!',
        'group_name.unique' => '角色名称已经存在!',
        'desc.max' => '觉得介绍不得大于:rule位字符!',
        'is_super.require' => '请选择是否为超级管理员!',
        'is_super.in' => '请选择是否为超级管理员!'
    ];

    protected function sceneEdit()
    {
        return $this->only(['group_name','desc','is_super'])
            ->remove('group_name','unique')
            ->append('group_name','unique::AuthGroup,group_name^id');
    }
}