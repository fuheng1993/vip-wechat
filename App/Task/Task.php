<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/11/20 0020
 * Time: 10:44
 */

namespace App\Task;


use EasySwoole\Task\AbstractInterface\TaskInterface;


class Task implements TaskInterface
{
    protected $data;
    //通过构造函数,传入数据,获取该次任务的数据
    public function __construct($data)
    {
        $this->data = $data;
    }

    function run(int $taskId, int $workerIndex)
    {
        \Swoole\Coroutine::sleep(3);
        var_dump("模板任务运行".$workerIndex);
        //只有同步调用才能返回数据
        return "返回值:".$this->data['name'];
        // TODO: Implement run() method.
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // TODO: Implement onException() method.
    }
}
