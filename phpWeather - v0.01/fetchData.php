<?php
/**
 * fetchData.php
 * Fetch data from the raw table and returns a JSON object.
 * This script accepts a single GET parameter data
 * This data parameter is formatted as follows
 * {Fields:Date,Temp,Pressure|Sort:Date,Descending|Interval:0}
 * The data packet is encapsulated in {}
 * The commands are separated by the pipe |
 * the commands are delimted from the data by a colon :
 * the multiple data for each command is delimted by a comma ,
 * eg {Fields:Date,Temp,Pressure|Sort:Date,Descending|Interval:0}
 * becomes 
 *  Fields
 *      ->Date
 *      ->Temp
 *      ->Pressure
 * Sort
 *      ->Date
 *      ->Descending
 * Interval
 *      ->0
 */
require('sanitize.inc.php');
include("configuration.inc.php");
//PARANOID, SQL, SYSTEM, HTML, INT, FLOAT, LDAP, UTF8 
//echo sanitize($Test, $Flags); 
//error_log("==== START ====/n");
//var_dump($_GET);
$allowedFields = array(
    'Id' => 'Id',
    'Date' => 'dateutc',
    'Temp' => 'temp', 
    'WindSpeed' => 'windspeedmph',
    'Pressure' => 'barom',
    'Rain' => 'rain',
    'Humidity' => 'humidity',
    'WindDirection' => 'winddir',
    'WindGust' => 'windgustmph',
    'FeelsLike' => 'dewpt',
    'DailyRain' => 'dailyrain');
$defaultSort = $allowedFields['Id'];
$defaultDirection = "DESC";
$validCommands = array("Fields","Sort","Interval","Limit");
$defaultLimit = 10;
$junk = "";

if (isset($_GET['data']) || (isset($argc) && $argc > 0)){
    if(isset($argc) > 0){
        if ($argv[1] == "help"){
             usage();exit;
	}else{
            $cleanData = str_replace('"', '', $argv[1]);
        }
    }else{
	$decodedStr = urldecode($_GET['data']);
        $cleanData = sanitize($decodedStr, HTML+SQL);
    }
    $commands = explode('|', $cleanData);
    
    
    // Create and clean the requested fields
    $fields = explode(':', $commands[0]);
    $fieldList = explode(',', $fields[1]);
    
    // Create an Clean the Sort parameters
    $sort = explode(':', $commands[1]);
    list($sortOn, $direction) = explode(',', $sort[1]);
    // Only allow valid fields to be sorted on
    if(!array_key_exists($sortOn, $allowedFields)) $sortOn = $defaultSort;
    
    $sortOn = $allowedFields[$sortOn]; 
    
    if ($direction == "Ascending"){
        $direction = "ASC";
    }else{
        $direction = "DESC";
    }
    
    // Create and Clean the interval
    list($junk, $interval) = explode(':', $commands[2]);
    // Make Sure interval is an integer
    $interval = sanitize($interval, INT);

    // Create and Clean the Limit
    if (isset($commands[3])){
        list($limitCommand, $limit) = explode(':', $commands[3]);
        // Make Sure limit is an integer
        $limit = sanitize($limit, INT);
        $limitSQL = " Limit $limit";
        
    }else{
        $limitSQL = " LIMIT 1000";
    }
    //$limit = $defaultLimit;
    
    // Build the SQL
    foreach ($fieldList as $requestedField){
        if (array_key_exists($requestedField, $allowedFields)){
           $sqlFields[] =  "{$allowedFields[$requestedField]} AS $requestedField";  
            //print $requestedField;
        }
    }
};

$fieldSQL = implode(",",$sqlFields); 

try {
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die("Died in Connection");
}

// Seems pointless having cleaned all the paramters onlt to get to this point
// and be fored to hard code a limit since PDO doesn't support LIMIT in params
$sql = "SELECT $fieldSQL
            FROM raw 
            WHERE dateutc >= DATE_ADD(CURDATE(), INTERVAL -:interval DAY) 
            order by dateutc DESC $limitSQL";
//print $sql;
try {
    $sth = $dbh->prepare($sql);
} catch (PDOException $e) {
    echo 'Prepare failed: ' . $e->getMessage();
    die("Died in Prepare");
}
//print $sql;
try {
    $sth->execute(array(
        ":interval" => $interval,
        //":sortOn" => $sortOn,
        //":direction" => $direction
        ));
    
} catch (PDOException $e) {
    echo 'Execution failed: ' . $e->getMessage();
    die("Died in execute");
}

$tableData = $sth->fetchAll(PDO::FETCH_ASSOC);
$jsonData = json_encode($tableData, JSON_NUMERIC_CHECK);

print $jsonData;
/*
[
  {
      "Date": "04/27/2013 15:14",
      "Time": " 15:14 BST ",
      "TempOut": 11.2,
      "FeelsLike": 8.8,
      "HumidityOut": 53,
      "WindDirection": " NE ",
      "WindAvg": 1,
      "WindGust": 2,
      "Rain": 0.0,
      "AbsPressure": 1010.0  },
 ]
*/

function usage(){
   echo "
	usage : 
	as a command 
	fetchData.php \"{Fields:Date,Temp,FeelsLike,Pressure|Sort:Date,Ascending|Interval:6}\" 
	or as a http call
	http://weather.sajb.co.uk/fetchData.php?data={Fields:Date,Temp,FeelsLike,Pressure|Sort:Date,Ascending|Interval:6}

	";
} 
