<?php
$class    = new Sms();
$template = $class->getTemplateDetails('ClientAreaRegister_clientarea');

if($template['active'] == 0){
            return null;
}
	
if(isset($_POST['action']) && $_POST['action'] === 'resendOtp'):
        $user_id = $_POST['user_id'];
		$class   = new Sms();
		$class->getotpdetails($user_id);
	exit;
endif;

if(isset($_POST['action']) && $_POST['action'] === 'Logout'):
         session_regenerate_id();
         session_destroy(); 
	exit;
endif;

if(isset($_POST['action']) && $_POST['action'] === 'otpVerification'):	
	$user_otp     = $_POST['otp'];
	$user_id      = $_POST['user_id'];
	$class->setUserid($user_id );
	$client_query = $class->getClientDetailsBy( $class->userid );
	$client       = mysql_fetch_array( $client_query , MYSQL_ASSOC);	
	$number       = $client['gsmnumber'];
	$class->setGsmnumber( $number );
	$class->setMessage( $user_otp );
	
	if($class->verifyotp()):
	     $datas = select_query("mod_SmsAlert_otp", "*",array('user_id'=>$class->userid));
		$data   = mysql_fetch_array($datas);
		 if(empty($data))
		 {
			$values = array(
				"user_id" => $class->userid,
				"phone"   => $number,
				"verify"  => 1
           );
           insert_query('mod_SmsAlert_otp', $values); 
		 }
		 else{
			 update_query('mod_SmsAlert_otp',array('phone'=>$number,'verify'=>1),array('user_id'=>$class->userid));
		 }
		
	   echo json_encode(['code' => 1 , 'message' => 'Your phone number has been verified.' , 'data' => []]);
		exit;
	endif;	
	
	echo json_encode(['code' => 0 , 'message' => 'OTP you have entered didn\'t match' , 'data' => []]);
	exit;
	
endif;

//Action Hooks.
 add_hook('AdminAreaClientSummaryPage', 1, function($vars) {
	$class   = new Sms();
	$user_id = $vars['userid'];
	if($user_id):
		$datas       = select_query("mod_SmsAlert_otp", "*",array('user_id'=>$user_id));
		$data        = mysql_fetch_array($datas); 
		$userDetails = mysql_fetch_array($class->getClientDetailsBy( $user_id ) , MYSQL_ASSOC);
		if(count($data)):
				if($userDetails['gsmnumber'] == $data['phone']):
					if($data['verify']):
						return '<div class="clientsummaryactions">Phone Verified: <span id="taxstatus" class="csajaxtoggle"><strong class="textgreen">Yes</strong></span></div>';
					else:
						return '<div class="clientsummaryactions">Phone Verified: <span id="taxstatus" class="csajaxtoggle"><strong class="textred">No</strong></span></div>';						
					endif;
				endif;
		endif;	
		
	endif;
});  
	
add_hook('ClientAreaHeadOutput', 1, function($vars) {
	return '<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
			<script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>';
});

add_hook('ClientAreaHeaderOutput', 1, function($vars) {
	if(isset($vars['client'])):
	    $return='';
	    $datas   = select_query("mod_SmsAlert_otp", "*",array('user_id'=>$vars['client']->id,'phone'=>$vars['client']->phonenumber));
		$data    = mysql_fetch_array($datas);	
		$base = basename($_SERVER['PHP_SELF']);
		if(empty($data) || !$data['verify']):
		$class = new Sms();
		if(($class->getotpdetails($vars['client']->id)) && $base!='cart.php'):
			$return = '<!-- Trigger the modal with a button -->
						<!-- Modal -->
						<form method="post" class="using-password-strength" action="" onSubmit="return checkOtpForm(this);" role="form" name="otpVerification" id="frmOtpVerification">
							<input type="hidden" name="action" value="otpVerification">
							<input type="hidden" name="user_id" value="' . $vars['client']->id . '">
							<div id="hook-modal" class="modal fade" role="dialog">
							  <div class="modal-dialog">
							
								<!-- Modal content-->
								<div class="modal-content">
								  <div class="modal-header">									
									<h4 class="modal-title">Mobile Verification</h4>
								  </div>
								  <div class="modal-body col-lg-12">
								  <p>OTP sent to Phone number  '. $vars['client']->phonenumber .'</p>
									<div class="form-group col-lg-8 col-xs-12">
										<input type="text" name="otp" class="form-control" id="otp" placeholder="Enter OTP">
									</div>
									<div class="form-group col-lg-4 col-xs-12">
										<button type="submit" class="btn btn-primary">Validate OTP</button>
									</div>
								  </div>
								  <div class="modal-footer">
								  <hr/>
								  <p class="text-center margin-10"><a href="javascript::void(0)" id="logout" style="pointer-events: auto; cursor: pointer; opacity: 1; float: left;">Go Back</a>
									</p>
									<p class="text-center margin-10"><a href="javascript::void(0)" id="resend-otp" style="pointer-events: auto; cursor: pointer; opacity: 1; float: right;">Resend</a><span id="timer" style="min-width: 80px; float: right; display: none;">00:00:00 sec</span>
									</p>
								  </div>
								</div>
							
							  </div>
							</div>
						</form>';
				 endif;
			endif;
		endif;
	return $return;
});


