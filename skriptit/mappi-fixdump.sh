#!/bin/sh

# Autoincrement fields can not be set 0.
# Dumppi --quote-names ja koko --opt paitsi --extended-insert 
#  mysqldump --add-drop-table --add-locks --all --quick --lock-tables --quote-names
sed 's/TYPE=ISAM/TYPE=MyISAM/' \
    | sed 's/INSERT INTO `kohde` VALUES (0/INSERT INTO `kohde` VALUES (-1/' \
    | sed 's/INSERT INTO `liite` VALUES (0/INSERT INTO `liite` VALUES (-1/' \
    | sed 's/INSERT INTO `ryhma` VALUES (0/INSERT INTO `ryhma` VALUES (-1/'

echo 
echo 'UPDATE kohde SET kohdeid = 0 WHERE kohdeid = -1;'
echo 'UPDATE liite SET liiteid = 0 WHERE liiteid = -1;'
echo 'UPDATE ryhma SET ryhmaid = 0 WHERE ryhmaid = -1;'
