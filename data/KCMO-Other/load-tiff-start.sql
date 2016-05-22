DROP FOREIGN TABLE kcmo_tiff_fdw;
DROP SERVER kcmo_other_server;

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