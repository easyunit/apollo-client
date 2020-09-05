<?php

// 查看所有配置
// dd($apollo);

// 设置默认值
$apollo['debug'] = $apollo['debug'] ?? 'false';
$apollo['trace'] = $apollo['trace'] ?? 'false';

$apollo['fastadmin.usercenter'] = $apollo['fastadmin.usercenter'] ?? 'false';
$apollo['fastadmin.captcha'] = $apollo['fastadmin.captcha'] ?? 'false';

echo "[app]
debug = {$apollo['debug']}
trace = {$apollo['trace']}
";

echo "
[apollo]
host = {$apollo['host']}
secret = {$apollo['secret']}
";

echo "
[fastadmin]
usercenter = {$apollo['fastadmin.usercenter']}
captcha = {$apollo['fastadmin.captcha']}

";

// 写入mysql配置项

echo "
[database]
";
if ($apollo['mysql-type'] == 'yaml') {
    $apollo['mysql'] = str_replace(": ", " = ", $apollo['mysql']);
    echo "{$apollo['mysql']}
";
} elseif ($apollo['mysql-type'] == 'json') {
    // 处理json格式数据
    $json = json_decode($apollo['mysql'], true);
    foreach ($json as $key => $value) {
        echo "{$key} = '{$value}'
";
    }
} else {

    // 处理普通格式数据
    echo "
hostname = {$apollo['mysql.host']}
hostport = {$apollo['mysql.port']}
username = {$apollo['mysql.user']}
password = '{$apollo['mysql.password']}'
database = {$apollo['mysql.db']}
prefix = m_
";
}

// 写入redis配置
echo "
[redis]
";
if ($apollo['redis-type'] == 'yaml') {
    $apollo['redis'] = str_replace(": ", " = ", $apollo['redis']);
    echo "{$apollo['redis']}
";
} elseif ($apollo['redis-type'] == 'json') {
    $json = json_decode($apollo['redis'], true);
    foreach ($json as $key => $value) {
        echo "{$key} = '{$value}'
";
    }
} else {
    echo "host = {$apollo['redis.host']}
port = {$apollo['redis.port']}
pass = '{$apollo['redis.password']}'
dbindex = {$apollo['redis.db']}
";
}

// 如果需要mongo等其他 yaml json配置 请在上面复制模板