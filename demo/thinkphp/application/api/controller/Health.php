<?php

/**
 * k8s安全检查
 */

namespace app\api\controller;

use app\common\controller\Api;
use fast\Http;
use think\Db;

class Health extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function check()
    {
        define('SECRET_CONFIG', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apollo' . DIRECTORY_SEPARATOR . 'config.php');
        define('CACHE_CONFIG', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'apolloConfig.application.php');
        $healthy = true;
        $response = ['message' => 'OK'];

        // 检查env
        if (file_exists(ROOT_PATH . '.env')) {
            // try {
            // Db::name('admin')->find();

            // moka_redis()->set('mid_check_health', 'success', 10);
            // moka_redis()->get('mid_check_health');

            // $response = ['message' => '.env success'];
            // return $this->success('check success', $response, 200);
            // } catch (\Exception $e) {
            // $healthy = false;
            // $response['message'] = $e->getMessage();
            // return $this->error('check fail', $response, 500);
            // }

            return $this->success('check success', $response, 200);
        }

        // 检查cache目录
        if (file_exists(CACHE_CONFIG)) {
            $response = ['message' => 'cache file_exists'];
            return $this->success('check success', $response, 200);
        }

        // 检车apollo

        if (file_exists(SECRET_CONFIG)) {
            require_once SECRET_CONFIG;

            $res = Http::get($host);

            if (empty($res)) {
                $response = ['message' => "$host not healthy"];
                return $this->success('check fail', $response, 500);
            }
            $response = ['message' => "$host is healthy"];
            return $this->success('check success', $response, 200);
        }

        $healthy = false;
        $response = ['message' => '.env file not exists \n cache file not exists \n  config.php not exists '];
        return $this->success('check fail', $response, 500);
    }
}
