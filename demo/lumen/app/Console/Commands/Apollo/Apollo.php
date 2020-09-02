<?php

/**
 * 测试
 * @author  sam
 * @date    2019-09-12 10:47:28
 */

namespace App\Console\Commands\Apollo;

use App\Libraries\Aes;
use App\Libraries\Signature;
use Elasticsearch\ClientBuilder;
use Endroid\QrCode\QrCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Moka\ApolloClient;



class Apollo extends Command
{
    /**
     * 控制台命令 signature 的名称
     * php artisan test:sam
     * @var string
     */
    protected $signature = 'apollo:run';

    /**
     * 控制台命令说明。
     * php artisan help  test:sam命令后的输出内容
     * @var string
     *
     */
    protected $description = "apollo config";

    protected $retry;


    /**
     * 执行控制台命令。
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->getData();
        } catch (\Exception $e) {
            echo $e->getMessage() . '|' . $e->getLine();
        }
    }

    public function getData()
    {

        defined('ROOT_PATH') or define('ROOT_PATH', __DIR__ . '/../../../config/');
        define('SAVE_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'cache');
        define('ENV_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apollo');
        define('ENV_TPL', ENV_DIR . DIRECTORY_SEPARATOR . '.env_tpl.php');
        define('ENV_CONFIG', ENV_DIR . DIRECTORY_SEPARATOR . '.env.php');
        define('SECRET_CONFIG', ENV_DIR . DIRECTORY_SEPARATOR . 'config.php');
        define('ENV_FILE', ROOT_PATH . DIRECTORY_SEPARATOR . '.env');
        require_once ENV_CONFIG;

        if (file_exists(SECRET_CONFIG)) {
            require_once SECRET_CONFIG;
        }

        // 回调写入.env
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
                include ENV_TPL;
                $env_config = ob_get_contents();

                ob_end_clean();
                file_put_contents(ENV_FILE, $env_config);
            }
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
        do {
            $error = $apollo->start($callback);
            if ($error) echo ('error:' . $error . "\n");
        } while ($error && $restart); // 失败自动重启
    }
}
