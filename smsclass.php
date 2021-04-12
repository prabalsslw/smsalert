<?php
/* WHMCS SMS Addon with GNU/GPL Licence
 * SMS Alert - https://www.smsalert.co.in
 *
 * https://www.smsalert.co.in
 *
 * 
 * Licence: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.txt)
 * */
//include("smsalert/classes/smsalert.php");
include("smsalert/vendor/autoload.php");
class Sms{
    public $params;
    public $gsmnumber;
    public $message;
    public $userid;
    public $errors = array();
    public $logs = array();
	
    public function setGsmnumber($gsmnumber){
        $this->gsmnumber = $gsmnumber;
    }
	
    public function getGsmnumber(){
        return $this->gsmnumber;
    }

    public function setMessage($message){
        $this->message = $message;
    }

    public function getMessage(){
        return $this->message;
    }

    public function setUserid($userid){
        $this->userid = $userid;
    }

    public function getUserid(){
        return $this->userid;
    }
	
    public function getSettings(){
        $result = select_query("mod_SmsAlert_settings", "*",array('id'=>1));
        return mysql_fetch_array($result);
    }
	
    public function getParams(){
        $settings	= $this->getSettings();
        $params 	= json_decode($settings['apiparams'],true);
		return $params;
    }

    public function createObject(){
        $params 	= $this->getParams();
		$username 	= $params['username'];
		$password 	= $params['password'];
		$senderId 	= $params['senderid'];
		$c_code		= $params['country_code'];
		$platform   = $params['apitype'];
		$apitoken   = $params['apitoken'];
		return (new SMSAlert\Lib\Smsalert\Smsalert()) 		
					   ->authWithUserIdPwd($username, $password)
					   ->setForcePrefix($c_code)
					   ->setSender($senderId)
					   ->setPlatform($platform)
					   ->setApiToken($apitoken);
					 
					   return array();
    }

    function send(){
        $text     = $this->message;
        $to       = $this->getGsmnumber();
		$smsalert = $this->createObject();
		$result   = $smsalert->send($to,$text); 
		$phid='';
		if ($result['status'] == "success") {
			$log[] = ("Message sent!");
			$phid  = $result['description']['batchid'];
		} else {
			$err_mesg  = is_array($result['description']) ? $result['description']['desc'] : $result['description'];
			$log[]     = ("Error: $ret");
			$error[]   = ("Error: $err_mesg");
			
		}
        
        $result =  array(
            'log' 	=> $log,
            'error' => $error,
			'phid' 	=> $phid
        );
		foreach($result['log'] as $log){
                $this->addLog($log);
            }
		if($result['error']){
			foreach($result['error'] as $error){
				$this->addError($error);
			}

			$this->saveToDb($result['phid'],'error',$this->getErrors(),$this->getLogs());
			return false;
		}else{
			$this->saveToDb($result['phid'],'',null,$this->getLogs());
			return true;
		}	
    }

    function getotpdetails($userid=null)
	{	
	    $this->setUserid( $userid );
		$client_query = $this->getClientDetailsBy( $this->userid );
		$client       = mysql_fetch_array( $client_query , MYSQL_ASSOC);
		
		$datas        = select_query("mod_SmsAlert_otp", "*",array('user_id'=>$userid,'phone'=>$client['gsmnumber']));
		$params 	= $this->getParams();
		$country_code = $params['country_code'];
		$no = str_replace('+','',$client['gsmnumber']);
		$numbers = explode('.',$no);
		$data         = mysql_fetch_array($datas);
		if((empty($data) || !$data['verify']) && ($country_code==$numbers[0] || $country_code==''))
		{
			$template = $this->getTemplateDetails('ClientAreaRegister_clientarea');
			$message = str_replace(['{firstname}' , '{lastname}', '{otp}'] , [$client['firstname'], $client['lastname'],'[otp]'] , $template['template']);
			$this->setGsmnumber( $client['gsmnumber'] );
			$this->setMessage( $message );
			if($this->sendotp())
			{
				return true;
			}
		}
		return false;
	}

    function sendotp(){
        $text     = $this->message;
		$to       = $this->getGsmnumber();
		$smsalert = $this->createObject();
		$result   = $smsalert->generateOtp($to,$text);
		$phid='';
		if ($result['status'] == "success") {
			$log[] = ("Message sent!");
			$phid  = $result['description']['batchid'];
		} else {
			$err_mesg = is_array($result['description']) ? $result['description']['desc'] : $result['description'];
			$log[]    = ("Message could not be sent. Error: $ret");
			$error[]  = $err_mesg;
			
		}
        
        $result =  array(
            'log'   => $log,
            'error' => $error,
			'phid'  => $phid
        );
		foreach($result['log'] as $log){
                $this->addLog($log);
            }
		if($result['error']){
			foreach($result['error'] as $error){
				$this->addError($error);
			}

			$this->saveToDb($result['phid'],'error',$this->getErrors(),$this->getLogs());
			return false;
		}else{
			$this->saveToDb($result['phid'],'',null,$this->getLogs());
			return true;
		}	
    }
	
