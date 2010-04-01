<?php
require_once('mappi_rc.php');
require_once('MAPPI/html.php');
require_once('MAPPI/general.php');
require_once('MAPPI/translate.php');
require_once('libseitti-php/getnum.function.php');

///////////////////////////////////////////////////////////////////////////////
// Vakiot
///////////////////////////////////////////////////////////////////////////////
if (!defined('PHP_INT_MAX')) define('PHP_INT_MAX', pow(2,31));
define('TIMESTAMP_MINUSINF', -pow(2,31)   );
define('TIMESTAMP_PLUSINF',   pow(2,31)-1 );

///////////////////////////////////////////////////////////////////////////////
// Funktiom��ritykset
///////////////////////////////////////////////////////////////////////////////

/******************************************************************************
 ** Istuntomuuttujat
 ** Jaetaan istuntomuuttujat nimiavaruuksiin tietokannan mukaan.
 */

// FIXME: n�m� vanhat pois
// Vanha
function session_set($k, $v) { return sessionvar_set($k, $v); }
function session_register_and_set($k, $v) { return sessionvar_set($k, $v); }

// Nykyinen
function sessionvar_set($key, $val) {
  global $settings;
  if ( is_array($settings) && isset($settings['db']) ) {
    if ( ! is_array($_SESSION[$settings['db']]) ) {
      $_SESSION[$settings['db']] = array();
    }
    $_SESSION[$settings['db']][$key] = $val;
    return true;
  } else {
    // ei tietoa tietokannasta
    return false;
  }
}

function sessionvar_get($key, $default=false) {
  global $settings;
  if ( is_array($settings) && isset($settings['db']) &&
       is_array($_SESSION[$settings['db']]) &&
       array_key_exists($key, $_SESSION[$settings['db']]) ) {
    return $_SESSION[$settings['db']][$key];
  }
  return $default;
}

function sessionvar_unset($key) {
  global $settings;
  if ( is_array($settings) && isset($settings['db']) &&
       is_array($_SESSION[$settings['db']]) ) {
    unset($_SESSION[$settings['db']][$key]);
    return true;
  }
  return false;
}
// Polku istuntokeksiin
function sessioncookie() {
  // FIXME: T�ss� voisi tehd� sitten ehk� jotain muutakin.
  session_set_cookie_params(0, dirname($_SERVER['PHP_SELF']));
}


function mappi_log($action, $data='') {
  global $settings;

  // Old configs don't provide logfile, must have default
  $logfile = '/var/log/mappi.log';
  if ( array_haskey($settings, 'logfile') ) {
    $logfile = $settings['logfile'];
  }

  // Don't do logging if logfile isn't there
  if ( file_exists($logfile) ) {
    $stamp = date("Y-m-d H:i:s");
    error_log(sprintf("%s; %s; %s; '%s' uid %d; %s\n", 
		      $stamp, $action, $settings['db'],
		      sessionvar_get('realname'), sessionvar_get('uid'),$data),
	      3, $logfile);
  }
}

function link_scripts($regexp='.*\.php$', $dir='.') {
  $output = '';

  // Haetaan annetun skriptihakemiston regexpiin t�sm��vien
  // tiedostojen otsikkotiedot
  $raportit =& firstlines($regexp, $dir);

  // Indeksoidaan otsikkotiedot uudelleen 1-sarakkeesta l�ytyv�n
  // ryhmittelyotsikon perusteella.
  $raportit =& array_fold($raportit, array(1));
  
  // Tulostetaan otsikoittain linkit raportteihin 
  foreach ($raportit as $otsikko => $raplist) {
    $output = $output."\n<h3>$otsikko</h3>\n\t<p>\n";
    foreach ($raplist as $rap) {
      $output = $output."\t".'<a href="'.$rap[0].'">'.$rap[2].'</a><br>'."\n";
    }
    $output = $output."\t</p>\n\n";
  }
  
  // Palautetaan html-koodi
  return $output;
}

///////////////////////////////////////////////////////////////////////////////
// P��tell��n tiedoston mime -tyyppi
///////////////////////////////////////////////////////////////////////////////
function filemimetype($file) {
  // FIXME: t�m� k�ytt�m��n tiedostop��tep��ttely� ja tarvittaessa
  // file -komentoa omalla magiatiedostollaan.
  return 'application/octet-stream';
}

