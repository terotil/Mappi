<?php

/******************************************************************************
 ** General function library written for MAPPI.  This code is by no
 ** means MAPPI spesific.  Feel free to use.
 **
 ** Copyringht (c) 2001 Mediaseitti
 **                     Tero Tilus
 **
 ** @author Tero Tilus / Mediaseitti
 */

/******************************************************************************
 ** Get the contents of file.  If not found returns false.
 **
 ** @param $file (path and) name of file
 **
 ** @return Contents of file, false if not found
 */
require_once('libseitti-php/filecontents.function.php');

/******************************************************************************
 ** Get the first line (or first 80 characters) of file with newline
 ** still attached.  Uses <code>fgets()</code> so reading is not binary
 ** safe.  This function can turn out to be useful when when fetchin
 ** content-type information (shebang -line, etc.) from files.
 **
 ** @param $file (path and) name of file
 **
 ** @return String from the beginning of the file up to (and including)
 ** first newline or first 80 characters, whichever first qualifies.
 */
function firstline($file) {
  // Open file
  $handle = fopen($file, 'r')
    or trigger_error('Virhe!  Tiedoston avaaminen epäonnistui.');

  // Read first line, max. 80 characters
  $line = fgets($handle, 80);

  // Close file
  fclose($handle);

  // Return resulting line
  return $line;
}

/******************************************************************************
 ** Retrieves first lines of files matcing <code>$fileregexp</code>
 ** from <code>$dir</code>.  Splits lines on <code>$delim</code>,
 ** replaces first member of result array with filename and returns
 ** array of these arrays.
 **
 ** In MAPPI this function is used to implement a plugin-like behaviour
 ** of certain pages.  For example 'Reports' -page shows a link to
 ** every report_*.php and retrieves link text, etc. from that
 ** particular php-file.  This makes it easy to create new report views
 ** and just drop them in a running system without editing a single
 ** file.
 **
 ** @param $fileregexp Extended regular expression to filter files
 **
 ** @param $dir Directory to search from
 **
 ** @param $delim Field delimitter for splitting first lines into array
 **
 ** @return Array containing first lines of files found from
 ** <code>$dir</code> and matching <code>$fileregexp</code>.  Lines are
 ** splitten up on <code>$delim</code> and first element is replaced
 ** with filename.
 */
function &firstlines($fileregexp='.*', $dir='.', $delim=';') {
  // Open given directory
  $handle = opendir($dir);
  if ( !$handle ) {
    // Something went wrong
    trigger_error('mappi_lib.php::firstlines(): '.
		  'Virhe avatessa hakemistoa.', 
		  E_USER_WARNING);
    return array();
  }

  // Initialize empty array for lines
  $lines = array();

  // Iterate thru contents of the directory
  while (false!==($file = readdir($handle))) {
    // Files matching regexp are processed
    if ( ereg($fileregexp, $file) ) {

      // Read the first line and split it up on $delim
      $line = split($delim, firstline($file), 5);

      // Replace first member of first-line -array with filename
      $line[0] = $file;
      //      array_shift($line);
      //      array_unshift($line, $file);
      
      // Add the splitten up line to lines -array
      $lines[] = $line;
    } 
  }
  closedir($handle); 
  return $lines;
}

/******************************************************************************
 ** Get requested URL without parameters.  Contents of environment
 ** variables seem to be platform (or something else) dependant.  This
 ** has worked for me.  It propably doesn't work in general situation.
 ** 
 ** @return requested URL without parameters 
 */
function script_name() {
    $protocol = 'http';
    if ( ( isset($_SERVER['HTTPS']) &&
	   ($_SERVER['HTTPS'] == 'on') ) ||
	 ( $_SERVER['SERVER_PORT'] == '443' ) ) {
      $protocol = 'https';
    }
    //    return $protocol.'://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
    return $protocol.'://www.oulunetelaisenpuuyritykset.com' . $_SERVER['SCRIPT_NAME'];
}

