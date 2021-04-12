<?php
$hook = array(
    'hook'           => 'AfterRegistrarRegistration',
    'function'       => 'AfterRegistrarRegistration',
    'description'    => array(
        'english'    => 'After Domain Registration'
    ),
    'type'           => 'client',
    'extra'          => '',
    'defaultmessage' => 'Hi {firstname} {lastname},Entries in the name field for the domain name {domain} have been successfully made.',
    'variables'      => '{firstname},{lastname},{domain}'
);

if(!function_exists('AfterRegistrarRegistration')){
    function AfterRegistrarRegistration($args){
    $class    = new Sms();
    $template = $class->getTemplateDetails(__FUNCTION__);
    if($template['active'] == 0){
        return null;
    }
    $settings = $class->getSettings();
    if(!$settings['api'] || !$settings['apiparams'] ){
        return null;
    }
    $result = $class->getClientDetailsBy($args['params']['userid']);
    $num_rows = mysql_num_rows($result);
    if($num_rows == 1){
        $UserInformation       = mysql_fetch_assoc($result);
        $template['variables'] = str_replace(" ","",$template['variables']);
        $replacefrom           = explode(",",$template['variables']);
        $replaceto             = array($UserInformation['firstname'],$UserInformation['lastname'],$args['params']['sld'].".".$args['params']['tld']);
        $message               = str_replace($replacefrom,$replaceto,$template['template']);
        $class->setGsmnumber($UserInformation['gsmnumber']);
        $class->setUserid($args['params']['userid']);
        $class->setMessage($message);
        $class->send();
    }
}
}
return $hook;