<?php // ;Analyysi;Irtolaiset (=tyhjät kohteet)
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

echo html_make_tag('h3', 'Mikä ihmeen irtolainen?');

echo html_make_tag('p', '"Irtolaiset" syntyvät, kun uusi kohde lisätään, mutta sille ei syystä tai toisesta tehdä mitään muuta.  Tällöin tietokantaan jää kohde, jonka nimi on "Uusi" tai nimeä ei ole ja josta ei ole yhtään mitään tietoa.  Luonnollisestikaan tällaisia ei saisi löytyä.', array());

if ( count($uudet) > 0 ) {
  echo html_make_tag('font', 
		     html_make_tag('h1', 
				   'Irtolaisia löydetty!', 
				   array('style' => 'margin-top: 1em')), 
		     array('color'=>'red'));
  foreach ( $uudet as $nro => $uusi ) {
    echo html_make_a('kohde.php?kohdeid='.$uusi['kohdeid'], 
		     'Irtolainen nro '.$nro);
    echo ', lisätty '.date('j.n.Y', $uusi['timestamp']);
    echo ', ryhmä "'.$uusi['ryhma_nimi'].'"<br>'."\n";
  }
} else {
  echo html_make_tag('h1', 'Ei irtolaiskandidaatteja');
}


  require_once('mappi_html-body-end.php');
?>
