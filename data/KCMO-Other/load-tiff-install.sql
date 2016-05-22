DROP TABLE address_spatial.kcmo_tiff ;
CREATE TABLE address_spatial.kcmo_tiff (
   fid integer,
   geom geometry,
   name varchar,
   ordnum varchar,
   status varchar,
   amendment varchar,
   lastupdate varchar,
   shape_length real,
   shape_area real ,
  CONSTRAINT pk_kcmo_nhood_fid PRIMARY KEY (fid)
);

CREATE INDEX idx_kcmo_tiff ON
  address_spatial.kcmo_tiff
USING gist(geom);

