<?php

namespace Moka;

/**
 * ApolloClient
 * @author Lucifer from Moka
 */
class ApolloClient
{
    // 构造方法中初始化
    protected $configServer;            // apollo服务端地址
    protected $appId;                   // apollo配置项目的appid
    protected $notifications = [];      // 命名空间

    // 外部设置
    protected $save_dir;                // 配置保存目录
    protected $cluster  = 'default';
    protected $clientIp = '127.0.0.1';  // 绑定IP做灰度发布用
    protected $secret   = '';           // 秘钥

    // 请求时间
    protected $pullTimeout     = 10;    // 获取某个namespace配置的请求超时时间
    protected $intervalTimeout = 60;    // 每次请求获取apollo配置变更时的超时时间

    // 身份验证请求
    protected $pathinfo = '/notifications/v2'; // 加密用
    protected $request_uri = '';
    protected $header;                         // 请求头
    protected $timestamp;                      // 时间戳


    public function __construct($configServer, $appId, array $namespaces)
    {
        $this->configServer = $configServer;
        $this->appId = $appId;

        foreach ($namespaces as $namespace) {
            $this->notifications[$namespace] = ['namespaceName' => $namespace, 'notificationId' => -1];
        }
    }

    public function setCluster($cluster)
    {
        $this->cluster = $cluster;
    }

