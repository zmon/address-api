#!/bin/sh

#
# Recreate the databases
#

# Need to drop databases first

sudo -u postgres dropdb address_api
sudo -u postgres dropdb code4kc

SQL=$(cat <<EOF
CREATE DATABASE address_api  WITH ENCODING 'UTF8' TEMPLATE=template0;
GRANT ALL PRIVILEGES ON DATABASE address_api TO c4kc;

CREATE DATABASE code4kc  WITH ENCODING 'UTF8' TEMPLATE=template0;
GRANT ALL PRIVILEGES ON DATABASE code4kc TO c4kc;

\q
EOF
)

echo "${SQL}" | sudo -u postgres psql


#
# For some unknown resone I had to load the extension separatly
#
SQL=$(cat <<EOF

CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;
CREATE EXTENSION fuzzystrmatch;
CREATE EXTENSION postgres_fdw;
CREATE EXTENSION ogr_fdw;

\q
EOF
)

echo "${SQL}" | sudo -u postgres psql code4kc

#
# Load the backups
#
(
    cd /var/www/dumps

    sudo -u postgres pg_restore -C -d address_api address_api-20160220-0548.dump
    sudo -u postgres pg_restore -C -d code4kc code4kc-20160220-0548.dump

)
sudo service postgresql stop
sudo service postgresql start

#
# INSTALL THIS RELEASE
#
sudo -u postgres psql code4kc < ./load-tiff-install-spatial.sql
sudo -u postgres psql address_api < ./load-tiff-install.sql
#
# Fix Permissions
#
(
   cd /var/www/data/scripts
   sudo -u postgres psql -f fix_ownerships.psql
)


#
# Now setup for Load
#
cp Other.zip /tmp

(
    cd /tmp
    rm -rf Other.gdb

    # curl http://maps.kcmo.org/apps/download/GisDataDownload/Other.gdb.zip > Other.zip

    unzip Other.zip
    rm Other.zip

)