add_hook('ClientAreaFooterOutput', 1, function($vars) {
	  $return = '<script type="text/javascript">
					  $(window).on(\'load\',function(){
						  $(\'#hook-modal	\').modal({backdrop: \'static\', keyboard: false});
						   timerCount();
					  });
					    function timerCount()
						{
							var timer = function(secs){
								var sec_num = parseInt(secs, 10)    
								var hours   = Math.floor(sec_num / 3600) % 24
								var minutes = Math.floor(sec_num / 60) % 60
								var seconds = sec_num % 60    
								hours = hours < 10 ? "0" + hours : hours;
								minutes = minutes < 10 ? "0" + minutes : minutes;
								seconds = seconds < 10 ? "0" + seconds : seconds;
								return [hours,minutes,seconds].join(":");
							};
							document.getElementById("timer").style.display = "block";
							document.getElementById("timer").innerHTML = timer(15)+" sec&nbsp;";
							var counter = 15;
							 interval = setInterval(function() {
								counter--;
								document.getElementById("timer").innerHTML = timer(counter)+ " sec&nbsp;";
								if (counter == 0) {
									counterRunning=false;
									document.getElementById("timer").style.display = "none";
									var cssString = "pointer-events: auto; cursor: pointer; opacity: 1; float:right"; 
									document.getElementById("resend-otp").style.cssText = cssString;
									clearInterval(interval);
								}
								else
								{
									document.getElementById("resend-otp").style.cssText = "pointer-events: none; cursor: default; opacity: 1; float:right";
								}
							}, 1000);
						} 
					  $("#resend-otp").click(function(e){
                              timerCount();
							  $.ajax({
									  url: \'clientarea.php\',
									  type: \'post\',
									  data: {"user_id": $("input[name=user_id]").val()  , "action":"resendOtp" },
									  //async: false,
									  beforeSend: function () {
										  //Can we add anything here.
									  },
									  cache: true,
									  dataType: \'json\',
									  crossDomain: true,
									  success: function (data) {
										  console.log(data);
										  if (data.code == 1) {
											  
											  swal({
													  title: "Success!",
													  text: data.message,
													  type: "success"
												  }, function() {
													  window.location = "clientarea.php";
												  });
											  
										  } else {
											  bootbox.alert(data.message);
										  }
									  },
									  error: function (data) {
										  console.log(\'Error:\', data);
									  }
								  });
							  

					  });
					  $("#logout").click(function(e){
                              $.ajax({
									  url: \'clientarea.php\',
									  type: \'post\',
									  data: {"user_id": $("input[name=user_id]").val()  , "action":"Logout" },
									  cache: true,
									  dataType: \'json\',
									  crossDomain: true,
									  success: function (data) {
										   window.location = "clientarea.php";
									  },
									  error: function (data) {
										 window.location = "clientarea.php";
									  }
								  });
							  

					  });
					  function checkOtpForm(elements) {
						  
						  if(elements.otp.value == \'\') {
							  swal({
									  title: "Error!",
									  text: "Please Enter OTP",
									  type: "error"
								  }, function() {
									  elements.otp.focus();
								  });
						  } else {
							  
							  $.ajax({
									  url: \'clientarea.php\',
									  type: \'post\',
									  data: $(elements).serialize(),
									  //async: false,
									  beforeSend: function () {
										  //Can we add anything here.
									  },
									  cache: true,
									  dataType: \'json\',
									  crossDomain: true,
									  success: function (data) {
										  console.log(data);
										  if (data.code == 1) {
											  
											  swal({
													  title: "Success!",
													  text: data.message,
													  type: "success"
												  }, function() {
													  window.location = "clientarea.php";
												  });
											  
										  } else {
											  swal({
													  title: "Error!",
													  text: data.message,
													  type: "error"
												  }, function() {
													  $("#otp").val("");
												  });
										  }
									  },
									  error: function (data) {
										  console.log(\'Error:\', data);
									  }
								  });
							  
							  
						  }
						  
						  return false;
					  }
					  
				  </script>';
	return $return;					
});