# -----------------------------------------------------------------------
# K‰yttˆliittym‰n toiminnan edellytt‰m‰ minimaalinen data.
# -----------------------------------------------------------------------

# -----------------------------------------------------------------------
# Oletusryhm‰t
# -----------------------------------------------------------------------
INSERT INTO ryhma VALUES 
       (1,'Oikeudet','Oikeusryhm‰','Info','M‰‰rittelyt','-','-');

# Id:n korjaus, autoincrement -kentt‰‰ ei saa asetettua nollaksi
UPDATE ryhma SET ryhmaid = 0 WHERE ryhmaid = 1;

# -----------------------------------------------------------------------
# Oletusliitteet
# -----------------------------------------------------------------------
INSERT INTO liite VALUES 
       (1,0,'Login');

# Id:n korjaus, autoincrement -kentt‰‰ ei saa asetettua nollaksi
UPDATE liite SET liiteid = 0 WHERE liiteid = 1;


# -----------------------------------------------------------------------
# Oikeusasetukset p‰‰k‰ytt‰j‰ryhm‰lle
# -----------------------------------------------------------------------
INSERT INTO kohde VALUES 
       (1,0,'P‰‰k‰ytt‰j‰','',' Oikeudet ryhmiin (r/w) : <code>\'<read>*</read>/<write>*</write>\'</code>
Oikeudet ryhm‰tauluun : <code>\'<ryhma>write</ryhma>\'</code>', NULL);

# Id:n korjaus, autoincrement -kentt‰‰ ei saa asetettua nollaksi
UPDATE kohde SET kohdeid = 0 WHERE kohdeid = 1;


# -----------------------------------------------------------------------
# Kirjautuminen jumalana, eli p‰‰k‰ytt‰j‰ryhm‰n‰ p‰‰k‰ytt‰j‰n
# oikeuksilla, jolloin uid on maaginen 0
# -----------------------------------------------------------------------
# md5('')     = d41d8cd98f00b204e9800998ecf8427e
# md5('root') = 63a9f0ea7bb98050796b649e85481845
# md5('asdf') = 912ec803b2ce49e4a541068d495ab570
# -----------------------------------------------------------------------
INSERT INTO kohde_liite VALUES (1,0,0,0,'K‰ytt‰j‰tunnus : <code>\'<uname>root</uname>\'</code>
Salasana (MD5-summa) : <code>\'<pw>63a9f0ea7bb98050796b649e85481845</pw>\'</code>
NT-domain : <code>\'<ntdomain></ntdomain>\'</code>', NULL);

# Viittaus kirjautumisesta p‰‰k‰ytt‰j‰n oikeusryhm‰‰n
INSERT INTO viittaus VALUES (1,1,0);


# -----------------------------------------------------------------------
# Asetetaan auto increment laskurit siten, ett‰ "standarditiedoille"
# j‰‰ sopivasti tilaa alap‰‰h‰n.  0-99 varataan mapattavaksi sovitusti.
# -----------------------------------------------------------------------
# N‰ytti sotkevan jotenkin omituisella tavalla jo lis‰ttyj‰ tietoja.
# Ei oteta t‰t‰ viel‰ k‰yttˆˆn.  Ehk‰p‰ joskus erillisess‰ skriptiss‰.
#ALTER TABLE kohde       AUTO_INCREMENT = 100;
#ALTER TABLE liite       AUTO_INCREMENT = 100;
#ALTER TABLE kohde_liite AUTO_INCREMENT = 100;
#ALTER TABLE viittaus    AUTO_INCREMENT = 100;
#ALTER TABLE ryhma       AUTO_INCREMENT = 100;
#ALTER TABLE jakso       AUTO_INCREMENT = 100;
#ALTER TABLE tiedosto    AUTO_INCREMENT = 100;
