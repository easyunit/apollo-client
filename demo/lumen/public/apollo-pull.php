<?php

defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(__DIR__)) . '/');
define('SAVE_DIR', ROOT_PATH . 'docs' . DIRECTORY_SEPARATOR . 'cache');
define('ENV_DIR', ROOT_PATH  . 'docs' . DIRECTORY_SEPARATOR . 'apollo');
define('ENV_TPL', ENV_DIR . DIRECTORY_SEPARATOR . '.env_tpl.php');
define('ENV_CONFIG', ENV_DIR . DIRECTORY_SEPARATOR . '.env.php');
define('ENV_FILE', ROOT_PATH  . '.env');

define('CACHE_CONFIG', SAVE_DIR . DIRECTORY_SEPARATOR . 'apolloConfig.application.php');  // health健康检查多一项

require_once './../vendor/easyunit/apollo-client/src/ApolloClient.php';

function successful($msg)
{
    exit($msg);
}

function failure($msg)
{
    header("HTTP/1.1 400 Bad Request");
    exit($msg);
}

function envinit()
{
    defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
    $env = parse_ini_file(ENV_FILE, true);
    foreach ($env as $key => $val) {
        $name = ENV_PREFIX . strtoupper($key);

        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $item = $name . '_' . strtoupper($k);
                putenv("$item=$v");
            }
        } else {
            putenv("$name=$val");
        }
    }
}

function envload($name, $default = null)
{
    $result = getenv(ENV_PREFIX . strtoupper(str_replace('.', '_', $name)));

    if (false !== $result) {
        if ('false' === $result) {
            $result = false;
        } elseif ('true' === $result) {
            $result = true;
        }

        return $result;
    }

    return $default;
}

function check()
{
    $callback = function () {
        $list = glob(SAVE_DIR . DIRECTORY_SEPARATOR . 'apolloConfig.*');
        if (!empty($list)) {

            $apollo = [];
            foreach ($list as $l) {
                $config = require $l;
                if (is_array($config) && isset($config['configurations'])) {
                    $apollo = array_merge($apollo, $config['configurations']);
                }
            }

            if (!$apollo) {
                echo ('Load Apollo Config Failed, no config available');
            }
            ob_start();
            include_once ENV_TPL;
            $env_config = ob_get_contents();

            ob_end_clean();
            file_put_contents(ENV_FILE, $env_config);
        }
    };

    if (is_file(ENV_FILE)) {
        envinit();
        $env_host = envload("APOLLO_HOST");
        $env_secret = envload('APOLLO_SECRET');

        $error = pull($env_host, $env_secret);
    } else {
        $error = pull();
    }

    if (file_exists(CACHE_CONFIG)) {
        ($callback instanceof \Closure) && call_user_func($callback);
        successful('successful pull config');
    } else {
        failure('failure pull config');
    }
}

function pull($env_host = null, $env_secret = null)
{
    require_once ENV_CONFIG;

    $host = $env_host ?? $host;
    $secret = $env_secret ?? $secret;

    $apollo = new \Moka\ApolloClient($host, $appid, $namespaces);

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

    $apollo->setPullTimeout(1);
    $apollo->setIntervalTimeout(5);

    $callback = function () {
    };
    ini_set('memory_limit', '128M');
    $error = $apollo->start($callback);
    return $error;
}

check();
