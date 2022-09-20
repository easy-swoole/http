# EasySwoole HTTP 服务组件
## 大文件上传

Easyswoole的Request对象会默认处理Swoole\Http\Request的files属性，并复制一份到EasySwoole\Http\Request对象中。
若上传大文件对象时，这会导致双倍内存占用。因此允许在Swoole\Http\Request对象中设置一个ignoreFile属性值为true.这样则会忽略文件处理。


## 动态路由匹配规则

![Dispatcher](./resource/router.jpg)