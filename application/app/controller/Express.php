<?php
namespace app\app\controller;

use think\Controller;

class Express extends Controller
{
    public function index()
    {
        $id = input("id",0);
    	$content = "福州中科易达计算机技术有限公司成立于2008年，注册资金801万，是一家集计算机相关软、硬件产品研发、销售与服务为一体的科技创新型企业。几年以来，公司本着“诚信为本、科技创新、以质为本”的企业宗旨，经过全体员工的不懈努力，与广大客户的大力支持下，公司的产品与服务在全国范围得到了广泛的应用于发展，赢得了业界的一致好评与信任！ ";
    	$this->assign("content",$content);
    	$this->assign("Subtitle","快递查询");
        return $this->fetch('index');
    }
}
