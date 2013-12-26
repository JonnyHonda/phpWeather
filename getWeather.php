<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once 'fetchWeatherData.php';

$weatherObject = new WeatherData();

$data = $_GET['data'];
//$data = file_get_contents("newjson.json");
$weatherObject->fetchData($data,"json");
//var_dump($weatherObject);
//$weatherObject->fetchDataAsJson(array("Date","Temp","FeelsLike","Humidity",1,1));