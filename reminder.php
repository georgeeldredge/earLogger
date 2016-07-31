<?php
	
ob_start();
session_start();

$datetime = time();

require("tools/mysqlConnect.php");
require("tools/config.php");
require("tools/recipient.php");

require("tools/classes.php");
require("tools/functions.php");


// ONLY SEND TEXT OUTPUT IF CALLING FROM A REMOTE SERVER
if (!strstr($_SERVER['HTTP_USER_AGENT'],"Lynx")) {
	$textReport = true;
}

$goNoGo = true;

// HAS ENOUGH TIME PASSED SINCE THE LAST UPDATE?
$hourDelay = 6;
$minuteDelay = 0;
$secondDelay = 0;

$additionalHours = 0;
$additionalMinutes = 30;
$additionalSeconds = 0;

$delay = ($hourDelay * 60 * 60) + ($minuteDelay * 60) + $secondDelay;
$additional = ($additionalHours * 60 * 60) + ($additionalMinutes * 60) + $additionalSeconds;

$query_findUpdate = "SELECT datetime,status FROM updates ORDER BY datetime DESC LIMIT 1";
$result_findUpdate = mysqli_query($dbc,$query_findUpdate) or die ("Could not find last update: " . mysqli_error($dbc));
if (mysqli_affected_rows($dbc)) {
	$row_findUpdate = mysqli_fetch_array($result_findUpdate);
	$lastUpdate = $row_findUpdate['datetime'];
	$lastStatus = $row_findUpdate['status'];
}

$delay += ($lastStatus * $additional);

if ((time() - $delay) < $lastUpdate) {
	if ($textReport) {
		echo 'too soon from update<br />';
	}
	$goNoGo = false;	
}

// HAS ENOUGH TIME PASSED SINCE THE LAST REMINDER?
$hourDelay = 2;
$minuteDelay = 0;
$secondDelay = 0;

$delay = ($hourDelay * 60 * 60) + ($minuteDelay * 60) + $secondDelay;

$query_findReminder = "SELECT datetime FROM reminders ORDER BY datetime DESC LIMIT 1";
$result_findReminder = mysqli_query($dbc,$query_findReminder) or die ("Could not find last update: " . mysqli_error($dbc));
if (mysqli_affected_rows($dbc)) {
	$row_findReminder = mysqli_fetch_array($result_findReminder);
	$lastReminder = $row_findReminder['datetime'];
}

if ((time() - $delay) < $lastReminder) {
	if ($textReport) {
		echo 'too soon from reminder<br />';
	}
	$goNoGo = false;	
}

// IS IT WITHIN ACCEPTABLE HOURS?
$hourMin = 7;
$minuteMin = 30;

$hourMax = 22;
$minuteMax = 30;

if ((date('Gi') < $hourMin . str_pad($minuteMin,2,"0",STR_PAD_LEFT)) || (date('Gi') > $hourMax . str_pad($minuteMax,2,"0",STR_PAD_LEFT))) {
	if ($textReport) {
		echo 'unacceptable time<br />';
	}
	$goNoGo = false;
}

if ($goNoGo) {
	
	// SEND THE REMINDER
// REMOVED BECAUSE API BROKE 2016-07-28
//	$googleVoice = new GoogleVoice($googleUser, $googlePass, $googleRecoveryEmail);

	$message = $reminderMessage[rand(0,(sizeof($reminderMessage)-1))];
	$recipient = $recipientAddress;
	
// REMOVED BECAUSE API BROKE 2016-07-28
//	$googleVoice->sendSMS($recipient,$message);

	$to = $recipient;
	$headers = 'From: Ear Log <ear@georgeeldredge.com>' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	
	mail($to,'',$message,$headers);
	
	// INSERT THE REMINDER INTO THE DB
	$query_addReminder = "INSERT INTO reminders (datetime) VALUES ('$datetime')";
	$result_addReminder = mysqli_query($dbc,$query_addReminder) or die ("Could not add reminder: " . mysqli_error($dbc));
	echo "reminder sent";

	exit();

} else {
	if ($textReport) {
		echo "no reminder sent";
	}
}
		
?>
