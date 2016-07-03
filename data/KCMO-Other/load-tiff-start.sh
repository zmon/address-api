#!/bin/sh
#
# Move the data from KCMO Other.zip's layer incentivetaxincrementfinancing to our spatial database.
#
# Some extra code in here for debuging, and extra calls to psql for debuging
#
cd /tmp
#
# Unpack Other.zip into Other.gdb

#
# Clean up from last run
#
#
psql -d code4kc -c "DROP TABLE incentivetaxincrementfinancing;"
#
# Load the one layer incentivetaxincrementfinancing
#
ogr2ogr -f "PostgreSQL" PG:"dbname=code4kc user=postgres" Other.gdb incentivetaxincrementfinancing
#
# Lets take a look at what we have
#
psql -d code4kc -c "\dt"
psql -d code4kc -c "\d incentivetaxincrementfinancing"
#
# Do the conversion to 4326
#
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing ALTER COLUMN wkb_geometry  TYPE geometry(MultiPolygon, 4326) USING ST_Transform(wkb_geometry, 4326);"
#
# Now rename columns so I do not need to change everything else
#
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing RENAME ogc_fid TO fid"
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing RENAME wkb_geometry TO geom"

#
# Now lets try to get a point that we know is in the River Market TIF
#
echo "\nFirst Point\n"
psql -d code4kc -c "SELECT name  FROM incentivetaxincrementfinancing WHERE ST_Intersects( ST_MakePoint( -94.5799701873, 39.1081197154)::geography::geometry, geom);"

#
# Now change ownership
#
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing OWNER TO c4kc;"
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing_pkey OWNER TO c4kc;"
psql -d code4kc -c "ALTER TABLE incentivetaxincrementfinancing_wkb_geometry_geom_idx OWNER TO c4kc;"
#
# Final Look
#
psql -d code4kc -c "\dt"
psql -d code4kc -c "\d incentivetaxincrementfinancing"
#
# Now lets try to get the same point again
#
echo "\n Second Point\n"
psql -d code4kc -c "SELECT name  FROM incentivetaxincrementfinancing WHERE ST_Intersects( ST_MakePoint( -94.5799701873, 39.1081197154)::geography::geometry, geom);"
