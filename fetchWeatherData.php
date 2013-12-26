<?php

require('sanitize.inc.php');
include_once 'weatherInterface.php';
define('APPLICATION_ENV', 'development');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of fetchWeatherData
 *
 * @author john
 */
class WeatherData implements weatherInterface {

    public $allowedFields = array(
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
    public $validCommands;
    public $defaultSort;
    public $defaultDirection;
    public $defaultLimit;
    private $dbh;
    private $config;
    private $fieldList;
    private $direction;
    private $interval;
    private $limit = 1000;
    private $tableData;

    /**
     * 
     * @param type $vc
     * @param type $ds
     * @param type $dd
     * @param type $dl
     */
    public function __construct($vc = array("Fields", "Sort", "Interval", "Limit"), $ds = "Id", $dd = "DESC", $dl = 10) {
        $this->validCommands = $vc;
        $this->defaultSort = $ds;
        $this->defaultDirection = $dd;
        $this->defaultLimit = $dl;
        $this->getConfig();
        $this->getConnection();
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 
     * @param String $data 
     * @param type $returnType
     */
    public function fetchData($data, $returnType = "json") {
        if ($this->isJson($data)) {
            $dataArray = json_decode($data, true);
            $this->fieldList = explode(",", $dataArray['Fields']);
            $this->direction = $dataArray['Sort'];
            $this->interval = $dataArray['Interval'];
            $this->limit = $dataArray['Limit'];
        } else {
            // assume that the format is the name value pairs
            $decodedStr = urldecode($data);
            $cleanData = sanitize($decodedStr, HTML + SQL);
            $commands = explode('|', $cleanData);

            // Create and clean the requested fields
            $fields = explode(':', $commands[0]);
            $this->fieldList = explode(',', $fields[1]);

            // Create and Clean the interval
            list($junk, $interval) = explode(':', $commands[2]);
            // Make Sure interval is an integer
            $this->interval = sanitize($interval, INT);

            // Create and Clean the Limit
            if (isset($commands[3])) {
                list($limitCommand, $limit) = explode(':', $commands[3]);
                // Make Sure limit is an integer
                $this->limit = sanitize($limit, INT);
            } else {
               $this->limit = $this->defaultLimit;
            }
        }
        switch ($returnType) {
            case "json" : $this->fetchDataAsJson();
                break;
        }
    }

    /**
     * 
     * @param type $fieldList
     * @param type $direction
     * @param type $interval
     * @param type $limit
     */
    private function fetchDataAsCSV($fieldList, $direction = "DESC", $interval = 1, $limit) {
        $this->fieldList = $fieldList;
        $this->direction = $direction;
        $this->interval = $interval;
        $this->limit = $limit;
        $this->prepareSQL();
// TODO : implement to csv
    }

    private function fetchDataAsJson() {
        $this->prepareSQL();
        $jsonData = json_encode($this->tableData, JSON_NUMERIC_CHECK);
        print $jsonData;
    }

    private function prepareSQL() {
        foreach ($this->fieldList as $requestedField) {
            $requestedField = trim($requestedField);
            if (array_key_exists($requestedField, $this->allowedFields)) {
                $sqlFields[] = "{$this->allowedFields[$requestedField]} AS $requestedField";
//print $requestedField;
            }
        }

        $fieldSQL = implode(",", $sqlFields);

        if ($this->direction == "Ascending") {
            $this->direction = "ASC";
        } else {
            $this->direction = "DESC";
        }

        $limitSQL = " Limit $this->limit";

        $sql = "SELECT $fieldSQL
            FROM raw 
            WHERE dateutc >= DATE_ADD(CURDATE(), INTERVAL -:interval DAY) 
            order by dateutc DESC $limitSQL";

        try {
            $sth = $this->dbh->prepare($sql);
        } catch (PDOException $e) {
            echo 'Prepare failed: ' . $e->getMessage();
            die("Died in Prepare");
        }

        try {
            $sth->execute(array(
                ":interval" => $this->interval,
                    //":sortOn" => $sortOn,
//":direction" => $direction
            ));
        } catch (PDOException $e) {
            echo 'Execution failed: ' . $e->getMessage();
            die("Died in execute");
        }
        $this->tableData = $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConfig() {
        $this->config = parse_ini_file("configuration.php", true);
// print_r($this->config);
    }

    private function getConnection() {
        $dsn = $this->config[APPLICATION_ENV]['dsn'];
        $user = $this->config[APPLICATION_ENV]['user'];
        $password = $this->config[APPLICATION_ENV]['password'];
        try {
            $this->dbh = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
            die("Died in Connection");
        }
    }

}
