<?php 
  // ============================================================
  // Tietokannan asetukset 
  // $Id: mappi_rc.php,v 1.15 2007/02/24 00:59:05 mediaseitti Exp $
  // ============================================================
require_once('MAPPI/mappi_rc_defaults.php');

$settings['name'] = 'Tietovaraston nimi';

$settings['db']   = 'database';
$settings['host'] = 'localhost';
$settings['user'] = 'dbuser';
$settings['pw']   = 'dbpassword';

$settings['logfile'] = 'mappi.log';

$settings['admin_email'] = '';
$settings['admin_phone'] = '';

// Funktio, jota kutsutaan tietokannan muuttuessa.
function database_changed($id=-1, $type='') {
  global $skip_dbchanged;
  if ( $skip_dbchanged ) { return true; } 
  
  // Tässä kohdassa voi sitten tehdä kaikki temput, jotka ovat
  // välttämättömiä tietokannan sisällön muututtua, esim. sivuston
  // välimuistin siivous.
  
  return true;
}

?>
