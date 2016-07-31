<?php

$classDir = opendir("tools/classes");
while ($file = readdir($classDir)) {
	if (strstr($file,"class_")) {
		include("tools/classes/" . $file);
	}
}

?>