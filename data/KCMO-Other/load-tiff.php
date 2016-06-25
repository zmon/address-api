<?php

require '../../vendor/autoload.php';
require '../../config/config.php';

ini_set("auto_detect_line_endings", true);

/**
 * Class Address
 */
class KCMOTIF extends \Code4KC\Address\SpatialLoad
{

    var $spatial_fields_to_update = array(
        'name',
        'fid',
        'geom',
        'ordnum',
        'status',
        'amendment',
        'lastupdate',
        'shape_length',
        'shape_area',
        'active'
    );

    var $active_spatial_ids = array();
    var $inactive_spatial_ids = array();

    var $totals = array(
        'spatial' => array('insert' => 0, 'update' => 0, 'inactive' => 0, 're-activate' => 0, 'N/A' => 0, 'error' => 0),
        'tif' => array('insert' => 0, 'update' => 0, 'inactive' => 0, 're-activate' => 0, 'N/A' => 0, 'error' => 0),
    );

    function __construct($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS, $debug = false)
    {

        if (!$this->valid_cli_options()) {

            print "\nBAD\n";
            $this->help();
        } else {

            parent::__construct($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS, $debug);

            //    $this->display_cli_options($DB_NAME, $spatial_DB_NAME);

            $this->load_spatial();
            $this->load();
            $this->end_load();

        }

    }


    function load_spatial()
    {

        $this->Spatial = new \Code4KC\Address\SpatialTable($this->spatial_dbh, true);

        $sql = "SELECT *  FROM kcmo_tiff_fdw LIMIT 5;";

        $this->list_query = $this->spatial_dbh->prepare("$sql  -- " . __FILE__ . ' ' . __LINE__);

        try {
            $this->list_query->execute();
        } catch (PDOException  $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            print "ERROR " . $e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__;
            //throw new Exception('Unable to query database');
            return false;
        }
        $records = $this->list_query->fetchAll(PDO::FETCH_ASSOC);


        foreach ($records AS $rec) {

            $data = $rec;
            $this->row++;


            $fid = $data['fid'];
            $data['active'] = 1;

            if ($current_record = $this->Spatial->find_by_fid($fid)) {

                $this->active_spatial_ids[] = $current_record['id'];

                $changes = $this->Spatial->is_same($data, $current_record, $this->spatial_fields_to_update);

                $number_of_changes = count($changes);

                if ($number_of_changes > 0) {

                    if (array_key_exists('active', $changes)) {                  // Are we reactivating
                        $this->totals['spatial']['re-activate']++;
                        if ($number_of_changes > 1) {                            // and are there other changes
                            $this->totals['spatial']['update']++;
                        }
                    } else {
                        $this->totals['spatial']['update']++;                    // We only have changes NO reactivating
                    }

                    if ($this->verbose) {
                        $this->display_record($this->row, 'Change', $data);
                    }

                    if (!$this->dry_run && $this->Spatial->save_changes($current_record['id'], $changes)) {
                    }

                } else {

                    $this->totals['spatial']['N/A']++;

                    if ($this->verbose) {
                        $this->display_record($this->row, 'N/A', $data);
                    }
                }
            } else {
                $this->totals['spatial']['insert']++;

                if ($this->verbose) {
                    $this->display_record($this->row, 'Add', $data);
                }

                if ($id = $this->Spatial->add($data)) {
                    $this->active_spatial_ids[] = $id;
                }
            }
        }

        if (!$this->dry_run && count($this->active_spatial_ids)) {
            $this->totals['spatial']['inactive'] = $this->Spatial->mark_inactive_if_not_in($this->active_spatial_ids);
        }

    }


    function load()
    {

        print "\n UNCOMMENT LOAD\n";
        return;

        $Tif = new \Code4KC\Address\Tif($this->dbh, true);

        if (!empty($this->input_file)) {
            if (file_exists($this->input_file)) {
                $records = $this->get_data_file($this->input_file);
            } else {
                print "\nERROR: input file " . $this->input_file . " was not found or readable.\n";
                return;
            }
        } else {

            print "\n" . $this->parameters . "\n";
            $json = $this->get_data_curl($this->input_url, $this->parameters);
            $records = json_decode($json, true);        // Convert JSON into an array
        }

        $active_ids = array();

        foreach ($records['features'] AS $rec) {

            $record = $rec['attributes'];

            /**
             *     [features] => Array(
             * [0] => Array(
             * [attributes] => Array(
             * [OBJECTID] => 592422
             * [KIVAPIN] => 47371
             * [LANDUSECODE] => 9500
             * [APN] => JA27530020101000000
             * [ADDRESS] =>
             * [ADDR] =>
             * [FRACTION] =>
             * [PREFIX] =>
             * [STREET] =>
             * [STREET_TYPE] =>
             * [SUITE] =>
             * [OWN_NAME] => Land Bank of Kansas City Missouri
             * [OWN_ADDR] => 4900 Swope Pkwy
             * [OWN_CITY] => Kansas City
             * [OWN_STATE] => MO
             * [OWN_ZIP] => 64130
             * [SHAPE.AREA] => 979.35888888889
             * [SHAPE.LEN] => 158.40463690498
             */

            $data['kivapin'] = $record['KIVAPIN'];
            $data['land_bank_property'] = 1;

            $this->row++;


            if (false /* !$CityAddressAttributes->load_and_validate($data) */) {
                $this->display_rejected_record($this->row, $data, $CityAddressAttributes->error_messages);
                $this->totals['input']['error']++;
            } else {


                $fields_to_update = array(
                    'land_bank_property',
                );

                $city_id = $data['kivapin'];

                if ($current_record = $CityAddressAttributes->find_by_id($city_id)) {

                    $changes = $CityAddressAttributes->is_same($data, $current_record, $fields_to_update);

                    if (count($changes)) {

                        if ($this->verbose) {
                            $this->display_record($this->row, 'Change', $data);
                        }

                        if (!$this->dry_run
                            //                       && $current_record['active']
                            && $CityAddressAttributes->save_changes($current_record['id'], $changes)
                        ) {

                        }

                        $active_ids[] = $current_record['id'];
                        $this->totals['tif']['update']++;
                    } else {
                        if ($this->verbose) {
                            $this->display_record($this->row, 'N/A', $data);
                        }

                        $this->totals['tif']['N/A']++;
                        $active_ids[] = $current_record['id'];
                    }

                } else {
                    $this->totals['tif']['error']++;
                    //                   if ($this->verbose) {
                    $this->display_record($this->row, 'Add', $data);

                    //                   }


//                    if ($id = $CityAddressAttributes->add($data)) {
//                        $active_ids[] = $id;
//                    }
                }
            }
        }


    }

    function display_record($line_number, $msg, $data)
    {

        printf("%10s: %5d %16.16s %s \n", $msg, $line_number, $data['land_use_code'], $data['land_use_description']);
    }

    function display_rejected_record($line_number, $data, $data_errors)
    {

        printf("\nERROR: %5d %16.16s %s \n", $line_number, $data['land_use_code'], $data['land_use_description']);
        $last_field = '.';
        foreach ($data_errors AS $field => $msgs) {
            foreach ($msgs AS $msg) {

                if ($last_field != $field) {
                    $dsp_field = $field . ':';
                    $last_field = $field;
                } else {
                    $dsp_field = '';
                }

                printf("         %20.20s %-s\n", $dsp_field, $msg);
            }
        }
    }

}


$run = new KCMOTIF($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS);
