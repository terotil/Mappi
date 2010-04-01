<?php // ;Tietokanta-asetukset;Muokkaa kieliä;
require_once('mappi_headers.php');
$pagename = html_make_a('asetukset.php', 'Asetukset') . ' &gt; Kielet';
require_once('mappi_html-head.php');
require_once('mappi_html-body-begin.php');

if ( trans_tables_available() ) { // käännökset ovat päällä
echo '<div id="tools">';
echo html_toolform('Uusi kieli', 'post', 'kieli.php', 
		   '<input type="hidden" name="action" value="insert">'."\n".
		   '<input type="submit" value="Lisää kieli">'."\n");
echo "</div>\n\n";

// Haetaan kielet
$kielet = qry('SELECT * FROM kieli');

$nimet = array('nimi' => 'Nimi', 'koodi' => 'Kielikoodi (ISO 639-1)', 'locale' => 'Maa-asetus', 'charset' => 'Merkistö');
$hide = array('kieliid');
$code = array('nimi' => '"<td><a href=\"kieli.php?action=edit&amp;kieliid=" . $row[\'kieliid\']."\">".$row[$key]."</a></td>";');
print_as_table($kielet, 'class="qtable"', $nimet, $hide, $code);
} else { // käännökset ovat pois päältä
  echo tag('p', 'Sisällön kieliversiointi ei ole käytössä.');
}

require_once('mappi_html-body-end.php');
?>