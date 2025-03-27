<?php

try {
    $conn_dsn = 'mysql:dbname=dexp_gwmdahruj_12-2024;host=34.151.226.187';
    $conn = new \PDO($conn_dsn, 'castroitalo', '74789905');

    return $conn;
} catch (\PDOException $ex) {
    error_log($ex->getMessage());

    return null;
}
