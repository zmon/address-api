# How we build from Other.zip from KCMO

cd /tmp
curl
unzip Other.zip

# Determain layers

````
ogrinfo Other.gdb
````

````
Had to open data source read-only.
INFO: Open of `Other.gdb'
      using driver `OpenFileGDB' successful.
1: Annexations (Multi Polygon)
2: AreaPlanBoundaries (Multi Polygon)
3: CityLimit (Multi Polygon)
4: CouncilDistricts (Multi Polygon)
5: CouncilDistricts_2001 (Multi Polygon)
6: CouncilDistricts_2010 (Multi Polygon)
7: CountyBoundary (Multi Polygon)
8: MaintenanceDistricts_PW (Multi Polygon)
9: NeighborhoodCensus (Multi Polygon)
10: InspectionAreas (Multi Polygon)
11: PoliceDistricts (Multi Polygon)
12: PoliceDivisions (Multi Polygon)
13: PoliceSectors (Multi Polygon)
14: SolidWasteCollectionZones (Multi Polygon)
15: FederalHomeLoanBankAreas (Multi Polygon)
16: GreenImpactZone (Multi Polygon)
17: IncentiveCommunityImprove (Multi Polygon)
18: IncentiveEnterpriseZones (Multi Polygon)
19: IncentiveNbhdImproveDistrict (Multi Polygon)
20: IncentiveNbhdImproveProgram (Multi Polygon)
21: IncentivePlannedIndustrialExp (Multi Polygon)
22: IncentiveTaxIncrementFinancing (Multi Polygon)
23: IncentiveTransDevelopDistrict (Multi Polygon)
24: IncentiveUrbanRedevelopment353 (Multi Polygon)
25: IncentiveUrbanRenewal (Multi Polygon)
26: LandmarkKCRegister (Multi Polygon)
27: LandmarkNationalRegister (Multi Polygon)
28: LandUsePlanningAreas (Multi Polygon)
29: MinorHomeRepairProgramTargets (Multi Polygon)
30: NeighborhoodActionPlanAreas (Multi Polygon)
31: NeighborhoodOrganizationsNCSD (Multi Polygon)
32: SpecialReviewDistricts (Multi Polygon)
33: StreetImpactFeeDistricts (Multi Polygon)
34: Zoning (Multi Polygon)
35: ZoningOverlayDistricts (Multi Polygon)
36: PointsOfInterest (Point)
37: Streetlights (Point)
38: RiversLakes (Multi Polygon)
39: sweWatershedBoundary (Multi Polygon)
40: VacantParcels (Multi Polygon)
41: Parks (Multi Polygon)
42: CapitalProjects (Multi Polygon)
43: CapitalProjects__ATTACH (None)
````

# Lets look at a layer

````
ogrinfo -so Other.gdb Zoning
````

````
Had to open data source read-only.
INFO: Open of `Other.gdb'
      using driver `OpenFileGDB' successful.

Layer name: Zoning
Geometry: Multi Polygon
Feature Count: 2402
Extent: (2713525.138334, 967821.560834) - (2821300.080000, 1161493.075000)
Layer SRS WKT:
PROJCS["NAD_1983_StatePlane_Missouri_West_FIPS_2403_Feet",
    GEOGCS["GCS_North_American_1983",
        DATUM["North_American_Datum_1983",
            SPHEROID["GRS_1980",6378137.0,298.257222101]],
        PRIMEM["Greenwich",0.0],
        UNIT["Degree",0.0174532925199433]],
    PROJECTION["Transverse_Mercator"],
    PARAMETER["False_Easting",2788708.333333333],
    PARAMETER["False_Northing",0.0],
    PARAMETER["Central_Meridian",-94.5],
    PARAMETER["Scale_Factor",0.9999411764705882],
    PARAMETER["Latitude_Of_Origin",36.16666666666666],
    UNIT["Foot_US",0.3048006096012192],
    AUTHORITY["ESRI","102698"]]
FID Column = OBJECTID
Geometry Column = SHAPE
CLASSIFICATION: String (0.0)
LANDUSE: String (0.0)
ORD_NO: String (0.0)
CLASSIFICATION_OLD: String (0.0)
MAPPED_DATE: DateTime (0.0)
ORD_DATE: DateTime (0.0)
LASTUPDATE: DateTime (0.0)
CLASSIFICATION2: String (0.0)
CLASSIFICATION3: String (0.0)
CLASSIFICATION4: String (0.0)
CLASSIFICATION5: String (0.0)
SHAPE_Length: Real (0.0)
SHAPE_Area: Real (0.0)
````

