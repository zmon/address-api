<?php

namespace Code4KC\Address;

use PDO as PDO;

/**
 * Class KCMO_Tif
 */
class Tif extends BaseTable
{
    var $table_name = 'address_spatial.kcmo_tif';
    var $primary_key_sequence = 'address_spatial.kcmo_tif_id_seq';
    var $list_query = null;
    var $fields = array(
        'id' => '',
        'name' => '',
        'fid' => '',
        'geom' => '',
        'ordnum' => '',
        'status' => '',
        'amendment' => '',
        'lastupdate' => '',
        'shape_length' => '',
        'shape_area' => ''
    );

    var $fid_query = null;

    /**
     * @param $id
     * @return false or found record
     */
    function findallgeo()
    {


        if (!$this->list_query) {
            // From http://www.postgresonline.com/journal/archives/267-Creating-GeoJSON-Feature-Collections-with-JSON-and-PostGIS-functions.html
            $sql = "SELECT row_to_json(fc)
                     FROM ( SELECT 'FeatureCollection' As type, array_to_json(array_agg(f)) As features
                     FROM (SELECT 'Feature' As type
                        , ST_AsGeoJSON(lg.geom)::json As geometry
                        , row_to_json(lp) As properties
                       FROM address_spatial.kcmo_tiff As lg
                             INNER JOIN (SELECT fid, name FROM address_spatial.kcmo_tiff) As lp
                           ON lg.fid = lp.fid  ORDER BY lg.name) As f )  As fc;";
            $this->list_query = $this->dbh->prepare("$sql  -- " . __FILE__ . ' ' . __LINE__);
        }

        try {
            $this->list_query->execute();
        } catch (PDOException  $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            //throw new Exception('Unable to query database');
            return false;
        }
        return $this->list_query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * @param $id
     * @return false or found record
     */
    function findall()
    {


        if (!$this->list_query) {
            $sql = 'SELECT id, name  FROM ' . $this->table_name . ' order by name';
            $this->list_query = $this->dbh->prepare("$sql  -- " . __FILE__ . ' ' . __LINE__);
        }

        try {
            $this->list_query->execute();
        } catch (PDOException  $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            //throw new Exception('Unable to query database');
            return false;
        }
        return $this->list_query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return false or found record
     */
    function find_by_fid($fid)
    {
        if (!$this->fid_query) {
            $sql = 'SELECT *  FROM ' . $this->table_name . ' WHERE fid = :fid';
            $this->id_query = $this->dbh->prepare("$sql  -- " . __FILE__ . ' ' . __LINE__);
        }

        try {
            $this->id_query->execute(array(':fid' => $fid));
        } catch (PDOException  $e) {
            error_log($e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
            //throw new Exception('Unable to query database');
            return false;
        }

        return $this->id_query->fetch(PDO::FETCH_ASSOC);
    }

}
