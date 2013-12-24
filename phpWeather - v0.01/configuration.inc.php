<?php
define ('APPLICATION_ENV','production');

if (APPLICATION_ENV == "development"){
    $dsn = 'mysql:dbname=weather;host=sajb.co.uk';
    $user = 'weatheruser';
    $password = 'dragon32';
}else{
    $dsn = 'mysql:dbname=weather;host=127.0.0.1';
    $user = 'weatheruser';
    $password = 'dragon32';
}
?>

