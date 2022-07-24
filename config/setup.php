<?php

echo "Setting Up Database.\n" . '</br>';

include "conn.php";

echo "Connected!\n" . '</br>';

// Setup database tables.
$query = "CREATE TABLE IF NOT EXISTS trees (
    id SERIAL PRIMARY KEY,
    name VARCHAR(1000)                      NOT NULL,
    description VARCHAR(1000)               NOT NULL,
    creator VARCHAR(1000)                   NOT NULL,
    breed VARCHAR(1000)                     NOT NULL,
    location VARCHAR(1000)                  NOT NULL,
    image_url VARCHAR(1000)                 NOT NULL,
    start_date VARCHAR(1000)                NOT NULL,
    log_timestamp TIMESTAMP                         
);";
$result = pg_query($db, $query);

$query = "CREATE TABLE IF NOT EXISTS api_request_log (
    id SERIAL PRIMARY KEY,
    request VARCHAR(1000)                   ,
    user_id VARCHAR(100)                    ,
    response VARCHAR(10000)                 ,
    log_timestamp TIMESTAMP                 ,
    description VARCHAR(100)                ,
    type VARCHAR(100)
);";
$result = pg_query($db, $query);

echo "Done.\n" . '</br>';

?>