/******************************************************************************
 ** Reindex two-dimensional array ( = array containing arrays, an array
 ** of table-rows) with values of given columns ( = keys found from
 ** every row) as new keys in given order.  Every row of given
 ** 2-dimensional array must have all the given keys.  Old indexing is
 ** lost during operation.  If it has to be preserved you can append
 ** the key of every array to array itself before reindexing.
 **
 ** Example:
 **
 ** <pre>
 ** $arr = array(array('a',1,'c'), 
 **              array('a',2,'d'), 
 **              array('a',2,'e'), 
 **              array('b',3,'f'), 
 **              array('b',3,'g'), 
 **              array('b',4,'h'));
 ** $folded = array_fold($arr, array(0));
 ** </pre>
 **
 ** Now <code>$folded</code> contains
 **
 ** <pre>
 ** array("a" => array(array("a",1,"c"),
 **		      array("a",2,"d"),
 **		      array("a",2,"e")
 **		      ),
 **	 "b" => array(array("b",3,"f"),
 **		      array("b",3,"g"),
 **		      array("b",4,"h")
 **		      )
 **	 );
 ** </pre>
 **
 ** @param $arr 2-dimensional array to be reindexed
 **
 ** @param $dimcols Array of keys to use when reindexing.  Every row of
 ** $arr must have all the keys listed in $dimcols
 **
 ** @param $verified True when it has been checked that reindexing
 ** request is valid.  This variable is used internally to speed up
 ** recursive requests when reindexing on more than one column.
 **
 ** @return Reference to reindexed array.  
 */
function &array_fold(&$arr, $dimcols, $verified=false) {
  // Check that every row contains keys in $dimcols
  foreach ($arr as $arrkey => $row) {
    $rowkeys = array_keys($row);
    foreach ($dimcols as $wanted_key) {
      if ( !in_array($wanted_key, $rowkeys) ) {
	// A missing key!
	// FIXME: Proper error message
	trigger_error("mappi_lib.php::array_fold() : taulukon ".
		      "riviltä '$arrkey' puuttuu indeksi '$wanted_key'",
		      E_USER_WARNING);
	return array();
      }
    }
  }

  // Take first key from $dimcols and fold array on that column.
  $folded = array();
  $newdim = array_shift($dimcols);
  foreach ($arr as $row) {
    $folded[$row[$newdim]][] = $row;
  }

  // If there's still keys left in $dimcols, call array_fold
  // recursively on every member of newly folded array.
  if ( count($dimcols) > 0 ) {
    foreach (array_keys($folded) as $foldedkey) {
      $folded[$foldedkey] =& array_fold($folded[$foldedkey], $dimcols, true);
    }
  }

  return $folded;
}

/******************************************************************************
 ** Get n:th value of array.  Counting from end of array for negative n
 ** and from beginning for positive n.  When n=0 returns value of the
 ** first element of array, 1 is second, -1 last and so on.
 **
 ** @param &$arr Array to search from
 **
 ** @param $n Numeric index of value to return.  Negative indexes count
 ** from end of the array.
 **
 ** @return n:th value of array
 */
function array_peek(&$arr, $n=-1) {
  if ( $n < 0 ) {
    // Start counting from the end of the array.
    $init = 'end';
    $move = 'prev';
    // $n = -1 means last element of array, so $n must be incremented
    // by one before taking absolute value.  Otherwise walking loop
    // does one extra cycle.
    $n = abs(++$n);
  } else {
    // Start counting from the beginning of tha array.
    $init = 'reset';
    $move = 'next';
  }

  // Move the internal pointer of the array to starting position.
  $init($arr);
  // Walking loop.  Advance pointer to wanted position, ie. $n steps
  // to direction defined by $move.
  for ( $i=0 ; $i<$n ; $i++ ) {
    $move($arr);
  }

  return current($arr);
}
function array_peek_2ndlast(&$arr) {
  return array_peek($arr, -2);
}

