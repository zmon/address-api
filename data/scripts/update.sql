
\c address_api

---- Organizations

DROP TABLE IF EXISTS organizations ;
DROP SEQUENCE IF EXISTS organizations_id_seq;



CREATE SEQUENCE organizations_id_seq
START WITH 2001
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER TABLE public.organizations_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE organizations (
  id integer DEFAULT nextval('organizations_id_seq'::regclass) NOT NULL,
  alias character varying(24) DEFAULT NULL::character varying,
  name character varying(80) DEFAULT NULL::character varying,
  active integer DEFAULT 1,
  added timestamp without time zone DEFAULT now(),
  changed timestamp without time zone DEFAULT now()
);

ALTER TABLE public.organizations OWNER TO c4kc;

INSERT INTO organizations VALUES (101,'KCMO','Kansas City, MO Open Data',1,now(),now());

---- Data Sets

DROP TABLE IF EXISTS data_sets ;
DROP SEQUENCE IF EXISTS data_sets_id_seq;



CREATE SEQUENCE data_sets_id_seq
START WITH 2001
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER TABLE public.data_sets_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE data_sets (
  id integer DEFAULT nextval('data_sets_id_seq'::regclass) NOT NULL,
  organization_id integer NOT NULL,
  alias character varying(24) DEFAULT NULL::character varying,
  name character varying(80) DEFAULT NULL::character varying,
  version character varying(80) DEFAULT NULL::character varying,
  end_point character varying(500) DEFAULT NULL::character varying,
  end_point_type integer NOT NULL,

  active integer DEFAULT 1,
  added timestamp without time zone DEFAULT now(),
  changed timestamp without time zone DEFAULT now()
);

ALTER TABLE public.data_sets OWNER TO c4kc;

INSERT INTO data_sets VALUES (201,101, 'luc','Land Use Codes','1.0',
                              'https://data.kcmo.org/resource/mgwp-vfsh.json',
                              1,1,now(),now());

INSERT INTO data_sets VALUES (202,101, 'lbp','lbp','1.0',
                              'https://maps.kcmo.org/kcgis/rest/services/external/DataLayers/MapServer/12/query',
                              2,1,now(),now());

---- Data Set Fields

DROP TABLE IF EXISTS data_set_fields ;
DROP SEQUENCE IF EXISTS data_set_fields_id_seq;



CREATE SEQUENCE data_set_fields_id_seq
START WITH 2001
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER TABLE public.data_set_fields_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE data_set_fields (
  id integer DEFAULT nextval('data_set_fields_id_seq'::regclass) NOT NULL,
  data_set_id integer NOT NULL,
  data_set_field_name character varying(80) DEFAULT NULL::character varying,
  attribute_name character varying(80) DEFAULT NULL::character varying,

  active integer DEFAULT 1,
  added timestamp without time zone DEFAULT now(),
  changed timestamp without time zone DEFAULT now()
);

ALTER TABLE public.data_set_fields OWNER TO c4kc;

---- Loads

DROP TABLE IF EXISTS loads ;
DROP SEQUENCE IF EXISTS loads_id_seq;



CREATE SEQUENCE loads_id_seq
START WITH 2001
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER TABLE public.loads_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE loads (
  id integer DEFAULT nextval('loads_id_seq'::regclass) NOT NULL,
  data_set_id integer NOT NULL,
  alias character varying(24) DEFAULT NULL::character varying,
  name character varying(80) DEFAULT NULL::character varying,
  active integer DEFAULT 1,
  added timestamp without time zone DEFAULT now(),
  changed timestamp without time zone DEFAULT now()
);

ALTER TABLE public.loads OWNER TO c4kc;

---- Attributes

DROP TABLE IF EXISTS attributes ;
DROP SEQUENCE IF EXISTS attributes_id_seq;



CREATE SEQUENCE attributes_id_seq
START WITH 2001
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER TABLE public.attributes_id_seq OWNER TO c4kc;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE attributes (
  id integer DEFAULT nextval('attributes_id_seq'::regclass) NOT NULL,
  name character varying(80) DEFAULT NULL::character varying,
  description text DEFAULT NULL::character varying,

  active integer DEFAULT 1,
  added timestamp without time zone DEFAULT now(),
  changed timestamp without time zone DEFAULT now()
);

ALTER TABLE public.attributes OWNER TO c4kc;
