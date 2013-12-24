<?php
/**
 * Description of weatherClass
 *
 * @author John
 */
define("DEBUG",FALSE);
class weatherClass {
    public $z_hpa = 0;
    public $z_month = 0;
    public $z_wind = 0;
    public $z_trend = 0;
    public $z_where = 1;
    public $baro_top = 1050;
    public $baro_bottom = 950;
    public $tableData = array();
    

    /**
     * @param int $hpa reletive air pressure
     * @param int $month current month, used to determin season
     * @param String $wind Wind Rose angle (NNE etc)
     * @param int $trend Barom trend, 0,1,2 nochane, rise or fall
     */
    function __construct() {
        date_default_timezone_set("Europe/London");
        $this->populateZembretti();
        //var_dump($this->tableData);
        $this->z_hpa = $this->tableData[0]["barom"];
        $this->setMonth($this->tableData[0]["dateutc"]);
        $this->setWind($this->tableData[0]["winddir"]);
        $this->setTrend($this->tableData[0]["barom"], $this->tableData[1]["barom"]);
    }

    public function setHpa($v) {
        $this->z_hpa = $v;
    }

    public function getHpa() {
        return $this->z_hpa;
    }

    /**
     * 
     * @param String $date utc date string 
     */
    public function setMonth($date) {
        $time = strtotime($date);
        $this->z_month = date("m", $time);
        if (DEBUG) echo "Setting month:" . $this->getMonth(). " using date string $date<br />";          
    }

    public function getMonth() {
        return $this->z_month;
    }

    /**
     * 
     * @param Integer $dir Direction of wind in degrees
     * 
     */
    public function setWind($dir) {      
        $arr = array("N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW");
        $this->z_wind = $arr[$dir % 16];
        if (DEBUG) echo "Setting wind:" . $this->getWind() . "<br />";
    }

    public function getWind() {
        return $this->z_wind;
    }

    /**
     * 
     * @param type $p1
     * @param type $p2
     */
    public function setTrend($p1, $p2) {
        /* @var $trend type */
        $trend = $p1 - $p2;
        if ($trend < 0) {
            $this->z_trend = 2;
        } else if ($trend > 0) {
            $this->z_trend = 1;
        } else {
            $this->z_trend = 0;
        }
        
        if (DEBUG) echo "Setting trend:" . $this->z_trend . "<br />";
    }

    public function getTrend() {
        return $this->z_trend;
    }

    public function setWhere($v) {
        $this->z_where = $v;
        if (DEBUG) echo "Setting z_where:" . $this->z_where . "<br />";
    }

    public function getWhere() {
        return $this->z_where;
    }

    public function setBaroTop($v) {
        $this->baro_top = $v;
    }

    public function getBaroTop() {
        return $this->baro_top;
    }

    public function setBaroBottom($v) {
        $this->baro_bottom = $v;
    }

    public function getBaroBottom() {
        return $this->baro_bottom;
    }

    public function populateZembretti() {
        include ('configuration.inc.php');
        try {
            $dbh = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
            die("Died in Connection");
        }

        $sql = "SELECT * FROM raw ORDER BY dateutc DESC LIMIT 2";
        try {
            $sth = $dbh->prepare($sql);
        } catch (PDOException $e) {
            echo 'Prepare failed: ' . $e->getMessage();
            die("Died in Prepare");
        }
        //print $sql;
        try {
            $sth->execute();
        } catch (PDOException $e) {
            echo 'Execution failed: ' . $e->getMessage();
            die("Died in execute");
        }
        $this->tableData = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (DEBUG){
            print "<pre>";
            print_r($this->tableData);
            print "</pre>";
        }
    }

