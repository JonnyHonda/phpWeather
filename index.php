<?php
require('sanitize.inc.php');
$page = sanitize($_GET['page'], PARANOID);

switch ($page){
    case "About" : 
		$page = file_get_contents("mobile/Credits-Screen.html"); 
		break;
    case "LiveData" : $page = file_get_contents("mobile/LineChart.html"); break;
    default:
        
	include ("weatherClass.php");
        $json = shell_exec("php fetchData.php \"{Fields:Date,Temp,FeelsLike,Humidity,WindDirection,WindSpeed,WindGust,Pressure,Rain,DailyRain|Sort:Date,Descending|Interval:0|Limit:1}\"");
	$data = json_decode($json,true);
	// [Date] => 2013-10-11 17:10:01 [Temp] => 11.1111 [FeelsLike] => 10 [Humidity] => 93 [WindDirection] => 22 [WindSpeed] => 3.13 [WindGust] => 6.04 [Pressure] => 1014.8 [Rain] => 6.29999 [DailyRain] => 6.29999 
	$toReplace = array("[--Date--]","[--Temp--]","[--FeelsLike--]",
			"[--Humidity--]","[--WindDirection--]","[--WindGust--]",
			"[--Pressure--]","[--Rain--]","[--DailyRain--]");
//print_r(array_values($data[0]));
//	die();
//ini_set('display_errors', 'On');
//error_reporting(E_ALL);
	$weather = new weatherClass();
        $x = $weather->fetchForecast();
	$page = file_get_contents("mobile/Home.html");
	$page = str_replace($toReplace, array_values($data[0]),$page);
        $page = str_replace("[--DESCRIPTION--]",$x[0],$page);
        $page = str_replace("[--IMG--]",$x[1],$page);
}
echo($page);
exit;

