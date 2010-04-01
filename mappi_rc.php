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
  
  // T�ss� kohdassa voi sitten tehd� kaikki temput, jotka ovat
  // v�ltt�m�tt�mi� tietokannan sis�ll�n muututtua, esim. sivuston
  // v�limuistin siivous.
  
  return true;
}

?>
