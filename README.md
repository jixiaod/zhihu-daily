# 概述
每天cron定时发送带有知乎日报的附件邮件到kindle邮箱，通过kindle自己的推送服务，达到在kindle上阅读知乎日报的功能。

# 如何使用？

```shell
    ~$ git clone git@github.com:ttihgiesr/zhihu-daily.git
    ~$ cd zhihu-daily
```

修改zhihu-daily.php头部的配置

```php
    define( 'MAIL_HOST', 'mail server');
    define( 'MAIL_USERNAME', 'your send mail email');
    define( 'MAIL_PASSWORD', 'your email pass');
    define( 'MAIL_FROM', 'your display email');
    define( 'MAIL_ADD_ADDRESS', 'kindle email address');
```

可以放到服务器上，用cron每天定时push知乎日报新的内容到你的kindle

