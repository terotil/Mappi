<?php
///////////////////////////////////////////////////////////////////////////////
// Tietokanta-abstraktio
// MySQL
define('DB_HUGEINT', 8388607);

// Tarvitaan kirjastoja
include_once('libseitti-php/decode_entities.function.php');

///////////////////////////////////////////////////////////////////////////////
// Tietokantayhteyden luonti
function connect_db($guest=false) {
  global $settings;
  $link = mysql_connect($settings['host'], 
			$settings[($guest?'guest':'').'user'], 
			$settings[($guest?'guest':'').'pw'])
    or die('<p>Error connecting, '. 
	   mysql_errno().' : '.mysql_error().$settings['emsg']);
  mysql_select_db($settings['db'], $link)
    or die('<p>Error selecting db, '. 
	   mysql_errno().' : '.mysql_error().$settings['emsg']);
  $settings['link'] = $link;
  return $link;
}

///////////////////////////////////////////////////////////////////////////////
// Tietokantayhteyden sulkeminen
function close_db() {
  global $settings;
  return mysql_close($settings['link']);
}

///////////////////////////////////////////////////////////////////////////////
// K‰yd‰‰n l‰pi annetut sarakkeet vastauksesta ja poistetaan lainaukset
///////////////////////////////////////////////////////////////////////////////
// $result  = Kyselyn tulos, joka pit‰‰ unquotata
// $columns = L‰pik‰yt‰vien sarakkeiden nimet
///////////////////////////////////////////////////////////////////////////////
function unquote(&$result, $columns) {
  // Tuodaan tulostaulukko referenssin‰ ja muutetaan alkuper‰ist‰
  foreach ( array_keys($result) as $rowkey ) {
    foreach ( $columns as $col ) {
      $result[$rowkey][$col] = stripslashes($result[$rowkey][$col]);
    }
  }
}

///////////////////////////////////////////////////////////////////////////////
// K‰yd‰‰n l‰pi annetut sarakkeet vastauksesta ja poistetaan lainaukset
///////////////////////////////////////////////////////////////////////////////
function sanitize_intlist($string) {
  return implode(',', array_map('intval', explode(',', $string)));
}

///////////////////////////////////////////////////////////////////////////////
// Suoritetaan kysely ja palautetaan sen vastaus
function qry($sql, $limit=-1, $offset=-1) {
  // Debug:
  // echo html_make_tag('pre', $sql);

  // Tarvitaan asetuksia
  global $settings;
  
  // Suoritetaan kysely, tietokantayhteyden oletetaan jo olevan p‰‰ll‰
  $result_set = mysql_query($sql, $settings['link']);
  if ( ! $result_set ) {
    mappi_log('Error', '"'.str_replace("\n", '\n', $sql).'"');
    die("<p>An error occured during SQL query.</p>\n".
	'<p>Query:<pre style="border: 1px solid black; background: #E0E0E0">'.
	htmlspecialchars($sql)."</pre></p>\n".
	'<p>Errorcode: '. mysql_errno().' : '.mysql_error() .'</p>'.
	$settings['emsg']);
  }

  $tmp_str = strtoupper(substr(' '.$sql, 0, 15));
  $is_edit_query = false;
  if ( strpos($tmp_str, 'SELECT') ) { $query='SELECT'; }
  if ( strpos($tmp_str, 'INSERT') ) { $is_edit_query = true; $query='INSERT'; }
  if ( strpos($tmp_str, 'UPDATE') ) { $is_edit_query = true; $query='UPDATE'; }
  if ( strpos($tmp_str, 'DELETE') ) { $is_edit_query = true; $query='DELETE'; }

  if ( !(strpos($tmp_str, 'SELECT') === false) ) {
    // Select -kyselyist‰ pistet‰‰n talteen kyselyn tuottama
    // kokonaisrivim‰‰r‰, vaikka palautettavien m‰‰r‰‰
    // rajoitettaisiinkin $limit ja $offset -parametreill‰.
    // $is_edit_query -tulosta ei voi k‰ytt‰‰, koska on
    // editointikyselyiden lis‰ksi olemassa muitakin kyselyit‰, jotka
    // eiv‰t anna tulosrivim‰‰r‰‰.
    $settings['lastrowcount'] = mysql_num_rows($result_set);
  } else {
    $settings['lastrowcount'] = -1;
  }

  $ret_value = array();
  if ( gettype($result_set) == 'resource' ) {

    if ( $offset > -1 and
	 $offset < $settings['lastrowcount'] ) {
      // Offset annettu, siirryt‰‰n valmiiksi sen verran askelia
      // eteenp‰in, eli palautetaan kyselyn tulos rivilt‰ $offset
      // alkaen.
      mysql_data_seek($result_set, $offset);
    }

    // Looppi, jossa tehd‰‰n tuloksesta php-taulukko
    if ( $limit > -1 ) {
      // Palautettavien tulosten m‰‰r‰ on rajoitettu.
      $i = 0;
      while ( ($row = mysql_fetch_array($result_set, MYSQL_ASSOC)) and
	      ($i++ < $limit) )
	array_push($ret_value, $row);
    } else {
      // Palautettavien tulosten m‰‰r‰‰ ei ole rajoitettu.  Haetaan
      // kaikki.
      while ( $row = mysql_fetch_array($result_set, MYSQL_ASSOC) )
	array_push($ret_value, $row);
    }

    // Vapautetaan vastaus
    mysql_free_result($result_set);

  }

  // Jos kyseess‰ oli muuttava kysely, kutsutaan lis‰ksi
  // muutosfunktiota ja lokitusta
  if ( $is_edit_query ) {
    database_changed();
    switch ( $query ) {
    case 'INSERT':
      $msg = preg_replace('/^.*insert\s+(into\s+)?(\w+)\s.*$/si', 'table $2',
			  $sql);
      $msg .= ' id '.mysql_insert_id($settings['link']);
      break;
    case 'DELETE':
      $msg = preg_replace('/^.*delete\s+(from\s+)?(\w+)\s.*where\s+(.*)$/si', 
			  'table $2 cond $3', $sql);
      break;
    case 'UPDATE':
      $msg = preg_replace('/^.*update\s+(\w+)\s.*where\s+(.*)$/si',
			  'table $1 cond $2', $sql);
      break;
    }
    mappi_log($query, preg_replace("/\s+/", ' ', $msg));
  }

  // Palautetaan taulukko
  return $ret_value;
}

///////////////////////////////////////////////////////////////////////////////
// Suoritetaan kysely ja palautetaan viimeksi tehdyn insertin antama id
function qry_insert($sql) {
  global $settings;
  qry($sql);
  return mysql_insert_id($settings['link']);
}

///////////////////////////////////////////////////////////////////////////////
// Tietokannan tila
function print_dbstatus() {
  // Globaalit asetukset
  global $settings;

  // Haetaan lista tauluista
  $tables = qry("show tables");
  // Muodostetaan taulukko vastauksen sarakenimist‰ (koska eri mysql:n
  // versioissa "show tables" toimii v‰h‰n eri tavalla).
  $keys = array_keys($tables[0]);
  $rivimaarat = array();
  foreach ( $tables as $taulu ) {
    // Vastauksen sarakkeista ensimm‰inen (numero 0) sarake on taulun
    // nimi.
    $tablename = $taulu[$keys[0]];
    // Lasketaan taulun rivit
    $rowcount = qry('select count(*) c from '.$tablename);
    $rivimaarat[] = array("tab" => $tablename, "rowc" => $rowcount[0]['c']);
  }
  $names = array('tab'=>'Taulu', 'rowc'=>'Rivej‰');
  $code = array('tab' =>'', 
		'rowc'=>'"<td align=\"right\">".$row[$key]."&nbsp;</td>";');
  print_as_table($rivimaarat, 'class="qtable"', $names, '', $code);
}

