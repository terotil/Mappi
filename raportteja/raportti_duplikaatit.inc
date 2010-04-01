<?php

// Used by raportit_duplikaatit_selvat.php
//         raportit_duplikaatit.php
// Prints out duplicate candidates found.


// Include some libraries
require_once('MAPPI/html.php');

// Print out links to possible duplicate entities
$duplicate_rows = array();
foreach ( $duplicates as $pair ) {
  $row_pieces = array();
  foreach ( array('a', 'b') as $ab ) {
    $row_pieces[] = 
      html_make_tag('td', 
		    html_make_a('kohde.php?kohdeid='.$pair[$ab.'kohdeid'], 
				kohde_kokonimi($pair[$ab.'nimi'], $pair[$ab.'nimi2'], '', '', $pair[$ab.'ryhmaid'])),
		    array('style' => 'border-left: 2px solid black'));
    $row_pieces[] =
      html_make_tag('td', 
		    $pair[$ab.'ryhmanimi']);
    $row_pieces[] =
      html_make_tag('td',
		    date('d.m.Y', $pair[$ab.'leima']));
  }
  $duplicate_rows[] = html_make_tag('tr', join("\n", $row_pieces));
}

echo html_make_tag('table',
		   '<tr><th colspan="3" style="border-left: 2px solid black" align="left">&nbsp;&nbsp;Kohde</th>'.
		       '<th colspan="3" style="border-left: 2px solid black" align="left">&nbsp;&nbsp;Mahdollinen kaksoiskappale</th></tr>'.
		   '<tr><th style="border-left: 2px solid black">Nimi</th><th>Ryhmä</th><th>Muokattu</th>'.
		       '<th style="border-left: 2px solid black">Nimi</th><th>Ryhmä</th><th>Muokattu</th></tr>'.
		   join("\n\t", $duplicate_rows),
		   array('class' => 'qtable',
			 'cellspacing' => '0'));

?>