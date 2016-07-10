#!/bin/sh

echo "\n\nStart\n";
#sudo -u postgres psql code4kc < ./load-tiff-start.sql

sudo -u postgres sh /var/www/data/KCMO-Other/load-tiff-start.sh
echo "Start is done\n"

/usr/bin/php ./load-tiff.php -U -f=/tmp/Other.gdb

echo "\n\nEnd\n";
#sudo -u postgres psql code4kc < ./load-tiff-end.sql
