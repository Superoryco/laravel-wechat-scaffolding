1. 在config/wxxcx.php中填写小程序appid及secret
2. 在config/jpush.php中填写appKey及masterSecret

创建数据库

在.env中填写数据库基本配置

运行passort install

将数据库中创建的client信息填写至.env

更新APP_KEY:php artisan key:generate