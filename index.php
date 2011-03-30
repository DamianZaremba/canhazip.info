<?php
if(isset($_SERVER) && array_key_exists("REMOTE_ADDR", $_SERVER) && !empty($_SERVER['REMOTE_ADDR'])) {
	$ip = ($_SERVER["REMOTE_ADDR"]);
} else {
	$ip = ("127.0.0.1");
}

if(isset($_GET) && array_key_exists("format", $_GET) && !empty($_GET['format'])) {
	$requested_format = strtolower(trim(trim($_GET['format']), "/"));
} else {
	$requested_format = "plain";
}

switch($requested_format) {
	case "xml":
		header('Content-type: text/xml');
		print "<output>\n";
		print "\t<ip>" . $ip . "</ip>\n";
		print "</output>\n";
	break;

	case "json":
		header('Content-type: text/json');
		print json_encode(array("IP" => $ip));
	break;

	case "yaml":
		header('Content-type: text/yaml');
		print "IP: " . $ip . "\n";
	break;

	case "php":
		header('Content-type: text/plain');
		print serialize(array("IP" => $ip));
	break;

	case "plain":
	default:
		header('Content-type: text/plain');
		print $ip . "\n";
	break;
}
?>
