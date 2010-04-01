<?php  
// K��nn�sten lis�ys ja poisto

// Pomppusivu
$forwardpage = true;
// Kirjastot
require_once('mappi_headers.php');

// Onko k��nn�kset p��ll�?
if ( ! trans_tables_available() ) {
  // Ei, virhetilanne.
  die("K��nn�sominaisuudet eiv�t ole p��ll�.");
}

// Lista k��nn�stauluista
$trans_tables = qry_trans_tables();
// Parametrit
$table   = safe_retrieve_gp('table', 'notable');
$kieliid = intval(safe_retrieve_gp('kieliid', -1));
$id      = intval(safe_retrieve_gp('id', -1));

// Onko parametrina annettu taulu olemassa?
if ( ! array_haskey($trans_tables, "trans_{$table}") ) {
  // Ei, virhetilanne.
  die("K��nn�st� ei voi k�sitell�.  Taulua 'trans_{$table}' ei ole.");
}

// Onko annettu "k��nn�skieli"  (oletuskielle, id=1, ei k��nnet�)
if ( ! ( $kieliid > 1 ) ) {
  // Ei, virhetilanne.
  die("K��nn�st� ei voi k�sitell�. Kieli '$kieliid' ei ole k��nn�skieli.");
}

// Onko id asiallinen?
if ( ! ( $id > 0 ) ) {
  // Ei, virhetilanne.
  die("K��nn�st� ei voi k�sitell�. Virheellinne parametri {$table}id = '$id'");
}

switch ( safe_retrieve_gp('action', 'noop') ) {
 case 'insert':
   // Haetaan k��nn�skent�t
   $datafields = trim(join(',', qry_trans_datafields($table)));
   // Tarkistetaan ett� saatiin jotain
   if ( $datafields == '' ) die("Virhe. Ei k��nn�skentti� taulussa '$table'.");
   // Uuden k��nn�ksen luonti kopioimalla oletuskielen tiedot pohjaksi.
   $query = "INSERT INTO trans_{$table} (kieliid,{$table}id,$datafields) ".
     "SELECT $kieliid as kieliid, {$table}id, $datafields ".
     "FROM $table WHERE {$table}id = $id";
   break;
 case 'delete':
   // K��nn�ksen poisto
   $query = "DELETE FROM trans_{$table} ".
     "WHERE {$table}id = $id AND kieliid = $kieliid";
   // K��nn�ksen poiston j�lkeen vaihdetaan oletuskielelle.
   $kieliid = 1;
   break;
 default:
}

// Ajetaan edell� luotu kysely
qry($query);

// Tiedostoja muokataan kohde_liite_edit.php -sivulla, joten pit��
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

// Ved�tys onnistui.  Pompataan takaisin ja vaihdetaan samalla kielt�.
forward(sprintf($fwd[$table], $id, $kieliid));

?>