<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Muodostetaan saapuvien parametrien nimet
  $varnames_atime = array();
  $varnames_ltime = array();
  $suff = array('', '_p','_k','_v','_t','_m');
  foreach( $suff as $v ) { $varnames_atime[] = "alku$v"; }
  foreach( $suff as $v ) { $varnames_ltime[] = "loppu$v";}
  $varnames_other = array('kohde_liiteid', 'type', 'repeat', 'times');
  $varnames = array_merge($varnames_atime, $varnames_ltime, $varnames_other);

  // FIXME: Toipuminen kohde_liiteid:n puuttumisesta

  // Haetaan parametrit
  foreach ($varnames as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // puretaan p�iv�ys yhdest� kent�st�
list($alku_v, $alku_k, $alku_p) = split_ymd($alku);
list($loppu_v, $loppu_k, $loppu_p) = split_ymd($loppu);
 
  // Vuoden voi sy�tt�� lyhemmin.  1-2 viimeist� numeroa riitt��.
$tyhja = array('alku_v'=>true, 'loppu_v'=>true);
foreach ( $varnames_atime as $k ) if ( $$k != '' ) $tyhja['alku_v'] = false;
foreach ( $varnames_ltime as $k ) if ( $$k != '' ) $tyhja['loppu_v'] = false;

  foreach ( array('alku_v', 'loppu_v') as $varname ) {
    // Kokonaan tyhj� setti aikakentti� tarkoittaa "tappiarvoa" ja
    // siihen ei t�ss� kosketa.
    if ( $tyhja[$varname] ) continue;
    if ( intval($$varname) == 0 ) {
      $$varname = date('Y');
    } elseif ( intval($$varname) < 70 ) {
      $$varname = 2000 + intval($$varname);
    } elseif ( intval($$varname) < 100 ) {
      $$varname = 1900 + intval($$varname);
    }
  }

  // Tehd��n p�iv�m��rist� unix timestampit
  $alku  = mktime($alku_t,  $alku_m,  0, $alku_k,  $alku_p,  $alku_v);
  // Alkuaika oli laiton (kent�t oli j�tetty tyhj�ksi), s��det��n "tappiin"
  if ( $alku  == -1 ) {
    $alku  = TIMESTAMP_MINUSINF;
    $repeat = 'no';
  }

  $loppu = mktime($loppu_t, $loppu_m, 0, $loppu_k, $loppu_p, $loppu_v);
  // Loppuajan kent�t tyhj�t
  if ( $loppu == -1 ) {
    $loppu = TIMESTAMP_PLUSINF;
    $repeat = 'no';
  }

  // Lis�t��n jakso
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid') ) {
    qry_jakso_insert($kohde_liiteid, $alku, $loppu, $type, true, 
		     $repeat, intval($times));
  }

  // Heitet��n takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
?>
