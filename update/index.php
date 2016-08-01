<?php
	
ob_start();
session_start();

if ($_SERVER['REMOTE_ADDR'] != '98.1833.54.9') {
	header("Location: /");
	exit();
}

require("../tools/mysqlConnect.php");
require("../tools/config.php");

if ($_POST) {
	
	if ($_POST['action'] == 'submittedUpdate') {
		
		$status = $_POST['status'];
		$notes = $_POST['notes'];
		$prednisone = $_POST['prednisone'];
		$leftScore = $_POST['leftScore'];
		$rightScore = $_POST['rightScore'];
		$xanax = $_POST['xanax'];
		$dizzy = $_POST['dizzy'];
		$datetime = time();

		if ($status == '') { // THERE IS NO STATUS PROVIDED - USE THE LAST STATUS IN THE DB
			$query_getStatus = "SELECT status FROM updates ORDER BY datetime DESC LIMIT 1";
			$result_getStatus = mysqli_query($dbc,$query_getStatus) or die ("Could not get previous status: " . mysqli_error($dbc));
			if (mysqli_affected_rows($dbc)) { // BETTER BE ONE!
				$row_getStatus = mysqli_fetch_array($result_getStatus);
				$status = $row_getStatus['status'];
			}
		}

		$query_addUpdate = "INSERT INTO updates (status, prednisone, notes, leftScore, rightScore, xanax, dizzy, datetime) VALUES ('$status', '$prednisone', '$notes', '$leftScore', '$rightScore', '$xanax', '$dizzy', '$datetime')";
		$result_addUpdate = mysqli_query($dbc,$query_addUpdate) or die ("Could not add update: " . mysqli_error($dbc) . "<br /><br />" . $query_addUpdate);
		
	} elseif ($_POST['action'] == 'editedValues') {
		
		foreach ($_POST['updateId'] as $id) {
			$status = $_POST['status'][$id];
			$notes = $_POST['notes'][$id];
			$prednisone = $_POST['prednisone'][$id];
			$leftScore = $_POST['leftScore'][$id];
			$rightScore = $_POST['rightScore'][$id];
			$xanax = $_POST['xanax'][$id];
			$dizzy = $_POST['dizzy'][$id];
			$delete = $_POST['delete'][$id];
			
			$query_updateUpdate = "UPDATE updates SET status='$status', notes='$notes', prednisone='$prednisone', leftScore='$leftScore', rightScore='$rightScore', xanax='$xanax', dizzy='$dizzy' WHERE id='$id'";
			$result_updateUpdate = mysqli_query($dbc,$query_updateUpdate) or die ("Could not update update: " . mysqli_error($dbc));
			
			if ($delete) {
				$query_deleteUpdate = "DELETE FROM updates WHERE id='$id'";
				$result_deleteUpdate = mysqli_query($dbc,$query_deleteUpdate) or die ("Could not delete update: " . mysqli_error($dbc));
			}
			
		}
	
	}
	
	header("Location: /update");
	exit();	
}

$query_getData = "SELECT * FROM updates ORDER BY datetime ASC";
$result_getData = mysqli_query($dbc,$query_getData) or die ("Could not get data: " . mysqli_error($dbc));
if (mysqli_affected_rows($dbc)) {
	
	while ($row_getData = mysqli_fetch_array($result_getData)) {
		$id = $row_getData['id'];
		$time = $row_getData['datetime'];
		$status = $row_getData['status'];
//		$status = abs($status - 5);
		$note = $row_getData['notes'];
		$prednisone = $row_getData['prednisone'];
		$leftScore = $row_getData['leftScore'];
		$rightScore = $row_getData['rightScore'];
		$xanax = $row_getData['xanax'];
		$dizzy = $row_getData['dizzy'];

		$timePoint = date("YmdHis",$time);
		
		if ($timePoint > $timePointMax) {
			$timePointMax = $timePoint;
		}
		
		$dataArray[$timePoint] = array (
							'id'			=>	$id,
							'time'			=>	$time,
							'status'		=>	$status,
							'note'			=>	$note,
							'prednisone'	=>	$prednisone,
							'leftScore'		=>	$leftScore,
							'rightScore'	=>	$rightScore,
							'xanax'			=>	$xanax,
							'dizzy'			=>	$dizzy,
		);
	}
	
	ksort($dataArray);
		
}

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="/tools/style.css" />