///////////////////////////////////////////////////////////////////////////////
// K�ytt�j�n kirjaaminen j�rjestelm��n
///////////////////////////////////////////////////////////////////////////////
// $uname   = k�ytt�j�tunnus
// $pw      = salasana
// $chipher = 'clear' -> selv�kielinen, tai 'md5'
///////////////////////////////////////////////////////////////////////////////
// Palauttaa onnistui -> true, ep�onnistui -> false
///////////////////////////////////////////////////////////////////////////////
function login($uname, $pw='', $chipher='clear') {
  $login_lista = qry_login_lista();
  $authenticated = false;
  if ( array_haskey($login_lista, $uname) ) {
    if ( $login_lista[$uname][0]['ntdomain'] != '' ) {
      // Autentikointi NT-domainista
      // Ei onnistu viel�
    } else {
      // Autentikointi tietokannan salasanalla
      if ( $chipher=='clear' ) $pw = md5($pw);
      if ( $login_lista[$uname][0]['pw'] == $pw ) $authenticated = true;
    }
  } 
  
  if ( $authenticated ) {
    // K�ytt�j� on tunnistettu ja hyv�ksytty
    
    // Asetetaan perustiedot kohdalleen
    sessionvar_set('uid',   $login_lista[$uname][0]['kohdeid']);
    sessionvar_set('uname', $uname);
    sessionvar_set('read',  $login_lista[$uname][0]['read']);
    sessionvar_set('write', $login_lista[$uname][0]['write']);
    sessionvar_set('ryhma_rights', $login_lista[$uname][0]['ryhma']);

    // Haetaan tarvittavat k�ytt�j�tiedot
    sessionvar_set('realname', 
      request_user_info($login_lista[$uname][0]['kohdeid'], 'realname'));

    // Merkit��n onko k��nn�kset p��ll�
    sessionvar_set('trans_on', trans_tables_available());

    // Oletuskielen� kieliid=1
    aseta_istunnon_kieli(1);
    
    // Lokitetaan
    mappi_log('Login', 'Login succeeded.');
  } else {
    // FIXME: K�ytt�j�nimi ja salasana eiv�t kelpaa. T��ll� voidaan
    // tehd� jotain virheidenkirjausjuttuja, jos tarvetta sellaiseen
    // on.  Ainakin rootin ep�onnistuneet loggaukset voisi kirjata
    // muistiin
    mappi_log('Login', "Login of user $uname failed.");
  }
  return $authenticated;
}