	function verifyotp(){
		$otp      = $this->message;
        $to       = $this->getGsmnumber();
		$smsalert = $this->createObject();
		$result   = $smsalert->validateOtp($to,$otp);
		if ($result['status'] == "success") {
			if($result['description']['desc']=='Code Matched successfully.')
			{
				return true;
			}
			return false;
		} else {
			return false;
		}
			
    }

    function getBalance(){
		$smsalert = $this->createObject();
		$result   = $smsalert->balanceCheck();
		if ($result['status'] == "success"){
			return $result['description']['routes'][0]['credits'];
		}else{
			return null;
		}
    }

    function getReport($phid){
		if($phid){
			$smsalert = $this->createObject();
			$result   = $smsalert->pullReport($phid);
			if ($result['status'] == "success"){
               return $result['description']['report'][0]['status'];
            }else{
				 return "error";
            }
        }else{
            return null;
        }
    }

    function getHooks(){
        if ($handle = opendir(dirname(__FILE__).'/hooks')) {
            while (false !== ($entry = readdir($handle))) {
                if(substr($entry,strlen($entry)-4,strlen($entry)) == ".php"){
                    $file[] = require_once('hooks/'.$entry);
                }
            }
            closedir($handle);
        }
        return $file;
    }

    function saveToDb($phid,$status,$errors = null,$logs = null){
		$params = $this->getParams();
        $now    = date("Y-m-d H:i:s");
        $table  = "mod_SmsAlert_messages";
        $values = array(
            "sender"   => $params['senderid'],
            "to"       => $this->getGsmnumber(),
            "text"     => $this->getMessage(),
            "phid"     => $phid,
            "status"   => $status,
            "errors"   => $errors,
            "logs"     => $logs,
            "user"     => $this->getUserid(),
            "datetime" => $now
        );
        insert_query($table, $values);
    }

    public function addError($error){
        $this->errors[] = $error;
    }

    public function addLog($log){
        $this->logs[] = $log;
    }

    public function getErrors()
    {
        $res = '<pre><p><ul>';
        foreach($this->errors as $d){
            $res .= "<li>$d</li>";
        }
        $res .= '</ul></p></pre>';
        return $res;
    }

    public function getLogs()
    {
        $res = '<pre><p><strong>Debug Result</strong><ul>';
        foreach($this->logs as $d){
            $res .= "<li>$d</li>";
        }
        $res .= '</ul></p></pre>';
        return $res;
    }

    function checkHooks($hooks = null){
        if($hooks == null){
            $hooks = $this->getHooks();
        }

        $i=0;
        foreach($hooks as $hook){
            $sql = "SELECT `id` FROM `mod_SmsAlert_templates` WHERE `name` = '".$hook['function']."' AND `type` = '".$hook['type']."' LIMIT 1";
            $result   = mysql_query($sql);
            $num_rows = mysql_num_rows($result);
            if($num_rows == 0){
                if($hook['type']){
                    $values = array(
                        "name"        => $hook['function'],
                        "type"        => $hook['type'],
                        "template"    => $hook['defaultmessage'],
                        "variables"   => $hook['variables'],
                        "extra"       => $hook['extra'],
                        "description" => json_encode(@$hook['description']),
                        "active"      => 1
                    );
                    insert_query("mod_SmsAlert_templates", $values);
                    $i++;
                }
            }else{
                $values = array(
                    "variables" => $hook['variables']
                );
                update_query("mod_SmsAlert_templates", $values, "name = '" . $hook['name']."'");
            }
        }
        return $i;
    }

    function getTemplateDetails($template = null){
        $where  = array("name" => $template);
        $result = select_query("mod_SmsAlert_templates", "*", $where);
        $data   = mysql_fetch_assoc($result);
        return $data;
    }

    function changeDateFormat($date = null){
        $settings   = $this->getSettings();
        $dateformat = $settings['dateformat'];
        if(!$dateformat){
            return $date;
        }
        $date       = explode("-",$date);
        $year       = $date[0];
        $month      = $date[1];
        $day        = $date[2];
        $dateformat = str_replace(array("%d","%m","%y"),array($day,$month,$year),$dateformat);
        return $dateformat;
    }

    function getClientDetailsBy($clientId){
            $userSql = "SELECT `a`.`id`,`a`.`firstname`, `a`.`lastname`, `a`.`phonenumber` as `gsmnumber`, `a`.`country`
        FROM `tblclients` as `a` WHERE `a`.`id`  = '".$clientId."'
        LIMIT 1";
        return mysql_query($userSql);
    }
	
    function getClientAndInvoiceDetailsBy($clientId){
        $userSql = "
        SELECT a.total,a.duedate,b.id as userid,b.firstname,b.lastname,`b`.`country`,`b`.`phonenumber` as `gsmnumber` FROM `tblinvoices` as `a`
        JOIN tblclients as b ON b.id = a.userid
        WHERE a.id = '".$clientId."'
        LIMIT 1
    ";
        return mysql_query($userSql);
    }
}