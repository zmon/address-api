

What to call it?  Shape

CREATE FOREIGN TABLE zoning (
  fid bigint,
  shape Geometry(MultiPolygon),
  shape_type_id integer,                -- Councel Distric
  shape_name_type integer, 
  shape_name varchar,
  shape_note varchar,
  shape_length real,
  shape_area real
) SERVER myserver
OPTIONS (layer 'Zoning');