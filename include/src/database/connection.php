<?php

declare(strict_types=1);

/**
 * Creates a database connection
 *
 * @param string $db_host Database host
 * @param string $db_name Database name
 * @param string $db_user Database user
 * @param string $db_pass Database password
 * @return null|PDO
 */
function connection(string $db_host, string $db_name, string $db_user, string $db_pass): ?\PDO
{
    try {
        $conn_dsn = 'mysql:dbname=' . $db_name . ';host=' . $db_host;
        $conn = new \PDO($conn_dsn, $db_user, $db_pass);

        return $conn;
    } catch (\PDOException $ex) {
        error_log($ex->getMessage());

        return null;
    }
}
