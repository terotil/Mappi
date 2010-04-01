<?php
$isroot = (sessionvar_get('uid') == 0);
  if ( $isroot ) {
    echo '<table width="80%"><tr>'."\n";
    echo '</td><td valign="top" width="50%">'."\n";
  } 
?>
	<h3 style="margin-top:0px">Tiedot</h3>
	<dl>
	<dt>Kirjautunut käyttäjä</dt>
		<dd><?php echo sessionvar_get('realname') . ' (login ' .
		               sessionvar_get('uname'); ?>)</dd>
	<dt>Tietokanta</dt>
		<dd><?php echo $settings['db'], '@', $settings['host']; ?></dd>
	<dt>Tietokantaohjelmisto</dt>
		<dd><?php echo $settings['dbengine']; 
                          echo ' ('.dbengine_version().')'; ?></dd>
<?php if ( file_exists('/proc/loadavg') ) { ?>
	<dt>Palvelimen kuormitus</dt>
		<dd><?php 
$lavg = split(' ', join('', @file('/proc/loadavg'))); 
foreach ( array(0,1,2) as $k ) {
  $load = $lavg[$k];
  $color = ceil(128 * (float) $load);  // Consider load 2.00 "bad"
  if ( $color > 255 ) { $color = 255; }
  $color = str_pad(dechex((255<<16) + ((255-$color)<<8) + (255-$color)), 
                   6, '0', STR_PAD_LEFT);
  echo "<span style=\"background-color: #$color; padding: 2px\">$load</span>" . ($k==2?'':' / ');
}
?></dd>
	</dl>
<?php }
  if ( $isroot ) {
    // Pääkäyttäjälle näytetään myös tietokannan datamäärä
    echo '<td valign="top" width="50%">'."\n";
    echo '<h3 style="margin-top:0px">Tietokanta</h3>'."\n";
    print_dbstatus();
    echo '</td></tr></table>'."\n";
  } 
?>
