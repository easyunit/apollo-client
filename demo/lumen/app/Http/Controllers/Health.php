<?php

namespace App\Http\Controllers;

/**
 * k8s健康检查
 */
class Health
{
    public function check()
    {
        define('SECRET_CONFIG', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'apollo' . DIRECTORY_SEPARATOR . 'config.php');
        define('CACHE_CONFIG', ROOT_PATH . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'apolloConfig.application.php');
        $healthy = true;
        $response = ['message' => 'OK'];

        // 检查env
        if (file_exists(ROOT_PATH . '.env')) {
            try {
                // 这里可以检查数据库 redis连接等
                $response = ['message' => '.env success'];
            } catch (\Exception $e) {
                $healthy = false;
                $response['message'] = $e->getMessage();
                return response()->json($response, 500);
            }

            return response()->json($response, 200);
        }

        // 检查cache目录
        if (file_exists(CACHE_CONFIG)) {
            $response = ['message' => 'cache file_exists'];
            return response()->json($response, 200);
        }

        // 检查apollo
        if (file_exists(SECRET_CONFIG)) {
            require_once SECRET_CONFIG;

            $http = new \GuzzleHttp\Client;

            $res = $http->request('GET', $host);

            if (empty($res)) {
                $response = ['message' => "$host not healthy"];
                return response()->json($response, 500);
            }
            $response = ['message' => "$host is healthy"];
            return response()->json($response, 200);
        }

        $healthy = false;
        $response = ['message' => '.env file not exists \n cache file not exists \n  config.php not exists '];
        return response()->json($response, 500);
    }
}
