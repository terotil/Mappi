# -----------------------------------------------------------------------
# Käyttöliittymän toiminnan edellyttämä minimaalinen data.
# -----------------------------------------------------------------------

# -----------------------------------------------------------------------
# Oletusryhmät
# -----------------------------------------------------------------------
INSERT INTO ryhma VALUES 
       (1,'Oikeudet','Oikeusryhmä','Info','Määrittelyt','-','-');

# Id:n korjaus, autoincrement -kenttää ei saa asetettua nollaksi
UPDATE ryhma SET ryhmaid = 0 WHERE ryhmaid = 1;

# -----------------------------------------------------------------------
# Oletusliitteet
# -----------------------------------------------------------------------
INSERT INTO liite VALUES 
       (1,0,'Login');

# Id:n korjaus, autoincrement -kenttää ei saa asetettua nollaksi
UPDATE liite SET liiteid = 0 WHERE liiteid = 1;


# -----------------------------------------------------------------------
# Oikeusasetukset pääkäyttäjäryhmälle
# -----------------------------------------------------------------------
INSERT INTO kohde VALUES 
       (1,0,'Pääkäyttäjä','',' Oikeudet ryhmiin (r/w) : <code>\'<read>*</read>/<write>*</write>\'</code>
Oikeudet ryhmätauluun : <code>\'<ryhma>write</ryhma>\'</code>', NULL);

# Id:n korjaus, autoincrement -kenttää ei saa asetettua nollaksi
UPDATE kohde SET kohdeid = 0 WHERE kohdeid = 1;


# -----------------------------------------------------------------------
# Kirjautuminen jumalana, eli pääkäyttäjäryhmänä pääkäyttäjän
# oikeuksilla, jolloin uid on maaginen 0
# -----------------------------------------------------------------------
# md5('')     = d41d8cd98f00b204e9800998ecf8427e
# md5('root') = 63a9f0ea7bb98050796b649e85481845
# md5('asdf') = 912ec803b2ce49e4a541068d495ab570
# -----------------------------------------------------------------------
INSERT INTO kohde_liite VALUES (1,0,0,0,'Käyttäjätunnus : <code>\'<uname>root</uname>\'</code>
Salasana (MD5-summa) : <code>\'<pw>63a9f0ea7bb98050796b649e85481845</pw>\'</code>
NT-domain : <code>\'<ntdomain></ntdomain>\'</code>', NULL);

# Viittaus kirjautumisesta pääkäyttäjän oikeusryhmään
INSERT INTO viittaus VALUES (1,1,0);


# -----------------------------------------------------------------------
# Asetetaan auto increment laskurit siten, että "standarditiedoille"
# jää sopivasti tilaa alapäähän.  0-99 varataan mapattavaksi sovitusti.
# -----------------------------------------------------------------------
# Näytti sotkevan jotenkin omituisella tavalla jo lisättyjä tietoja.
# Ei oteta tätä vielä käyttöön.  Ehkäpä joskus erillisessä skriptissä.
#ALTER TABLE kohde       AUTO_INCREMENT = 100;
#ALTER TABLE liite       AUTO_INCREMENT = 100;
#ALTER TABLE kohde_liite AUTO_INCREMENT = 100;
#ALTER TABLE viittaus    AUTO_INCREMENT = 100;
#ALTER TABLE ryhma       AUTO_INCREMENT = 100;
#ALTER TABLE jakso       AUTO_INCREMENT = 100;
#ALTER TABLE tiedosto    AUTO_INCREMENT = 100;
