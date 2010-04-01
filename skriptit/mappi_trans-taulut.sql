-- Käännökset

DROP TABLE IF EXISTS trans_kohde;
CREATE TABLE trans_kohde (
-- Translations share ryhmaid and mime with the original
       kieliid INTEGER NOT NULL,
       kohdeid INTEGER NOT NULL,
       nimi CHAR(255) NOT NULL,
       nimi2 CHAR(255) NOT NULL,
       data LONGBLOB,
       leima TIMESTAMP(14),

       PRIMARY KEY (kieliid,kohdeid),
       INDEX (kohdeid),
       INDEX (nimi2(20), nimi(20)),
       INDEX (nimi(20)),
       INDEX (leima)
);

DROP TABLE IF EXISTS trans_liite;
CREATE TABLE trans_liite (
	kieliid INTEGER NOT NULL,
       liiteid INTEGER NOT NULL,
       nimi CHAR(255) NOT NULL,

       PRIMARY KEY (kieliid,liiteid),
       INDEX (liiteid),
       INDEX (nimi(20))
);

DROP TABLE IF EXISTS trans_kohde_liite;
CREATE TABLE trans_kohde_liite (
-- Translations share kohdeid, liiteid and mime with the original
	kieliid INTEGER NOT NULL,
       kohde_liiteid INTEGER NOT NULL,
       data LONGBLOB,
       leima TIMESTAMP(14),

       PRIMARY KEY (kieliid,kohde_liiteid),
       INDEX (kohde_liiteid)
);

DROP TABLE IF EXISTS trans_ryhma;
CREATE TABLE trans_ryhma (
	kieliid INTEGER NOT NULL,
       ryhmaid INTEGER NOT NULL,
       nimi CHAR(255) NOT NULL,
       kohde_nimi CHAR(255) NOT NULL,
       kohde_nimi2 CHAR(255) NOT NULL,
       kohde_data CHAR(255) NOT NULL,
       liite_nimi CHAR(255) NOT NULL,
       kohde_liite_data CHAR(255) NOT NULL,

       PRIMARY KEY (kieliid,ryhmaid),
       INDEX (ryhmaid)
);

DROP TABLE IF EXISTS trans_tiedosto;
CREATE TABLE trans_tiedosto (
	kieliid INTEGER NOT NULL,
       tiedostoid INTEGER NOT NULL,
       data LONGBLOB,
       nimi CHAR(255) NOT NULL,
       mime CHAR(255) NOT NULL,

       PRIMARY KEY (kieliid,tiedostoid),
       INDEX (tiedostoid),
       INDEX (nimi)
);

DROP TABLE IF EXISTS kieli;
CREATE TABLE kieli (
	kieliid INTEGER NOT NULL AUTO_INCREMENT,
	nimi CHAR(255) NOT NULL,
	koodi CHAR(3) NOT NULL,
	locale CHAR(255),
	charset CHAR(255),
	
	PRIMARY KEY (kieliid)
);

DROP TABLE IF EXISTS trans_kieli;
CREATE TABLE trans_kieli (
	kieliid INTEGER NOT NULL, -- tälle kielelle käännetty
	trans_kieliid INTEGER NOT NULL, -- tämän kielen nimi
	nimi CHAR(255) NOT NULL,
	
	PRIMARY KEY (trans_kieliid,kieliid)
);