///////////////////////////////////////////////////////////////////////////////
// Pakotettu kirjautuminen kohdeid:n perusteella
///////////////////////////////////////////////////////////////////////////////
function idlogin($kohdeid, $realname='IDLogged') {
  // Haetaan kirjautumisliitetieto
  $kohde_liite = qry("
    SELECT kohde_liite.kohde_liiteid AS klid,
           kohde_liite.kohdeid AS kohdeid,
           kohde_liite.data AS login
    FROM   kohde_liite
    WHERE  kohde_liite.liiteid = 0 AND
           kohde_liite.kohdeid = $kohdeid");
  // Haetaan ensimm�ist� l�ytynytt� kirjautumisliitetietoa vastaavat
  // oikeudet
  $login = qry_login($kohde_liite[0]);

  // Tallennetaan tiedot istuntomuuttujiin
  sessionvar_set('uid', $login['kohdeid']);
  sessionvar_set('uname', $login['uname']);
  sessionvar_set('read', $login['read']);
  sessionvar_set('write', $login['write']);
  sessionvar_set('ryhma_rights', $login['ryhma']);
  sessionvar_set('realname', $realname);
}

///////////////////////////////////////////////////////////////////////////////
// Oikeuslistojen parsiminen tekstist�
///////////////////////////////////////////////////////////////////////////////
// $str = Oikeusryhm�n data -kent�n teksti
function parse_rights($str) {
  $maxlid = qry("SELECT MAX(ryhmaid) AS mr FROM ryhma");
  foreach ( array('read','write','ryhma') as $varname ) {
    if ( eregi("<$varname>(.*)</$varname>", $str, $match) ) {
      $$varname = trim($match[1]);
    } else {
      $$varname = '';
    }
    if ( $varname == 'ryhma' ) continue; // ryhm�� ei ajeta evalin l�pi
    if ( ereg('\*', $$varname) ) {
      // Sallittuja ovat kaikki ryhm�t
      $$varname = range(1,$maxlid[0]['mr']);
      // K�ytt�j�oikeusryhm�n 0 lis�ksi pistet��n listan loppuun viel�
      // -1 merkiksi siit�, ett� oikeudet olivat '*'
      array_push($$varname, 0, -1);
    } else {
      $$varname = eval('return array('.$$varname.');');
    }
  }
  return array($read, $write, $ryhma);
}

///////////////////////////////////////////////////////////////////////////////
// Oikeuslistojen kirjoittaminen tekstiksi
///////////////////////////////////////////////////////////////////////////////
// $read  = Niiden ryhmien id:t, joista on oikeus lukea
// $write = Niiden ryhmien id:t, joihin on oikeus kirjoittaa
// $ryhma = Oikeudet ryhm�tauluun (read|write)
function write_rights($read, $write, $ryhma) {
  if ( in_array(-1, $read) ) {
    $rtxt = '*';
  } else {
    $rtxt = join(',',$read);
  }

  if ( in_array(-1, $write) ) {
    $wtxt = '*';
  } else {
    $wtxt = join(',',$write);
  }

  return "Oikeudet ryhmiin (r/w) : ".
    "<code>'<read>$rtxt</read>/<write>$wtxt</write>'</code>\n".
    "Oikeudet ryhm�tauluun : <code>'<ryhma>$ryhma</ryhma>'</code>";
}

///////////////////////////////////////////////////////////////////////////////
// Ollaanko kirjautuneena?
function is_logged() {
  return sessionvar_get('uname');
  /*
  global $HTTP_SESSION_VARS;
  return (is_array($HTTP_SESSION_VARS) && 
	  array_haskey($HTTP_SESSION_VARS, 'uname') &&
	  ($HTTP_SESSION_VARS['uname'] != ''));
  */
  /* Vanha versio, jos joskus vaikka luovun t�st� sessiohallinnasta...
  return (session_is_registered('uname') &&
    ($HTTP_SESSION_VARS['uname'] != ''));
  */
}

///////////////////////////////////////////////////////////////////////////////
// K�ytt�j�tietojen haku
///////////////////////////////////////////////////////////////////////////////
// $uid    = k�ytt�j�n tunniste (=kirjautuneena olevan kohteen numero)
// $info   = haettavan tietokent�n nimi: realname, email, phone, address, www
// $method = mist� tieto haetaan 'db' -> kanta ($uid=kohdeid), 'ntdomain'-> ?
///////////////////////////////////////////////////////////////////////////////
function request_user_info($uid, $info='realname', $method='db') {
  switch ($info) {
  case 'realname':
    switch ($method) {
    case 'db':
      $kohde = qry_kohde($uid);
      return $kohde['nimi'].' '.$kohde['nimi2'];
      break;
    }
    break;
  case 'email':
    break;
  default:
    return "Unknown request $info";
  }
}

///////////////////////////////////////////////////////////////////////////////
// Kirjoittaa listan parametreiksi
///////////////////////////////////////////////////////////////////////////////
// $arr     = parametrit sis�lt�v� lista, array('nimi'=>'arvo', ...)
// $method  = 'get' -> &nimi=arvo&... vai 'post' -> <input name='nimi'...>
///////////////////////////////////////////////////////////////////////////////
function array_to_params($arr, $method='GET') {
  $method = strtoupper($method);
  $paramstr = '';
  switch ($method) {
  case 'GET':
    foreach ($arr as $k => $v) {
      if ( ! is_array($v) ) {
	$paramstr = $paramstr.'&amp;'.urlencode($k).'='.urlencode($v);
      }
    }
    break;
  case 'POST':
    foreach ($arr as $k => $v) {
      if ( ! is_array($v) ) {
	$paramstr = $paramstr.
	  "<input type=\"hidden\" name=\"$k\" value=\"$v\">\n";
      }
    }
    break;
  default:
  }
  return $paramstr;
}

///////////////////////////////////////////////////////////////////////////////
// "Takaisin" -linkki
function back_url() {
  if ( isset($_SERVER["HTTP_REFERER"]) ) return $_SERVER["HTTP_REFERER"];
  global $settings;
  return $settings['root'];
}

///////////////////////////////////////////////////////////////////////////////
// Tulostetaan listan�kym�n m��r�rajauksen valintalista
function html_make_limitdropdown($selected) {
  global $settings;
  return html_make_dropdown_tab($settings['limit'], 1, 0, 'limit', $selected);
}
// FIXME: DEPRECATED
function print_limitdropdown($selected) {
  global $settings;
  print_as_dropdown($settings['limit'], 'limit', 0, 1, $selected);
}

///////////////////////////////////////////////////////////////////////////////
// Virheilmoituksen tulostus
function errmsg($msg, $file=false, $line=false, $func=false) {
  if ( $file || $line ) $loc = " <small>$file:$line</small>";
  return "<div class=\"error\">$msg$loc</div>"; 
}
function print_errmsg($msg) { echo errmsg($msg); }
function print_errmsg_standalone($msg, &$settings, $pagename) {
  include('mappi_html-head.php');
  include('mappi_html-body-begin.php'); 
  print_errmsg($msg);
  include('mappi_html-body-end.php'); 
}

///////////////////////////////////////////////////////////////////////////////
// Haetaan kohteen sarakkeiden nimet
///////////////////////////////////////////////////////////////////////////////
// $rid = kohteiden ryhm�id
///////////////////////////////////////////////////////////////////////////////
function qry_kohde_colnames($rid) {
  // Oletukseksi joku ryhm�, johon on oikeudet
  if ( $rid == -1 || $rid === '' ) {
    $read = sessionvar_get('read');
    $rid = $read[0];
  }

  // Haetaan ryhm�n tiedot ja tehd��n niist� sarakenimet
  $tmp_ryhma = qry_ryhma($rid);
  $nimet = array('nimi'  => $tmp_ryhma['kohde_nimi'],
		 'nimi2' => $tmp_ryhma['kohde_nimi2'],
		 'data'  => $tmp_ryhma['kohde_data'],);
  return $nimet;
}

///////////////////////////////////////////////////////////////////////////////
// Oikeustarkistus.  Kiellot kumuloituvat, pit�� olla kohde_liite
// muokkaukseen oikeus muokata sek� kohdetta, ett� liitett�, eli 
// molempien on kuuluttava ryhm��n, johon on write -oikeus.
// Kaikilla on luku- ja muokkausoikeus kaikkiin omiin tietoihinsa
///////////////////////////////////////////////////////////////////////////////
// $id     = on sen asian id, johon kysyt��n oikeuksia
// $action = 'read' tai 'write'
// $target = mik� $id on
// $result = tarvittava kyselytulos, jos sellainen sattuu olemaan
//           valmiiksi saapuvilla
///////////////////////////////////////////////////////////////////////////////
function is_allowed($id, $action='read', $target='ryhmaid', $result='') {
  $too = true;
  $oma = false;
  switch ( $target ) {
  case 'jaksoid':
    // Haetaan jakson tiedot
    if ( $result == '' ) {
      $jakso = qry_jakso($id);
    } else {
      $jakso = $result;
    }
    $id = $jakso['kohde_liiteid'];
    $result = '';
  case 'kohde_liiteid':
    // Haetaan kohdeliitteen tiedot
    if ( $result == '' ) {
      $kl = qry_kohde_liite($id);
    } else {
      $kl = $result;
    }
    // Omiin tietoihin on aina oikeus
    if ( $kl['kohdeid'] == sessionvar_get('uid') ) $oma = true;
    // Pit�� olla kohteen ryhm�n lis�ksi oikeus my�s liitteen ryhm��n
    $too = in_array($kl['liite_ryhmaid'], sessionvar_get($action));
    $ryhmaid = $kl['kohde_ryhmaid'];
    break;
  case 'liiteid':
    if ( $result == '' ) {
      $liite = qry_liite($id);
    } else {
      $liite = $result;
    }
    $ryhmaid = $liite['ryhmaid'];
    break;
  case 'kohdeid':
    // Omiin tietoihin on aina oikeus
    if ( $id == sessionvar_get('uid') ) $oma = true;
    // Muuten oikeudet ryhm�n perusteella
    if ( $result == '' ) {
      $kohde = qry_kohde($id);
    } else {
      $kohde = $result;
    }
    $ryhmaid = $kohde['ryhmaid'];
    break;
  case 'ryhmaid':
    $ryhmaid = $id;
  }
  $on_oikeus = ($too && in_array($ryhmaid, sessionvar_get($action)));
  if ( $on_oikeus ) {
    return 1; // On ihan oikeasti oikeudet
  } else {
    if ( $oma ) {
      return 2; // On oikeudet vain siit� syyst�, ett� kohde on oma
    }
  }
  return false;
}

///////////////////////////////////////////////////////////////////////////////
// Oikeustarkistus ryhm�taululle
///////////////////////////////////////////////////////////////////////////////
function is_allowed_ryhma($action='write') {
  return ( ( sessionvar_get('ryhma_rights') == $action ) or
	   ( sessionvar_get('ryhma_rights') == 'write' and
	     $action == 'read' ) );
}

///////////////////////////////////////////////////////////////////////////////
// Funktio, joka tulostaa edelleenohjausheaderin
///////////////////////////////////////////////////////////////////////////////
// $url = osoite, johon matkaa jatketaan
// $msg = viestiteksti, joka tulee n�kyviin seuraavalla sivulla
///////////////////////////////////////////////////////////////////////////////
function forward($url, $msg='') {
  // unescape ampersand entity
  $final_url = str_replace('&amp;', '&', $final_url);

  if ( $msg != '' ) {
    // Lis�t��n msg -parametri
    $encoded = urlencode(base64_encode($msg));
    if ( strpos($url, '?') === false ) {
      // URL:ssa ei ennest��n ole parametreja
      $final_url = "$url?msg=$encoded";
    } else {
      // Entisi� parametreja on
      $final_url = "$url&msg=$encoded";
    }
  } else {
    // msg tyhj�, ei lis�t�
    $final_url = $url;
  }

  header("Location: $final_url");
}

///////////////////////////////////////////////////////////////////////////////
// Konversio p�iv�m��r�formaattien v�lill�
///////////////////////////////////////////////////////////////////////////////
function ymd_to_dmy($ymd) {
  // P�iv�m��r�n osat talteen
  list($y, $m, $d) = split_ymd($ymd);
  // Kokonaislukuarvot
  foreach (array('y','m','d') as $vn) { $$vn = intval($$vn); }
  // Muotoilu
  if ($y*$m*$d) {
    return "$d.$m.$y";
  } else {
    // P�iv�m��r�ss� on jotain pieless�
    return '';
  }
}

///////////////////////////////////////////////////////////////////////////////
// Muodostaa kohteelle "julkaisukelpoisen" nimen
///////////////////////////////////////////////////////////////////////////////
// $nimi, $nimi2             = kohde -taulun sarakkeet (pakollinen)
// $kohde_nimi, $kohde_nimi2 = ryhma -taulun kohdetta vastaavat sarakkeet
// $rid                      = Ryhm�id (pakollinen, jos conffin halutaan toimivan)
///////////////////////////////////////////////////////////////////////////////
function kohde_kokonimi($nimi1, $nimi2, $kohde_nimi='', $kohde_nimi2='', $rid=-1) {
  global $settings;

  $metanimi[1] = $kohde_nimi;
  $metanimi[2] = $kohde_nimi2;
  $nimi[1] = $nimi1;
  $nimi[2] = $nimi2;

  if ( $rid >= 0 &&
       $kohde_nimi == '' &&
       $kohde_nimi2 == '') {
    if ( $rid === '' ) {
      // Ei riitt�v�sti parametreja, palautetaan oletusnimi
      $kokonimi = trim($nimi1 . ' ' . $nimi2);
      if ( !$kokonimi ) $kokonimi = '[nimet�n]';
      return $kokonimi;
    }
    $ryhma = qry_ryhma($rid);
    $metanimi[1] = $ryhma['kohde_nimi'];
    $metanimi[2] = $ryhma['kohde_nimi2'];
  }

  $kohde_kokonimi = '';
  foreach ( array(1,2) as $kentta ) {
    $takaperin = false;
    $uusiosa = '';
    if ( $metanimi[$kentta] != '' and
	 $metanimi[$kentta][0] != '-' ) {
      // Kentt� on k�yt�ss�
      $uusiosa = $nimi[$kentta];
      // Muotoilu
      if ( isset($settings['paivayskentat'][$rid]) ) {
	if ( abs($settings['paivayskentat'][$rid]) == $kentta ) {
	  // Kyseess� on p�iv�m��r�kentt�
	  $uusiosa = ymd_to_dmy($uusiosa);
	}
	if ( $settings['paivayskentat'][$rid] <= 0 ) {
	  // Kent�t kootaan p�invastaisessa j�rjestyksess�
	  $takaperin = true;
	}
      }
      $valimerkki = (($kohde_kokonimi=='')||($uusiosa==''))?'':' ';
      if ( $takaperin ) {
	$kohde_kokonimi = $uusiosa . $valimerkki . $kohde_kokonimi;
      } else {
	$kohde_kokonimi = $kohde_kokonimi . $valimerkki . $uusiosa;
      }
    }
  }
  if ( !trim($kohde_kokonimi) ) $kohde_kokonimi = '[nimet�n]';
  return $kohde_kokonimi;
}

///////////////////////////////////////////////////////////////////////////////
// Luodaan login -liitetiedon tekstisis�lt�
///////////////////////////////////////////////////////////////////////////////
function make_login($uname, $pw) {
  return 
    "K�ytt�j�tunnus : <code>'<uname>$uname</uname>'</code>\n"
    ."Salasana (MD5) : <code>'<pw>$pw</pw>'</code>\n"
    //    ."NT-domain : <code>'<ntdomain>$ntdomain</ntdomain>'</code>"
    ;
}

///////////////////////////////////////////////////////////////////////////////
// Muotoillaan liitetiedon tekstisis�lt� esitt�mist� varten
///////////////////////////////////////////////////////////////////////////////
/*
asis sellaisenaan
mime mime-tyypin mukaan
default -''-
tel puhelin, faksi, gsm
www kotisivu
addr postios
email sposti
eur rahaa
m2 neli�metrej�
 */
function format_liite($teksti, $liiteid, $mime='text/plain') {
  global $settings;
  global $trans;
  $teksti = trim($teksti);
  $prepend = '';
  $tyyppi = isset($settings['liitetyypit'][$liiteid]) ? 
    $settings['liitetyypit'][$liiteid] : 'default';
  $pakotatextplain = true;
  switch ( $tyyppi ) {
  case 'tel':  // Puhelinnumero, faksi
    // ylim��r�iset tyhj�t pois
    $teksti = htmlspecialchars(trim($teksti));
    if ( strlen($teksti) > 4 ) {
      // Nelj�n merkin mittaiset tai lyhyemm�t oletetaan
      // ohivalintanumeroiksi tai muuksi sellaiseksi, jota ei tarvitse
      // muokata.
      $numero = split('-', $teksti, 2);
      $eisuuntanumeroa = split(' ', $numero[1], 2);
      // $jakomitta on numeroiden ryhmittelyn ensimm�isen osan pituus.
      $jakomitta = 4;
      if ( strlen($eisuuntanumeroa[0]) == 6 ) {
	$jakomitta = 3;
      }
      if ( !oletuskieli_kaytossa() ) {
	// Kansaenv�linen muoto '+358 8 452 097'
	$teksti = '+358 '.substr($numero[0], 1).' '.
	  substr($numero[1], 0, $jakomitta).' '.
          substr($numero[1], $jakomitta);
      } else {
	// Kotimainen muoto '(08) 452 097'
	$teksti = 
	  '('.$numero[0].') '.
	  substr($numero[1], 0, $jakomitta).' '.
	  substr($numero[1], $jakomitta);
      }
    }
    break;
  case 'email': // S�hk�posti
    // $prepend = 'mailto:';
    // heksaentiteettimuodossa, ultrakevyt spammiharvesterisuojaus
    // muodon vuoksi
    $prepend = '&#x6d;&#x61;&#x69;&#x6c;&#x74;&#x6f;&#x3a;';
  case 'www': // Kotisivu
    // ylim��r�iset tyhj�t pois
    $teksti = htmlspecialchars(trim($teksti));
    // Ladotaan linkitetty URL
    $url = split(' ', $teksti, 2);
    if ( $url ) {
      $teksti = html_make_a($prepend.$url[0], $url[0], 
			    // Pidet��n huolta siit�, ett�
			    // web-urleihin tulee mukaan
			    // 'target="blank"'
			    //$liiteid==2?array('target'=>'_blank'):array()
			    array()
			    );
      if ( count($url) == 2 ) {
	$teksti .= ' '.$url[1];
      }
    }
    break;
  case 'addr':  // Postiosoite, K�yntiosoite
    // Kansaenv�linen muoto, oletetaan kaikkien osoitteiden olevan
    // suomalaisia
    if ( function_exists('hae_istunnon_kieliid') && 
	 hae_istunnon_kieliid() != 1 ) {
      $teksti .= "\nFINLAND";
    }
    // Karttalinkki
    if ( ereg("([^0-9,\r\n]{4,25}[^ \r\n]) +([0-9]+)((,|.*\n\r?([0-9]{5}))( +[^0-9,\r\n]+)?)?", 
	      $teksti, $match) ) {
      $kadunnimi = $match[1];
      $numero = $match[2];
      $postinumero = $match[5];
      // Selvitet��n kunta
      global $postinumeroidenkunnat;
      $kunta = 'Haapavesi'; // Sivistynyt arvaus kunnaksi
      if ( is_array($postinumeroidenkunnat) && $match[5] ) {
	// Postinumeroluettelo on k�ytett�viss� ja osoitteessa oli
	// postinumero.
	if ( $match[6] && in_array(trim($match[6]), $postinumeroidenkunnat) ) {
	  // Osoitteen postitoimipaikka l�ytyy postinumeroluettelon
	  // kunnista, k�ytet��n sit�.
	  $kunta = trim($match[6]);
	} else {
	  // Osoitteessa ei ole kuntaa tai sit� ei l�ydy luettelosta
	  // (ts. toimipaikka ei ole kunta vaan kyl� tms), koitetaan
	  // m�p�t� postinumeron perusteella
	  if ( array_key_exists($match[5], $postinumeroidenkunnat) ) {
	    $kunta = $postinumeroidenkunnat[$match[5]];
	  } elseif ( $match[6] ) {
	    // Ei onnistunut postinumeron perusteella, mutta
	    // postitoimipaikka on annettuna, joten k�ytet��n sit�
	    // sivistyneen� arvauksena.
	    $kunta = trim($match[6]);
	  }
	}
      } else {
	// Postinumeroluettelo ei ole k�ytett�viss� tai osoitteessa ei
	// ollut postinumeroa.
	if ( $match[6] ) {
	  // Postinumeroluetteloa ei ole, mutta osoitteesta l�ytyi
	  // kunta.
	  $kunta = trim($match[6]);
	}
      }
      if ( function_exists('muotoile_karttalinkki') ) {
	$karttalinkki = 
	  muotoile_karttalinkki($kadunnimi, $numero, $postinumero, $kunta);
      } else {
	$url = sprintf('http://maps.google.fi/?q=%s+%s,+%s', 
		       urlencode($kadunnimi), $numero, urlencode($kunta));
	$karttalinkki = 
	  html_make_a($url, is_array($trans) ?
		      $trans['n�yt�kartalla'] : 'n�yt� kartalla');
      }
      $teksti .= sprintf(' <small>(%s)</small>', $karttalinkki);
    }
    break;
  case 'm2': // Neli�metri�, kent�ss� pelkk� luku
    $teksti = number_format(getnum($teksti), 0, ',', ' ') . ' m<sup>2</sup>';
    break;
  case 'eur': // Raham��r�, kent�ss� pelkk� luku
    $teksti = number_format(getnum($teksti), 2, ',', ' ') . ' &euro;';
    break;
  case 'asis':
    // ei mit��n
    break;
  case 'default':
  case 'mime':
  default:
    $pakotatextplain = false;
    $teksti = convert_content($mime, 'text/html', $teksti);
    break;
  }
  if ( $tyyppi != 'asis' ) {
    // muut paitsi raaka: @ entiteettimuodossa
    $teksti = str_replace('@', '&#64;', $teksti);
    if ( $pakotatextplain ) {
      // kovat rivinvaihdot niihin, miss� mime� ei kunnioiteta
      $teksti = nl2br($teksti);
    }
  }
  return $teksti;
}

///////////////////////////////////////////////////////////////////////////////
// Voimassaolotarkistus jaksolistan perusteella
///////////////////////////////////////////////////////////////////////////////
// Liitetieto on voimassa, mik�li
///////////////////////////////////////////////////////////////////////////////

function jakso_voimassa($jakso, $timestamp) {
  return 
    ( $jakso['alku']  <= $timestamp ) and 
    ( $jakso['loppu'] >= $timestamp );
}

function kohde_liite_voimassa($kohde_liiteid, $timestamp=false) {
  $kohde_liiteid = intval($kohde_liiteid);
  return jaksot_voimassa(qry_jakso_lista($kohde_liiteid), $timestamp);
}

function jaksot_voimassa($jaksolista, $timestamp=false, $folded=false) {
  // Tarkastetaan jaksolista l�pi ja kerrotaan kuuluiko $timestamp
  // johonkin listan jaksoon.
  if ( $folded ) {
    foreach ( $jaksolista as $jakso ) {
      if ( jakso_voimassa($jakso, $timestamp)) {
	return true;
      }
    }
    return false;
  }

  // Annetut jaksot sis�lt�v�n liitetiedon validisuustarkistus.
  // Oletuksena tarkastetaan validisuus "nyt".
  if ( $timestamp === false ) {
    $timestamp = time();
  }

  if ( isset($jaksolista) and
       is_array($jaksolista) and
       array_haskey($jaksolista, 0) )
    if ( array_haskey($jaksolista[0], 'type') ) {
    $jaksolista = array_fold($jaksolista, array('type'));

    // Mukaanlukevat jaksot.  Jos niit� on ja aika ei kuulu johonkin
    // v�lille, ei liitetieto ole validi.
    if ( array_haskey($jaksolista, 'in') and 
	 !jaksot_voimassa($jaksolista['in'], $timestamp, true) ) {
      return false;
    }

    // Poislukevat jaksot.  Jos niit� on ja aika kuuluu jollekin
    // v�lille, ei liitetieto ole validi.
    if ( array_haskey($jaksolista, 'ex') and
	 jaksot_voimassa($jaksolista['ex'], $timestamp, true) ) {
      return false;
    }
  }
  return true;
}

// Is the requested conversion possible to perform?
function convert_content_capableof($conversion) {
  global $settings;
  return is_array($settings['converters']) &&
    array_key_exists($conversion, $settings['converters']) &&
    function_exists($settings['converters'][$conversion]);
}

// Data conversion between mime-types $from and $to. Available
// conversion functions are listed in array $settings['converters']
// as key-value pairs 'from->to' => 'function'.
function convert_content($from, $to, &$content) {
  global $settings;
  
  if ( $from == $to ) return $content;  // no need to convert

  $conversion = "{$from}->{$to}";
  // find a suitable conversion function
  if ( convert_content_capableof($conversion) ) {
    return call_user_func($settings['converters'][$conversion], $content);
  }
  // no converter found
  $error = "Virhe: Muunnosta sis�lt�muotojen '$from' ja '$to' v�lill� ".
    "ei voitu tehd�.  Soveltuvaa muunnosfunktiota ei l�ytynyt. ".
    "Tarkista \$settings['converters'], tiedosto mappi_rc.php.";
  switch ( $to ) {
  case 'text/html':
    $error = errmsg($error);
    break;
  case 'text/x-latex':
    $error = addcslashes($error, '$&%#_{}');
    break;
  }
  return $error;
}

function convert_datafield($table, $id, $to) {
  $id = intval($id);
  if ( $id < 1 ) { 
    trigger_error('Assertion $id > 0 failed.');
    return false;
  }
  if ( $table != 'kohde' && $table != 'kohde_liite' ) {
    trigger_error("Illegal table '$table'.", E_USER_WARNING);
    return false;
  }
  $entry = 
    array_pop(qry("SELECT data, mime FROM {$table} WHERE {$table}id = $id"));
  $from = $entry['mime'];
  if ( $from == $to ) return true; // ei tarvitse muuntaa

  if ( convert_content_capableof("{$from}->{$to}") ) {
    $new_data = convert_content($from, $to, $entry['data']);
    qry("UPDATE {$table} SET data = '$new_data', mime = '$to', leima = leima WHERE {$table}id = $id");
    // k��nn�kset
    if ( trans_tables_available() ) {
      $translations = qry("SELECT kieliid, data FROM trans_{$table} WHERE {$table}id = $id");
      foreach ( $translations as $translation ) {
	$new_data = convert_content($from, $to, $translation['data']);
	qry("UPDATE trans_{$table} SET data = '$new_data', leima = leima WHERE {$table}id = $id AND kieliid = {$translation['kieliid']}");
      }
    }
    return true;
  } else {
    trigger_error("Conversion failed.  Not capable of '{$from}->{$to}'.");
    return false;
  }
}

function get_conversion_link_list($table, $id, $from, $linktpl='convert.php?table=%s&amp;id=%d&amp;to=%s') {
  global $settings;
  foreach ( $settings['mimetypes'] as $key => $label ) {
    $conversion = "{$from}->{$key}";
    if (convert_content_capableof($conversion)) {
      $conversion_list[] = 
	html_make_a(sprintf($linktpl, $table, $id, $key),$label);
    }
  }
  return $conversion_list;
}

// format fields of kohde for display
function format_kohde($field, $data, $kohde_mime='text/plain') {
  switch ($field) {
  case 'mime':
    global $settings;
    if ( array_key_exists($data, $settings['mimetypes']) ) {
      return sprintf("%s (%s)", 
		     htmlspecialchars($settings['mimetypes'][$data]), 
		     htmlspecialchars($data));
    } else {
      return htmlspecialchars($data);
    }
    break;
  case 'data':
    return convert_content($kohde_mime, 'text/html', $data);
    break;
  case 'leima_unix':
    return date('j.n.Y H:i', $data);
    break;
  default:
    return htmlspecialchars($data);
  }
}

///////////////////////////////////////////////////////////////////////////////
// Tietokantariippuvaiset funktiot
require_once('MAPPI/mappi_lib_'.$settings['dbengine'].'.php');
///////////////////////////////////////////////////////////////////////////////

?>