<title>George's Downward Spiral Into Hearing Loss - Admin</title>

</head>

<body>

<h1 style="font-size: 18px;">George's Downward Spiral Into Hearing Loss - Admin</h1>

<p style="margin-bottom: 1em;"><a href="/">Return to Graph</a></p>

<div style="border: 1px solid #AAAAAA; border-left: none; border-right: none; padding: 6px 6px 6px 6px;">

	<form action="" method="post">
		<div class="line">
			<div class="ask">Status:</div>
			<div class="answer"><input type="text" name="status" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Note:</div>
			<div class="answer"><input type="text" name="notes" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Prednisone:</div>
			<div class="answer"><input type="text" name="prednisone" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Left Score:</div>
			<div class="answer"><input type="text" name="leftScore" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Right Score:</div>
			<div class="answer"><input type="text" name="rightScore" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Xanax:</div>
			<div class="answer"><input type="checkbox" name="xanax" value="1" /></div>
			<div class="clear"></div>
		</div>

		<div class="line">
			<div class="ask">Dizzy:</div>
			<div class="answer"><input type="checkbox" name="dizzy" value="1" /></div>
			<div class="clear"></div>
		</div>

		<div class="line submit">
			<input type="hidden" name="action" value="submittedUpdate" />
		    <input type="submit" value="Submit Update" />
		</div>
        
	</form>

</div>

<?php
krsort($dataArray);
?>

<form action="" method="post">
<p>
    <input type="hidden" name="action" value="editedValues" />
    <input type="submit" value="Submit Edits" />
</p>
<table id="tabularData">
   	<tr class="header">
       	<th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th class="note">Note</th>
        <th>Prednisone Since Last Update</th>
        <th>Left Score</th>
        <th>Right Score</th>
        <th>Xanax</th>
        <th>Dizzy</th>
        <th>Delete</th>
    </tr>
<?php
	foreach ($dataArray as $dataPoint => $data) {
?>
		<tr>
        	<td class="center"><?=substr($dataPoint,0,4) . '-' . substr($dataPoint,4,2) . '-' . substr($dataPoint,6,2)?></td>
        	<td class="center"><?=substr($dataPoint,8,2) . ':' . substr($dataPoint,10,2) . ':' . substr($dataPoint,12,2)?></td>
            <td class="center">
			    <input type="hidden" name="updateId[]" value="<?=$data['id']?>" />
            	<select name="status[<?=$data['id']?>]">
<?php
					for ($ss=5;$ss>=0;$ss = $ss - .5) {
						if ($ss == $data['status']) {
							$selected = 'selected="selected"';
						} else {
							unset($selected);
						}
?>
						<option value="<?=$ss?>" <?=$selected?>><?=$ss?></option>
<?php
					}
?>
				</select>
            </td>
            <td class="note"><input type="text" name="notes[<?=$data['id']?>]" value="<?=$data['note']?>" /></td>
            <td class="center">
            	<select name="prednisone[<?=$data['id']?>]">
<?php
					for ($pp=100;$pp>=0;$pp -= 5) {
						if ($pp == $data['prednisone']) {
							$selected = 'selected="selected"';
						} else {
							unset($selected);
						}
?>
						<option value="<?=$pp?>" <?=$selected?>><?=$pp?></option>
<?php
					}
?>
				</select>
			</td>
            <td class="center"><input type="text" name="leftScore[<?=$data['id']?>]" value="<?=$data['leftScore']?>" /></td>
            <td class="center"><input type="text" name="rightScore[<?=$data['id']?>]" value="<?=$data['rightScore']?>" /></td>
            <td class="center">
            	<input type="checkbox" name="xanax[<?=$data['id']?>]" value="1" <?php if ($data['xanax'] > 0) { echo 'checked="checked"'; }?> />
            </td>
            <td class="center">
            	<input type="checkbox" name="dizzy[<?=$data['id']?>]" value="1" <?php if ($data['dizzy'] > 0) { echo 'checked="checked"'; }?> />
            </td>
            <td class="center">
            	<input type="checkbox" name="delete[<?=$data['id']?>]" value="1" />
            </td>
        </tr>
<?php
	}
?>
</table>
<p>
    <input type="hidden" name="action" value="editedValues" />
    <input type="submit" value="Submit Edits" />
</p>

</form>

</body>

</html>
