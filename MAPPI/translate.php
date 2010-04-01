<?php
/******************************************************************************
 ** MAPPI, kirjastorutiinit k��nn�sten k�sittelyyn
 */

global $DEFAULTLANG; $DEFAULTLANG = array('kieliid' => 1, 'nimi' => 'suomi', 'koodi' => 'fi', 'locale' => 'finnish', 'charset' => 'iso-8859-1');

// L�ytyv�tk� k��nn�staulut kannasta, eli onko k��nn�sominaisuudet p��ll�.
function trans_tables_available() {
  // Haetaan lista tietokannan tauluista
  $records = qry('show tables');
  foreach ( $records as $fields ) {
    // Jos 'kieli' -niminen taulu l�ytyy, ovat k��nn�staulut kannassa
    // ja k��nn�sominaisuuksia voidaan k�ytt��
    foreach ( $fields as $field ) {
      if ( $field == 'kieli' ) return true;
    }
  }
  return false;
}

// K��nn�staulut
function qry_trans_tables() {
  // Haetaan lista tietokannan tauluista
  //  tulos = array(0 => array('taulusarake' => 'taulu1'),
  //                1 => array('taulusarake' => 'taulu2'), ... )
  $records = qry('SHOW TABLES');
  // Taulun nimen sarakkeen nimi
  $colname = array_keys($records[0]); $colname = $colname[0];
  foreach ( $records as $fields ) {
    if ( ereg('^trans_.*', $fields[$colname]) ) {
      $trans_tables[] = $fields[$colname];
    }
  }
  return $trans_tables;
}

// Palauttaa annetun taulun k��nn�stietokent�t
function qry_trans_datafields($table) {
  // Ei k��nn�stauluja, ei k��nn�skentti�
  if ( !trans_tables_available() ) return array();
  // Haetaan taulun kuvaus
  $description = qry("DESCRIBE trans_{$table}");
  // Poimitaan sielt� muut paitsi kieliid, aikaleima ja taulun avainkentt�
  $datafields = array();
  foreach ( $description as $field ) {
    if ( ! ereg("^(kieliid|leima|{$table}id)$", $field['Field'] ) ) {
      $datafields[] = $field['Field'];
    }
  }
  return $datafields;
}

function qry_kieli($kieliid) {
  $kieliid = intval($kieliid);
  if ( trans_tables_available() ) {
    $kieli = qry("SELECT * FROM kieli WHERE kieliid = $kieliid");
    if ( count($kieli) == 1 ) {
      return $kieli[0];
    }
  }
  return array();
}

