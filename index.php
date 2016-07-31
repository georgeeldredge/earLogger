<?php
	
ob_start();
session_start();

require("tools/mysqlConnect.php");
require("tools/config.php");

require("tools/classes.php");
require("tools/functions.php");

$leftScoreMin = 100;
$rightScoreMin = 100;

$allowedFilters = array ('status', 'prednisone', 'scores', 'xanax', 'dizzy',);
if ($_GET) {
	foreach ($_GET as $key => $value) {
		if 	(!in_array($key,$allowedFilters)) {
			header("Location: /");
			exit();
		} else {
			$displayFilters[$key] = true;
		}
	}
} else {
	foreach ($allowedFilters as $filter) {
		$displayFilters[$filter] = true;	
	}
}

if ($_POST) {
/*
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
		$result_addUpdate = mysqli_query($dbc,$query_addUpdate) or die ("Could not add update: " . mysqli_error($dbc));
	}
*/
	
	header("Location: /");
	exit();	
}

$query_getData = "SELECT * FROM updates ORDER BY datetime ASC";
$result_getData = mysqli_query($dbc,$query_getData) or die ("Could not get data: " . mysqli_error($dbc));
if (mysqli_affected_rows($dbc)) {
	
	while ($row_getData = mysqli_fetch_array($result_getData)) {
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
		
		if (($leftScore) && ($leftScore < $leftScoreMin)) {
			$leftScoreMin = $leftScore;
		}
		
		if (($rightScore) && ($rightScore < $rightScoreMin)) {
			$rightScoreMin = $rightScore;
		}
		
		$dataArray[$timePoint] = array (
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

function displayGraph() {
	
	global $dataArray;
	global $timePointMax;
	global $leftScoreMin;
	global $rightScoreMin;
	global $displayFilters;

	$canvasWidth = 1200;
	$canvasHeight = 400;
	$marginTop = 30.5;
	$marginBottom = 30.5;
	$marginLeft = 30.5;
	$marginRight = 200.5;
	$legendWidth = 160.5;
	$legendHeight = 300;
	$originX = $marginLeft;
	$originY = $canvasHeight - $marginBottom;
	$endX = $canvasWidth - $originx - $marginRight;
	$endY = $marginTop;
//	$ticksX = 6;
	$ticksX = 3;
	$ticksY = 20;
	$tickSpacingX = ($endX - $originX)/$ticksX;
	$tickSpacingY = ($endY - $originY)/$ticksY;
//	$dataPointsX = 365 * 24 * 60 * 60 / 2;
//	$dataPointsX = 365 * 24 * 60 * 60 / (12 / $ticksX);
	$dataPointsX = 90 * 24 * 60 * 60; // 90 DAYS, IN SECONDS
	$dataPointsY = 5;
	$dataPointsYp = 100;
	$dataPointsYs = 100;
	$dataSpacingX = ($endX - $originX)/$dataPointsX;
	$dataSpacingY = ($endY - $originY)/$dataPointsY;
	$dataSpacingYp = ($endY - $originY)/$dataPointsYp;
	$dataSpacingYs = ($endY - $originY)/$dataPointsYs;
	$largeTickLength = 10.5;
	$smallTickLength = 5.5;
	$dataPointRadius = 2.5;
	$dataPointRadiusLarge = 4;
	
	$oneWeekMinusSecond = (60 * 60 * 24 * 7) - 1;
	$oneDay = (60 * 60 * 24);
	$end = $start + $oneWeekMinusSecond;
	
// WHEN DOP WE START OUR DATA?	
	
	$lastMonth = substr($timePointMax,4,2);
//	$firstMonth = date('m',strtotime(date('d F Y',$dataArray[$timePointMax]['time']) . " -4 months"));
	$firstMonth = date('m',strtotime(date('d F Y',$dataArray[$timePointMax]['time']) . " -2 months"));
	$month = $firstMonth;
//	$firstSecond = strtotime(date('d F Y',$dataArray[$timePointMax]['time']) . " -4 months");
//	$firstSecond = strtotime(date('d F Y',$dataArray[$timePointMax]['time']) . " -2 months");
	$firstSecond = strtotime(date('d F Y',$dataArray[$timePointMax]['time']) . " -85 days");
	$firstSecond = mktime(0,0,0,date('m',$firstSecond),date('d',$firstSecond),date('Y',$firstSecond));
	
	// CLEAN UP THE DATA - IF OLDER THAN 6 MONTHS IT DONT MATTER
	foreach ($dataArray as $date => $data) {
		if ($data['time'] < $firstSecond) {
			unset($dataArray[$date]);
		}
	}

	$response .= "\r\n" . '<canvas id="earChart" width="' . $canvasWidth . '" height="' . $canvasHeight . '" style="display: block;"></canvas>';

	$response .= "\r\n" . '<script type="text/javascript">';

// DRAW MAJOR TICK LINES (MONTHS), AND PUT IN THEIR LABELS

		$response .= "\r\n\r\n" . '// MAJOR TICK MARKS';

		for ($ss=$firstSecond;$ss<($firstSecond+$dataPointsX);$ss=$ss+86400) {
			if (date('j',$ss) == '1') {
				$response .= "\r\n\r\n" . '// ' . $ss;
				$response .= "\r\n" . '// ' . ($ss - $firstSecond);
				$response .= "\r\n" . '// ' . date('j',$ss);
				$response .= "\r\n" . '// ' . date('r',$ss);
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#BBBBBB',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y1: ' . ($originY) . ',';
					$response .= "\r\n" . 'x2: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y2: ' . ($endY) . ',';
				$response .= "\r\n" . '});';
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#000000',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y1: ' . $originY . ',';
					$response .= "\r\n" . 'x2: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y2: ' . ($originY - $largeTickLength) . ',';
				$response .= "\r\n" . '});';	
				$response .= "\r\n\r\n" . '// LABEL';
				$response .= "\r\n" . "$('canvas#earChart').drawText({";
					$response .= "\r\n" . "fillStyle: '#000000',";
					$response .= "\r\n" . "strokeStyle: '#000000',";
					$response .= "\r\n" . 'strokeWidth: 0,';
					$response .= "\r\n" . 'x: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y: ' . ($originY + $largeTickLength) . ',';
					$response .= "\r\n" . 'fontSize: 8.5,';
					$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
					$response .= "\r\n" . "text: '" . date("j",$ss) . "'";
				$response .= "\r\n" . '});';
				$response .= "\r\n" . "$('canvas#earChart').drawText({";
					$response .= "\r\n" . "fillStyle: '#000000',";
					$response .= "\r\n" . "strokeStyle: '#000000',";
					$response .= "\r\n" . 'strokeWidth: 0,';
					$response .= "\r\n" . 'x: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y: ' . ($originY + $largeTickLength + $largeTickLength) . ',';
					$response .= "\r\n" . 'fontSize: 8.5,';
					$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
					$response .= "\r\n" . "text: '" . date("M",$ss) . "'";
				$response .= "\r\n" . '});';
			} elseif (!(date('j',$ss) % 5)) {
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#DEDEDE',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y1: ' . ($originY) . ',';
					$response .= "\r\n" . 'x2: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y2: ' . ($endY) . ',';
				$response .= "\r\n" . '});';
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#000000',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y1: ' . $originY . ',';
					$response .= "\r\n" . 'x2: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y2: ' . ($originY - $smallTickLength) . ',';
				$response .= "\r\n" . '});';	
				$response .= "\r\n\r\n" . '// LABEL';
				$response .= "\r\n" . "$('canvas#earChart').drawText({";
					$response .= "\r\n" . "fillStyle: '#000000',";
					$response .= "\r\n" . "strokeStyle: '#000000',";
					$response .= "\r\n" . 'strokeWidth: 0,';
					$response .= "\r\n" . 'x: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y: ' . ($originY + $largeTickLength) . ',';
					$response .= "\r\n" . 'fontSize: 8.5,';
					$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
					$response .= "\r\n" . "text: '" . date('j',$ss) . "'";
				$response .= "\r\n" . '});';
			} else {
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#FBFBFB',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y1: ' . ($originY) . ',';
					$response .= "\r\n" . 'x2: ' . ($originX + (($ss - $firstSecond + 86400) * $dataSpacingX)) . ', y2: ' . ($endY) . ',';
				$response .= "\r\n" . '});';
			}
		}
		
// DRAW MINOR TICK LINES
		$response .= "\r\n\r\n" . '// MINOR TICK MARKS';
		for ($t=1;$t<=$ticksY;$t++) {
			$response .= "\r\n" . "$('canvas#earChart').drawLine({";
				$response .= "\r\n" . "strokeStyle: '#CCCCCC',";
				$repsonse .= "\r\n" . 'strokeWidth: 1,';
				$response .= "\r\n" . 'x1: ' . $originX . ', y1: ' . ($originY + ($t * $tickSpacingY)) . ',';
				$response .= "\r\n" . 'x2: ' . $endX . ', y2: ' . ($originY + ($t * $tickSpacingY)) . ',';
			$response .= "\r\n" . '});';
		}
		
// ADD MINOR AXIS LABELS (PREDNISONE/SCORES ON LEFT, STATUS ON RIGHT)
		$response .= "\r\n\r\n" . '// MINOR AXIS LABELS';
		for ($t=0;$t<=$ticksY;$t++) {
			$response .= "\r\n" . "$('canvas#earChart').drawText({";
				$response .= "\r\n" . "fillStyle: '#0000FF',";
				$response .= "\r\n" . "strokeStyle: '#0000FF',";
				$response .= "\r\n" . 'strokeWidth: 0,';
				$response .= "\r\n" . 'x: ' . ($originX - $smallTickLength) . ', y: ' . ($originY + ($t * $tickSpacingY)) . ',';
				$response .= "\r\n" . "align: 'right',";
				$response .= "\r\n" . 'fontSize: 8.5,';
				$response .= "\r\n" . 'respectAlign: true,';
				$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
				$response .= "\r\n" . "text: '" . ($t * 5) . "'";
			$response .= "\r\n" . '});';
			if (($t<=10) && !($t%2)) {
				$response .= "\r\n" . "$('canvas#earChart').drawText({";
					$response .= "\r\n" . "fillStyle: '#990000',";
					$response .= "\r\n" . "strokeStyle: '#990000',";
					$response .= "\r\n" . 'strokeWidth: 0,';
					$response .= "\r\n" . 'x: ' . ($endX + $smallTickLength) . ', y: ' . ($originY + ($t * ($tickSpacingY*2))) . ',';
					$response .= "\r\n" . "align: 'left',";
					$response .= "\r\n" . 'fontSize: 8.5,';
					$response .= "\r\n" . 'respectAlign: true,';
					$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
					$response .= "\r\n" . "text: '" . $t/2 . "'";
				$response .= "\r\n" . '});';
			}
		}
		
// DRAW AXES
		$response .= "\r\n\r\n" . '// Y AXIS LEFT';
		$response .= "\r\n" . "$('canvas#earChart').drawLine({";
			$response .= "\r\n" . "strokeStyle: '#000000',";
			$repsonse .= "\r\n" . 'strokeWidth: 1,';
			$response .= "\r\n" . 'x1: ' . $originX . ', y1: ' . $originY . ',';
			$response .= "\r\n" . 'x2: ' . $originX . ', y2: ' . $endY . ',';
		$response .= "\r\n" . '});';
		$response .= "\r\n\r\n" . '// Y AXIS RIGHT';
		$response .= "\r\n" . "$('canvas#earChart').drawLine({";
			$response .= "\r\n" . "strokeStyle: '#000000',";
			$repsonse .= "\r\n" . 'strokeWidth: 1,';
			$response .= "\r\n" . 'x1: ' . $endX . ', y1: ' . $originY . ',';
			$response .= "\r\n" . 'x2: ' . $endX . ', y2: ' . $endY . ',';
		$response .= "\r\n" . '});';
		$response .= "\r\n\r\n" . '// X AXIS LEFT';
		$response .= "\r\n" . "$('canvas#earChart').drawLine({";
			$response .= "\r\n" . "strokeStyle: '#000000',";
			$repsonse .= "\r\n" . 'strokeWidth: 1,';
			$response .= "\r\n" . 'x1: ' . $originX . ', y1: ' . $originY . ',';
			$response .= "\r\n" . 'x2: ' . $endX . ', y2: ' . $originY . ',';
		$response .= "\r\n" . '});';

// DRAW EACH DAY'S DATA PLOT
		
		// DIZZYNESS?
		if ($displayFilters['dizzy']) {
			foreach ($dataArray as $dataPoint => $data) {
				if ($data['dizzy'] > 0) {

					$x = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
					$y1 = $originY;
					$y2 = $endY;
					
					$response .= "\r\n\r\n" . '// DIZZYNESS? ' . date("Y-m-d H:i",$data['time']);
					// JUST A LINE ON THE CHART
					$response .= "\r\n" . "$('canvas#earChart').drawLine({";
						$response .= "\r\n" . "strokeStyle: '#FF6600',";
						$repsonse .= "\r\n" . 'strokeWidth: 1,';
						$response .= "\r\n" . 'x1: ' . $x . ', y1: ' . $y1 . ',';
						$response .= "\r\n" . 'x2: ' . $x . ', y2: ' . $y2 . ',';
					$response .= "\r\n" . '});';
					
				}
			}
			unset($datumPrevious);
		}
		
		// LEFT AND RIGHT EAR SCORES
		if ($displayFilters['scores']) {
			foreach ($dataArray as $dataPoint => $data) {
				if ($data['leftScore'] > 0) {
	
					if (!$datumPrevious) {
						$x1l = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
						$x1r = $x1l;
						$y1l = $originY + ($data['leftScore'] * $dataSpacingYs);
						$y1r = $originY + ($data['rightScore'] * $dataSpacingYs);
					} else {
						$x1l = $datumPrevious['x1l'];
						$x1r = $datumPrevious['x1r'];
						$y1l = $datumPrevious['y1l'];
						$y1r = $datumPrevious['y1r'];
					}
					
					// X2
					// SECOND OF THE YEAR
					// TIMES THE X DATA SPACING
					$x2l = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
					$x2r = $x2l;
					
					// Y2
					// SCORE
					$y2l = $originY + ($data['leftScore'] * $dataSpacingYs);
					$y2r = $originY + ($data['rightScore'] * $dataSpacingYs);
					
					$response .= "\r\n\r\n" . '// LEFT EAR SCORE ' . date("Y-m-d H:i",$data['time']);
					// FIRST, THE POINT
					// IF THE MIN SCORE, USE A STAR, ELSE USE A CIRCLE
					if ($data['leftScore'] == $leftScoreMin) {
						$response .= "\r\n" . "$('canvas#earChart').drawPolygon({";
							$response .= "\r\n" . 'strokeStyle: "#009900",';
							$response .= "\r\n" . 'fillStyle: "#009900",';
							$response .= "\r\n" . 'x: ' . $x2l . ', y: ' . $y2l . ',';
							$response .= "\r\n" . 'sides: 5,';
							$response .= "\r\n" . 'concavity: 0.5,';
							$response .= "\r\n" . 'radius: ' . $dataPointRadiusLarge;
						$response .= "\r\n" . '});';
					} else {					
						$response .= "\r\n" . "$('canvas#earChart').drawArc({";
							$response .= "\r\n" . 'fillStyle: "#009900",';
							$response .= "\r\n" . 'x: ' . $x2l . ', y: ' . $y2l . ',';
							$response .= "\r\n" . 'radius: ' . $dataPointRadius;
						$response .= "\r\n" . '});';
					}					
					// THE LINE TO THAT POINT
					$response .= "\r\n" . "$('canvas#earChart').drawLine({";
						$response .= "\r\n" . "strokeStyle: '#009900',";
						$repsonse .= "\r\n" . 'strokeWidth: 1,';
						$response .= "\r\n" . 'x1: ' . $x1l . ', y1: ' . $y1l . ',';
						$response .= "\r\n" . 'x2: ' . $x2l . ', y2: ' . $y2l . ',';
					$response .= "\r\n" . '});';
	
					$response .= "\r\n\r\n" . '// RIGHT EAR SCORE ' . date("Y-m-d H:i",$data['time']);
					// FIRST, THE POINT
					// IF THE MIN SCORE, USE A STAR, ELSE USE A CIRCLE
					if ($data['rightScore'] == $rightScoreMin) {
						$response .= "\r\n" . "$('canvas#earChart').drawPolygon({";
							$response .= "\r\n" . 'strokeStyle: "#CC00CC",';
							$response .= "\r\n" . 'fillStyle: "#CC00CC",';
							$response .= "\r\n" . 'x: ' . $x2r . ', y: ' . $y2r . ',';
							$response .= "\r\n" . 'sides: 5,';
							$response .= "\r\n" . 'concavity: 0.5,';
							$response .= "\r\n" . 'radius: ' . $dataPointRadiusLarge;
						$response .= "\r\n" . '});';
					} else {
						$response .= "\r\n" . "$('canvas#earChart').drawArc({";
							$response .= "\r\n" . 'fillStyle: "#CC00CC",';
							$response .= "\r\n" . 'x: ' . $x2r . ', y: ' . $y2r . ',';
							$response .= "\r\n" . 'radius: ' . $dataPointRadius;
						$response .= "\r\n" . '});';
					}
					// THE LINE TO THAT POINT
					$response .= "\r\n" . "$('canvas#earChart').drawLine({";
						$response .= "\r\n" . "strokeStyle: '#CC00CC',";
						$repsonse .= "\r\n" . 'strokeWidth: 1,';
						$response .= "\r\n" . 'x1: ' . $x1r . ', y1: ' . $y1r . ',';
						$response .= "\r\n" . 'x2: ' . $x2r . ', y2: ' . $y2r . ',';
					$response .= "\r\n" . '});';
					
					$datumPrevious['x1l'] = $x2l;
					$datumPrevious['x1r'] = $x2r;
					$datumPrevious['y1l'] = $y2l;
					$datumPrevious['y1r'] = $y2r;
				}
			}
			unset($datumPrevious);	
		}

		// PREDNISONE
		if ($displayFilters['prednisone']) {
			foreach ($dataArray as $dataPoint => $data) {
//				if ($data['prednisone'] > 0) {
	
					if (!$datumPrevious) {
						$x1 = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
						$y1 = $originY + ($data['prednisone'] * $dataSpacingYp);					
					} else {
						$x1 = $datumPrevious['x1'];
						$y1 = $datumPrevious['y1'];
					}
					
					// X2
					// SECOND OF THE YEAR
					// TIMES THE X DATA SPACING
					$x2 = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
					
					// Y2
					// PREDNISONE
					$y2 = $originY + ($data['prednisone'] * $dataSpacingYp);
					
					$response .= "\r\n\r\n" . '// PREDNISONE ' . date("Y-m-d H:i",$data['time']);
					// FIRST, THE POINT
					$response .= "\r\n" . "$('canvas#earChart').drawPolygon({";
						$response .= "\r\n" . "fillStyle: '#0000FF',";
						$response .= "\r\n" . 'x: ' . $x2 . ', y: ' . $y2 . ',';
						$response .= "\r\n" . 'radius: ' . $dataPointRadius . ',';
						$response .= "\r\n" . "sides: 6,";
					$response .= "\r\n" . "});";

					// THE LINE TO THAT POINT				
					$response .= "\r\n" . "$('canvas#earChart').drawLine({";
						$response .= "\r\n" . "strokeStyle: '#0000FF',";
						$repsonse .= "\r\n" . 'strokeWidth: 1,';
						$response .= "\r\n" . 'x1: ' . $x1 . ', y1: ' . $y1 . ',';
						$response .= "\r\n" . 'x2: ' . $x2 . ', y2: ' . $y2 . ',';
					$response .= "\r\n" . '});';
					
					$datumPrevious['x1'] = $x2;
					$datumPrevious['y1'] = $y2;
//				}
			}
			unset($datumPrevious);
		}

		// STATUS
		if ($displayFilters['status']) {
			foreach ($dataArray as $dataPoint => $data) {
				if (!$datumPrevious) {
					$x1 = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
					$y1 = $originY + ($data['status'] * $dataSpacingY);				
				} else {
					$x1 = $datumPrevious['x1'];
					$y1 = $datumPrevious['y1'];
				}
				
				// X2
				// SECOND OF THE YEAR
				// TIMES THE X DATA SPACING
				$x2 = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
				
				// Y2
				// STATUS
				$y2 = $originY + ($data['status'] * $dataSpacingY);
					
				$response .= "\r\n\r\n" . '// STATUS ' . date("Y-m-d H:i",$data['time']);
				$response .= "\r\n" . '// STATUS ' . $data['time'];
				// FIRST, THE POINT
				$response .= "\r\n" . "$('canvas#earChart').drawPolygon({";
					$response .= "\r\n" . "fillStyle: '#990000',";
					$response .= "\r\n" . 'x: ' . $x2 . ', y: ' . $y2 . ',';
					$response .= "\r\n" . 'radius: ' . $dataPointRadius . ',';
					$response .= "\r\n" . "sides: 5,";
				$response .= "\r\n" . "});";
				// THE LINE TO THAT POINT	
				$response .= "\r\n" . "$('canvas#earChart').drawLine({";
					$response .= "\r\n" . "strokeStyle: '#990000',";
					$repsonse .= "\r\n" . 'strokeWidth: 1,';
					$response .= "\r\n" . 'x1: ' . $x1 . ', y1: ' . $y1 . ',';
					$response .= "\r\n" . 'x2: ' . $x2 . ', y2: ' . $y2 . ',';
				$response .= "\r\n" . '});';
				
				$datumPrevious['x1'] = $x2;
				$datumPrevious['y1'] = $y2;
			}
			unset($datumPrevious);
		}
		
		// XANAX?
		if ($displayFilters['xanax']) {
			foreach ($dataArray as $dataPoint => $data) {
				if ($data['xanax'] > 0) {

					$x = $originX + (($data['time'] - $firstSecond + 86400) * $dataSpacingX);
					$y = $endY - $largeTickLength;
					
					$response .= "\r\n\r\n" . '// XANAX? ' . date("Y-m-d H:i",$data['time']);
					// JUST A POINT ABOVE THE CHART
					$response .= "\r\n" . "$('canvas#earChart').drawPolygon({";
						$response .= "\r\n" . 'strokeStyle: "#660066",';
						$response .= "\r\n" . 'fillStyle: "#660066",';
						$response .= "\r\n" . 'x: ' . $x . ', y: ' . $y . ',';
						$response .= "\r\n" . 'sides: 5,';
						$response .= "\r\n" . 'concavity: 0.5,';
						$response .= "\r\n" . 'radius: ' . $dataPointRadiusLarge;
					$response .= "\r\n" . '});';
				}
			}
			unset($datumPrevious);
		}
		
// DRAW THE LEGEND
		$response .= "\r\n\r\n" . '// I AM LEGEND';
		$response .= "\r\n" . "$('canvas#earChart').drawLine({";
			$response .= "\r\n" . "strokeStyle: '#000000',";
			$repsonse .= "\r\n" . 'strokeWidth: 1,';
			$response .= "\r\n" . 'x1: ' . $canvasWidth . ', y1: ' . $marginTop . ',';
			$response .= "\r\n" . 'x2: ' . $canvasWidth . ', y2: ' . ($marginTop + $legendHeight) . ',';
			$response .= "\r\n" . 'x3: ' . ($canvasWidth - $legendWidth) . ', y3: ' . ($marginTop + $legendHeight) . ',';
			$response .= "\r\n" . 'x4: ' . ($canvasWidth - $legendWidth) . ', y4: ' . $marginTop . ',';
			$response .= "\r\n" . 'closed: true,';
		$response .= "\r\n" . '});';
		
		$legendArray = array (
						"Status"									=>	"#990000",
						"(5: Perfect Clarity,"						=>	"#990000",
						"0: Fully Blocked)"							=>	"#990000",
						"Prednisone"								=>	"#0000FF",
						"(Dosage in mg)"							=>	"#0000FF",
						"Left Ear Score"							=>	"#009900",
						"Right Ear Score"							=>	"#CC00CC",
						"(From iPhone App)"							=>	"#000000",
						"Xanax Taken"								=>	"#660066",
						"Dizzyness"									=>	"#FF6600",
		);
		
		$slotHeight = ($legendHeight / sizeof($legendArray));
		$slotHeightHalf = ($slotHeight / 2);
		
		foreach ($legendArray as $label => $color) {
			
			$slot++;
			
			$response .= "\r\n" . "$('canvas#earChart').drawText({";
				$response .= "\r\n" . "fillStyle: '" . $color . "',";
				$response .= "\r\n" . "strokeStyle: '#000000',";
				$response .= "\r\n" . 'strokeWidth: 0,';
				$response .= "\r\n" . 'x: ' . (($canvasWidth - $legendWidth) + ($legendWidth / 2)) . ', y: ' . ($marginTop + ($slot * $slotHeight) - $slotHeightHalf) . ',';
				$response .= "\r\n" . 'fontSize: 16,';
				$response .= "\r\n" . "fontFamily: 'Verdana, sans-serif',";
				$response .= "\r\n" . "text: '" . $label . "'";
			$response .= "\r\n" . '});';
			
		};

	$response .= "\r\n" . '</script>';
			
	return $response;
	
}

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="/tools/style.css" />

<title>George's Downward Spiral Into Hearing Loss</title>

<script src="/tools/jscript/jquery.js" type="text/javascript"></script>
<script src="/tools/jscript/jquery.canvas.js" type="text/javascript"></script>

</head>

<!--<body onload="run()">-->
<body>

<h1 style="font-size: 18px;">George's Downward Spiral Into Hearing Loss</h1>

<div class="graph">

<?php
    // GET WEEK RANGES
    // GET UNIQUE DAYS IN THE DATABASE, THEN FIGURE WHICH ARE SUNDAYS, AND SET WEEK RANGES
    
    $query_getDays = "SELECT YEAR(FROM_UNIXTIME(datetime)) as year, MONTH(FROM_UNIXTIME(datetime)) AS month, DAYOFMONTH(FROM_UNIXTIME(datetime)) AS day, DAYOFWEEK(FROM_UNIXTIME(datetime)) AS dayOfWeek, datetime FROM updates ORDER BY year ASC, month ASC, day ASC";
    $result_getDays = mysqli_query($dbc,$query_getDays) or die ("Could not get days: " . mysqli_error($dbc));
    if (mysqli_affected_rows($dbc)) {
        while ($row_getDays = mysqli_fetch_array($result_getDays)) {
            
            // START FROM THE BOTTOM - IF THE FIRST DATE IS NOT A SUNDAY, FIGURE OUT WHEN THE
            // PRECEDING SUNDAY HAPPENS TO BE, FIGURE OUT THE DATE RANGE FOR THE WEEK, AND 
            // PUT THAT WEEK RANGE INTO THE ARRAY.  THEN, UNSET THE WEEK RANGE AND CONTINUE UP
            // THE LINE
            
            if ($row_getDays['dayOfWeek'] == 1) { // SUNDAY - YAY
                $weekStart = mktime(0,0,0,$row_getDays['month'],$row_getDays['day'],$row_getDays['year']);
                $weekRanges[$weekStart]++;
            } else {
                $offset = $row_getDays['dayOfWeek'] - 1;
                $weekStart = mktime(0,0,0,$row_getDays['month'],$row_getDays['day']-$offset,$row_getDays['year']);
                $weekRanges[$weekStart]++;
            }
        }
        
        // REVERSE THE ARRAY
        if ($weekRanges) {
            $weekRanges = array_reverse($weekRanges,true);
        }
        
        // DISPLAY IT
          
        echo displayGraph();

	}
			
?>
        
</div>
<!--
<form action="/" method="post">
	<p>Status: <input type="text" name="status" /></p>
	<p>Note: <input type="text" name="notes" /></p>
	<p>Prednisone: <input type="text" name="prednisone" /></p>
	<p>Left Score: <input type="text" name="leftScore" /></p>
	<p>Right Score: <input type="text" name="rightScore" /></p>
	<p>Xanax: <input type="checkbox" name="xanax" value="1" /></p>
	<p>Dizzy: <input type="checkbox" name="dizzy" value="1" /></p>
	<p>
  		<input type="hidden" name="action" value="submittedUpdate" />
        <input type="submit" value="Submit Update" />
	</p>
</form>
-->

<div class="controls">
    <form action="/" method="get">
        <p class="bold">Display:</p>
        <p><label for="status">Status</label> <input type="checkbox" id="status" name="status" value="on" <?php if (($_GET['status']) || (!$_GET)) { echo 'checked="checked"'; }?> /></p>
        <p><label for="prednisone">Prednisone</label> <input type="checkbox" id="prednisone" name="prednisone" value="on" <?php if (($_GET['prednisone']) || (!$_GET)) { echo 'checked="checked"'; }?> /></p>
        <p><label for="scores">Test Scores</label> <input type="checkbox" id="scores" name="scores" value="on" <?php if (($_GET['scores']) || (!$_GET)) { echo 'checked="checked"'; }?> /></p>
        <p><label for="xanax">Xanax</label> <input type="checkbox" id="xanax" name="xanax" value="on" <?php if (($_GET['xanax']) || (!$_GET)) { echo 'checked="checked"'; }?> /></p>
        <p><label for="dizzy">Dizzyness</label> <input type="checkbox" id="dizzy" name="dizzy" value="on" <?php if (($_GET['dizzy']) || (!$_GET)) { echo 'checked="checked"'; }?> /></p>
        <p><input type="submit" value="Apply Filters" /></p>
    </form>
</div>

<?php
krsort($dataArray);
?>

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
    </tr>
<?php
	foreach ($dataArray as $dataPoint => $data) {
?>
		<tr>
        	<td class="center"><?=substr($dataPoint,0,4) . '-' . substr($dataPoint,4,2) . '-' . substr($dataPoint,6,2)?></td>
        	<td class="center"><?=substr($dataPoint,8,2) . ':' . substr($dataPoint,10,2) . ':' . substr($dataPoint,12,2)?></td>
            <td class="center"><?=$data['status']?></td>
            <td class="note"><?=$data['note']?></td>
            <td class="center"><?php if ($data['prednisone'] > 0) { echo $data['prednisone']; }?></td>
            <td class="center"><?php if ($data['leftScore'] > 0) { echo $data['leftScore']; }?></td>
            <td class="center"><?php if ($data['rightScore'] > 0) { echo $data['rightScore']; }?></td>
            <td class="center"><?php if ($data['xanax'] > 0) { echo "X"; }?></td>
            <td class="center"><?php if ($data['dizzy'] > 0) { echo "X"; }?></td>
        </tr>
<?php
	}
?>
</table>

</body>

</html>