# apiframe-php7-framework
ApiFrame一个基于php7的快速、精简服务端Api接口开发框架，在一些大型应用中也得到过实际使用考验。特点及功能如下：
1. 采用的MVCS层结构。增加service层整合认可数据库、缓存层的处理。
2. 支持MYSQL读写分离、多库操作支持。
3. 支持model层与表关联，内置很多封装好的MYSQL操作。
4. 支持composer功能扩展，可修改composer配置自由加载第三方compose模块。
5. 可生成唯一日志ID在接口响应和服务器端日志中都有记录以便协作排查问题。
6. 非常方便地支持CLI模式下的后台任务开发。
7. 内置了一个简单的视图后台界面方便做一个程序处理的管理界面。
8. 内置redis连接类、beanstalkd连接类、文件缓存处理等等
总之：非常方便快速进行APP服务器端接口程序开发。框架的目录结构如下：
<img src="https://github.com/KermitCode/apiframe-php7-framework/blob/master/%E6%A1%86%E6%9E%B6%E7%9B%AE%E5%BD%95%E4%BB%8B%E7%BB%8D.png?raw=true">
nginx配置文件见代码根目录中相关nginx文件，URL中请求地址：http://api.04007.cn/?appid=1001&sign=areqrewqrewrew&timestamp=12312 返回json数据示例：
<img src="https://github.com/KermitCode/apiframe-php7-framework/blob/master/json%E5%93%8D%E5%BA%94.jpg?raw=true">
简单后台的登录界面：http://api.04007.cn/admin/login?logout=yes 截图如下：
<img src="https://github.com/KermitCode/apiframe-php7-framework/blob/master/apiFrame%E7%AE%80%E5%8D%95%E5%90%8E%E5%8F%B0-%E7%99%BB%E5%BD%95.jpg?raw=true">
后台登录之后的页面示例：
<img src="https://github.com/KermitCode/apiframe-php7-framework/blob/master/apiFrame%E7%AE%80%E5%8D%95%E5%90%8E%E5%8F%B0-%E7%95%8C%E9%9D%A2.jpg?raw=true">