Forgin Data Wrapers gives us some interesting information

````
ogr_fdw_info -s Other.gdb -l Zoning
````

We are only intrested in the `CREATE FOREIGN TABLE` section

````
CREATE SERVER myserver
  FOREIGN DATA WRAPPER ogr_fdw
  OPTIONS (
    datasource 'Other.gdb',
    format 'OpenFileGDB' );

CREATE FOREIGN TABLE zoning (
  fid bigint,
  shape Geometry(MultiPolygon),
  classification varchar,
  landuse varchar,
  ord_no varchar,
  classification_old varchar,
  mapped_date timestamp,
  ord_date timestamp,
  lastupdate timestamp,
  classification2 varchar,
  classification3 varchar,
  classification4 varchar,
  classification5 varchar,
  shape_length real,
  shape_area real
) SERVER myserver
OPTIONS (layer 'Zoning');
````

# Load data into postgres

````
ogr2ogr -f "PostgreSQL" PG:"dbname=code4kc user=postgres" Other.gdb Zoning
````

# See what was created in postgres

Start psql

````
psql code4kc
````

Now list what is in t

````
\d
````

````
                  List of relations
  Schema  |        Name        |   Type   |  Owner   
----------+--------------------+----------+----------
 public   | geography_columns  | view     | postgres
 public   | geometry_columns   | view     | postgres
 public   | raster_columns     | view     | postgres
 public   | raster_overviews   | view     | postgres
 public   | spatial_ref_sys    | table    | postgres
 public   | zoning             | table    | postgres
 public   | zoning_ogc_fid_seq | sequence | postgres
 topology | layer              | table    | postgres
 topology | topology           | table    | postgres
 topology | topology_id_seq    | sequence | postgre
````

Take a looks at zoning.  
We will see that the geometry is MultiPolygon and the projection is 900914.


````
\d zoning
````

````
                                             Table "public.zoning"
       Column       |             Type              |                        Modifiers                         
--------------------+-------------------------------+----------------------------------------------------------
 ogc_fid            | integer                       | not null default nextval('zoning_ogc_fid_seq'::regclass)
 wkb_geometry       | geometry(MultiPolygon,900914) | 
 classification     | character varying             | 
 landuse            | character varying             | 
 ord_no             | character varying             | 
 classification_old | character varying             | 
 mapped_date        | timestamp with time zone      | 
 ord_date           | timestamp with time zone      | 
 lastupdate         | timestamp with time zone      | 
 classification2    | character varying             | 
 classification3    | character varying             | 
 classification4    | character varying             | 
 classification5    | character varying             | 
 shape_length       | double precision              | 
 shape_area         | double precision              | 
Indexes:
    "zoning_pkey" PRIMARY KEY, btree (ogc_fid)
    "zoning_wkb_geometry_geom_idx" gist (wkb_geometry)
````

Now lets talk a look at the fields

````
SELECT ogc_fid, classification, landuse, classification_old, classification2, classification3, classification4, classification5  
FROM zoning ORDER BY classification LIMIT 20;

 ogc_fid | classification |       landuse       | classification_old | classification2 | classification3 | classification4 | classification5 
---------+----------------+---------------------+--------------------+-----------------+-----------------+-----------------+-----------------
    1845 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 
    2268 | AG-R           | Residential         | GP7                | AG-R            |                 |                 | 
    1729 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 
    1810 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 
    1905 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 
    2188 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 
    1603 | AG-R           | Agricultural/Vacant | GP7                | AG-R            |                 |                 | 


SELECT ogc_fid, ord_no, mapped_date, ord_date, lastupdate 
FROM zoning ORDER BY classification LIMIT 10;

 ogc_fid | ord_no |      mapped_date       |        ord_date        |       lastupdate       
---------+--------+------------------------+------------------------+------------------------
    1603 |        |                        |                        | 2013-12-30 11:53:26+00
    1712 |        |                        |                        | 2015-09-09 11:32:21+00
      76 |        |                        |                        | 2013-06-11 10:45:33+00
    1579 |        |                        |                        | 
    1666 |        |                        |                        | 2015-09-15 11:56:10+00
    1672 |        |                        |                        | 2016-05-06 11:39:05+00
      71 |        |                        |                        | 
      74 |        |                        |                        | 
    1778 | 060828 |                        |                        | 2012-01-26 10:14:46+00
    1729 | 070487 | 2007-07-13 00:00:00+00 | 2007-06-07 00:00:00+00 | 2012-05-14 11:55:35+00

````

