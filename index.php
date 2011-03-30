<?php
@include_once("maxmind/geoipcity.inc.php");
@include_once("maxmind/geoipregionvars.php");

$maxmind = False;
if(function_exists("geoip_open")) {
	if(is_file("GeoLiteCity.dat")) {
		$gi = @geoip_open("GeoLiteCity.dat", GEOIP_STANDARD);
		if(isset($gi)) {
			$maxmind = True;
		}
	}
}

function valid_ip($ip_address) {
	if (preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $ip_address)) {
		return True;
	}

	if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $ip_address)) {
		return True;
	}

	return False;
}

if(isset($_GET) && array_key_exists("ip", $_GET) && !empty($_GET["ip"]) && valid_ip($_GET["ip"])) {
	$ip = $_GET["ip"];
} else {
	if(isset($_SERVER) && array_key_exists("REMOTE_ADDR", $_SERVER) && !empty($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER["REMOTE_ADDR"];
	} else {
		$ip = "127.0.0.1";
	}
}

$extended = False;
if(isset($_GET) && array_key_exists("output", $_GET) && !empty($_GET["output"]) && strtolower(trim($_GET["output"])) === "extended") {
	$extended = True;
}

$requested_format = null;
if (isset($_SERVER) && array_key_exists('PATH_INFO', $_SERVER) && !empty($_SERVER["PATH_INFO"])) {
	$requested_format = strtolower(trim(trim($_SERVER['PATH_INFO'], "/")));
} else if(isset($_SERVER) && array_key_exists('ORIG_PATH_INFO', $_SERVER) && !empty($_GET["ORIG_PATH_INFO"])) {
	$requested_format = strtolower(trim(trim($_SERVER['ORIG_PATH_INFO'], "/")));
} else if(isset($_SERVER) && array_key_exists("REQUEST_URI", $_SERVER) && !empty($_SERVER["REQUEST_URI"]) && strpos("/", $_SERVER["REQUEST_URI"]) !== False){
	$farg = strpos($_SERVER['REQUEST_URI'], '?');
	$requested_format = strtolower(trim(trim(substr($_SERVER['REQUEST_URI'], 0, $farg), "/")));
}

if(empty($requested_format)) {
	$requested_format = "plain";
}

if($extended === True && $maxmind === True) {
	$rd = geoip_record_by_addr($gi, $ip);
	if(!isset($rd)) {
		$maxmind = False;
	}
}

switch($requested_format) {
	case "xml":
		header('Content-type: text/xml');
		print "<output>\n";
		print "\t<ip>" . $ip . "</ip>\n";
		if($extended === True && $maxmind === True) {
			print "\t<country_code>" . $rd->country_code . "</country_code>\n";
			print "\t<country_name>" . $rd->country_name . "</country_name>\n";
			print "\t<region_name>" . $GEOIP_REGION_NAME[$rd->country_code][$rd->region] . "</region_name>\n";
			print "\t<city>" . $rd->city . "</city>\n";
			print "\t<postal_code>" . $rd->postal_code . "</postal_code>\n";
			print "\t<latitude>" . $rd->latitude . "</latitude>\n";
			print "\t<longitude>" . $rd->longitude . "</longitude>\n";
			print "\t<metro_code>" . $rd->metro_code . "</metro_code>\n";
			print "\t<area_code>" . $rd->area_code . "</area_code>\n";
		}
		print "</output>\n";
	break;

	case "json":
		header('Content-type: text/json');
		$data = array();
		$data["ip"] = $ip;
		if($extended === True && $maxmind === True) {
			$data["country_code"] = $rd->country_code;
			$data["country_name"] = $rd->country_name;
			$data["region_name"] = $GEOIP_REGION_NAME[$rd->country_code][$rd->region];
			$data["city"] = $rd->city;
			$data["postal_code"] = $rd->postal_code;
			$data["latitude"] = $rd->latitude;
			$data["longitude"] = $rd->longitude;
			$data["metro_code"] = $rd->metro_code;
			$data["area_code"] = $rd->area_code;
		}
		print json_encode($data);
	break;

	case "yaml":
		header('Content-type: text/yaml');
		print "IP: " . $ip . "\n";
		if($extended === True && $maxmind === True) {
			print "country_code: " . $rd->country_code . "\n";
			print "country_name: " . $rd->country_name . "\n";
			print "region_name: " . $GEOIP_REGION_NAME[$rd->country_code][$rd->region] . "\n";
			print "city: " . $rd->city . "\n";
			print "postal_code: " . $rd->postal_code . "\n";
			print "latitude: " . $rd->latitude . "\n";
			print "longitude: " . $rd->longitude . "\n";
			print "metro_code: " . $rd->metro_code . "\n";
			print "area_code: " . $rd->area_code . "\n";
		}
	break;

	case "php":
		header('Content-type: text/plain');
		$data = array();
		$data["IP"] = $ip;
		if($extended === True && $maxmind === True) {
			$data["country_code"] = $rd->country_code;
			$data["country_name"] = $rd->country_name;
			$data["region_name"] = $GEOIP_REGION_NAME[$rd->country_code][$rd->region];
			$data["city"] = $rd->city;
			$data["postal_code"] = $rd->postal_code;
			$data["latitude"] = $rd->latitude;
			$data["longitude"] = $rd->longitude;
			$data["metro_code"] = $rd->metro_code;
			$data["area_code"] = $rd->area_code;
		}
		print serialize($data);
	break;

	case "plain":
	default:
		header('Content-type: text/plain');
		print $ip . "\n";
	break;
}
@geoip_close($gi);
?>
