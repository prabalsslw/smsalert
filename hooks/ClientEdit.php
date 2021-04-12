<?php
$hook = array(
    'hook'           => 'ClientEdit',
    'function'       => 'ClientEdit_clientarea',
	'description'    => array(
        'english'    => 'After Client Edit (OTP) Mobile Verification'
    ),
    'type'           => 'client',
    'extra'          => '',
    'defaultmessage' => 'Dear {firstname} {lastname},your profile has been updated.',
    'variables'      => '{firstname},{lastname}'
);

if(!function_exists('ClientEdit_clientarea')) {
	
	function ClientEdit_clientarea($args){
		//Check if Phone Number is Changed.
		$class    = new Sms();
        $template = $class->getTemplateDetails(__FUNCTION__);
		 if($template['active'] == 0){
            return null;
        }
		if($args['olddata']['phonenumber'] != $args['phonenumber']):
			
			//Set User
			$class->setUserid( $args['userid'] );
			$client_query = $class->getClientDetailsBy( $class->userid );
			$client       = mysql_fetch_array( $client_query , MYSQL_ASSOC);  
			$message      = str_replace(['{firstname}' , '{lastname}'] , [$client['firstname'], $client['lastname']] , $template['template']);
			$class->setGsmnumber( $client['gsmnumber'] );
			$class->setMessage( $message );
			$class->send();
		endif;
	}
}
return $hook;