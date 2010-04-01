<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 *
 * Minimal resources to connect to MAPPI database under MySQL.
 **/

function connect_db() {
  global $settings;
  return 
    mysql_connect($settings['host'],
		  $settings['user'],
		  $settings['pw']) &&
    mysql_select_db($settings['db']);
}

function qry($sql) {
  // Save last query (for error reporting)
  global $minimappi_last_query;
  $minimappi_last_query = $sql;

  // Execute query
  $result_set = mysql_query($sql);

  // If query fails, return false to indicate failure.
  if ( ! $result_set ) {
    global $config;
    if ( isset($config->autoreport) && $config->autoreport ) {
      echo report_qry_error();
    }
    return false; 
  }

  // Query was successfully executed.
  $ret_value = array();
  while ( ($row = mysql_fetch_array($result_set, MYSQL_ASSOC)) ) {
    array_push($ret_value, $row);
  }

  // Free the result set
  mysql_free_result($result_set);

  // Return result
  return $ret_value;
}

// Produce error report 
function report_qry_error() {
  global $minimappi_last_query;
  return sprintf('<p><pre style="border: 1px solid black; background: '.
		 '#E0E0E0">%s</pre></p><p>%d: %s</p>', 
		 htmlspecialchars($minimappi_last_query), 
		 mysql_errno(), mysql_error());
}
?>
