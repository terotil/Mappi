<?php // ;Tietokanta-asetukset;Muokkaa kieliä;
require_once('mappi_headers.php');

// Parametrit
$kieliid = intval(safe_retrieve_gp('kieliid'));

switch ( safe_retrieve_gp('action', 'edit') ) {

 case 'delete':
   if ( is_allowed_ryhma('write') ) {
     if ( $kieliid == 1 ) {
       // Oletuskieltä ($kieliid==1) ei saa poistaa.
       print_errmsg_standalone('Oletuskieltä ei voi poistaa.', 
			       $settings, $pagename);
       exit;
     }
     if ( qry_translations_exist($kieliid, $transcounts) ) {
       // Käännöksiä on vielä.
       foreach ( $transcounts as $tab => $count ) {
	 $tmp .= "Taulu '$tab': $count kpl<br />\n";
       }
       print_errmsg_standalone('Kieltä ei voi poistaa. Käännöksiä kielelle '.
			       'on olemassa seuraavasti.<br /><br />'.$tmp, 
			       $settings, $pagename);
     } else {
       // Poistetaan kieli
       qry("DELETE FROM kieli WHERE kieliid = $kieliid");
       forward('asetus_kielet.php');
     }
   } else {
     forward('kielet.php', 'Sinulla ei ole oikeutta poistaa kieltä.');
   }
   break;

 case 'update':
   if ( is_allowed_ryhma('write') ) {
   if ( $kieliid < 1 ) {
     print_errmsg_standalone('Virheellinen tai puuttuva kieliid.',
			     $settings, $pagename);
     exit;
   }
   $nimi    = safe_retrieve_gp('nimi');
   $koodi   = safe_retrieve_gp('koodi');
   $locale  = safe_retrieve_gp('locale');
   $charset = safe_retrieve_gp('charset');
   qry("UPDATE kieli SET 
          nimi = '$nimi', koodi = '$koodi', 
          locale = '$locale', charset = '$charset' WHERE kieliid = $kieliid");
   forward('asetus_kielet.php');
   } else {
     forward('kielet.php', 'Sinulla ei ole oikeutta muuttaa kielen tietoja.');
   }
   break;

 case 'insert':
   if ( is_allowed_ryhma('write') ) {
     // Luodaan uusi kieli ja jatketaan editointiin
     $kieliid = qry_insert("INSERT INTO kieli (nimi,koodi,locale,charset) VALUES ('uusi','', 'C', 'ISO-8859-1')");
     forward('kieli.php?action=edit&kieliid='.$kieliid);
   } else {
     forward('kielet.php', 'Sinulla ei ole oikeutta lisätä kieltä.');
   }
   exit;
 case 'edit':
 default:
   if ( is_allowed_ryhma('write') ) {
     if ( $kieliid < 1 ) {
       // Hjuuston, meillon ropleemi
       print_errmsg('Virheellinen tai puuttuva kieliid.');
       exit;
     }

     // Haetaan kieli
     $kieli = qry("SELECT * FROM kieli WHERE kieliid = $kieliid");
     $kieli = $kieli[0];

     require_once('MAPPI/html.php');
   
     $pagename .= html_make_a('asetukset.php', 'Asetukset');
     $pagename .= ' &gt; ' . html_make_a('asetus_kielet.php', 'Kielet');
     $pagename .= ' &gt; ' . $kieli['nimi'];

     require_once('mappi_html-head.php');
     require_once('mappi_html-body-begin.php'); 

     echo '<div id="tools">';
     if ( $kieliid > 1 ) {
       // Oletuskieltä ($kieliid==1) ei saa poistaa.
       echo html_toolform('Kielen poisto', 'post', 'kieli.php',
			  '<input type="submit" value="Poista kieli &quot;'.
			  $kieli['nimi'].'&quot;">'."\n",
			  array('action' => 'delete', 
				'kieliid' => $kieliid));
     }
     echo "</div>\n\n";

?>
<form method="post" action="kieli.php">
 <input type="hidden" name="action" value="update">
 <input type="hidden" name="kieliid" value="<?php echo $kieliid ?>">
<dl>
<?php

   $nimet = array('nimi' => 'Nimi', 'koodi' => 'Kielikoodi (ISO 639-1)', 
	       'locale' => 'Maa-asetus', 'charset' => 'Merkistö');
   $tpl ='<dt>%s</dt><dd><input type="text" size="%d" name="%s" value="%s"></dd>';
   $tpl .= "\n";
   foreach ( $nimet as $sarake => $nimi ) {
     printf($tpl, $nimi, ($sarake=='koodi')?'3':'30', 
	    $sarake, htmlspecialchars($kieli[$sarake]));
   }
?>
</dl>
<input type="submit" value="Tallenna">
</form>
<h3>Muistilappu</h3>
<dl>
 <dt>ISO 639-1 kielikoodit</dt>
 <dd><?php
    // Kahden merkin kielikoodit (ISO 639-1) löytyvät tiedostosta
    // arvot ja avaimet pitää kääntää toisin päin (array_flip), koska
    // valintalistassa avaimet ovat näkyvissä ja arvot piilossa
    echo html_make_dropdown(array_flip(file('MAPPI/iso-639-1-fi.txt')), 
			    'kielikoodit');
?>
 </dd>
 <dt>Tuetut maa-asetukset</dt>
 <dd><?php
     // `locale -a` listaa kaikki generoidut (tuetut) maa-asetukset
     // arvot ja avaimet pitää kääntää toisin päin (array_flip), koska
     // valintalistassa avaimet ovat näkyvissä ja arvot piilossa
     echo html_make_dropdown(array_flip(split("\n", `locale -a`)), 
			     'maaasetukset');
?>
 </dd>
</dl>
<?php
     require_once('mappi_html-body-end.php');
   } else {
     forward('kielet.php', 'Sinulla ei ole oikeutta muuttaa kielen tietoja.');
   }
}
?>