    public function fetchForecast() {
        // ---- 'environment' variables ------------
        //$z_where  Northern = 1 or Southern = 2 hemisphere
        //$baro_top  upper limits of your local 'weather window' (1050.0 hPa for UK)
        //$baro_bottom	 lower limits of your local 'weather window' (950.0 hPa for UK)
        // usage:   forecast = betel_cast( $z_hpa, $z_month, $z_wind, $z_trend [, $z_where] [, $this.baro_top] [, $this.baro_bottom]);
        // $z_hpa is Sea Level Adjusted (Relative) barometer in hPa or mB
        // $z_month is current month as a number between 1 to 12
        // $z_wind is English windrose cardinal eg. N, NNW, NW etc.
        // NB. if calm a 'nonsense' value should be sent as $z_wind (direction) eg. 1 or calm !
        // $z_trend is barometer trend: 0 = no change, 1= rise, 2 = fall
        // $z_where - OPTIONAL for posting with form
        // $this.baro_top - OPTIONAL for posting with form
        // $this.baro_bottom - OPTIONAL for posting with form
        // a short forecast text is returned

        $baro_top= $this->getBaroTop(); 
        $baro_bottom= $this->getBaroBottom();
        $z_month= $this->getMonth();
        $z_where= $this->getWhere();
        $z_wind= $this->getWind();
        $z_hpa= $this->getHpa();  
        $z_trend = $this->getTrend();
        
        if (DEBUG){
            print "Baro Top: " . $baro_top . "<br />";
            print "Baro Bottom: " . $baro_bottom . "<br />";
            print "Month: " . $z_month . "<br />";
            print "Where: " . $z_where . "<br />";
            print "Wind Dir: " . $z_wind. "<br />";
            print "hPa: " . $z_hpa . "<br />";
             print "Trend: " . $z_trend . "<br />";
        }
        
        $z_forecast = Array("Settled fine", "Fine weather", "Becoming fine", "Fine, becoming less settled", "Fine, possible showers", "Fairly fine, improving", "Fairly fine, possible showers early", "Fairly fine, showery later", "Showery early, improving", "Changeable, mending", "Fairly fine, showers likely", "Rather unsettled clearing later", "Unsettled, probably improving", "Showery, bright intervals", "Showery, becoming less settled", "Changeable, some rain", "Unsettled, short fine intervals", "Unsettled, rain later", "Unsettled, some rain", "Mostly very unsettled", "Occasional rain, worsening", "Rain at times, very unsettled", "Rain at frequent intervals", "Rain, very unsettled", "Stormy, may improve", "Stormy, much rain");

        // equivalents of Zambretti 'dial window' letters A - Z
        $rise_options = Array(25, 25, 25, 24, 24, 19, 16, 12, 11, 9, 8, 6, 5, 2, 1, 1, 0, 0, 0, 0, 0, 0);
        $steady_options = Array(25, 25, 25, 25, 25, 25, 23, 23, 22, 18, 15, 13, 10, 4, 1, 1, 0, 0, 0, 0, 0, 0);
        $fall_options = Array(25, 25, 25, 25, 25, 25, 25, 25, 23, 23, 21, 20, 17, 14, 7, 3, 1, 1, 1, 0, 0, 0);



        $z_range = $baro_top - $baro_bottom;
        $z_constant = round(($z_range / 22), 3);

        $z_season = (($z_month >= 4) && ($z_month <= 9));  // true if 'Summer'

        if ($z_where == 1) {    // North hemisphere
            if ($z_wind == "N") {
                $z_hpa += 6 / 100 * $z_range;
            } else if ($z_wind == "NNE") {
                $z_hpa += 5 / 100 * $z_range;
            } else if ($z_wind == "NE") {
//			$z_hpa += 4 ;  
                $z_hpa += 5 / 100 * $z_range;
            } else if ($z_wind == "ENE") {
                $z_hpa += 2 / 100 * $z_range;
            } else if ($z_wind == "E") {
                $z_hpa -= 0.5 / 100 * $z_range;
            } else if ($z_wind == "ESE") {
//			$z_hpa -= 3 ;  
                $z_hpa -= 2 / 100 * $z_range;
            } else if ($z_wind == "SE") {
                $z_hpa -= 5 / 100 * $z_range;
            } else if ($z_wind == "SSE") {
                $z_hpa -= 8.5 / 100 * $z_range;
            } else if ($z_wind == "S") {
//			$z_hpa -= 11 ;  
                $z_hpa -= 12 / 100 * $z_range;
            } else if ($z_wind == "SSW") {
                $z_hpa -= 10 / 100 * $z_range;  //
            } else if ($z_wind == "SW") {
                $z_hpa -= 6 / 100 * $z_range;
            } else if ($z_wind == "WSW") {
                $z_hpa -= 4.5 / 100 * $z_range;  //
            } else if ($z_wind == "W") {
                $z_hpa -= 3 / 100 * $z_range;
            } else if ($z_wind == "WNW") {
                $z_hpa -= 0.5 / 100 * $z_range;
            } else if ($z_wind == "NW") {
                $z_hpa += 1.5 / 100 * $z_range;
            } else if ($z_wind == "NNW") {
                $z_hpa += 3 / 100 * $z_range;
            }
            if ($z_season == TRUE) {   // if Summer
                if ($z_trend == 1) {   // rising
                    $z_hpa += 7 / 100 * $z_range;
                } else if ($z_trend == 2) {  //	falling
                    $z_hpa -= 7 / 100 * $z_range;
                }
            }
        } else {   // must be South hemisphere
            if ($z_wind == "S") {
                $z_hpa += 6 / 100 * $z_range;
            } else if ($z_wind == "SSW") {
                $z_hpa += 5 / 100 * $z_range;
            } else if ($z_wind == "SW") {
//			$z_hpa += 4 ;  
                $z_hpa += 5 / 100 * $z_range;
            } else if ($z_wind == "WSW") {
                $z_hpa += 2 / 100 * $z_range;
            } else if ($z_wind == "W") {
                $z_hpa -= 0.5 / 100 * $z_range;
            } else if ($z_wind == "WNW") {
//			$z_hpa -= 3 ;  
                $z_hpa -= 2 / 100 * $z_range;
            } else if ($z_wind == "NW") {
                $z_hpa -= 5 / 100 * $z_range;
            } else if ($z_wind == "NNW") {
                $z_hpa -= 8.5 / 100 * $z_range;
            } else if ($z_wind == "N") {
//			$z_hpa -= 11 ;  
                $z_hpa -= 12 / 100 * $z_range;
            } else if ($z_wind == "NNE") {
                $z_hpa -= 10 / 100 * $z_range;  //
            } else if ($z_wind == "NE") {
                $z_hpa -= 6 / 100 * $z_range;
            } else if ($z_wind == "ENE") {
                $z_hpa -= 4.5 / 100 * $z_range;  //
            } else if ($z_wind == "E") {
                $z_hpa -= 3 / 100 * $z_range;
            } else if ($z_wind == "ESE") {
                $z_hpa -= 0.5 / 100 * $z_range;
            } else if ($z_wind == "SE") {
                $z_hpa += 1.5 / 100 * $z_range;
            } else if ($z_wind == "SSE") {
                $z_hpa += 3 / 100 * $z_range;
            }
            if ($z_season == FALSE) {  // if Winter
                if ($z_trend == 1) {  // rising
                    $z_hpa += 7 / 100 * $z_range;
                } else if ($z_trend == 2) {  // falling
                    $z_hpa -= 7 / 100 * $z_range;
                }
            }
        }  // END North / South

        if ($z_hpa == $baro_top) {
            $z_hpa = $baro_top - 1;
        }
        $z_option = floor(($z_hpa - $baro_bottom) / $z_constant);
        $z_output = "";

        if ($z_option < 0) {
            $z_option = 0;
            $z_output = "Exceptional Weather, ";
        }
        if ($z_option > 21) {
            $z_option = 21;
            $z_output = "Exceptional Weather, ";
        }

        if ($z_trend == 1) {
            $z_output .= $z_forecast[$rise_options[$z_option]];
        } else if ($z_trend == 2) {
            $z_output .= $z_forecast[$fall_options[$z_option]];
        } else {
            $z_output .= $z_forecast[$steady_options[$z_option]];
        }
        return (array($z_output,$z_option));
        // END function   		
    }
 public function dump(){
            print "Baro Top: " . $this->getBaroTop() . "<br />";
            print "Baro Bottom: " . $this->getBaroBottom() . "<br />";
            print "Month: " . $this->getMonth() . "<br />";
            print "Where: " . $this->getWhere() . "<br />";
            print "Wind Dir: " . $this->getWind() . "<br />";
            print "hPa: " . $this->getHpa() . "<br />";
            print "Trend: " . $this->getTrend() . "<br />";
            print "<pre>";
            print_r ($this->tableData);
            print "</pre>";
 }
}