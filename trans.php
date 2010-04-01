<?php  
// Käännösten lisäys ja poisto

// Pomppusivu
$forwardpage = true;
// Kirjastot
require_once('mappi_headers.php');

// Onko käännökset päällä?
if ( ! trans_tables_available() ) {
  // Ei, virhetilanne.
  die("Käännösominaisuudet eivät ole päällä.");
}

// Lista käännöstauluista
$trans_tables = qry_trans_tables();
// Parametrit
$table   = safe_retrieve_gp('table', 'notable');
$kieliid = intval(safe_retrieve_gp('kieliid', -1));
$id      = intval(safe_retrieve_gp('id', -1));

// Onko parametrina annettu taulu olemassa?
if ( ! array_haskey($trans_tables, "trans_{$table}") ) {
  // Ei, virhetilanne.
  die("Käännöstä ei voi käsitellä.  Taulua 'trans_{$table}' ei ole.");
}

// Onko annettu "käännöskieli"  (oletuskielle, id=1, ei käännetä)
if ( ! ( $kieliid > 1 ) ) {
  // Ei, virhetilanne.
  die("Käännöstä ei voi käsitellä. Kieli '$kieliid' ei ole käännöskieli.");
}

// Onko id asiallinen?
if ( ! ( $id > 0 ) ) {
  // Ei, virhetilanne.
  die("Käännöstä ei voi käsitellä. Virheellinne parametri {$table}id = '$id'");
}

switch ( safe_retrieve_gp('action', 'noop') ) {
 case 'insert':
   // Haetaan käännöskentät
   $datafields = trim(join(',', qry_trans_datafields($table)));
   // Tarkistetaan että saatiin jotain
   if ( $datafields == '' ) die("Virhe. Ei käännöskenttiä taulussa '$table'.");
   // Uuden käännöksen luonti kopioimalla oletuskielen tiedot pohjaksi.
   $query = "INSERT INTO trans_{$table} (kieliid,{$table}id,$datafields) ".
     "SELECT $kieliid as kieliid, {$table}id, $datafields ".
     "FROM $table WHERE {$table}id = $id";
   break;
 case 'delete':
   // Käännöksen poisto
   $query = "DELETE FROM trans_{$table} ".
     "WHERE {$table}id = $id AND kieliid = $kieliid";
   // Käännöksen poiston jälkeen vaihdetaan oletuskielelle.
   $kieliid = 1;
   break;
 default:
}

// Ajetaan edellä luotu kysely
qry($query);

// Tiedostoja muokataan kohde_liite_edit.php -sivulla, joten pitää
// tiedoston tapauksessa paluuta varten kaivaa esiin kohde_liiteid.
if ( $table == 'tiedosto' ) {
  $tiedosto = qry("SELECT kohde_liiteid FROM tiedosto WHERE tiedostoid = $id");
  $id = $tiedosto[0]['kohde_liiteid'];
}

// Paluuosoitteet
$fwd = array('kieli' => 'kieli.php?kieliid=%d&lang=%d',
	     'kohde' => 'kohde.php?kohdeid=%d&lang=%d',
	     'kohde_liite' => 'kohde_liite_edit.php?kohde_liiteid=%d&lang=%d',
	     'liite' => 'liite_edit.php?liiteid=%d&lang=%d',
	     'ryhma' => 'ryhma_edit.php?ryhmaid=%d&lang=%d',
	     'tiedosto' => 'kohde_liite_edit.php?kohde_liiteid=%d&lang=%d');

// Vedätys onnistui.  Pompataan takaisin ja vaihdetaan samalla kieltä.
forward(sprintf($fwd[$table], $id, $kieliid));

?>