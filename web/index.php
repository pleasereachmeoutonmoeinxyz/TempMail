<?php

define(DOMAIN,      "tempmai.ir");
define(DB_DNS,      "mysql:host=localhost;dbname=database");
define(DB_UNAME,    "username");
define(DB_PASS,     "password");
define(ENV,         "production");
define(PRODUCTION,  "production");

require 'vendor/autoload.php';
use RedBean_Facade as R;
R::setup(DB_DNS,DB_UNAME,DB_PASS);

$app = new \Slim\Slim();
$app->config('templates.path','./template');

if(ENV != PRODUCTION)   $app->config('debug', true);

$app->get('/',function() use ($app){    
    $app->render('index.php');
});

$app->post('/get',function() use($app){
    $email    =     $app->request->post('email');
    $ip       =     $app->request()->getIp();
    if ($email == NULL || $email == '' || !check_email_address($email)){
        echo json_encode(array(
            'success'   =>  FALSE,
            'error'     =>  'INVALID'
        ));        
    } else if (R::count('email','ip=:ip AND time>=:time',array(':ip'=>$ip,':time'=>time() + 119*60)) >= 20){
        echo json_encode(array(
            'success'   =>  FALSE,
            'error'     =>  'LIMITATION'
        ));
    } else {
        $bean   =   R::findOne('email','forwardto=:email AND time >= :time',array(':email'=>$email,':time'=>time()));
        if ($bean){
            $bean->time = time() + 120*60;
            R::store($bean);
            echo json_encode(array(
                'success'   =>  TRUE,
                'email'     =>  $bean->email.'@tempmail.ir'
            ));
        } else {
            $rndmail        =   '';
            do{
                $rndmail    =  generateRandomString(8);
            } while (R::count('email','email=:email AND time>=:time',array(':email'=>$rndmail,':time'=>  time())) > 0);
            
            $bean               =   R::dispense('email');
            $bean->email        =   $rndmail;
            $bean->forwardto    =   $email;
            $bean->time         =   time() + 120 * 60;
            $bean->ip           =   $ip;
            R::store($bean);
            echo json_encode(array(
                'success'   =>  TRUE,
                'email'     =>  $rndmail.'@tempmail.ir'
            ));
        }
    }
});



$app->run();

 function check_email_address($email) {
    if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
        return false;
    }
    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
        if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
            return false;
        }
    }
    if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
        $domain_array = explode(".", $email_array[1]);
        if (sizeof($domain_array) < 2) {
            return false; // Not enough parts to domain
        }
        for ($i = 0; $i < sizeof($domain_array); $i++) {
            if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
                return false;
            }
        }
    }
    
    if (strtolower($email_array[1]) === strtolower(DOMAIN))
        return false;

    return true;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}