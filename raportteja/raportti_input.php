<?php //;Tietojen sy�tt�;Tiedostosta;
  $pagename = 'Ty�kalut &gt; Taulukkotiedon sy�tt�';
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');
  require_once('MAPPI/html.php');

$separators = array('Rivinvaihto (unix)'  => ":lf:",
		    'Rivinvaihto (windows)'  => ":crlf:",
		    'Rivinvaihto (mac)'  => ":cr:",
		    'Sarkain' => ":tab:",
		    'Joku muu'     => '');

$vaihe = safe_retrieve_gp('vaihe', 'alku', true);

switch ( $vaihe ) {
  /**************************************************************************/
 case 'alku':
   // Tulostetaan tiedoston upload-form ja erottimien
   // valintavipstaakit

   // Haetaan ryhm�t
   $ryhmat_tmp = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');
   foreach ($ryhmat_tmp as $ryhma) {
     $ryhmat[$ryhma['nimi']] = $ryhma['ryhmaid'];
   }

   echo html_make_tag('form',
		      
		      '<input type="hidden" name="MAX_FILE_SIZE" value="2097152">
Tiedosto <small>(max 2 Mt)</small><br> <input name="tiedosto" type="file">
<input type="hidden" name="vaihe" value="tiedosto"><br>
Tietue-erotin <br>'.
		      html_make_dropdown($separators, 'tietue', ':crlf:')."\n".
		      html_make_tag('input', '', 
				    array('type'=>'text', 
					  'name'=>'tietue_txt', 
					  'value'=>'')
				    )."\n".
		      '<br>Kentt�erotin<br>'.
		      html_make_dropdown($separators, 'kentta', ':tab:')."\n".
		      html_make_tag('input', '', 
				    array('type'=>'text', 
					  'name'=>'kentta_txt', 
					  'value'=>'')
				    )."\n".
		      '<br>Kohteet sy�tet�n ryhm��n<br>'."\n".
		      html_make_dropdown($ryhmat, 'ryhmaid', '1')."\n<br><br>".
		      html_make_tag('input', '', 
				    array('type'=>'submit',  
					  'value'=>'Jatka &gt;&gt;')
				    )."\n",
		      
		      array('method'=>'POST', 
			    'action'=>'raportti_input.php',
			    'enctype'=>'multipart/form-data')
		      );
   break;
  /**************************************************************************/
 case 'tiedosto':
   // Turvatarkastus.  Tiedoston nimi ja valitut erottimet talteen.
   // Tarkistetaan, ett� saatiin tiedosto
   if ( ! (isset($HTTP_POST_FILES) and
	   is_array($HTTP_POST_FILES) and
	   count($HTTP_POST_FILES) != 0) ) {
     echo 'Tiedostoa ei voitu vastaanottaa.  T�m� voi johtua '.
       'siit�, ett� tiedoston koko oli yli 2Mt tai ett� '.
       'k�ytt�m�si selain on Opera 5.';
   } else {

     // Ryhm�id talteen sessiotietoihin
     sessionvar_set('input_ryhmaid', safe_retrieve_gp('ryhmaid', '1', true));

     // K�sitell��n data
     $tiedostonimi = $HTTP_POST_FILES['tiedosto']['tmp_name'];
     if ( is_uploaded_file($tiedostonimi) ) {
       // Tiedoston validiuden osalta homma on kunnossa.

       // Parsitaan erottimet
       foreach ( array('tietue', 'kentta') as $varname ) {
	 $$varname = safe_retrieve_gp($varname, 
				      safe_retrieve_gp($varname.'_txt', 
						       $varname=='kentta'?':tab:':':newline:', 
						       true), 
				      true);
	 switch ( $$varname ) {
	 case ':rc:':
	   $$varname = "\r";
	   break;
	 case ':lf:':
	   $$varname = "\n";
	   break;
	 case ':crlf:':
	   $$varname = "\r\n";
	   break;
	 case ':tab:':
	   $$varname = "\t";
	   break;
	 }
       }
       
       // Haetaan tiedoston sis�lt�     
       $content = fread(fopen($tiedostonimi, 'r'), filesize($tiedostonimi));
       
       // Pilkotaan annettujen erottimien mukaan
       $content = split($tietue, $content);
       foreach ( array_keys($content) as $key ) {
	 $content[$key] = split($kentta, $content[$key]);
       }

       // Tuupataan valmiiksi pilkottu data tiedostoon odottamaan
       // jatkok�sittely�.
       $tmp_nimi = tempnam('/tmp', 'serialized');
       fwrite(fopen($tmp_nimi, 'w'), serialize($content));
       sessionvar_set('tietokantatiedosto', $tmp_nimi);

       // Haetaan ryhm�n mukaiset kenttien nimet
       $ryhma = qry_ryhma(safe_retrieve_gp('ryhmaid', 1, true));

       // Haetaan liitetiedot, joita on oikeus lis�t�
       $liitetiedot = qry_liite_lista('', 'write');
       
       // Luodaan lista sarakevaihtoehdoista
       $sarakkeet['Ei tallenneta']       = 'nosave';
       // Kohteen kent�t
       $sarakkeet[$ryhma['kohde_nimi']]  = 'nimi';
       $sarakkeet[$ryhma['kohde_nimi2']] = 'nimi2';
       $sarakkeet[$ryhma['kohde_data']]  = 'data';
       // Jos osoite on sy�tetty silppuna
       $sarakkeet['Os::l�hiosoite']     = 'lahiosoite';
       $sarakkeet['Os::postinumero']    = 'postinumero';
       $sarakkeet['Os::toimipaikka']    = 'postitoimipaikka';
       foreach ( $liitetiedot as $liite ) {
	 $sarakkeet[$liite['nimi']] = $liite['liiteid'];
       }

       // Luodaan saraketyypin valintalistat
       $colcount = count($content[0]);
       for ( $i=0; $i < $colcount; $i++ ) {
	 $tmp_row[] = html_make_dropdown($sarakkeet, "saraketyypit[$i]");
       }

       // Tuupataan valintalistat taulukon ensimm�iseksi riviksi
       array_unshift($content, $tmp_row);

       // Tulostetaan formi ja taulukko
       echo html_make_tag('form',
			  '<input type="hidden" name="vaihe" value="syotto">'."\n".
			  '<input type="submit" value="Sy�t� tiedot">'."\n".
			  html_make_table($content),
			  array('method'=>'post', 
				'action'=>'raportti_input.php')
			  );
       
     } else {
       echo 'Siirto ep�onnistui.';
     }
   } 
   // Tulostetaan data taulukkoon ja ensimm�iselle riville
   // tyyppivalintalistat.
   break;
  /**************************************************************************/
 case 'syotto':
   // Sy�tet��n data kantaan annettujen m��ritysten mukaan

   // Haetaan parametrit
   global $settings;
   $tyypit = $HTTP_POST_VARS['saraketyypit'];
   $tiedosto = sessionvar_get('tietokantatiedosto');
   $ryhmaid = sessionvar_get('input_ryhmaid');

   // Siivotaan j�ljet
   sessionvar_unset('tietokantatiedosto');
   sessionvar_unset('input_ryhmaid');

   // Haetaan data v�liaikaistallennuksesta
   $content = unserialize(fread(fopen($tiedosto, 'r'), 
				filesize($tiedosto)));

   // Selvitet��n saraketyypit
   $on = array('nimi'  => false,
	       'nimi2' => false,
	       'data'  => false,
	       'lahiosoite'  => false,
	       'postinumero' => false,
	       'postitoimipaikka' => false);
   foreach ( $tyypit as $i => $type ) {
     if ( intval($type) > 0 ) {
       // Tyyppi on asiallinen numero, eli kyseess� on liitetieto.
       // Mapataan sarakkeeseen.
       $liitetiedot[] = array('liiteid' => $type, 'sarake' => $i);
     } else {
       // Kyseess� on joku erikoistapauksista.  Mapataan sarakkeeseen.
       $on[$type] = $i;
     }
   }

   // K�yd��n data l�pi
   foreach ( $content as $row ) {
     // Perustiedot
     foreach ( array('nimi', 'nimi2', 'data') as $varname ) {
       if ( $on[$varname] === false ) {
	 $$varname = '';
       } else {
	 $$varname = trim($row[$on[$varname]]);
       }
     }

     // Lis�t��n kohde
     if ( strlen($nimi.$nimi2.$data) > 0 ) {
       //       echo 'Kohde ', $nimi, ' ', $nimi2, '<br>';
       // FIXME kohteen data-kent�n mime-tyypin valinta
       // nyt menee oletus-mimell�
       $kohdeid = qry_kohde_insert($ryhmaid, $nimi, $nimi2, $data, false);

       // Osoite
       if ($on['lahiosoite'] ) {
	 $osoite = trim($row[$on['lahiosoite']]);
	 if ($on['postinumero'] ) {
	   $osoite .= "\n".trim($row[$on['postinumero']]);
	   if ($on['postitoimipaikka'] ) {
	     $osoite .= " ".trim($row[$on['postitoimipaikka']]);
	   }
	 }
	 if ( strlen($osoite) > 5 ) {
	   // Lis�t��n osoite
	   // DEBUG //   echo 'Osoite ', $kohdeid, 3, $osoite, '<br>';
	   // FIXME  mime-tyypin valinta
	   // nyt menee oletus-mimell�
	   qry_kohde_liite_insert($kohdeid, 3, $osoite, false);
	 }
       }
       
       // Muut liitetiedot
       if ( isset($liitetiedot) and
	    is_array($liitetiedot) ) {
	 foreach ( $liitetiedot as $liite ) {
	   $data = trim($row[$liite['sarake']]);
	   if ( $data != '' ) {
	     // DEBUG //echo 'Liite ',$kohdeid, $liite['liiteid'],$data,'<br>';
	     // FIXME  mime-tyypin valinta
	     // nyt menee oletus-mimell�
	     qry_kohde_liite_insert($kohdeid, $liite['liiteid'], $data, false);
	   }
	 }
       }

     }
   }
   echo 'Annetun taulukon tiedot sy�tetty.';

   break;
}
  require_once('mappi_html-body-end.php');
?>
