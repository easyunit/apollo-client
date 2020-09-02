## 安装

```composer require easyunit/apollo-client=v0.0.6```

## apollo配置中心客户端

- 支持thinkphp框架
- 支持lumen/laravel框架
- 其他框架请自行测试
- 支持秘钥安全请求

### 前言

- 使用此SDK之前，您需要简单了解一下以下概念
- 配置中心服务端
- ConfigServer
- AppId
- Namesapces 支持多命名空间
- Cluster
- Namespaces
- Secret 支持秘钥安全请求

### lumen接入指引

- 流程说明
  - apollo-php-client，链接配置中心，读取到配置文件，写入缓存文件，然后将配置写入到env文件，供PHP程序使用
- 步骤说明
  - 将demo/lumen/apollo.php复制到根目录
  - 将demo/lumen/docs/目录复制到lumen的根目录
  - 修改.env.php文件中的 host appid namesapce等配置信息
  - 按需修改.env_tpl.php模板输出信息
  - 执行 php apollo.php 即可看到成功生成.env配置信息
- 补充说明
  - lumen模板暂时可输出以下几种配置，可自行扩展
    - key=>val
    - json
  - 扩展说明 $apollo变量内存储了所有的配置项

### thinkphp5 接入指引

- 流程说明
  
  - apollo-php-client，链接配置中心，读取到配置文件，写入缓存文件，然后将配置写入到env文件，供PHP程序使用
- 步骤说明
  - 将demo/thinkphp/中的文件(或相关代码)一一对应复制到tp的相关目录，注意保留程序的源代码
    - /application/command.php为追加了一个命令行
    - /application/common/command/Apollo.php为新文件
    - /docs/*为新文件
  - 修改.env.php文件中的 host appid namesapce等配置信息
  - 按需修改.env_tpl.php模板输出信息
  - 执行 php think apollo 即可看到成功生成.env配置信息
- 补充说明
  - lumen模板暂时可输出以下几种配置，可自行扩展
    - key=>val
    - yaml
    - json
    
  - 扩展说明 $apollo变量内存储了所有的配置项
  
    

### swoole-jobs接入指引

- 将demo/swoole-jobs目录下文件复制到swoole-jobs项目的对应位置
- 自定义修改.env_tpl.php模板文件以生成config.php文件
- 将.env.php文件配置为你的配置中心地址

### docker环境客户端启动
Docker环境客户端自启动
在docker的启动脚本中加入启动代码，一般的php容器启动脚本是docker-php-entrypoint

- lumen

```bash
if [ -f "/path/to/start.php" ]; then
    apollo_ps=$(ps -aux | grep -c "php /path/to/apollo.php")
    if [ $apollo_ps -eq 1 ]; then
        php /path/to/apollo.php &
    fi
fi
```

- thinkphp
将中文汉字目录换成你的项目的位置

```bash
if [ -f "/var/www/目录/think" ]; then
    apollo_ps=$(ps -ef | grep -c "php /var/www/目录/think")
    if [ $apollo_ps -eq 1 ]; then
        cd /var/www/目录/ && php think apollo &
    fi
fi
```

### 提供健康检查
- laravel路由
```php
Route::get('/ping', 'Health@check');
```

### 其他

- 本sdk基于multilinguals/apollo-client改写
- 增加秘钥安全请求功能
- 增加tp5使用