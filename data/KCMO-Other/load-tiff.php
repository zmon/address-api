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
        'city_address_attributes' => array('insert' => 0, 'update' => 0, 'inactive' => 0, 're-activate' => 0, 'N/A' => 0, 'error' => 0),
    );

    function __construct($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS, $debug = false)
    {

        if (!$this->valid_cli_options()) {

            print "\nBAD\n";
            $this->help();
        } else {

            parent::__construct($DB_NAME, $DB_USER, $DB_PASS, $DB_CODE4KC_NAME, $DB_CODE4KC_USER, $DB_CODE4KC_PASS, $debug);

            //    $this->display_cli_options($DB_NAME, $spatial_DB_NAME);

            $this->Spatial = new \Code4KC\Address\SpatialTable($this->spatial_dbh, true);
            $this->load_spatial();
            $this->load();
            $this->end_load();

        }

    }


    function load_spatial()
    {



        $sql = "SELECT fid, geom::geography::geometry AS geom, name, ordnum, status, amendment, lastupdate, shape_length, shape_area  FROM public.incentivetaxincrementfinancing;";

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

                if (!$this->dry_run && $id = $this->Spatial->add($data)) {
                    $this->active_spatial_ids[] = $id;
                }
            }
        }

        if (!$this->dry_run && count($this->active_spatial_ids)) {
            $this->totals['spatial']['inactive'] = $this->Spatial->mark_inactive_if_not_in($this->active_spatial_ids);
        }
//$sql = "ALTER TABLE address_spatial.kcmo_tif ALTER COLUMN geom  TYPE geometry(MultiPolygon, 4326) USING ST_Transform(geom, 4326);";
//        $alter_ret = $this->spatial_dbh->exec($sql);

    }


    function load()
    {

        $address = new \Code4KC\Address\Address($this->dbh, true);
        $city_address_attributes = new \Code4KC\Address\CityAddressAttributes($this->dbh, true);


        $sql = 'SELECT a.id, a.longitude, a.latitude, k.city_address_id FROM address a
                LEFT JOIN address_keys k ON ( k.address_id = a.id)
                LEFT JOIN census_attributes c ON ( k.city_address_id = c.city_address_id) ';


        $query = $this->dbh->prepare("$sql  -- " . __FILE__ . ' ' . __LINE__);

        try {
            $query->execute();
        } catch (PDOException  $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            //throw new Exception('Unable to query database');
            return false;
        }
        $row = 0;
        $count = 0;
        while ($rec = $query->fetch(PDO::FETCH_ASSOC)) {


            $row++;
            $lng = $rec['longitude'];
            $lat = $rec['latitude'];
            $city_address_id = $rec['city_address_id'];

            if (empty($city_address_id)) {
                $this->totals['city_address_attributes']['input']['N/A']++;
                continue;
            }



            $cc_rec = $this->Spatial->find_name_by_lng_lat($lng, $lat);


            if ($row % 1000 == 0 ) print ".";

            if ( $cc_rec ) {       // We found a shape this address is in

                $count++;

                $cc_rec['tif'] = $cc_rec['name'];                                       // rename to source table field
                unset($cc_rec['name']);

                $new_rec = array('tif' => $cc_rec['tif']);

                if ($city_address_attributes_rec = $city_address_attributes->find_by_id($city_address_id)) {
                    $city_address_attributes_id = $city_address_attributes_rec['id'];

                    if ($city_address_attribute_differences = $city_address_attributes->diff($city_address_attributes_rec, $new_rec)) {
                        if (!$this->dry_run) {
                            $city_address_attributes->update($city_address_attributes_id, $city_address_attribute_differences);
                        }
                        $this->totals['city_address_attributes']['update']++;
                    } else {
                        $this->totals['city_address_attributes']['N/A']++;
                    }
                } else {
                                                                                        // We are not adding new addresses,
                                                                                        // this would be some sort of an error
                    $this->totals['city_address_attributes']['error']++;
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
