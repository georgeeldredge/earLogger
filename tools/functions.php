<?php

$functionDir = opendir("tools/functions");
while ($file = readdir($functionDir)) {
	if (strstr($file,"function_")) {
		require("tools/functions/" . $file);
		$approvedFunctions[] = substr(substr($file,9),0,-4);
	}
}

?>