function qry_kieli_lista() {
  if ( trans_tables_available() ) {
      $kieliid = hae_istunnon_kieliid();
      $kielet = 
	qry("SELECT DISTINCT k.kieliid,
               IF(tk.trans_kieliid <=> $kieliid, tk.nimi, k.nimi) AS nimi,
               k.koodi, k.locale, k.charset,
               IF(tk.trans_kieliid <=> $kieliid, $kieliid, 1) AS tk_kieliid
             FROM kieli k LEFT 
               JOIN trans_kieli tk ON k.kieliid = tk.trans_kieliid");
    // Ylim��r�iset pois
    $filtered =& filter_translations($kielet, 'kieliid', array('tk_kieliid'));
    return $filtered;
  }
  return array();
}

// Kielten nimet kyseisell� kielell�
function qry_kieli_lista_natiivi() {
  if ( trans_tables_available() ) {
      $kielet = 
	qry("SELECT DISTINCT k.kieliid,
               IF(tk.trans_kieliid <=> k.kieliid, tk.nimi, k.nimi) AS nimi,
               k.koodi, k.locale, k.charset,
               IF(tk.trans_kieliid <=> k.kieliid, k.kieliid, 1) AS tk_kieliid
             FROM kieli k LEFT 
               JOIN trans_kieli tk ON k.kieliid = tk.trans_kieliid");
    // Ylim��r�iset pois
    $filtered =& filter_translations($kielet, 'kieliid', array('tk_kieliid'));
    return $filtered;
  }
  return array();
}

// Mit� k��nn�ksi� taulun $table tietueesta $id on olemassa?
function qry_trans_avail($table, $id) {
  if ( ! trans_tables_available() ) {
    // Jos k��nn�kset eiv�t ole k�yt�ss�, annetaan aina tyhj� lista 
    return array();
  }
  // Kielitaulu tarvitsee paikkoheiton, koska kieliid ei ole yksik�sitteinen
  if ( $table == 'kieli' ) { $fix = 'trans_'; } else { $fix = ''; }
  // Haetaan kaikki annettua taulu-rivi -yhdistelm�� ($table, $id)
  // vastaavien k��nn�sten id:t ja koodit.
  $langs = qry("SELECT k.kieliid, k.koodi ".
	       "FROM trans_$table tt, kieli k ".
	       "WHERE tt.{$fix}{$table}id = $id AND ".
	       "tt.kieliid = k.kieliid");
  // Oletuskieli on aina tarjolla
  $trans_avail = array(1 => 'oletus');
  // Lis�t��n listaan muut kielet
  foreach ( $langs as $lang ) {
    $trans_avail[$lang['kieliid']] = $lang['koodi'];
  }
  return $trans_avail;
}

// Onko k��nn�ksi� annetulla kielell� olemassa?
function qry_translations_exist($kieliid, &$trancounts) {
  // Oletuskielt� ei vied� k��nn�stauluihin, joten se l�ytyy aina -->
  // oletuskielt� ei voi poistaa
  if ( $kieliid == 1 ) return true;
  $trans_tables = qry_trans_tables(); // Hae k��nn�staulujen nimet
  $translations_exist = false;
  foreach ( $trans_tables as $tt ) {
    $tc = qry("SELECT COUNT(kieliid) AS tc FROM $tt WHERE kieliid = $kieliid");
    $trancounts[substr($tt, 6)] = $tc[0]['tc'];
    if ( $tc[0]['tc'] > 0 ) $translations_exist = true;
  }
  return $translations_exist;
}

// Mit� k��nn�ksi� voi viel� lis�t�?
function qry_trans_insertable($table, $id) {
  if ( ! trans_tables_available() ) {
    // Jos k��nn�kset eiv�t ole k�yt�ss�, annetaan aina tyhj� lista 
    return array();
  }
  // Haetaan olemassa olevat k��nn�kset ja py�r�ytet��n taulukon
  // avaimet (kieliid:t) arvoiksi.
  $avail = array_flip(qry_trans_avail($table, $id));
  if ( count($avail) < 1 ) return array();  // Virhe, peli poikki.
  $inlist = join(',', $avail);
  $langs = qry("SELECT kieliid, nimi ".
               "FROM kieli ".
               "WHERE kieliid NOT IN ($inlist)");
  foreach ( $langs as $lang ) {
    $insertable[$lang['nimi']] = $lang['kieliid'];
  }
  return $insertable;
}

// Asettaa annetun kielen tietueen lang -istuntomuuttujaan.  Jos
// kielt� ei l�ydy, asetetaan oletuskieli (suomi).  Oletuskielen
// kieliid on aina 1.
function aseta_istunnon_kieli($kieliid) {
  $kieli = qry_kieli($kieliid);
  if ( count($kieli) == 0 ) {
    $kieli = $DEFAULTLANG;
  }
  sessionvar_set('lang', $kieli);
}

function hae_istunnon_kieli() {
  global $DEFAULTLANG;
  return sessionvar_get('lang', $DEFAULTLANG);
}

function hae_istunnon_kieliid() {
  $kieli = hae_istunnon_kieli();
  return $kieli['kieliid'];
}

// Palauttaa 'ep�tosi', jos k��nn�kset ovat p��ll�, istuntoon on
// asetettuna kieli ja se ei ole oletuskieli.  Muutoin palauttaa
// 'tosi'.
function oletuskieli_kaytossa() {
  $lang = sessionvar_get('lang');
  if ( trans_tables_available() && 
       isset($lang['kieliid']) && $lang['kieliid'] > 1 ) {
    return false;
  }
  return true;
}

// Palauttaa 'tosi', mik�li tiedot tulee tallentaa oletuskielelle ja
// 'ep�tosi', jos k��nn�kseen.  Pys�htyy virheilmoitkseen, jos valitun
// kielen mukaista k��nn�st� tietojen tallentamiseen ei ole olemassa.
function tallennus_oletuskielella($taulu, $id) {
  if ( ! oletuskieli_kaytossa() ) {
    // Istuntoon merkitty kielivalinta
    $lang = sessionvar_get('lang');
    // Haetaan lista kielist�, joille on olemassa k��nn�s
    $kaannokset = qry_trans_avail($taulu, $id);
    if ( array_haskey($kaannokset, $lang['kieliid']) &&
	 $lang['kieliid'] > 1 ) {
      // Sessioon merkityn kielen mukainen k��nn�s l�ytyy ja se on
      // jotain muuta kuin oletuskieli, tallennus siihen.
      return false;
    } else {
      // K��nn�st� ei ole.  Puhalletaan peli poikki ja annetaan
      // virheilmoitus.
      die("Virhe:  Valittuna on kieli, jota vastaavaa k��nn�st� ".
	  "tallennettavalle '$taulu' taulun tietueelle ei ole luotu. ".
	  "Luo k��nn�s ja yrit� uudelleen.");
    }
  }
  return true;
}

function kaannos_olemassa($taulu, $id, $kieliid=0) {
  // Ellei kielt� ole annettu, k�ytet��n istuntoon asetettua kielt�
  if ( $kieliid < 1 ) 
    $kieliid = hae_istunnon_kieliid();

  // Oletuskieli ei ole k��nn�s
  if ( $kieliid == 1 ) return false; 
  // Haetaan lista kielist�, joille on olemassa k��nn�s             
  $kaannokset = qry_trans_avail($taulu, $id);
  return array_haskey($kaannokset, $kieliid);
}

// Kieliversiolinkkien luonti.
// Palauttaa merkkijonon " (kieli1, kieli2, ...)", jossa kieliX on
// $template, josta %d on korvattu $trans_avail taulukon avaimella ja
// %s arvolla.
function trans_links($trans_avail, $template='<a href="?lang=%d">%s</a>') {
  // Jos k��nn�ksi� ei ole, tarjotaan tyhj��
  if ( count($trans_avail) < 1 ) {
    return '';
  }
  // Tehd��n linkkilista
  $tmp = array();
  foreach ( $trans_avail as $kieliid => $koodi ) {
    $tmp[] = sprintf($template, $kieliid, $koodi);
  }
  return ' (' . join(', ', $tmp) . ')';
}

// Remove all rows from table which have column $idcol equal to
// another row having greater values in at least one of $kieliidcols.
function &filter_translations(&$table, $idcol, $kieliidcols) {
  // Array of id's which have been encountered
  $oldids = array();
  // Array of rowkeys indexed with corresponding id
  $oldrecs = array();
  foreach ( array_keys($table) as $rowkey ) { // rows-loop
    $id = $table[$rowkey][$idcol];
    if ( in_array($id, $oldids) ) {
      // encountered duplicate id, record having more translations
      // stays and the other is removed
      $oldrowkey = $oldrecs[$id];
      // records $table[$rowkey] and $table[$oldrowkey]
      foreach ( $kieliidcols as $kieliidcol ) { // kieliidcols-loop
	// $kieliidcol's value is either 1 (id of default language) or
	// something greater than 1 (id of requested language), so
	// with single test we know if later record has a translation
	// and the former doesn't.
	if ( $table[$rowkey][$kieliidcol] > $table[$oldrowkey][$kieliidcol] ) {
	  // The later record has a translation and the former
	  // doesn't.  The former is deleted.
	  unset($table[$oldrowkey]);
	  // Update array mapping id's to rowkeys
	  $oldrecs[$id] = $rowkey;
	  // and continue with next round of rows-loop.
	  continue 2;
	}
      }
      // The later record didn't have any translation not found from
      // the former.  The later is deleted.  No need to update id to
      // rowkey -mapping.
      unset($table[$rowkey]);
    } else {
      // new id, record it
      $oldids[] = $id;
      // and the mapping from id to rowkey
      $oldrecs[$id] = $rowkey;
    }
  }
  return $table;
}


?>