    public function setClientIp($ip)
    {
        $this->clientIp = $ip;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function setSaveDir($save_dir)
    {
        $this->save_dir = $save_dir;
    }

    // 启动客户端
    public function start($callback = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->intervalTimeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        try {
            $this->_listenChange($ch, $callback);
        } catch (\Exception $e) {
            curl_close($ch);

            if (file_exists(ENV_FILE)) {
                ($callback instanceof \Closure) && call_user_func($callback);
            }

            return $e->getMessage();
        }
    }

    private function setHeader($ch, $request_uri = '')
    {
        $this->headers['Timestamp'] = $this->timestamp = (int) (microtime(true) * 1000);

        // get请求参数
        // $params['appId']   = $this->appId;
        // $params['cluster'] = $this->cluster;
        // $params['notifications'] =  json_encode(array_values($this->notifications));
        // $query_string = is_array($params) ? http_build_query($params) : $params;
        // $request_uri = $this->pathinfo . (stripos($this->pathinfo, "?") !== false ? "&" : "?") . $query_string;

        $this->str = "$this->timestamp
$request_uri";

        // 计算签名
        $this->sign = base64_encode(hash_hmac("sha1", $this->str, $this->secret, true));

        $this->headers['Authorization'] = 'Apollo ' . $this->appId . ':' . $this->sign;

        $this->header = array();
        foreach ($this->headers as $key => $head) {
            $this->header[] = $key . ': ' . $head;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
    }

    /**
     * 监听变更
     * 请求{{url}}/notifications/v 
     */
    private function _listenChange($ch, $callback)
    {
        $base_url = rtrim($this->configServer, '/') . '/notifications/v2?';

        $params = [];
        $params['appId'] = $this->appId;
        $params['cluster'] = $this->cluster;

        $counter = 0;

        do {
            $counter += 1;
            $params['notifications'] = json_encode(array_values($this->notifications));

            $query_string = http_build_query($params);

            // 身份认证
            if ($this->secret) {
                // 不含域名
                $request_uri = $this->pathinfo . (stripos($this->pathinfo, "?") !== false ? "&" : "?") . $query_string;
                $this->setHeader($ch, $request_uri);
            }

            // 含域名
            $geturl = $base_url . $query_string;  // 如果没设置秘钥，可以打印一下这个url，复制到浏览器请求看一下数据格式

            curl_setopt($ch, CURLOPT_URL, $geturl);

            $response = curl_exec($ch);   // 执行请求，得到配置版本号
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($httpCode == 200) {
                $res = json_decode($response, true);

                // 组合参数
                $change_list = [];
                foreach ($res as $r) {
                    if ($r['notificationId'] != $this->notifications[$r['namespaceName']]['notificationId']) {
                        $change_list[$r['namespaceName']] = $r['notificationId'];  // 版本号
                    }
                }

                /**
                 * 得到参数 请求配置
                 * 参数结构 
                 * {"mysql.yaml":39,"application":33,"redis.json":36,"MID.app":38}}
                 * 仅保留key
                 * ["mysql.yaml","application","redis.json","MID.app"]
                 */
                // $response_list = $this->pullConfigBatch(array_keys($change_list));
                // ↓
                // 兼容加密
                foreach ($change_list as $key => $channge) {
                    $response_list = $this->pullConfigBatch([$key]);
                }

                // {"mysql.yaml":true,"application":true,"redis.json":true,"MID.app":true}}
                foreach ($response_list as $namespaceName => $result) {
                    $result && ($this->notifications[$namespaceName]['notificationId'] = $change_list[$namespaceName]);
                }

                // 回调写入.env
                ($callback instanceof \Closure) && call_user_func($callback);
            } elseif ($httpCode != 304) {
                throw new \Exception($response ?: $error);
            }
            echo $counter;
            usleep(100);
        } while (true);
    }

    /**
     * 批量拉取配置
     * 无缓存形式
     */
    private function pullConfigBatch(array $namespaceNames)
    {
        if (empty($namespaceNames)) return [];

        // {url}/configs/appid/cluster/

        $pathinfo = '/configs/' . $this->appId . '/' . $this->cluster . '/';
        $base_url = rtrim($this->configServer, '/') . '/configs/' . $this->appId . '/' . $this->cluster . '/';

        $request_list = [];
        $query_args = [];
        $query_args['ip'] = $this->clientIp;

        $multi_ch = curl_multi_init();
        foreach ($namespaceNames as $namespaceName) {
            $request = [];

            // 拼接geturl
            $request_url = $base_url . $namespaceName;

            $config_file = $this->getConfigFilePath($namespaceName); // 获取文件路径
            $query_args['releaseKey'] = $this->_getReleaseKey($config_file); // 读取releaseKey
            $query_string = '?' . http_build_query($query_args);   // 参数

            $geturl = $request_url . $query_string;  // {url}/configs/appid/cluster/namespace?ip=127.0.0.1&releaseKey={$query_args['releaseKey']}

            // curl请求具体命名空间的配置 可能得到304或者完整的配置
            $ch = curl_init($geturl);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->pullTimeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if ($this->secret) {
                $pathinfo = $pathinfo . $namespaceName;
                $request_uri = $pathinfo . $query_string;
                $this->setHeader($ch, $request_uri);
            }

            $request['ch'] = $ch;
            $request['config_file'] = $config_file;
            $request_list[$namespaceName] = $request;
            curl_multi_add_handle($multi_ch, $ch);
        }

        $active = null;  // 存储(有变更)命名空间数量

        // 执行批处理句柄
        do {
            $mrc = curl_multi_exec($multi_ch, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);  // -1

        while ($active && $mrc == CURLM_OK) {  // CURLM_OK
            if (curl_multi_select($multi_ch) == -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($multi_ch, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        // 获取结果
        $response_list = [];
        foreach ($request_list as $namespaceName => $req) {
            $response_list[$namespaceName] = true;
            $result = curl_multi_getcontent($req['ch']);

            $code = curl_getinfo($req['ch'], CURLINFO_HTTP_CODE);  // 200 or 304
            $error = curl_error($req['ch']);
            curl_multi_remove_handle($multi_ch, $req['ch']);
            curl_close($req['ch']);

            if ($code == 200) {
                $result = json_decode($result, true);

                $this->compatibilityMultiConfig($result);
                $content = '<?php return ' . var_export($result, true) . ';';
                file_put_contents($req['config_file'], $content);  // 写入cache文件
            } elseif ($code != 304) {

                echo "ApolloClient.php 255 error" . 'pull config of namespace[' . $namespaceName . '] error:' . ($result ?: $error) . "\n";
                $response_list[$namespaceName] = false;
            }
        }
        curl_multi_close($multi_ch);
        return $response_list;
    }

    /**
     * 获取cache文件路径
     * 给出namespace返回缓存文件路径
     */
    private function getConfigFilePath($namespaceName)
    {
        return $this->save_dir . DIRECTORY_SEPARATOR . 'apolloConfig.' . $namespaceName . '.php';
    }

    /**
     * 读取文件中的 releaseKey
     */
    private function _getReleaseKey($config_file)
    {
        $releaseKey = '';
        if (file_exists($config_file)) {
            $last_config = require $config_file;
            is_array($last_config) && isset($last_config['releaseKey']) && $releaseKey = $last_config['releaseKey'];
        }
        return $releaseKey;
    }

    /**
     * 兼容glob函数
     * 读取多个配置文件时，key覆盖问题
     */
    private function compatibilityMultiConfig(&$result)
    {
        if (!empty($result['configurations']['content'])) {
            $namespaceName = $result['namespaceName'];
            $namespace = explode('.', $namespaceName);
            $result['configurations'][$namespace[0]] = $result['configurations']['content'];
            unset($result['configurations']['content']);
        }
    }
}
