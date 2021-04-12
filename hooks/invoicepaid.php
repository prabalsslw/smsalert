<?php
$hook = array(
    'hook'           => 'InvoicePaid',
    'function'       => 'InvoicePaid',
    'description'    => array(
        'english'    => 'Post Payment'
    ),
    'type'           => 'client',
    'extra'          => '',
    'defaultmessage' => 'Dear {firstname} {lastname},payment for invoice with id {invoiceid} is done! Thank you.',
    'variables'      => '{firstname},{lastname},{duedate},{invoiceid}'
);

if(!function_exists('InvoicePaid')){
    function InvoicePaid($args){
        $class    = new Sms();
        $template = $class->getTemplateDetails(__FUNCTION__);
        if($template['active'] == 0){
            return null;
        }
        $settings = $class->getSettings();
        if(!$settings['api'] || !$settings['apiparams'] ){
            return null;
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