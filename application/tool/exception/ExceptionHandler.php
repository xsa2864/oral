<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/14/014
 * Time: 2:56
 */

namespace app\tool\exception;

use app\tool\Tool;
use Exception;
use think\exception\Handle;
use think\Request;
use think\Log;

class ExceptionHandler extends Handle
{
    private $status;  //HTTP状态吗
    private $msg;   //错误具体信息
    private $data;  //错误码
    private $code;

    public function render(Exception $e)
    {
        //如果是自定义异常处理类
        if ($e instanceof Base) {
            $this->status = $e->status;     //给状态吗赋值
            $this->msg = $e->msg;       //给错误信息赋值
            $this->data = $e->data;   //给错误码复制
            $this->code = $e->code;   //给错误码复制
        } else {
            if (\think\facade\Config::get('app_debug')) {
                //如果在测试环境下直接抛出异常
                return parent::render($e);
            } else {
                //如果是在生产环境下将直接抛出服务器错误的异常
                $this->status = 500;
                $this->msg = "服务器内部错误";
                $this->data = '';
            }
            //如果是服务器错误 那么就将错误记录到日志里面
            $this->recordErrorLog($e);
        }
        //返回JSON结构的数据
        return Tool::showData($this->status, $this->msg, $this->data, $this->code);
    }

    //写日志
    private function recordErrorLog(Exception $e)
    {
        Log::init([
            'type' => 'File',
            // 日志保存目录
            'path' => LOG_PATH,
            // 日志记录级别
            'level' => ['error'],
        ]);

        Log::record($e->getMessage(), 'error');
    }
}