///////////////////////////////////////////////////////////////////////////////
// Tietokantaenginen versio
function dbengine_version() {
  $dbev = qry("SELECT VERSION() AS ver");
  return $dbev[0]['ver'];
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen tiedot
function qry_kohde($id) {
  $id = intval($id);
  if ( ! trans_tables_available() ) {
    // Ei k‰‰nnˆstauluja
    $kohde = qry("
    SELECT kohde.*, UNIX_TIMESTAMP(kohde.leima) AS leima_unix, 
      ryhma.nimi AS ryhma_nimi
    FROM kohde LEFT JOIN ryhma USING (ryhmaid)
    WHERE kohde.kohdeid = $id");
  } else {
    // K‰‰nnˆstaulut lˆytyy
    $kieliid = hae_istunnon_kieliid();
    $kohde = qry("
    SELECT DISTINCT k.kohdeid, k.ryhmaid, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi, k.nimi) AS nimi, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi2, k.nimi2) AS nimi2, 
      IF(tk.kieliid <=> {$kieliid}, tk.data, k.data) AS data, 
      IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima) AS leima,
      k.mime,
      UNIX_TIMESTAMP(IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima)) AS leima_unix,
      IF(tk.kieliid <=> {$kieliid}, kieli.nimi, 'suomi') AS kieli,
      IF(tr.kieliid <=> {$kieliid}, tr.nimi, r.nimi) AS ryhma_nimi,
      IF(tk.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tk_kieliid,
      IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid
    FROM kohde k
      LEFT JOIN trans_kohde tk USING (kohdeid)
      LEFT JOIN ryhma r ON k.ryhmaid = r.ryhmaid
      LEFT JOIN trans_ryhma tr ON k.ryhmaid = tr.ryhmaid
      LEFT JOIN kieli ON tk.kieliid = kieli.kieliid
    WHERE
      k.kohdeid = $id");

    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($kohde, 'kohdeid', 
				     array('tk_kieliid', 'tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $kohde =& $filtered;
  }

  // Palautetaan vastaus
  return array_pop($kohde);
}

///////////////////////////////////////////////////////////////////////////////
// Kohdelista
function qry_kohde_lista($rid=-1, $txt='', $limit=15, $offset=0, $orderby='nimi2, nimi') {
  global $settings;
  // Oletukseksi joku ryhm‰, johon on oikeudet
  if ( $rid == -1 ) {
    $read = sessionvar_get('read');
    $rid = $read[0];
  }
  if ( $rid == '' ) { $rid = '-1'; }

  if ( ! trans_tables_available() ) {
    // Ei k‰‰nnˆstauluja
    // Haetaan kohdelista
    $kohteet = qry("
    SELECT *, UNIX_TIMESTAMP(leima) AS leima_unix
    FROM   kohde
    WHERE
      ryhmaid IN ($rid) AND
      UCASE(CONCAT(nimi,' ',nimi2)) LIKE UCASE('%$txt%')
    ORDER BY $orderby", $limit, $offset);
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku m‰‰ritellyst‰ kielest‰
    $kieliid = hae_istunnon_kieliid();
    // Haetaan kohdelista
    $kohteet = qry("
    SELECT DISTINCT k.kohdeid, k.ryhmaid, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi, k.nimi) AS nimi, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi2, k.nimi2) AS nimi2, 
      IF(tk.kieliid <=> {$kieliid}, tk.data, k.data) AS data, 
      IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima) AS leima, 
      k.mime,
      UNIX_TIMESTAMP(IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima)) AS leima_unix,
      IF(tk.kieliid <=> {$kieliid}, kieli.nimi, 'suomi') AS kieli,
      IF(tk.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tk_kieliid
    FROM kohde k LEFT JOIN trans_kohde tk USING (kohdeid) 
      LEFT JOIN kieli USING (kieliid)
    WHERE
      k.ryhmaid IN ($rid)
    HAVING UCASE(CONCAT(nimi,' ',nimi2)) LIKE UCASE('%$txt%')
    ORDER BY $orderby", -1, -1 /* $limit, $offset */);

    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($kohteet, 'kohdeid', array('tk_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $kohteet =& $filtered;
    $settings['lastrowcount'] = count($kohteet);

    // Limit ja offset
    if ( $limit > -1 or $offset > -1 ) {
      $kohteet = 
	array_slice($kohteet, ($offset>-1)?$offset:0, 
		    ($limit>-1)?$limit:PHP_INT_MAX);
    }
  }

  // Palautetaan vastaus
  return $kohteet;
}
///////////////////////////////////////////////////////////////////////////////
// Kohdelista rajattuna tietyn liitetiedon tekstin perusteella
function qry_kohde_lista_liiterajaus($rid=-1, $lid=-1, $ltxt='', $txt='', $limit=15, $offset=0, $orderby='nimi2, nimi') {
  // Oletukseksi joku ryhm‰, johon on oikeudet
  if ( $rid == -1 ) {
    $read = sessionvar_get('read');
    $rid = $read[0];
  }
  if ( $rid == '' ) { $rid = '-1'; }

  if ( ! trans_tables_available() ) {
    // K‰‰nnˆstauluja ei ole, haku oletuskielest‰.
    // Haetaan kohdelista
    $kohteet = qry("
    SELECT k.*
    FROM   kohde k LEFT JOIN kohde_liite kl USING(kohdeid)
    WHERE
      k.ryhmaid IN ($rid) AND
      kl.liiteid IN ($lid) AND
      UCASE(CONCAT(nimi,' ',nimi2)) LIKE UCASE('%$txt%') AND
      UCASE(kl.data) LIKE UCASE('%$ltxt%')
    ORDER BY $orderby", $limit, $offset);
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku k‰ytt‰en m‰‰ritelty‰ kielt‰
    $kieliid = hae_istunnon_kieliid();
    // Haetaan kohdelista
    $kohteet = qry("
    SELECT DISTINCT k.kohdeid, k.ryhmaid, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi, k.nimi) AS nimi, 
      IF(tk.kieliid <=> {$kieliid}, tk.nimi2, k.nimi2) AS nimi2, 
      IF(tk.kieliid <=> {$kieliid}, tk.data, k.data) AS data, 
      IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima) AS leima, 
      k.mime,
      UNIX_TIMESTAMP(IF(tk.kieliid <=> {$kieliid}, tk.leima, k.leima)) AS leima_unix,
      IF(tk.kieliid <=> {$kieliid}, kieli.nimi, 'suomi') AS kieli,
      IF(tk.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tk_kieliid,
      IF(tkl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tkl_kieliid
    FROM kohde k 
       LEFT JOIN trans_kohde tk USING(kohdeid)
       LEFT JOIN kohde_liite kl USING(kohdeid)
       LEFT JOIN trans_kohde_liite tkl USING(kohde_liiteid)
       LEFT JOIN kieli ON tk.kieliid = kieli.kieliid
    WHERE
      k.ryhmaid IN ($rid) AND
      kl.liiteid IN ($lid) AND
      UCASE(IF(tkl.kieliid <=> {$kieliid}, tkl.data, kl.data))
        LIKE UCASE('%$ltxt%')
    HAVING 
      UCASE(CONCAT(nimi,' ',nimi2)) LIKE UCASE('%$txt%')
    ORDER BY $orderby", $limit, $offset);

    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($kohteet, 'kohdeid', 
				     array('tk_kieliid', 'tkl_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $kohteet =& $filtered;
  }
  // Palautetaan kohdelista
  return $kohteet;
}

///////////////////////////////////////////////////////////////////////////////
// Kohdeliitteen tiedot
function qry_kohde_liite($kohde_liiteid) {
  if ( ! trans_tables_available() ) {
    // K‰‰nnˆstauluja ei ole, haku oletuskielest‰.
    $kohde_liite = qry("
    SELECT kohde_liite.*, 
           kohde.ryhmaid AS kohde_ryhmaid,
           liite.ryhmaid AS liite_ryhmaid,
           liite.nimi AS liite_nimi
    FROM   kohde_liite, kohde, liite
    WHERE
      kohde_liite.kohde_liiteid = $kohde_liiteid AND
      kohde_liite.liiteid = liite.liiteid AND
      kohde_liite.kohdeid = kohde.kohdeid");
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku k‰ytt‰en m‰‰ritelty‰ kielt‰
    // Oletuskieli on aina kieliid = 1
    $kieliid = hae_istunnon_kieliid();
    $kohde_liite = qry("
    SELECT DISTINCT kl.kohde_liiteid, kl.jarjestys, kl.kohdeid, kl.liiteid,
      IF(tkl.kieliid <=> {$kieliid}, tkl.data, kl.data) AS data,
      IF(tkl.kieliid <=> {$kieliid}, tkl.leima, kl.leima) AS leima,
      kl.mime,
      k.ryhmaid AS kohde_ryhmaid,
      l.ryhmaid AS liite_ryhmaid,
      IF(tl.kieliid <=> {$kieliid}, tl.nimi, l.nimi) AS liite_nimi,
      IF(tkl.kieliid <=> {$kieliid}, kieli.nimi, 'suomi') AS kieli,
      IF(tl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tl_kieliid,
      IF(tkl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tkl_kieliid
    FROM   kohde_liite kl 
      LEFT JOIN kohde k USING(kohdeid)
      LEFT JOIN liite l ON l.liiteid = kl.liiteid
      LEFT JOIN trans_liite tl ON l.liiteid = kl.liiteid
      LEFT JOIN trans_kohde_liite tkl ON kl.kohde_liiteid = tkl.kohde_liiteid
      LEFT JOIN kieli ON tkl.kieliid = kieli.kieliid
    WHERE
      kl.kohde_liiteid = $kohde_liiteid");

    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($kohde_liite, 'kohde_liiteid', 
				     array('tl_kieliid', 'tkl_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $kohde_liite =& $filtered;
  }

  return array_pop($kohde_liite);
}

///////////////////////////////////////////////////////////////////////////////
// Lista kohteen liitetiedoista
function qry_kohde_liite_lista($kohdeid, $orderby='l.ryhmaid, kl.jarjestys', $hidden='-1') {
  if ( ! trans_tables_available() ) {
    // K‰‰nnˆstauluja ei ole, haku oletuskielest‰.
    $kohde_liite = qry("
    SELECT kl.*, 
           k.ryhmaid AS kohde_ryhmaid,
           l.ryhmaid AS liite_ryhmaid,
           l.nimi AS liite_nimi
    FROM kohde_liite kl LEFT JOIN kohde k USING(kohdeid)
      LEFT JOIN liite l ON kl.liiteid = l.liiteid
    WHERE
      k.kohdeid = $kohdeid AND
      kl.liiteid NOT IN ($hidden)
   ORDER BY $orderby");
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku k‰ytt‰en m‰‰ritelty‰ kielt‰
    $kieliid = hae_istunnon_kieliid();
    $kohde_liite = qry("
    SELECT DISTINCT kl.kohde_liiteid, kl.jarjestys, kl.kohdeid, kl.liiteid,
      IF(tkl.kieliid <=> {$kieliid}, tkl.data, kl.data) AS data,
      IF(tkl.kieliid <=> {$kieliid}, tkl.leima, kl.leima) AS leima,
      kl.mime,
      k.ryhmaid AS kohde_ryhmaid,
      l.ryhmaid AS liite_ryhmaid,
      IF(tl.kieliid <=> {$kieliid}, tl.nimi, l.nimi) AS liite_nimi,
      IF(tkl.kieliid <=> {$kieliid}, kieli.nimi, 'suomi') AS kieli,
      IF(tkl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tkl_kieliid,
      IF(tl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tl_kieliid
    FROM   kohde_liite kl 
      LEFT JOIN kohde k USING(kohdeid)
      LEFT JOIN liite l ON l.liiteid = kl.liiteid
      LEFT JOIN trans_liite tl ON l.liiteid = kl.liiteid
      LEFT JOIN trans_kohde_liite tkl ON kl.kohde_liiteid = tkl.kohde_liiteid
      LEFT JOIN kieli ON tkl.kieliid = kieli.kieliid
    WHERE k.kohdeid = $kohdeid AND 
      kl.liiteid NOT IN ($hidden)
    ORDER BY $orderby");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($kohde_liite, 'kohde_liiteid', 
				     array('tkl_kieliid', 'tl_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $kohde_liite =& $filtered;
  }

  return $kohde_liite;
}


///////////////////////////////////////////////////////////////////////////////
// Lista kohdeliitteen viittauksista
function qry_viittaus_lista($kohde_liiteid, $orderby='v.jarjestys') {
  if ( ! trans_tables_available() ) {
    // K‰‰nnˆstauluja ei ole, haku oletuskielest‰.
    $viittaukset = qry("
    SELECT v.kohdeid AS kohdeid,
           CONCAT(k.nimi, ' ', k.nimi2) AS kohde_nimi,
           k.ryhmaid AS kohde_ryhmaid,
           r.nimi AS ryhma_nimi,
           k.nimi AS kn, r.kohde_nimi AS rkn,
           k.nimi2 AS kn2, r.kohde_nimi2 AS rkn2
    FROM   viittaus v, kohde k, ryhma r
    WHERE  v.kohde_liiteid = $kohde_liiteid AND
           v.kohdeid = k.kohdeid AND
           k.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku k‰ytt‰en m‰‰ritelty‰ kielt‰
    $kieliid = hae_istunnon_kieliid();
    $viittaukset = qry("
    SELECT DISTINCT v.kohdeid AS kohdeid,
           IF(tk.kieliid <=> {$kieliid}, CONCAT(tk.nimi, ' ', tk.nimi2), 
              CONCAT(k.nimi, ' ', k.nimi2)) AS kohde_nimi,
           k.ryhmaid AS kohde_ryhmaid,
           IF(tr.kieliid <=> {$kieliid}, tr.nimi, r.nimi) AS ryhma_nimi,
           IF(tk.kieliid <=> {$kieliid}, tk.nimi, k.nimi) AS kn, 
           IF(tk.kieliid <=> {$kieliid}, tk.nimi2, k.nimi2) AS kn2, 
           IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi, r.kohde_nimi) AS rkn,
           IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi2,r.kohde_nimi2) AS rkn2,
           IF(tk.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tk_kieliid,
           IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid
    FROM   viittaus v, kohde k LEFT JOIN trans_kohde tk USING(kohdeid), 
           ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
    WHERE  v.kohde_liiteid = $kohde_liiteid AND
           v.kohdeid = k.kohdeid AND
           k.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
    // Ylim‰‰r‰iset pois
    //  yhden liitetiedon viittaukset, joten kohdeid on yksik‰sitteinen 
    $filtered =& filter_translations($viittaukset, 'kohdeid', 
				     array('tk_kieliid', 'tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $viittaukset =& $filtered;
  }

  return $viittaukset;
}

///////////////////////////////////////////////////////////////////////////////
// Lista viittauksista kohteeseen
function qry_inv_viittaus_lista($kohdeid, $orderby='l.nimi, k.nimi2, k.nimi') {
  if ( ! trans_tables_available() ) {
    // K‰‰nnˆstauluja ei ole, haku oletuskielest‰.
    $viittaukset = qry("
    SELECT k.kohdeid AS kohdeid,
           CONCAT(k.nimi, ' ', k.nimi2) AS kohde_nimi,
           k.ryhmaid AS kohde_ryhmaid,
           l.nimi AS liite_nimi,
           k.nimi AS kn, r.kohde_nimi AS rkn,
           k.nimi2 AS kn2, r.kohde_nimi2 AS rkn2
    FROM   viittaus v, kohde_liite kl, liite l, kohde k, ryhma r
    WHERE  v.kohdeid = $kohdeid AND
           v.kohde_liiteid = kl.kohde_liiteid AND
           kl.liiteid = l.liiteid AND
           kl.kohdeid = k.kohdeid AND
           k.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
  } else {
    // K‰‰nnˆstaulut lˆytyv‰t, haku k‰ytt‰en m‰‰ritelty‰ kielt‰
    $kieliid = hae_istunnon_kieliid();
    $viittaukset = qry("
    SELECT DISTINCT k.kohdeid AS kohdeid,
           IF(tk.kieliid <=> {$kieliid}, CONCAT(tk.nimi, ' ', tk.nimi2), 
              CONCAT(k.nimi, ' ', k.nimi2)) AS kohde_nimi,
           k.ryhmaid AS kohde_ryhmaid,
           IF(tl.kieliid <=> {$kieliid}, tl.nimi, l.nimi) AS liite_nimi,
           IF(tk.kieliid <=> {$kieliid}, tk.nimi, k.nimi) AS kn, 
           IF(tk.kieliid <=> {$kieliid}, tk.nimi2, k.nimi2) AS kn2, 
           IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi, r.kohde_nimi) AS rkn,
           IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi2,r.kohde_nimi2) AS rkn2,
           IF(tl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tl_kieliid,
           IF(tk.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tk_kieliid,
           IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid,
           kl.kohde_liiteid
    FROM   viittaus v LEFT JOIN kohde_liite kl USING(kohde_liiteid)
           LEFT JOIN liite l USING(liiteid) 
           LEFT JOIN trans_liite tl USING(liiteid),
           kohde k LEFT JOIN trans_kohde tk USING(kohdeid), 
           ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
    WHERE  v.kohdeid = $kohdeid AND
           kl.kohdeid = k.kohdeid AND
           k.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
    // Ylim‰‰r‰iset pois
    //  viittaukset yhteen kohteeseen, joten kohde_liiteid on yksik‰sitteinen
    $filtered =& filter_translations($viittaukset, 'kohde_liiteid', 
			 array('tl_kieliid', 'tk_kieliid', 'tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $viittaukset =& $filtered;
  }

  return $viittaukset;
}

///////////////////////////////////////////////////////////////////////////////
// Jakson tiedot
function qry_jakso($jaksoid) {
  $jakso = qry("
    SELECT kohde_liiteid,
           jaksoid,
           UNIX_TIMESTAMP(alku) AS alku,
           UNIX_TIMESTAMP(loppu) AS loppu,
           type
    FROM   jakso
    WHERE  jaksoid IN ($jaksoid)");
  if ($jakso[0]['alku'] ==0) $jakso[0]['alku']  = TIMESTAMP_MINUSINF;
  if ($jakso[0]['loppu']==0) $jakso[0]['loppu'] = TIMESTAMP_PLUSINF;
  return $jakso[0];
}

///////////////////////////////////////////////////////////////////////////////
// Lista kohdeliitteen jaksoista
function qry_jakso_lista($kohde_liiteid, $orderby='type, alku') {
  $jaksot = qry("
    SELECT kohde_liiteid,
           jaksoid,
           UNIX_TIMESTAMP(alku) AS alku,
           UNIX_TIMESTAMP(loppu) AS loppu,
           type
    FROM   jakso
    WHERE  kohde_liiteid = $kohde_liiteid
    ORDER BY $orderby");
  foreach ( array_keys($jaksot) as $key ) {
    if ($jaksot[$key]['alku'] ==0) $jaksot[$key]['alku']  = TIMESTAMP_MINUSINF;
    if ($jaksot[$key]['loppu']==0) $jaksot[$key]['loppu'] = TIMESTAMP_PLUSINF;
  }
  return $jaksot;
}

///////////////////////////////////////////////////////////////////////////////
// Ryhm‰n tiedot
function qry_ryhma($rid) {
  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $tmp_ryhma = qry("
    SELECT * 
    FROM ryhma 
    WHERE ryhmaid = $rid");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $tmp_ryhma = qry("
    SELECT DISTINCT r.ryhmaid,
      IF(tr.kieliid <=> {$kieliid}, tr.nimi, r.nimi) AS nimi,
      IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi, r.kohde_nimi) AS kohde_nimi,
      IF(tr.kieliid <=> {$kieliid}, tr.kohde_nimi2, r.kohde_nimi2) AS kohde_nimi2,
      IF(tr.kieliid <=> {$kieliid}, tr.kohde_data, r.kohde_data) AS kohde_data,
      IF(tr.kieliid <=> {$kieliid}, tr.liite_nimi, r.liite_nimi) AS liite_nimi,
      IF(tr.kieliid <=> {$kieliid}, tr.kohde_liite_data, r.kohde_liite_data) AS kohde_liite_data,
      IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid
    FROM ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
    WHERE r.ryhmaid = $rid");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($tmp_ryhma, 'ryhmaid', 
			 array('tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $tmp_ryhma =& $filtered;
  }

  return array_pop($tmp_ryhma);
}

///////////////////////////////////////////////////////////////////////////////
// Ryhm‰lista
function qry_ryhma_lista($txt='', $limit=15, $offset=0, $rights='read') {
  // Haetaan ryhm‰lista sen mukaan halutaanko ryhm‰t,
  // jotka on oikeus vain n‰hd‰, vai ryhm‰t, joita on oikeus 
  // myˆs muokata
  if ( !in_array($rights, array('write', 'read')) ) $rights = 'write';
  $acl = join(',', sessionvar_get($rights));
  if ( $acl == '' ) {
    $acl = '-1';
  }

  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $ryhmat = qry("
    SELECT   ryhmaid, nimi
    FROM     ryhma
    WHERE
     ryhmaid IN ($acl) AND
     nimi LIKE '%$txt%'
    ORDER BY nimi", $limit, $offset);
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $ryhmat = qry("
    SELECT DISTINCT r.ryhmaid,
      IF(tr.kieliid <=> {$kieliid}, tr.nimi, r.nimi) AS nimi,
      IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid,
      IF(tr.kieliid <=> {$kieliid}, k.nimi, 'suomi') AS kieli
    FROM ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
      LEFT JOIN kieli k USING(kieliid)
    WHERE r.ryhmaid IN ($acl)
    HAVING nimi LIKE '%$txt%' ORDER BY nimi", $limit, $offset);
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($ryhmat, 'ryhmaid', array('tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $ryhmat =& $filtered;
  }

  // Palautetaan tulos
  return $ryhmat;
}

///////////////////////////////////////////////////////////////////////////////
// Ryhm‰lista
function qry_ryhma_lista_all() {
  // Haetaan ryhm‰lista

  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $ryhmat = qry("
    SELECT   ryhmaid, nimi
    FROM     ryhma
    ORDER BY nimi");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $ryhmat = qry("
    SELECT DISTINCT r.ryhmaid,
      IF(tr.kieliid <=> {$kieliid}, tr.nimi, r.nimi) AS nimi,
      IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid
    FROM ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
    ORDER BY nimi");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($ryhmat, 'ryhmaid', array('tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $ryhmat =& $filtered;
  }

  // Poistetaan lainaukset
  //  unquote($ryhmat, array('nimi'));

  // Palautetaan tulos
  return $ryhmat;
}


///////////////////////////////////////////////////////////////////////////////
// Liitteen tiedot
function qry_liite($liiteid) {
  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $liite = qry("
    SELECT *
    FROM   liite
    WHERE  liiteid = $liiteid");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $liite = qry("SELECT DISTINCT l.liiteid, l.ryhmaid, 
                    IF(tl.kieliid <=> {$kieliid}, tl.nimi, l.nimi) AS nimi,
                    IF(tl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tl_kieliid
                  FROM liite l LEFT JOIN trans_liite tl USING(liiteid)
                  WHERE l.liiteid = $liiteid");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($liite, 'liiteid', array('tl_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $liite =& $filtered;
  }

  // Poistetaan lainaukset
  //  unquote($liite, array('nimi'));

  return array_pop($liite);
}

///////////////////////////////////////////////////////////////////////////////
// Liitelista
function qry_liite_lista($haku='', $rights='read', $orderby='ryhma_nimi, nimi') {
  // Tarkistetaan, ettei anneta humpuukiarvoja ja tehd‰‰n inlist
  if ( !in_array($rights, array('write', 'read')) ) $rights = 'write';
  $acl = join(',', sessionvar_get($rights));

  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $liitteet = qry("
    SELECT l.*, r.nimi AS ryhma_nimi,
           CONCAT(r.nimi, ' / ', l.nimi) AS ryhma_liite_nimi
    FROM   liite l, ryhma r
    WHERE  UCASE(l.nimi) LIKE UCASE('%$haku%') AND
           l.ryhmaid IN ($acl) AND
           l.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $liitteet = qry("
    SELECT DISTINCT l.liiteid, l.ryhmaid,
      IF(tl.kieliid<=>{$kieliid}, tl.nimi, l.nimi) AS nimi,
      IF(tr.kieliid<=>{$kieliid}, tr.nimi, r.nimi) AS ryhma_nimi,
      CONCAT(IF(tr.kieliid<=>{$kieliid}, tr.nimi, r.nimi), ' / ', 
             IF(tl.kieliid<=>{$kieliid}, tl.nimi, l.nimi)) AS ryhma_liite_nimi,
      IF(tl.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tl_kieliid,
      IF(tr.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tr_kieliid
    FROM   liite l LEFT JOIN trans_liite tl USING(liiteid), 
           ryhma r LEFT JOIN trans_ryhma tr USING(ryhmaid)
    WHERE  UCASE(IF(tl.kieliid<=>{$kieliid}, tl.nimi, l.nimi)) 
             LIKE UCASE('%$haku%') AND
           l.ryhmaid IN ($acl,-1) AND
           l.ryhmaid = r.ryhmaid
    ORDER BY $orderby");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($liitteet, 'liiteid', 
				     array('tl_kieliid', 'tr_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $liitteet =& $filtered;
  }

  return $liitteet;
}

///////////////////////////////////////////////////////////////////////////////
// Tiedostolista
function qry_tiedosto_lista($kohde_liiteid) {
  if ( ! trans_tables_available() ) {
    // ilman kieliversiointia
    $tiedostot = qry("
      SELECT tiedostoid, nimi, LENGTH(data) AS koko, mime
      FROM tiedosto WHERE kohde_liiteid = $kohde_liiteid");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $tiedostot = qry("
      SELECT DISTINCT t.tiedostoid, 
        IF(tt.kieliid <=> {$kieliid}, tt.nimi, t.nimi) AS nimi, 
        LENGTH(IF(tt.kieliid <=> {$kieliid}, tt.data, t.data)) AS koko,
        IF(tt.kieliid <=> {$kieliid}, tt.mime, t.mime) AS mime, 
        IF(tt.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tt_kieliid
      FROM tiedosto t LEFT JOIN trans_tiedosto tt USING(tiedostoid)
      WHERE t.kohde_liiteid = $kohde_liiteid");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($tiedostot, 'tiedostoid', 
				     array('tt_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $tiedostot =& $filtered;
  }

  return $tiedostot;
}

///////////////////////////////////////////////////////////////////////////////
// Tiedoston haku tietokannasta
function qry_tiedosto($tiedostoid) {
  if ( ! trans_tables_available() ) {
    $tiedosto = qry("SELECT * FROM tiedosto WHERE tiedostoid = $tiedostoid");
  } else {
    // kieliversioituna
    $kieliid = hae_istunnon_kieliid();
    $tiedosto = qry("
      SELECT DISTINCT t.tiedostoid, t.kohde_liiteid,
        IF(tt.kieliid <=> {$kieliid}, tt.data, t.data) AS data,
        IF(tt.kieliid <=> {$kieliid}, tt.nimi, t.nimi) AS nimi,
        IF(tt.kieliid <=> {$kieliid}, tt.mime, t.mime) AS mime,
        IF(tt.kieliid <=> {$kieliid}, {$kieliid}, 1) AS tt_kieliid
      FROM tiedosto t LEFT JOIN trans_tiedosto tt USING(tiedostoid)
      WHERE t.tiedostoid = $tiedostoid");
    // Ylim‰‰r‰iset pois
    $filtered =& filter_translations($tiedosto, 'tiedostoid', 
				     array('tt_kieliid'));
    // Suora binding ei onnistu, pit‰‰ tehd‰ mutkan kautta
    $tiedosto =& $filtered;
  }
  // Poistetaan lainaukset
  //  unquote($tiedosto, array('nimi','mime'));
  return array_pop($tiedosto);
}

///////////////////////////////////////////////////////////////////////////////
// Tiedoston lis‰‰minen tietokantaan
// FIXME: tiedostojen kieliversioinnit?
function qry_tiedosto_insert($kohde_liiteid, $data, $nimi, $mime) {
  // Merkataan vastaava kohde_liite muutetuksi
  touch_kohde_liite($kohde_liiteid);
  // Lis‰t‰‰n tiedosto
  return qry_insert("
    INSERT tiedosto
    VALUES (0, $kohde_liiteid, 
            '$data', 
            '$nimi',
            '$mime')");
}

///////////////////////////////////////////////////////////////////////////////
// Tiedoston poisto
function qry_tiedosto_delete($tiedostoid) {
  if ( $tiedostoid=='' ) return false;
  // Haetaan tiedostoa vastaava kohde_liiteid
  $tiedosto = qry("SELECT kohde_liiteid FROM tiedosto ".
		  "WHERE tiedostoid = $tiedostoid");
  if ( trans_tables_available() ) {
    // K‰‰nnˆkset
    qry("DELETE FROM trans_tiedosto WHERE tiedostoid = $tiedostoid");
  }
  // Poistetaan tiedosto
  $ret = qry("DELETE FROM tiedosto ".
             "WHERE tiedostoid = $tiedostoid");
  // Merkataan vastaava kohde_liite muutetuksi
  touch_kohde_liite($tiedosto[0]['kohde_liiteid']);
  return $ret;
}

///////////////////////////////////////////////////////////////////////////////
// Yhden kirjautumistiedon haku
///////////////////////////////////////////////////////////////////////////////
// $log = kohde_liite -taulun login -liitteen tiedot listassa, 
//        'klid'=>kohde_liiteid, 'kohdeid'=>kohdeid, 'login'=>data
//
// K‰‰nnˆksi‰ ei oteta kirjautumistietoja k‰sitelless‰ huomioon.
// K‰ytt‰j‰oikeuksm‰‰ritykset haetaan aina oletuskielest‰.
function qry_login(&$log) {
    // Login -tiedot
  include_once('MAPPI/html.php');
    foreach ( array('uname','pw','ntdomain') as $varname ) {
      $$varname = trim(html_extract_tag_contents($log['login'], $varname));
    }
    
    // Tarkistetaan, ett‰ tiedoissa on j‰rke‰
    if ( $uname=='' or ($pw=='' and $ntdomain=='') ) {
      return false;
    }

    // Oikeuslistat
    $oikeudet = qry("
      SELECT kohde.data AS oik
      FROM   viittaus, kohde
      WHERE  viittaus.kohde_liiteid = ".$log['klid']." AND
             viittaus.kohdeid = kohde.kohdeid");
    // Poistetaan lainaukset
    //    unquote($oikeudet, array('oik'));

    // FIXME: Jos yhdell‰ login -liitteell‰ on viittauksia moneen
    // oikeusryhm‰‰n, viittauksista k‰sitell‰‰n vain ensimm‰inen
    list($read, $write, $ryhma) = parse_rights($oikeudet[0]['oik']);

    // Palautetaan haetut tiedot taulukossa
    return array('kohdeid'  => $log['kohdeid'],
		 'klid'     => $log['klid'],
		 'uname'    => $uname,
		 'pw'       => $pw,
		 'ntdomain' => $ntdomain,
		 'read'     => $read,
		 'write'    => $write,
		 'ryhma'    => $ryhma);
}

///////////////////////////////////////////////////////////////////////////////
// Kirjautumistietojen kysely
//
// Kirjautumistiedot haetaan aina oletuskielest‰, ei koskaan
// k‰‰nnˆksist‰.  Login-liitetiedon k‰‰nnˆkset j‰tet‰‰n kirjautumista
// teht‰ess‰ huomiotta.
function qry_login_lista() {
  $login = qry("
    SELECT kohde_liite.kohde_liiteid AS klid,
           kohde_liite.kohdeid AS kohdeid,
           kohde_liite.data AS login
    FROM   kohde_liite
    WHERE  kohde_liite.liiteid = 0");

  // Poistetaan lainaukset
  //  unquote($login, array('login'));

  $retval = array();

  foreach ( $login as $log ) {
    $tmp = qry_login($log);
    if ( $tmp != false ) {
      $retval[] = $tmp;
    }
  }

  // Indeksoidaan k‰ytt‰j‰nimen perusteella
  return array_fold($retval, array('uname'));
}

///////////////////////////////////////////////////////////////////////////////
// Salasanan vaihto
//
// K‰‰nnˆksi‰ ei oteta kirjautumistietoja k‰sitelless‰ huomioon.
function passwd($kohde_liiteid, $old, $new1='', $new2='') {
  $kohde_liite = qry_kohde_liite($kohde_liiteid);

  // Salasana vaihdetaan tietenkin vain login -tietoon
  if ( $kohde_liite['liiteid'] != 0 ) return false;

  // Napataan loginin datakent‰st‰ salasana ja muu roina
  eregi("(.*<pw>)(.*)(</pw>.*)", $kohde_liite['data'], $match);

  // Jos vanha salasana meni oikein ja ehdotelmat ovat samat, niin
  // eikun vaihtamaan vain

  if ( $match[2] == md5($old) and $new1 == $new2 ) {
    // Otetaan valittu kieli talteen ja vaihdetaan v‰liaikaisesti
    // oletuskieleen
    $prev_lang = sessionvar_get('lang');
    global $DEFAULTLANG;
    sessionvar_set('lang', $DEFAULTLANG);
    // P‰ivitet‰‰n login-liitetieto
    qry_kohde_liite_update($kohde_liiteid, 
			   addslashes($match[1].strval(md5($new1)).$match[3]),
			   'text/x-accesscontrol');
    // Palataan takaisin valittuun kieleen
    sessionvar_set('lang', $prev_lang);
    mappi_log('Passwd', 'Password changed.');
    return true;
  }
  return false;
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen tietojen p‰ivitys
function qry_kohde_update($kohdeid, $ryhmaid, $nimi, $nimi2, $data, $mime) {
  // Tarkistetaan, ett‰ on annettu tarvittavat tiedot
  if ( ($kohdeid == '') or ($ryhmaid == '') ) return false;
  $kohdeid = intval($kohdeid);
  $ryhmaid = intval($ryhmaid);
  if ( ($kohdeid < 0) or ($ryhmaid < 0) ) return false;

  // Puretaan entiteettikoodaukset
  $nimi = decode_entities($nimi);
  $nimi2 = decode_entities($nimi2);
  $data = decode_entities($data);

  if ( tallennus_oletuskielella('kohde', $kohdeid) ) {
    // Oletuskieli k‰ytˆss‰, tallennus pelk‰st‰‰n kohde-tauluun.
    qry("
    UPDATE kohde
    SET    ryhmaid = $ryhmaid,
           nimi    = '$nimi',
           nimi2   = '$nimi2',
           data    = '$data',
           mime    = '$mime'
    WHERE  kohdeid = $kohdeid");
  } else {
    // K‰‰nnˆkset k‰ytˆss‰ ja valittua kielt‰ vastaava k‰‰nnˆs olemassa.

    // Haetaan istuntoon merkitty kielivalinta
    $kieliid = hae_istunnon_kieliid();

    // Ryhmaid ja mime kohde-tauluun
    qry("UPDATE kohde SET ryhmaid = $ryhmaid, mime = '$mime' ".
        "WHERE kohdeid = $kohdeid");
    // Tekstit trans_kohde-k‰‰nnˆstauluun
    qry("UPDATE trans_kohde ".
	"SET nimi = '$nimi', nimi2 = '$nimi2', data = '$data' ".
	"WHERE kohdeid = $kohdeid AND kieliid = {$kieliid}");
  }
}

///////////////////////////////////////////////////////////////////////////////
// Kohdeliitteen tietojen p‰ivitys
function qry_kohde_liite_update($kohde_liiteid, $data, $mime) {
  // Tarkistetaan, ett‰ on annettu tarvittavat tiedot
  if ( $kohde_liiteid == '' ) return false;
  $kohde_liiteid = intval($kohde_liiteid);
  if ( $kohde_liiteid < 0 ) return false;

  // Puretaan entiteettikoodaukset
  $data = decode_entities($data);

  if ( tallennus_oletuskielella('kohde_liite', $kohde_liiteid) ) {
    // Oletuskieli k‰ytˆss‰, tallennus pelk‰st‰‰n kohde_liite-tauluun.
    // P‰ivitet‰‰n liitetiedon sis‰ltˆ
    qry("UPDATE kohde_liite SET data = '$data', mime = '$mime' ".
	"WHERE kohde_liiteid = $kohde_liiteid");
    // Merkit‰‰n muutos, ett‰ myˆs kohde tulee merkitty‰ muutetuksi
    touch_kohde_liite($kohde_liiteid);
  } else {
    // K‰‰nnˆkset k‰ytˆss‰ ja valittua kielt‰ vastaava k‰‰nnˆs olemassa.

    // Haetaan istuntoon merkitty kielivalinta
    $kieliid = hae_istunnon_kieliid();
    // P‰ivitet‰‰n mime kohde_liite-tauluun
    qry("UPDATE kohde_liite SET mime = '$mime' ".
	"WHERE kohde_liiteid = $kohde_liiteid");
    touch_kohde_liite($kohde_liiteid);
    // P‰ivitet‰‰n k‰‰nnˆs
    qry("UPDATE trans_kohde_liite SET data = '$data' ".
	"WHERE kohde_liiteid = $kohde_liiteid AND ".
	"kieliid = {$kieliid}");
    touch_trans_kohde_liite($kohde_liiteid, $kieliid);
  }
}

///////////////////////////////////////////////////////////////////////////////
// Viittauksen lis‰‰minen
function qry_viittaus_insert($kohde_liiteid, $kohdeid, $touch=true) {
  if ( $kohde_liiteid=='' or $kohdeid=='' ) return false;
  // Haetaan suurin j‰rjestysindeksi ja pistet‰‰n pyk‰l‰‰ paremmaksi
  $max = qry('SELECT MAX(jarjestys) AS max FROM viittaus');
  $max = $max[0]['max'] + 1;
  qry_insert("INSERT viittaus (jarjestys, kohde_liiteid, kohdeid) 
              VALUES ($max, $kohde_liiteid, $kohdeid)");
  if ( $touch ) touch_kohde_liite($kohde_liiteid);
}

///////////////////////////////////////////////////////////////////////////////
// Viittauksen poisto
function qry_viittaus_delete($kohde_liiteid, $kohdeid, $touch=true) {
  if ( $kohde_liiteid=='' or $kohdeid=='' ) return false;
  qry("DELETE FROM viittaus 
       WHERE kohde_liiteid = $kohde_liiteid AND
             kohdeid = $kohdeid");
  // Merkit‰‰n muutos, ett‰ myˆs kohde tulee merkitty‰ muutetuksi
  if ( $touch ) touch_kohde_liite($kohde_liiteid);
}

///////////////////////////////////////////////////////////////////////////////
// Jakson lis‰‰minen
function qry_jakso_insert($kohde_liiteid, $alku, $loppu=0, $type='in', $touch=true, $repeat='no', $times=1) {
  if ($alku=='' or $loppu=='' or $type=='' or $kohde_liiteid=='') return false;
  $qry = "INSERT jakso (kohde_liiteid, alku, loppu, type)
    VALUES ($kohde_liiteid, %s, %s, '$type')";
  switch ($repeat) {
  default:
  case 'no':
    $times = 1;
    break;
  case 'daily':
    $skip = ' +1 day';
    break;
  case 'weekly':
    $skip = ' +1 week';
    break;
  }
  for ($i=0; $i<$times; $i++) {
    $res = qry_insert(sprintf($qry, date('YmdHis', $alku), 
			      date('YmdHis', $loppu)));
    $alku = strtotime(date('r', $alku) . $skip);
    $loppu = strtotime(date('r', $loppu) . $skip);
  }
  // Merkit‰‰n muutos, ett‰ myˆs kohde tulee merkitty‰ muutetuksi
  if ( $touch ) touch_kohde_liite($kohde_liiteid);
  return $res;
}

///////////////////////////////////////////////////////////////////////////////
// Jakson poisto
function qry_jakso_delete($jaksoid, $touch=true) {
  if ( $jaksoid=='' ) return false;
  // Otetaan jaksoa vastaava kohde_liiteid talteen
  $liitetieto = qry("SELECT kohde_liiteid FROM jakso ".
		    "WHERE jaksoid = $jaksoid");
  // Poistetaan jakso
  qry("DELETE FROM jakso ".
      "WHERE jaksoid = $jaksoid");
  // Merkit‰‰n muutos, ett‰ myˆs kohde tulee merkitty‰ muutetuksi
  if ( $touch ) touch_kohde_liite($liitetieto[0]['kohde_liiteid']);
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen liitetiedon lis‰‰minen
function qry_kohde_liite_insert($kohdeid, $liiteid, $data='', $mime=false, $touch=true) {
  if ( $kohdeid=='' or $liiteid=='' ) return false;

  // tyhj‰n mimen tilalle oletus
  global $settings;
  if ( !$mime ) $mime = $settings['mime_kohde_liite']; 
  if ( !$mime ) $mime = 'text/plain';

  // Lis‰t‰‰n j‰rjestyksess‰ viimeiseksi
  $max = qry('SELECT MAX(jarjestys) AS max FROM kohde_liite');
  $max = $max[0]['max'] + 1;

  $res = 
    qry_insert("INSERT kohde_liite (jarjestys, kohdeid, liiteid, data, mime) ".
	       "VALUES ($max, $kohdeid, $liiteid, '$data', '$mime')");
  // Merkit‰‰n muutos myˆs kohteeseen
  if ( $touch ) touch_kohde($kohdeid);
  return $res;
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen liitetiedon poistaminen
function qry_kohde_liite_delete($kohde_liiteid) {
  global $skip_touch;
  if ( isset($skip_touch_was) ) {
    $skip_touch_was = $skip_touch;
  } else { 
    $skip_touch_was = false; 
  }

  // Massapoiston aikana ei ole tolkkua tehd‰ aikaleimap‰ivityksi‰
  $skip_touch = true;

  // Jaksot
  $jaksot = qry_jakso_lista($kohde_liiteid);
  foreach ( $jaksot as $jakso )
    qry_jakso_delete($jakso['jaksoid']);

  // Viittaukset
  $viittaukset = qry_viittaus_lista($kohde_liiteid);
  foreach ( $viittaukset as $viittaus )
    qry_viittaus_delete($kohde_liiteid, $viittaus['kohdeid']);

  // Tiedostot
  $tiedostot = qry_tiedosto_lista($kohde_liiteid);
  foreach ( $tiedostot as $tiedosto )
    qry_tiedosto_delete($tiedosto['tiedostoid']);

  // Palautetaan aikaleimap‰ivitysten tila massapoistoa edelt‰neeksi
  $skip_touch = $skip_touch_was;

  // Merkataan muutos.  T‰m‰ pit‰‰ tehd‰ ennen liitetiedon
  // poistamista, koska muuten kohteen aikaleima ei p‰ivity.
  touch_kohde_liite($kohde_liiteid);
  if ( trans_tables_available() ) {
    // K‰‰nnˆkset
    qry("DELETE FROM trans_kohde_liite WHERE kohde_liiteid = $kohde_liiteid");
  }
  // Itse liitetieto
  qry("DELETE FROM kohde_liite WHERE kohde_liiteid = $kohde_liiteid");
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen lis‰‰minen
function qry_kohde_insert($ryhmaid, $nimi, $nimi2, $data='', $mime=false) {
  // tyhj‰n mimen tilalle oletus
  global $settings;
  if ( !$mime ) $mime = $settings['mime_kohde']; 
  if ( !$mime ) $mime = 'text/plain';

  return qry_insert("INSERT kohde (ryhmaid, nimi, nimi2, data, mime) ".
		    "VALUES ($ryhmaid, '$nimi', '$nimi2', '$data', '$mime')");
}

///////////////////////////////////////////////////////////////////////////////
// Kohteen poisto
function qry_kohde_delete($kohdeid) {
  // Paljonko kohteeseen on viittauksia muualta?
  $ivc = count(qry_inv_viittaus_lista($kohdeid));
  if ( $ivc > 0 ) return false;

  // Massapoiston aikana ei ole tolkkua tehd‰ aikaleimap‰ivityksi‰
  global $skip_touch;
  if ( isset($skip_touch_was) ) {
    $skip_touch_was = $skip_touch;
  } else {
    $skip_touch_was = false;
  }
  $skip_touch = true;

  // Poistetaan liitetiedot karvoineen ja hampaineen
  $liitteet = qry_kohde_liite_lista($kohdeid);
  foreach ( $liitteet as $liite )
    qry_kohde_liite_delete($liite['kohde_liiteid']);

  if ( trans_tables_available() ) {
    // K‰‰nnˆkset
    qry("DELETE FROM trans_kohde WHERE kohdeid = $kohdeid");
  }

  // Poistetaan itse kohde
  qry("DELETE FROM kohde WHERE kohdeid = $kohdeid");

  // Palautetaan aikaleimap‰ivitysten tila massapoistoa edelt‰neeksi
  $skip_touch = $skip_touch_was;

  return true;
}

///////////////////////////////////////////////////////////////////////////////
// Liitetietotyypin lis‰ys
function qry_liite_insert($ryhmaid, $nimi) {
  return qry_insert("INSERT liite (ryhmaid, nimi) 
                     VALUES ($ryhmaid, '$nimi')");
}

///////////////////////////////////////////////////////////////////////////////
// Liitetietotyypin p‰ivitys
function qry_liite_update($liiteid, $ryhmaid, $nimi) {
  if ( tallennus_oletuskielella('liite', $liiteid) ) {
    return qry("UPDATE liite SET ryhmaid = $ryhmaid, nimi = '$nimi'
                WHERE liiteid = $liiteid");
  } else {
    // Haetaan istuntoon merkitty kielivalinta
    $kieliid = hae_istunnon_kieliid();
    qry("UPDATE trans_liite SET nimi = '$nimi' 
         WHERE liiteid = $liiteid AND kieliid = $kieliid");
    return qry("UPDATE liite SET ryhmaid = $ryhmaid WHERE liiteid = $liiteid");
  }
}

///////////////////////////////////////////////////////////////////////////////
// Liitetietotyypin poisto
function qry_liite_delete($liiteid) {
  if ( trans_tables_available() )
    qry("DELETE FROM trans_liite WHERE liiteid = $liiteid");
  return qry("DELETE FROM liite WHERE liiteid = $liiteid");
}

///////////////////////////////////////////////////////////////////////////////
// Onko annetun tyyppisi‰ liitteit‰
function qry_kohde_liite_exists($liiteid) {
  return (count(qry("SELECT kohde_liiteid FROM kohde_liite WHERE liiteid = $liiteid"))>0);
}

///////////////////////////////////////////////////////////////////////////////
// Ryhmittelyotsikon lis‰ys
function qry_ryhma_insert($nimi) {
  return qry_insert("INSERT ryhma (nimi) VALUES ('$nimi')");
}

///////////////////////////////////////////////////////////////////////////////
// Ryhmittelyotsikon p‰ivitys
function qry_ryhma_update($ryhmaid, $nimi, $kohde_nimi, $kohde_nimi2, $kohde_data, $liite_nimi, $kohde_liite_data) {
  if ( tallennus_oletuskielella('ryhma', $ryhmaid) ) {
    return qry("UPDATE ryhma SET
              nimi             = '$nimi',
              kohde_nimi       = '$kohde_nimi',
              kohde_nimi2      = '$kohde_nimi2',
              kohde_data       = '$kohde_data',
              liite_nimi       = '$liite_nimi',
              kohde_liite_data = '$kohde_liite_data'
              WHERE ryhmaid = $ryhmaid");
  } else {
    // Haetaan istuntoon merkitty kielivalinta
    $kieliid = hae_istunnon_kieliid();
    return qry("UPDATE trans_ryhma SET
              nimi             = '$nimi',
              kohde_nimi       = '$kohde_nimi',
              kohde_nimi2      = '$kohde_nimi2',
              kohde_data       = '$kohde_data',
              liite_nimi       = '$liite_nimi',
              kohde_liite_data = '$kohde_liite_data'
              WHERE ryhmaid = $ryhmaid AND kieliid = $kieliid");
  }
}

///////////////////////////////////////////////////////////////////////////////
// Ryhmittelyotsikon poisto
function qry_ryhma_delete($ryhmaid) {
  if ( trans_tables_available() )
    qry("DELETE FROM trans_ryhma WHERE ryhmaid = $ryhmaid");
  return qry("DELETE FROM ryhma WHERE ryhmaid = $ryhmaid");
}

///////////////////////////////////////////////////////////////////////////////
// Lˆytyykˆ annettuun ryhm‰‰n kuuluvaa tavaraa?
function qry_ryhma_exists($ryhmaid) {
  $on_k =(count(qry("SELECT kohdeid FROM kohde WHERE ryhmaid = $ryhmaid"))>0);
  $on_l =(count(qry("SELECT liiteid FROM liite WHERE ryhmaid = $ryhmaid"))>0);
  return ($on_k or $on_l);
}

///////////////////////////////////////////////////////////////////////////////
// Haetaan kaikki annettua tyyppi‰ olevat viittaukset
function &qry_liiteviittaukset($from_inlist, $to_inlist, $liiteid) {
  return qry("
    SELECT kohde_liite.kohdeid as 'from', viittaus.kohdeid as 'to'
    FROM viittaus, kohde_liite
    WHERE
      viittaus.kohdeid IN (".join(',',$to_inlist).") AND
      viittaus.kohde_liiteid = kohde_liite.kohde_liiteid AND
      kohde_liite.kohdeid IN (".join(',',$from_inlist).") AND
      kohde_liite.liiteid IN ($liiteid)");
}

///////////////////////////////////////////////////////////////////////////////
// Asetetaan liitetiedon j‰rjestysluku
// J‰rjest‰minen ei merkkaa kohdetta tai liitetietoa muutetuiksi
function set_kohde_liite_jarjestys($id, $luku) {
  qry("UPDATE kohde_liite SET jarjestys = $luku WHERE kohde_liiteid = $id");
}

///////////////////////////////////////////////////////////////////////////////
// Vaihdetaan liitetietojen keskin‰ist‰ j‰rjestyst‰
// Palauttaa $id1 -liitetietoa vastaavan kohteen id:n
// J‰rjest‰minen ei merkkaa kohdetta tai liitetietoa muutetuiksi
function swap_kohde_liite($id1, $id2) {
  $tiedot = qry("
    SELECT kohdeid, kohde_liiteid AS id, jarjestys AS j 
    FROM kohde_liite
    WHERE kohde_liiteid IN ($id1, $id2)");

  // Ei lˆydy t‰sm‰lleen kahta kohdetta.  Ei vaihtoa.
  if ( count($tiedot) != 2 ) { return false; }

  // Vaihdetaan j‰rjestysluvut kesken‰‰n.
  set_kohde_liite_jarjestys($tiedot[0]['id'], 
			    $tiedot[1]['j']);
  set_kohde_liite_jarjestys($tiedot[1]['id'], 
			    $tiedot[0]['j']);

  // Palautetaan kohdeid
  return $tiedot[0]['kohdeid'];
}

///////////////////////////////////////////////////////////////////////////////
// Asetetaan viittauksen j‰rjestysluku
// J‰rjest‰minen ei merkkaa kohdetta tai liitetietoa muutetuiksi
function set_viittaus_jarjestys($j, $kl, $k) {
  qry("UPDATE viittaus SET jarjestys = $j 
       WHERE kohde_liiteid = $kl AND kohdeid = $k");
}

///////////////////////////////////////////////////////////////////////////////
// Vaihdetaan viittausten keskin‰ist‰ j‰rjestyst‰
// J‰rjest‰minen ei merkkaa kohdetta tai liitetietoa muutetuiksi
function swap_viittaus($kl1, $k1, $kl2, $k2) {
  $v1 = qry("SELECT jarjestys FROM viittaus 
             WHERE kohde_liiteid = $kl1 AND kohdeid = $k1");
  $v2 = qry("SELECT jarjestys FROM viittaus 
             WHERE kohde_liiteid = $kl2 AND kohdeid = $k2");

  // Ei onnaa, jos yksik‰sitteisi‰ viittauksia ei lˆydy
  if ( count($v1) != 1 or count($v2) != 1 ) { return false; }

  // Vaihdetaan j‰rjestys
  set_viittaus_jarjestys($v1[0]['jarjestys'], $kl2, $k2);
  set_viittaus_jarjestys($v2[0]['jarjestys'], $kl1, $k1);

  // Success
  return true;
}

///////////////////////////////////////////////////////////////////////////////
// Functions to "touch" (update timestamp to current time without
// actually modifying anything) kohde and kohde_liite.
function touch_kohde($kohdeid) {
  global $settings;

  global $skip_touch;
  if ( $skip_touch ) { return true; }

  mysql_query("UPDATE kohde SET leima = NULL ".
	      "WHERE kohdeid = $kohdeid", 
	      $settings['link']) or
    // Unsuccessfull touch is logged
    mappi_log('Touch',
              "Failed to 'touch' kohde $kohdeid");
  return true;
}

function touch_kohde_liite($kohde_liiteid) {
  global $settings;

  global $skip_touch;
  if ( $skip_touch ) { return true; }

  $res = mysql_query("SELECT kohdeid FROM kohde_liite ".
		     "WHERE kohde_liiteid = $kohde_liiteid",
		     $settings['link']);
  if ( $row = mysql_fetch_array($res, MYSQL_ASSOC) ) {
    touch_kohde($row['kohdeid']);
  } else {
    // Unsuccessfull touch is logged
    mappi_log('Touch', 
	      "Failed to 'touch' kohde for kohde_liite $kohde_liiteid");
  }

  mysql_query("UPDATE kohde_liite SET leima = NULL ".
	      "WHERE kohde_liiteid = $kohde_liiteid",
              $settings['link']) or
    // Unsuccessfull touch is logged
    mappi_log('Touch',
              "Failed to 'touch' kohde_liite $kohde_liiteid");

  return true;
}

function touch_trans_kohde_liite($kohde_liiteid, $kieliid) {
  global $settings;

  global $skip_touch;
  if ( $skip_touch ) { return true; }

  $res = 
    mysql_query("SELECT k.kohdeid FROM trans_kohde_liite t,kohde_liite k ".
		"WHERE t.kohde_liiteid = $kohde_liiteid AND ".
		"k.kohde_liiteid = $kohde_liiteid AND ".
		"t.kieliid = $kieliid",
		$settings['link']);
  if ( $row = mysql_fetch_array($res, MYSQL_ASSOC) ) {
    touch_kohde($row['kohdeid']);
  } else {
    // Unsuccessfull touch is logged
    mappi_log('Touch', 
	      "Failed to 'touch' kohde for trans_kohde_liite $kohde_liiteid");
  }

  mysql_query("UPDATE trans_kohde_liite SET leima = NULL ".
	      "WHERE kohde_liiteid = $kohde_liiteid AND ".
	      "kieliid = $kieliid",
              $settings['link']) or
    // Unsuccessfull touch is logged
    mappi_log('Touch',
              "Failed to 'touch' trans_kohde_liite $kohde_liiteid, $kieliid");

  return true;
}

?>
