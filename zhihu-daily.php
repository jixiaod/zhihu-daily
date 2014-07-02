<?PHP
/**
* @file zhihu-read.php
* 
* @brief  获取知乎日报并推送到kindle阅读器
* Copyright(C) 2013-2016 Ji Gang, individual. or its affiliates. All Rights Reserved.
* 
* @version $Id$
* @author tiger, ji.xiaod@gmail.com
* @date 2014-06-27
*/

define( 'MAIL_HOST', 'mail server');
define( 'MAIL_USERNAME', 'your send mail email');
define( 'MAIL_PASSWORD', 'your email pass');
define( 'MAIL_FROM', 'your display email');
define( 'MAIL_ADD_ADDRESS', 'kindle email address');

define( 'PATH_MAIL_ATTACHMENT', './mail_attachments/');

function send_kindle_mail()
{
    $zhihu_file = write_zhihu_file();

    require './PHPMailer-master/PHPMailerAutoload.php';
    $mail = new PHPMailer;

    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = MAIL_HOST;  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = MAIL_USERNAME;                 // SMTP username
    $mail->Password = MAIL_PASSWORD;                           // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

    $mail->From = MAIL_FROM;
    $mail->FromName = 'zhihu-rss';
    $mail->addAddress('ji.xiaod@gmail.com');               // Name is optional

    $mail->addAttachment($zhihu_file);
    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = 'zhihu daily';
    $mail->Body    = 'This is zhihu daily news rss push.';

    echo ">>log: kindle email sending.\n";
    if(!$mail->send()) {
        echo "!!error: message could not be sent.\n";
        echo "!!mailer error: " . $mail->ErrorInfo;
    } else {
        echo ">>log: message has been sent.\n";
    }   
}

function write_zhihu_file()
{
    $data = latest_data();

    $zhihu_file = PATH_MAIL_ATTACHMENT . $data['date'] . '.txt';
    if ( file_exists($zhihu_file) ) {
        echo ">>log: delte exists attachment file.\n";
        unlink($zhihu_file);
    }
    
    /**
    * @brief  data ={'date':..., 'news':['title':..,'url':...,....], .......}
    *
    */
    foreach ($data['news'] as $key => $new) {
        file_put_contents($zhihu_file, "{$key}#--------------------------------------------#\n",FILE_APPEND);
        file_put_contents($zhihu_file, $new['title'], FILE_APPEND);   
        $content = get_one_zhihu_new_content($new['url']);
        file_put_contents($zhihu_file, $content, FILE_APPEND);   
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

    $content= $arr_data['body'];
    $content = htmlspecialchars_decode( strip_tags($content) );
    return $content;
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
