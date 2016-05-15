- [ ] Get Other data

1) Goto http://maps.kcmo.org/apps/parcelviewer/
   Click Other or get http://maps.kcmo.org/apps/download/GisDataDownload/Other.gdb.zip

   ````
   curl http://maps.kcmo.org/apps/download/GisDataDownload/Other.gdb.zip > Other.zip
   ````

2) Add Other.zip to .gitignore

3) Unzip and remove .zip

````
unzip Other.zip
rm Other.zip
````


- [ ] Create Forgin Data Wraper Server

1) Show layers
````
ogr_fdw_info -s Other.gdb
````

Outputs:
````
Layers:
  Annexations
  AreaPlanBoundaries
  CityLimit
  CouncilDistricts
  CouncilDistricts_2001
  CouncilDistricts_2010
  CountyBoundary
  MaintenanceDistricts_PW
  NeighborhoodCensus
  InspectionAreas
  PoliceDistricts
  PoliceDivisions
  PoliceSectors
  SolidWasteCollectionZones
  FederalHomeLoanBankAreas
  GreenImpactZone
  IncentiveCommunityImprove
  IncentiveEnterpriseZones
  IncentiveNbhdImproveDistrict
  IncentiveNbhdImproveProgram
  IncentivePlannedIndustrialExp
  IncentiveTaxIncrementFinancing
  IncentiveTransDevelopDistrict
  IncentiveUrbanRedevelopment353
  IncentiveUrbanRenewal
  LandmarkKCRegister
  LandmarkNationalRegister
  LandUsePlanningAreas
  MinorHomeRepairProgramTargets
  NeighborhoodActionPlanAreas
  NeighborhoodOrganizationsNCSD
  SpecialReviewDistricts
  StreetImpactFeeDistricts
  Zoning
  ZoningOverlayDistricts
  PointsOfInterest
  Streetlights
  RiversLakes
  sweWatershedBoundary
  VacantParcels
  Parks
  CapitalProjects
  CapitalProjects__ATTACH
````

2) Show create for server and layer

````
ogr_fdw_info -s Other.gdb -l IncentiveTaxIncrementFinancing
````

````
CREATE SERVER kcmo_other_server
  FOREIGN DATA WRAPPER ogr_fdw
  OPTIONS (
    datasource '/var/www/data/KCMO-Other/Other.gdb',
    format 'OpenFileGDB' );

CREATE FOREIGN TABLE kcmo_tiff_fdw (
  fid integer,
  geom geometry,
  name varchar,
  ordnum varchar,
  status varchar,
  amendment varchar,
  lastupdate timestamp,
  shape_length real,
  shape_area real )
  SERVER kcmo_other_server
  OPTIONS ( layer 'IncentiveTaxIncrementFinancing' );
````

- [ ] Test
````
select fid, name, ordnum, status, amendment, lastupdate from kcmo_tiff_fdw;

 fid  |                       name                       | ordnum |   status   | amendment |     lastupdate      
------+--------------------------------------------------+--------+------------+-----------+---------------------
    1 | 13th & Washington TIF                            | 961187 | Active     |           | 
    3 | 10th & Troost TIF                                | 060204 | Terminated |           | 
    4 | 19th Terrace & Central TIF                       | 990702 | Active     |           | 
    6 | Americana Hotel TIF                              | 030336 | Active     | 2nd       | 
   14 | 11th Street Corridor TIF                         | 050325 | Active     | 9th       | 
   15 | Gateway 2000 TIF                                 | 951362 | Active     |           | 
   21 | Midtown TIF                                      | 930066 | Active     |           | 
   22 | New England Bank Bldg TIF                        | 001459 | Active     |           | 
   23 | New York Life TIF                                | 951485 | Active     | 1st       | 
   26 | Riverfront TIF                                   | 990206 | Terminated |           | 
   29 | Savoy Hotel TIF                                  | 120407 | Terminated |           | 2012-09-17 13:47:16
````

- [ ] Create spatial table and load

````
CREATE TABLE address_spatial.kcmo_tiff (
  fid integer,
  name varchar,
  ordnum varchar,
  status varchar,
  amendment varchar,
  lastupdate timestamp,
  geom geometry(MultiPolygon),
  CONSTRAINT pk_kcmo_nhood_fid PRIMARY KEY (fid)
);

CREATE INDEX idx_kcmo_tiff ON
  address_spatial.kcmo_tiff
USING gist(geom);

INSERT INTO address_spatial.kcmo_tiff
  (fid, name, ordnum, status, amendment, lastupdate)
     SELECT fid, name, ordnum, status, amendment, lastupdate
        FROM kcmo_tiff_fdw;

select fid, name, ordnum, status, amendment, lastupdate from address_spatial.kcmo_tiff LIMIT 10;
````


- [ ]  Create query for GEOJSON based off of http://www.postgresonline.com/journal/archives/267-Creating-GeoJSON-Feature-Collections-with-JSON-and-PostGIS-functions.html

````
SELECT row_to_json(fc)
 FROM ( SELECT 'FeatureCollection' As type, array_to_json(array_agg(f)) As features
 FROM (SELECT 'Feature' As type
    , ST_AsGeoJSON(lg.geom)::json As geometry
    , row_to_json(lp) As properties
   FROM address_spatial.kcmo_tiff As lg
         INNER JOIN (SELECT fid, name FROM address_spatial.kcmo_tiff) As lp      
       ON lg.fid = lp.fid  ORDER BY lg.name) As f )  As fc;

````


- [ ] Create data model in `src/Code4KC/Address`

````
<?php

namespace Code4KC\Address;

use \PDO as PDO;

/**
 * Class KCMO_Tiff
 */
class KCMO_Tiff extends BaseTable
{
    var $table_name = 'kcmo_tiff';
    var $primary_key_sequence = null;
    var $list_query = null;
    var $fields = array(
        'id' => '',
        'name' => '',
    );

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
   FROM address_spatial.mo_kc_city_kcmo_tiff As lg
         INNER JOIN (SELECT fid, name FROM address_spatial.mo_kc_city_kcmo_tiff) As lp
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
}
````


