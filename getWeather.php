<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once 'fetchWeatherData.php';

$weatherObject = new WeatherData();
if (isset($_GET['data'])){
    $data = $_GET['data'];
    $weatherObject->fetchData($data,"json");

}
