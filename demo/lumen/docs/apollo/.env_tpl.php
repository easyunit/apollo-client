<?php

// 设置默认值
$apollo['app.name'] = $apollo['app.name'] ?? 'Lumen';
$apollo['app.debug'] = $apollo['app.debug'] ?? 'true';
$apollo['app.key']  = $apollo['app.key'] ?? 'XHBNwiH8fiet3a3a7Yyp3pBmDbHT4Yk1';
$apollo['app.url']  = $apollo['app.url'] ?? 'www.mokasz.com';
$apollo['app.timezone']  = $apollo['app.timezone'] ?? 'Asia/Shanghai';

$apollo['cache.driver'] = $apollo['cache.driver'] ?? 'file';



// 写入 properties key => value 配置
echo "APP_NAME={$apollo['app.name']}
APP_ENV=local
APP_KEY={$apollo['app.key']}
APP_DEBUG={$apollo['app.debug']}
APP_URL={$apollo['app.url']}
APP_TIMEZONE={$apollo['app.timezone']}

###缓存
CACHE_DRIVER={$apollo['cache.driver']}
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_SLACK_WEBHOOK_URL=
";


// 写入redis配置
echo "
###Redis
";
if ($apollo['redis-type'] == 'yaml') {
    // Laravel暂时不推荐使用Yaml 拼REDIS_前缀比较麻烦
    // $apollo['redis'] = str_replace(": ", " = ", $apollo['redis']);
    // echo "{$apollo['redis']}
    // ";
} elseif ($apollo['redis-type'] == 'json') {
    $json = json_decode($apollo['redis'], true);
    foreach ($json as $key => $value) {
        echo strtoupper("REDIS_{$key}") . " = {$value}
";
    }
} else {
    echo "
REDIS_HOST = {$apollo['redis.host']}
REDIS_PORT = {$apollo['redis.port']}
REDIS_PASS = {$apollo['redis.password']}
REDIS_DBINDEX = {$apollo['redis.db']}
";
}

// 写入mysql配置项
echo "
### Mysql
";
if ($apollo['mysql-type'] == 'yaml') {
    // Laravel暂时不推荐使用Yaml 拼REDIS_前缀比较麻烦
    //     $apollo['mysql'] = str_replace(": ", " = ", $apollo['mysql']);
    //     echo "

    // {$apollo['mysql']}
    // ";
} elseif ($apollo['mysql-type'] == 'json') {
    $json = json_decode($apollo['mysql'], true);
    foreach ($json as $key => $value) {
        echo strtoupper("MYSQL_{$key}") . " = {$value}
";
    }
} else {
    echo "
MYSQL_HOSTNAME = {$apollo['mysql.host']}
MYSQL_HOSTPORT = {$apollo['mysql.port']}
MYSQL_USERNAME = {$apollo['mysql.user']}
MYSQL_PASSWORD = {$apollo['mysql.password']}
MYSQL_DATABASE = {$apollo['mysql.db']}
MYSQL_PREFIX = m_
";
}
