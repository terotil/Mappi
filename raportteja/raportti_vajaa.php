<?php // ;Analyysi;Irtolaiset (=tyhj�t kohteet)
require_once('MAPPI/html.php');
  $pagename = html_make_a('raportit.php', 'Raportit');
  $pagename .= ' &gt; Irtolaiskandidaatit';
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');
  require_once('MAPPI/html.php');

$uudet = qry("
  SELECT 
    kohde.kohdeid as kohdeid,
    ryhma.nimi as ryhma_nimi,
    UNIX_TIMESTAMP(leima) as timestamp
  FROM   
    kohde, ryhma
  WHERE  
    ( kohde.nimi = 'Uusi' OR
      kohde.nimi = '' ) AND
    kohde.nimi2  = '' AND
    kohde.ryhmaid = ryhma.ryhmaid");

echo html_make_tag('h3', 'Mik� ihmeen irtolainen?');

echo html_make_tag('p', '"Irtolaiset" syntyv�t, kun uusi kohde lis�t��n, mutta sille ei syyst� tai toisesta tehd� mit��n muuta.  T�ll�in tietokantaan j�� kohde, jonka nimi on "Uusi" tai nime� ei ole ja josta ei ole yht��n mit��n tietoa.  Luonnollisestikaan t�llaisia ei saisi l�yty�.', array());

if ( count($uudet) > 0 ) {
  echo html_make_tag('font', 
		     html_make_tag('h1', 
				   'Irtolaisia l�ydetty!', 
				   array('style' => 'margin-top: 1em')), 
		     array('color'=>'red'));
  foreach ( $uudet as $nro => $uusi ) {
    echo html_make_a('kohde.php?kohdeid='.$uusi['kohdeid'], 
		     'Irtolainen nro '.$nro);
    echo ', lis�tty '.date('j.n.Y', $uusi['timestamp']);
    echo ', ryhm� "'.$uusi['ryhma_nimi'].'"<br>'."\n";
  }
} else {
  echo html_make_tag('h1', 'Ei irtolaiskandidaatteja');
}


  require_once('mappi_html-body-end.php');
?>
