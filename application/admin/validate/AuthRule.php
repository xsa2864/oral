<?php
/**
 * User: 李昊天
 * Date: 18/7/10
 * Time: 上午1:14
 * Email: haotian0607@gmail.com
 */

namespace app\admin\validate;


use think\Validate;

class AuthRule extends Validate
{
    protected $rule = [
        'rule_name' => 'require|max:300',
        'rule_node' => 'require|max:50',
        'is_menu' => 'require|in:0,1',
        'pid' => 'require|number',
        'menu_ico' => 'max:20',
        'menu_name' => 'max:20',
        'menu_route' => 'max:50',
        'sort' => 'number',
    ];

    protected $message = [
        'rule_name.require' => '请输入规则名称!',
        'rule_name.max' => '规则名称不得大于:rule个字符!',
        'rule_node.require' => '请输入规则节点!',
        'rule_node.max' => '规则节点不得大于:rule个字符!',
        'is_menu.require' => '请选择是否为菜单!',
        'is_menu.in' => '请选择是否为菜单!',
        'pid.require' => '请选择所属规则!',
        'pid.number' => '请选择所属规则!',
        'menu_ico.max' => '菜单图标不得大于:rule个字符!',
        'menu_name.max' => '菜单名称不得大于:rule个字符!',
        'menu_route.max' => '菜单路由不得大于:rule个字符!',
        'sort.number' => '排序必须为正整数!',
    ];
}