/******************************************************************************
 ** Check if array contains given key
 **
 ** @param &$arr Haystack
 **
 ** @param $k Needle
 **
 ** @return True if Haystack contains needle as key.  Otherwise false.
 */
function array_haskey(&$arr, $k) {
  return in_array($k, array_keys($arr));
}

/******************************************************************************
 ** Get an array containing column of 2-dimensional array.  Every row
 ** of 2D-array must have $col as key or request will fail.
 **
 ** Example:
 **
 ** <pre>
 ** $arr = array(array('a',1,'c'), 
 **              array('a',2,'d'), 
 **              array('a',2,'e'), 
 **              array('b',3,'f'), 
 **              array('b',3,'g'), 
 **              array('b',4,'h'));
 ** $column3 = array_column($arr, 2);
 ** </pre>
 **
 ** Now <code>$column3</code> contains
 **
 ** <pre>
 ** array('c','d','e','f','g','h');
 ** </pre>
 **
 ** @param &$arr 2-dimensional array
 **
 ** @param $col Name of the column to return
 **
 ** @return Array containing column of 2-dimensional array 
 */
function array_column(&$arr, $col) {
  $column = array();
  foreach ($arr as $row) {
    $column[] = $row[$col];
  }
  return $column;
}

/******************************************************************************
 ** Get GPC, session, server or env variable.  Fallback to $default
 */
function safe_retrieve($varname, $default='', $null_is_default=false) {
  $arr = array(&$_REQUEST, &$_SESSION, &$_SERVER, &$_ENV);
  foreach ( array_keys($arr) as $key ) {
    if ( is_array($arr[$key]) && 
	 array_key_exists($varname, $arr[$key]) &&
	 ( ($arr[$key][$varname] != null) || (!$null_is_default) ) ) 
      return $arr[$key][$varname];
  }
  // $varname not found or 
  // value null and $null_is_default == true
  return $default;
}

/******************************************************************************
 ** Get GPC variable.  Fallback to $default
 */
function safe_retrieve_gp($varname, $default='', $null_is_default=false) {
  if ( is_array($_REQUEST) && 
       array_key_exists($varname, $_REQUEST) &&
       ( ($_REQUEST[$varname] != null) || (!$null_is_default) ) ) 
    return $_REQUEST[$varname];
  return $default;
}

/******************************************************************************
 ** Conversions to and from yyyy-mm-dd date format.
 */
function join_ymd($y, $m, $d) {
  $y = str_pad($y, 4, "0", STR_PAD_LEFT);  
  $m = str_pad($m, 2, "0", STR_PAD_LEFT);  
  $d = str_pad($d, 2, "0", STR_PAD_LEFT);  
  return $y.'-'.$m.'-'.$d;
}

function split_ymd($ymd) {
  $regexp = '/([0-9]{4})-([0-9]{2})-([0-9]{2})/';
  preg_match($regexp, $ymd, $match);
  array_shift($match);
  return $match;
}

/******************************************************************************
 ** Buffered version of system -function
 */
function buffered_system($command, &$buffer) {
  if (!($p=popen("($command)2>&1","r"))) return 126;
  while (!feof($p)) {
    $buffer = $buffer . fgets($p,1000);
  }
  return pclose($p);
}

function debug(&$var) {
  if ( onbleedingedge() ) {
    // Näytetään debuggi vain kehitystyötä tekevälle
    include_once('libseitti-php/print_php.function.php');
    echo '<pre>', print_php($var), '</pre>';
  }
}

/******************************************************************************
 ** tavi.auvila.jyu.fi and provider of special parameter live on the
 ** bleeding edge of development
 */
function onbleedingedge() {
  return ( ($_SERVER['REMOTE_ADDR']=='130.234.190.65') || 
	   ($_REQUEST['bleedingedge']=='yes') ) &&
    ! ($_REQUEST['bleedingedge']=='no');
}
?>
