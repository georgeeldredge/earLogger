<?php
	
ob_start();
session_start();

require("tools/mysqlConnect.php");
require("tools/config.php");

require("tools/classes.php");
require("tools/functions.php");

$input = json_decode(file_get_contents('php://input'), true);
$input = $_GET['data'];

$logMessage .= "\r\n\r\n" . $input['ear'];

$textDateTime = time();
$textDateTimeReadable = date("Y-m-d | H:i");
$text = $input;

$query_addText = "INSERT INTO textUpdates (date,data,datetime) VALUES ('$textDateTimeReadable', '$text', '$textDateTime')";
$result_addText = mysqli_query($dbc,$query_addText) or die ("Could not add text to db: " . mysqli_error($dbc));
	
$logContents = file_get_contents("data/log.txt");
$logFile = fopen("data/log.txt","r+");
$logBegin = date("Y-m-d | H:i");
fwrite($logFile,"\r\n\r\n");
fwrite($logFile,$logBegin);
fwrite($logFile,$logMessage);
fwrite($logFile,"\r\n\r\n");
fwrite($logFile,$logContents);
fclose($logFile);

$data = explode(',,',$input['ear']);
$data = explode(',,',$input);

$i=0;
if (($data[0] != '') && (!is_numeric($data[0]))) {
	$i--;
}
$status = $data[$i+0];
$notes = $data[$i+1];
$prednisone = $data[$i+2];
$leftScore = $data[$i+3];
$rightScore = $data[$i+4];
$xanax = $data[$i+5];
$dizzy = $data[$i+6];

if (strstr($notes,'xanax')) {
	$xanax = 1;
}

if (strstr($notes,'dizzy')) {
	$dizzy = 1;
}

if ($oldDateTime) {
	$datetime = mktime(12,0,0,substr($oldDateTime,4,2),substr($oldDateTime,6,2),substr($oldDateTime,0,4));
} else {
	$datetime = time();	
}

if ($status == '') { // THERE IS NO STATUS PROVIDED - USE THE LAST STATUS IN THE DB
	$query_getStatus = "SELECT status FROM updates ORDER BY datetime DESC LIMIT 1";
	$result_getStatus = mysqli_query($dbc,$query_getStatus) or die ("Could not get previous status: " . mysqli_error($dbc));
	if (mysqli_affected_rows($dbc)) { // BETTER BE ONE!
		$row_getStatus = mysqli_fetch_array($result_getStatus);
		$status = $row_getStatus['status'];
	}
}

// PUT THAT THING UP THERE
$query_addUpdate = "INSERT INTO updates (status, prednisone, notes, leftScore, rightScore, xanax, dizzy, datetime) VALUES ('$status', '$prednisone', '$notes', '$leftScore', '$rightScore', '$xanax', '$dizzy', '$datetime')";
$result_addUpdate = mysqli_query($dbc,$query_addUpdate) or die ("Could not add update: " . mysqli_error($dbc) . "<br /><br />" . $query_addUpdate);

?>
