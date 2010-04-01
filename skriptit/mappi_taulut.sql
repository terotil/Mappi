
CREATE TABLE kohde (
       kohdeid INTEGER NOT NULL AUTO_INCREMENT,
       ryhmaid INTEGER NOT NULL,
       nimi CHAR(255) NOT NULL,
       nimi2 CHAR(255) NOT NULL,
       data LONGBLOB,
       leima TIMESTAMP(14),
       mime CHAR(255) NOT NULL,

       PRIMARY KEY (kohdeid),
       INDEX (ryhmaid),
       INDEX (nimi2(20), nimi(20)),
       INDEX (nimi),
       INDEX (leima)
);
-- Add mime
-- ALTER TABLE kohde ADD COLUMN mime CHAR(255) NOT NULL AFTER leima

CREATE TABLE liite (
       liiteid INTEGER NOT NULL AUTO_INCREMENT,
       ryhmaid INTEGER NOT NULL,
       nimi CHAR(255) NOT NULL,

       PRIMARY KEY (liiteid),
       INDEX (ryhmaid),
       INDEX (nimi(20))
);

CREATE TABLE kohde_liite (
       kohde_liiteid INTEGER NOT NULL AUTO_INCREMENT,
       jarjestys INTEGER NOT NULL,
       kohdeid INTEGER NOT NULL,
       liiteid INTEGER NOT NULL,
       data LONGBLOB,
       leima TIMESTAMP(14),
       mime CHAR(255) NOT NULL,

       PRIMARY KEY (kohde_liiteid),
       INDEX (jarjestys),
       INDEX (kohdeid),
       INDEX (liiteid),
       INDEX (leima)
);
-- Add mime
-- ALTER TABLE kohde_liite ADD COLUMN mime CHAR(255) NOT NULL AFTER leima

CREATE TABLE viittaus (
       jarjestys INTEGER NOT NULL,
       kohde_liiteid INTEGER NOT NULL,
       kohdeid INTEGER NOT NULL,

       PRIMARY KEY (kohde_liiteid, kohdeid),
       INDEX (jarjestys),
       INDEX (kohdeid)
);

CREATE TABLE ryhma (
       ryhmaid INTEGER NOT NULL AUTO_INCREMENT,
       nimi CHAR(255) NOT NULL,
       kohde_nimi CHAR(255) NOT NULL,
       kohde_nimi2 CHAR(255) NOT NULL,
       kohde_data CHAR(255) NOT NULL,
       liite_nimi CHAR(255) NOT NULL,
       kohde_liite_data CHAR(255) NOT NULL,

       PRIMARY KEY (ryhmaid)
);

CREATE TABLE jakso (
       jaksoid INTEGER NOT NULL AUTO_INCREMENT,
       kohde_liiteid INTEGER NOT NULL,
       alku DATETIME NOT NULL,
       loppu DATETIME NOT NULL,
       type SET('in','ex') NOT NULL DEFAULT 'in',

       PRIMARY KEY (jaksoid),
       INDEX (kohde_liiteid),
       INDEX (alku, loppu)
);

CREATE TABLE tiedosto (
       tiedostoid INTEGER NOT NULL AUTO_INCREMENT,
       kohde_liiteid INTEGER NOT NULL,
       data LONGBLOB,
       nimi CHAR(255) NOT NULL,
       mime CHAR(255) NOT NULL,

       PRIMARY KEY (tiedostoid),
       INDEX (kohde_liiteid),
       INDEX (nimi)
);
