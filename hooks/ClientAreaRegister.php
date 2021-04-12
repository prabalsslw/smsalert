<?php

$hook = array(
    'hook'           => 'ClientAreaRegister',
    'function'       => 'ClientAreaRegister_clientarea',
	'description'    => array(
        'english'    => 'After Client Registration (OTP) Mobile Verification'
    ),
    'type'           => 'client',
    'extra'          => '',
    'defaultmessage' => 'Dear {firstname} {lastname}, OTP generated for your mobile phone verification is {otp}.',
    'variables'      => '{firstname},{lastname},{otp}'
);

if(!function_exists('ClientAreaRegister_clientarea')) {
	function ClientAreaRegister_clientarea($args){
		$class    = new Sms();
        $template = $class->getTemplateDetails(__FUNCTION__);
		 if($template['active'] == 0){
            return null;
        }
		//$class->getotpdetails($args['userid']);	
	}
}
return $hook;