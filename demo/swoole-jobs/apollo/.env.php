<?php

if (!function_exists('dd')) {
    function dd($param)
    {
        // header("Content-type:text/json");
        // header("HTTP/1.1 400 Bad Request");
        $data['code'] = 200;
        $data['time'] = time();
        $data['data'] = $param;
        echo json_encode($data);
        die;
    }
}

$host = 'http://122.51.59.5:8080';
$appid = 'mid-admin';

// 示例请求的命名空间 
$namespaces = ['application', 'mysql.json', 'redis.json'];

$secret = 'xxxxyyyy';
// $clientIp = '10.160.2.131';
$cluster = 'default';
$restart = false;
