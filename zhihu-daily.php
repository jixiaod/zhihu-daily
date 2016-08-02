<?PHP
/**
* @file zhihu-daily.php
* 
* @brief  获取知乎日报并推送到kindle阅读器
* Copyright(C) 2013-2016 Ji Gang, individual. or its affiliates. All Rights Reserved.
* 
* @version $Id$
* @author tiger, ji.xiaod@gmail.com
* @date 2016-08-02
*/

define( 'MAIL_HOST', 'mail server'); // stmp 服务
define( 'MAIL_USERNAME', 'your send mail email'); // 发送邮箱地址
define( 'MAIL_PASSWORD', 'your email pass'); // 发送邮箱密码
define( 'MAIL_FROM', 'your display email'); // 显示的邮箱来源
define( 'MAIL_ADD_ADDRESS', 'kindle email address'); // kindle 邮箱地址

define( 'PATH_MAIL_ATTACHMENT', '/var/tmp/'); // 知乎新闻邮件附件临时文件路径


require('vendor/autoload.php');

function send_kindle_mail()
{
    $zhihu_file = write_zhihu_file();
    echo ">>log: tmp mail file => $zhihu_file \n";

    $mail = new PHPMailer;

    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = 'tls';

    $mail->From = MAIL_FROM;
    $mail->FromName = 'zhihu-rss';
    $mail->addAddress(MAIL_ADD_ADDRESS);

    $mail->addAttachment($zhihu_file);
    $mail->isHTML(true);

    $mail->Subject = 'zhihu daily';
    $mail->Body    = 'This is zhihu daily news rss push.';

    echo ">>log: kindle email sending.\n";
    if(!$mail->send()) {
        echo "!!error: message could not be sent.\n";
        echo "!!mailer error: " . $mail->ErrorInfo;
    } else {
        echo ">>log: message has been sent.\n";
    }   

    @unlink($zhihu_file);
}

function write_zhihu_file()
{
    $data = latest_data();

    $zhihu_file = PATH_MAIL_ATTACHMENT . 'zhihu-daily-' . $data['date'] . '.txt';
    if ( file_exists($zhihu_file) ) {
        echo ">>log: delte exists attachment file.\n";
        unlink($zhihu_file);
    }
    
    /**
    * @brief  data ={'date':..., 'news':['title':..,'url':...,....], .......}
    *
    */
    foreach ($data['news'] as $key => $new) {

        list($title, $content) = get_one_zhihu_new_content($new['url']);
        file_put_contents($zhihu_file, "#{$title}#\n", FILE_APPEND);
        file_put_contents($zhihu_file, $content, FILE_APPEND);   
        file_put_contents($zhihu_file, "\n============================================\n", FILE_APPEND);   
        file_put_contents($zhihu_file, "\n==================Next======================\n", FILE_APPEND);   
        file_put_contents($zhihu_file, "\n============================================\n", FILE_APPEND);   
    }
    echo ">>log: write file done.\n";

    $file_array=file($zhihu_file);
    $file_array = array_values(array_filter($file_array, "trim"));
    file_put_contents($zhihu_file, implode("\n", $file_array) );
    return $zhihu_file;
}

function get_one_zhihu_new_content($url)
{
    $json_data = get_url_contents($url); 
    if ( empty($json_data) ) {
        echo "!!error: get zhihu new's data empty.\n";
        exit;
    }

    echo ">>log: data json decode pending.\n";

    $arr_data = json_decode($json_data, true);

    if ( isset($data['body']) && empty($data['body']) ) {
        echo "!!error: json decode new body error, format body error.\n";
    }

    $title      = $arr_data['title'];
    $content    = $arr_data['body'];
    $content    = strip_tags($content);
    $content    = htmlspecialchars_decode($content, ENT_QUOTES);
    $content    = html_entity_decode($content);

    return [$title, $content];
}

function latest_data() 
{
    $url = "http://news.at.zhihu.com/api/1.2/news/latest"; 
    echo ">>log: loading zhihu daily latest news.\n";
    $json_data= get_url_contents($url);
    return format_zhihu_json_data($json_data);
}

function format_zhihu_json_data($json_data)
{
    if ( empty($json_data) ) {
        echo "!!error: get zhihu data empty.\n";
        exit;
    }
    echo ">>log: data json decode pending.\n";
    $arr_data = json_decode($json_data, true);
    if ( isset($data['news']) && empty($data['news']) ) {
        echo "!!error: json decode error, format error.\n";
    }
    return $arr_data;
}

function get_url_contents($url)
{
    $crl = curl_init();
    $timeout = 5;
    curl_setopt ($crl, CURLOPT_URL,$url);
    curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
    $ret = curl_exec($crl);
    curl_close($crl);
    return $ret;
}

// run.
send_kindle_mail();

