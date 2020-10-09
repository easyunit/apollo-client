## 前言
- 本sdk基于multilinguals/apollo-client改写
- multilinguals/apollo-client不支持秘钥验证，本sdk添加了sdk验证
- PHP开发的apollo-client对代码侵入性太高，本项目已经完全使用go语言实现，需要技术支持可以添加我的QQ 289360775

## 安装

```composer require easyunit/apollo-client=v0.1.0```

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

### 非侵入式接入指引

- 流程说明
  - apollo-php-client，链接配置中心，读取到配置文件，写入缓存文件，然后将配置写入到env文件，供PHP程序使用
- 步骤说明
  - **${frame}**为thinkphp或者lumen
  - 将demo/**${frame}**/docs/目录复制到项目根目录
  - 修改**.env.php**文件中的namesapce等配置信息
  - 按需修改**.env_tpl.php**模板输出信息
  - 在public 目录 php apollo-listen.php即可启动监听
  - 在public 目录 php apollo-pull.php即可拉取一次配置
- 补充说明
  - lumen模板暂时可输出以下几种配置，可自行扩展
    - key=>val
    - json
    - yaml  需要自行扩展模板
  - 扩展说明 **.env_tpl.php**文件中**$apollo**变量内存储了所有的配置项

### docker环境客户端启动
Docker环境客户端自启动
在docker的启动脚本中加入启动代码，一般的php容器启动脚本是docker-php-entrypoint

```bash
if [ -f "/var/www/目录/public/apollo-listen.php" ]; then
    apollo_ps=$(ps -ef | grep -c "php /var/www/目录/public/apollo-listen.php")
    if [ $apollo_ps -eq 1 ]; then
        cd /var/www/目录/public && php apollo-listen.php &
    fi
fi
```

### 健康检查

- jenkins自动编排策略设计到的健康检查请自行扩展

### 其他
- 增加秘钥安全请求功能
- 增加tp5使用