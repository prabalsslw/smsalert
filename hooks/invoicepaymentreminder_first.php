<?php
$hook = array(
    'hook'           => 'InvoicePaymentReminder',
    'function'       => 'InvoicePaymentReminder_Firstoverdue',
    'description'    => array(
        'english'    => 'Invoice payment reminder for first overdue'
    ),
    'type'           => 'client',
    'extra'          => '',
    'defaultmessage' => 'Hi {firstname} {lastname},the payment associated with your account with date {duedate} is not done yet for invoice {invoiceid}. Kindly make the payment at the earliest to enjoy the services.',
    'variables'      => '{firstname},{lastname},{duedate},{invoiceid}'
);

if(!function_exists('InvoicePaymentReminder_Firstoverdue')){
    function InvoicePaymentReminder_Firstoverdue($args){
        if($args['type'] == "firstoverdue"){
            $class    = new Sms();
            $template = $class->getTemplateDetails(__FUNCTION__);
            if($template['active'] == 0){
                return null;
            }
            $settings = $class->getSettings();
            if(!$settings['api'] || !$settings['apiparams'] ){
                return null;
            }
        }else{
            return false;
        }
        $result   = $class->getClientAndInvoiceDetailsBy($args['invoiceid']);
        $num_rows = mysql_num_rows($result);
        if($num_rows == 1){
            $UserInformation       = mysql_fetch_assoc($result);
            $template['variables'] = str_replace(" ","",$template['variables']);
            $replacefrom           = explode(",",$template['variables']);
            $replaceto             = array($UserInformation['firstname'],$UserInformation['lastname'],$class->changeDateFormat($UserInformation['duedate']),$args['invoiceid']); 
            $message               = str_replace($replacefrom,$replaceto,$template['template']);
            $class->setGsmnumber($UserInformation['gsmnumber']);
            $class->setMessage($message);
            $class->setUserid($UserInformation['userid']);
            $class->send();
        }
    }
}

return $hook;