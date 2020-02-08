<?php
/**
 * Created by PhpStorm.
 * Users: yf
 * Date: 2019-01-01
 * Time: 20:06
 */

return [
    'SERVER_NAME' => "19diandian",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9501,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time'=>3,
            'package_max_length' => 104857600000,
        ],
        'TASK' => [
            'workerNum' => 4,
            'maxRunningNum' => 128,
            'timeout' => 15,
        ],
        'package_max_length' => 104857600000,
    ],

    'TEMP_DIR' => null,
    'LOG_DIR' => null,
    'PHAR' => [
        'EXCLUDE' => ['.idea', 'Log', 'Temp', 'easyswoole', 'easyswoole.install']
    ],
    'MYSQL' => [
        //数据库配置
        'host'                 => '127.0.0.1',//数据库连接ip
        'user'                 => 'store',//数据库用户名
        'password'             => 'store',//数据库密码
        'database'             => 'store',//数据库
        'port'                 => '3306',//端口
        'timeout'              => '30',//超时时间
        'connect_timeout'      => '5',//连接超时时间
        'charset'              => 'utf8',//字符编码
        'strict_type'          => false, //开启严格模式，返回的字段将自动转为数字类型
        'fetch_mode'           => false,//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        'alias'                => '',//子查询别名
        'isSubQuery'           => false,//是否为子查询
        'max_reconnect_times ' => '3',//最大重连次数
    ],
    /**##################     JWT 配置     #############*/
    'JWT' => [
        'iss' => 'potato', // 发行人
        'exp' => 172800, // 过期时间 默认2天 2*24*60*60=172800
        'sub' => 'potato', // 主题
        'nbf' => NULL, // 在此之前不可用
        'key' => 'potato', // 签名用的key
    ],
    /**##################     WEB 配置      #############*/
    'www'=>[
        'host_img'=>'http://shop.19diandian.com'
    ],

];
