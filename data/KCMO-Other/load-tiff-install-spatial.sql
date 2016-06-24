DROP TABLE IF EXISTS address_spatial.kcmo_tiff ;
DROP SEQUENCE IF EXISTS address_spatial.kcmo_tiff_id_seq;
DROP TABLE IF EXISTS address_spatial.kcmo_tif ;
DROP SEQUENCE IF EXISTS address_spatial.kcmo_tif_id_seq;



CREATE SEQUENCE address_spatial.kcmo_tif_id_seq
    START WITH 2001
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE address_spatial.kcmo_tif_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;


DROP TABLE address_spatial.kcmo_tif ;
CREATE TABLE address_spatial.kcmo_tif (
   id integer DEFAULT nextval('address_spatial.kcmo_tif_id_seq'::regclass) NOT NULL,
   fid integer,
   geom geometry,
   name varchar,
   ordnum varchar,
   status varchar,
   amendment varchar,
   lastupdate varchar,
   shape_length real,
   shape_area real ,
   active integer DEFAULT 1,
   added timestamp without time zone DEFAULT now(),
   changed timestamp without time zone DEFAULT now(),
  CONSTRAINT pk_kcmo_nhood_fid PRIMARY KEY (fid)
);

CREATE INDEX idx_kcmo_tif ON
  address_spatial.kcmo_tif
USING gist(geom);

ALTER TABLE address_spatial.kcmo_tif OWNER TO c4kc;

