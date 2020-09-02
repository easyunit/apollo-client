<?php

// 设置默认值
$apollo['app.name'] = $apollo['app.name'] ?? 'Lumen';
$apollo['app.debug'] = $apollo['app.debug'] ?? 'true';
$apollo['app.key']  = $apollo['app.key'] ?? 'XHBNwiH8fiet3a3a7Yyp3pBmDbHT4Yk1';
$apollo['app.url']  = $apollo['app.url'] ?? 'www.mokasz.com';
$apollo['app.timezone']  = $apollo['app.timezone'] ?? 'Asia/Shanghai';

$apollo['cache.driver'] = $apollo['cache.driver'] ?? 'file';

echo '<?php
date_default_timezone_set("Asia/Shanghai");
return $config = [
';

echo <<<EOF
    'system'            => 'swoole-jobs',
    //log目录
    'logPath'               => __DIR__ . '/log',
    'logSaveFileApp'        => 'application.log', //默认log存储名字
    'logSaveFileWorker'     => 'crontab.log', // 进程启动相关log存储名字
    'pidPath'               => __DIR__ . '/log',
    'sleep'                 => 2, // 队列没消息时，暂停秒数
    'queueMaxNum'           => 10, // 队列达到一定长度，发送消息提醒
    'queueMaxNumForProcess' => 10, // 队列达到一定长度，启动动态子进程
    'maxPopNum'             => 50, //子进程最多执行任务数，达到这个数量之后，自动退出
    'excuteTime'            => 600, // 子进程最长执行时间，防止内存泄漏
    'queueTickTimer'        => 1000 * 15, //一定时间间隔（毫秒）检查队列长度;默认10秒钟
    'messageTickTimer'      => 1000 * 180, //一定时间间隔（毫秒）发送消息提醒;默认3分钟
    'processName'           => ':swooleTopicQueue', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    //'eachJobExit'         => false, // true 开启； false 关闭；每个job执行完之后，主动exit,防止业务代码出现（正常不需要开启）

    //job任务相关
    'job'         => [
        //job相关属性
        'profile'=> [
            'maxTime'=> 3, //单个job最大执行时间
            'minTime'=> 0.0001, //单个job最少执行时间
        ],
        'topics'  => [
            //'autoAckBeforeJobStart'    => true, // true 开启； false 关闭；默认为true，job没跑之前是否直接ack,这样业务代码里面有exit、die等致命错误会丢弃消息，防止消息积压
            ['name'=>'MyJob', 'workerMinNum'=>1, 'workerMaxNum'=>3, 'queueMaxNum'=>10000, 'queueMaxNumForProcess' => 100, 'autoAckBeforeJobStart'=>true],
            ['name'=> 'MyJob2', 'workerMinNum'=>1, 'workerMaxNum'=>3, 'autoAckBeforeJobStart'=>true],
            ['name'=> 'MyJob3', 'workerMinNum'=>1, 'workerMaxNum'=>1],
            ['name'=> 'DefaultClassMethod.test1', 'workerMinNum'=>1, 'workerMaxNum'=>2, 'defaultJobClass'=>'DefaultClassMethod', 'defaultJobMethod'=>'test1'],
            ['name'=> 'DefaultClassMethod.test2', 'workerMinNum'=>1, 'workerMaxNum'=>2, 'defaultJobClass'=>'DefaultClassMethod', 'defaultJobMethod'=>'test2'],
            //不需要swoole-jobs消费的队列，只往队列里面写数据
            //['name'=> 'TojavaConsumer'],
        ],
        // redis
        'queue'   => [
            'class'    => '\Kcloze\Jobs\Queue\RedisTopicQueue',

EOF;

// 配置中心 redis配置示例
if ($apollo['redis-type'] == 'yaml') {
    echo "
    'tips' => '暂时不支持yaml格式模板输出'
    ";
} elseif ($apollo['redis-type'] == 'json') {
    // json配置
    $json = json_decode($apollo['redis'], true);
    foreach ($json as $key => $value) {
        echo "            '{$key}' => '{$value}',
";
    }
} else {
    // 普通配置
    echo "            'host' => '{$apollo['redis.host']}',
            'port' => '{$apollo['redis.port']}',
            'pass' => '{$apollo['redis.password']}',
            'dbindex' => '{$apollo['redis.db']}',
";
}
// 配置中心 redis配置示例结束

echo <<<EOF
        ],

        // rabbitmq
        'queue'   => [
            'class'         => '\Kcloze\Jobs\Queue\RabbitmqTopicQueue',
            'host'          => '192.168.3.9',
            'user'          => 'guest',
            'pass'          => 'guest',
            'port'          => '5672',
            'vhost'         => 'php',
            'exchange'      => 'php.amqp.ext',
        ],
   ],

   //框架类型及装载类
   'framework' => [
       //可以自定义，但是该类必须继承\Kcloze\Jobs\Action\BaseAction
       'class'=> '\Kcloze\Jobs\Action\SwooleJobsAction',
   ],
   'message'=> [
        'class'  => '\Kcloze\Jobs\Message\DingMessage',
        'token'  => '**',
   ],
   'httpServer' => [
                'host'    => '0.0.0.0',
                'port'    => 9502,
                'settings'=> [
                    'worker_num'    => 3,
                    'daemonize'     => true,
                    //'max_request'   => 1,
                    'dispatch_mode' => 2,
                    'pid_file'      => __DIR__ . '/log/server.pid',
                    'log_file'      => __DIR__ . '/log/server.log',
            ],
   ],


EOF;

echo '];';
