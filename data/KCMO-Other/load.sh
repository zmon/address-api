#!/bin/sh

echo "\n\nStart\n";
sudo -u postgres psql code4kc < ./load-tiff-start.sql

/usr/bin/php ./load-tiff.php -U -f=/tmp/Other.gdb

echo "\n\nEnd\n";
sudo -u postgres psql code4kc < ./load-tiff-end.sql
