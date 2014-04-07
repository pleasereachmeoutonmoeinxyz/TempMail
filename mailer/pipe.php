<?php
error_reporting(0);
require_once 'PHPMailerAutoload.php';
require_once 'MimeMailParser.class.php';
require_once 'rb.php';

define(DB_DNS,      "mysql:host=localhost;dbname=database");
define(DB_UNAME,    "username");
define(DB_PASS,     "password");

R::setup(DB_DNS,DB_UNAME,DB_PASS);


$parser = new MimeMailParser();
$parser->setStream(STDIN);

$to             =   $parser->getHeader('to');
$mail           = explode('@', $to);

if ($bean   =   R::findOne('email','email=:email AND time>=:time',array(':email'=>$mail[0],':time'=>  time()))){
    $from           =   $parser->getHeader('from');
    $subject        =   $parser->getHeader('subject');

    $mail           =   new PHPMailer;
    $mail->From     =   'temp@tempmail.ir';
    $mail->FromName =   'tempmail.ir';
    $mail->addReplyTo($from);
    $mail->addAddress($bean->forwardto);
    $mail->Subject  =   $subject;
    $mail->AltBody  =   $parser->getMessageBody();
    $mail->Body     =   $parser->getMessageBody('html');
    $mail->isHTML();

    $save_dir   =   __DIR__.'/attachments/';
    $hash       =   time().md5($to.$from.$subject);
    $filenames  =   array();
    foreach ($parser->getAttachments() as $attachment){
        $filenames[]        =   $attachment->filename;
            if ($fp = fopen($save_dir.$hash.$attachment->filename, 'w')){
                while ($bytes   =   $attachment->read()){
                    $content    =   $bytes;
                    fwrite($fp, $bytes);
                }            
                fclose($fp);
                $mail->addAttachment($save_dir.$hash.$attachment->filename,$attachment->filename);
            }
    }

    $mail->send();

    foreach ($filenames as $filename){
        unlink($save_dir.$hash.$filename);
    }
}



