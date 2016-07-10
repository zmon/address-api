<?php

namespace Code4KC\Address;

use \PDO as PDO;

/**
 * Class BaseTable
 */
class SpatialLoad extends BaseLoad
{

    /**
     * @param $dbh
     *
     * Connect to both the address and spatial database
     */
    function __construct($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS, $debug = false)
    {

        try {
            $dbh = new PDO("pgsql:host=localhost; dbname=$DB_NAME", $DB_USER, $DB_PASS);
        } catch (PDOException $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            return false;
        }

        try {
            $spatial_dbh = new PDO("pgsql:host=localhost; dbname=$DB_CODE4KC_NAME", $DB_CODE4KC_USER, $DB_CODE4KC_PASS);
        } catch (PDOException $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            return false;
        }



        $this->dbh = $dbh;
        $this->spatial_dbh = $spatial_dbh;

        $this->rustart = getrusage();   // Lets see how much system resources we use

        $this->start_time = time();     // Lest see wall clock time on this run


    }

    function load_spatial() {

    }

    function load() {

    }



}
