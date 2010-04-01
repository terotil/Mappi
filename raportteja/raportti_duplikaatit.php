<?php //;Analyysi;Mahdolliset kaksoiskappaleet (hidas!)
  require_once('MAPPI/html.php');
  $pagename = html_make_a('raportit.php', 'Raportit');
  $pagename .= ' &gt; Mahdolliset kaksoiskappaleet';
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

// Search possible duplicates
$duplicates = qry("
  SELECT 
    k1.kohdeid AS akohdeid,
    k1.ryhmaid AS aryhmaid,
    k1.nimi    AS animi, 
    k1.nimi2   AS animi2, 
    r1.nimi    AS aryhmanimi,
    UNIX_TIMESTAMP(k1.leima) AS aleima,
    k2.kohdeid AS bkohdeid,
    k2.ryhmaid AS bryhmaid,
    k2.nimi    AS bnimi, 
    k2.nimi2   AS bnimi2,
    r2.nimi    AS bryhmanimi,
    UNIX_TIMESTAMP(k2.leima) AS bleima
  FROM
    kohde k1, 
    kohde k2,
    ryhma r1,
    ryhma r2
  WHERE
    k1.kohdeid < k2.kohdeid AND
    CONCAT('%',UPPER(k1.nimi),'%')  like CONCAT('%',UPPER(k2.nimi),'%') AND 
    CONCAT('%',UPPER(k1.nimi2),'%') like CONCAT('%',UPPER(k2.nimi2),'%') AND
    k1.ryhmaid = r1.ryhmaid AND
    k2.ryhmaid = r2.ryhmaid
  ORDER BY animi2");

  require('raportti_duplikaatit.inc');

  require_once('mappi_html-body-end.php');
?>
