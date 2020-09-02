<?php
// 根目录
defined('ROOT_PATH') or define('ROOT_PATH', __DIR__);
define('SAVE_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apollo');
define('ENV_TPL', SAVE_DIR . DIRECTORY_SEPARATOR . '.env_tpl.php');
define('ENV_CONFIG', SAVE_DIR . DIRECTORY_SEPARATOR . '.env.php');
define('ENV_FILE', ROOT_PATH . DIRECTORY_SEPARATOR . 'config.php');

require_once './vendor/autoload.php';
require_once ENV_CONFIG;

use Moka\ApolloClient;

// 回调写入.env
$callback = function () {
    $list = glob(SAVE_DIR . DIRECTORY_SEPARATOR . 'apolloConfig.*');
    $apollo = [];
    foreach ($list as $l) {
        $config = require $l;
        if (is_array($config) && isset($config['configurations'])) {
            $apollo = array_merge($apollo, $config['configurations']);
        }
    }
    if (!$apollo) {
        throw new Exception('Load Apollo Config Failed, no config available');
    }
    ob_start();
    include ENV_TPL;
    $env_config = ob_get_contents();

    ob_end_clean();
    file_put_contents(ENV_FILE, $env_config);

    // TODO reload swoole-jobs
    // exec('systemctl reload swoole-jobs.service');
};

// 实例化
$apollo = new ApolloClient($host, $appid, $namespaces);

// 配置类属性
if (!empty($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP)) {
    $apollo->setClientIp($clientIp);
}
if (!empty($cluster)) {
    $apollo->setCluster($cluster);
}

if (!empty($secret)) {
    $apollo->setSecret($secret);
}
$apollo->setSaveDir(SAVE_DIR);

// 进程信息
ini_set('memory_limit', '128M');
$pid = getmypid();
echo "start [$pid]\n";

$counter = 0;

do {
    $counter += 1;
    $error = $apollo->start($callback);
    if ($error) echo ('error:' . $error . "\n");

    echo $counter;
} while ($error && $restart); // 失败自动重启
