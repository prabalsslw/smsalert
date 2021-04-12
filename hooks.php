<?php
/* WHMCS SMS Addon with GNU/GPL Licence
 * SMS Alert - https://www.smsalert.co.in
 *
 * https://www.smsalert.co.in/
 *
 * Licence: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.txt)
 * */
if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

require_once("smsclass.php");
$class = new Sms();
$hooks = $class->getHooks();

foreach($hooks as $hook){
    add_hook($hook['hook'], 1, $hook['function'], "");
}