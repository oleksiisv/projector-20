<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';

const CREATE_TABLE = true;
const INSERT_USERS = true;
const USERS_NUMBER_TO_INSERT = 10**4;

/** Script entrypoint */
execute();

/**
 * @return void
 */
function execute()
{
    $start = microtime(true);

    $mysqli = new mysqli('l20-db-1', 'admin', 'admin123', 'l20');

    if(CREATE_TABLE === true){
        createTable($mysqli);
    }

    if(INSERT_USERS === true){
        for ($i=0; $i < USERS_NUMBER_TO_INSERT; $i++){
            insertUserRow($mysqli, sampleUser());
        }
    }

    $mysqli->close();

    $delta = microtime(true) - $start;
    print_r($delta . "\n");
}

/**
 * @param $mysqli
 *
 * @return void
 */
function createTable($mysqli)
{
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS Users (
            user_id int NOT NULL AUTO_INCREMENT PRIMARY KEY, 
            first_name varchar(255), 
            last_name varchar(255), 
            email varchar(255) NOT NULL, 
            address varchar(255), 
            dob date
        );
    ");
}

/**
 * @param $mysqli
 * @param $user
 *
 * @return void
 */
function insertUserRow($mysqli, $user)
{
    $mysqli->query(sprintf("
        INSERT INTO Users (first_name, last_name, email, address, dob) 
        VALUES ('%s', '%s', '%s', '%s', '%s')",
        mysqli_real_escape_string($mysqli, $user['first_name']),
        mysqli_real_escape_string($mysqli, $user['last_name']),
        mysqli_real_escape_string($mysqli, $user['email']),
        mysqli_real_escape_string($mysqli, $user['address']),
        mysqli_real_escape_string($mysqli, $user['dob'])
    ));
}

/**
 * @return array
 */
function sampleUser()
{
    /** @var Faker\Generator $faker */
    $faker = Faker\Factory::create();

    return [
        'first_name' => $faker->firstname(),
        'last_name' => $faker->lastname(),
        'email' => $faker->email(),
        'address' => $faker->address(),
        'dob' => $faker->date('Y-m-d'),
    ];
}