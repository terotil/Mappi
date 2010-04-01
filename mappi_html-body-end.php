 </div>
 <div id="statusbar">
  <div id="username">Käyttäjä: <?php 
if ( $usr=sessionvar_get('realname') ) { 
  echo $usr; 
} else {
  echo '&mdash;';
} ?></div><?php 
if ( trans_tables_available() ) { ?>
  <div id="langset"><?php
  $kielilista = qry_kieli_lista();
  $istunnon_kieliid = hae_istunnon_kieliid();
  if ( count($kielilista) > 0 ) { 
    // Kaikki parametrit matkaan lukuunottamatta kieltä
    $params = array_merge($_GET, $_POST);
    if ( isset($params['lang']) ) unset($params['lang']);
    $kieli = hae_istunnon_kieli();
    echo "\nKieli: {$kieli['nimi']}, vaihda:\n";
    // Muutetaan parametrit url-muotoon
    $tmp_param = array_to_params($params);
    foreach ( $kielilista as $kieli ) {
      printf('<a href="%s?%s&amp;lang=%d">%s</a>'."\n",$_SERVER['SCRIPT_NAME'],
	     $tmp_param, $kieli['kieliid'], $kieli['koodi']);
    } ?>
  </div><?php
  } else {
    // ei kieliä määriteltynä tietokannassa
  }
} ?>
 </div>
</body>
</html>
