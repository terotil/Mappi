<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

// Tiedoston kokoa rajoittaa mysql (max_allowed_packet) ja php
// (upload_max_filesize, post_max_size, memory_limit, max_input_time,
// max_execution_time)

  // Tarkistetaan, että saatiin kohde_liiteid
  $kohde_liiteid = safe_retrieve('kohde_liiteid');
  if ( $kohde_liiteid == false ) {
    forward("kohteet.php", 
	    'Ei voi lisätä tiedostoa.  Tarvittava '.
	    'parametri <code>kohde_liiteid</code> puuttuu.');
    return;
  }

  // Tarkistetaan, että saatiin tiedosto
  if ( ! (isset($HTTP_POST_FILES) and
	  is_array($HTTP_POST_FILES) and
	  count($HTTP_POST_FILES) != 0) ) {
    forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid",
	    'Tiedostoa ei voitu vastaanottaa.');
  }

  $tmpfile = $HTTP_POST_FILES['tiedosto']['tmp_name'];
  if ( is_uploaded_file($tmpfile) ) {
    // Kaikki kunnossa, lisätään tiedosto kantaan
    // Tiedoston nimi.  Siihe ei kosketa.  Siinä saa vapaasti olla mitä
    // söhryä vain.
    $name = $HTTP_POST_FILES['tiedosto']['name'];
    // Uploadin mukana tullut mime-tyyppi
    $mime = $HTTP_POST_FILES['tiedosto']['type'];
    // Tiedoston sisältö
    $f = fopen($tmpfile, 'r');
    $content = fread($f, filesize($tmpfile)); 
    fclose($f);

    // Debug upload
    /*
    $filesize = strlen($content);
    `touch $tmpfile.$filesize`;
    copy($tmpfile, $tmpfile.".bak");
    */

    // Harrastetaan vähän mime-taikaa.  Jos 'file' on käytössä,
    // voidaan tehdä mime-arvailuja ja automaattisia muunnoksia.
    if ( isset($settings['autoconvert_uploads']) &&
	 $settings['autoconvert_uploads'] &&
	 `which file` != '' ) {
      // Mitä file ehdottaisi mime-tyypiksi
      $file_mime = trim(`file -b -i $tmpfile`);

      // ps -> pdf automaattimuunnos
      if ( $file_mime == 'application/postscript' &&
	   `which ps2pdf` != '' ) {
	// Päätteeksi .pdf ( file -> file.pdf, file.ext -> file.pdf )
	$name_pdf = ereg_replace('(\.[^\.]+)?$', '', $name).'.pdf';
	$tmpfile_pdf = $tmpfile . '.pdf';
	`ps2pdf $tmpfile $tmpfile_pdf`;
	$f = fopen($tmpfile_pdf, 'r');
	$content_pdf = fread($f, filesize($tmpfile_pdf)); 
	fclose($f);
	unlink($tmpfile_pdf); // Siivotaan jäljet
	qry_tiedosto_insert($kohde_liiteid, addslashes($content_pdf), 
			    $name_pdf, 'application/pdf');

	// Kun nyt tiedetään, että kysessä oikeasti on
	// postscript-tiedosto, voidaan rauhassa mulkata nimi ja mime
	// vastaamaan todellisuutta.  Tämä esim. sitä varten, jos
	// tietokantaan työnnetään ps-printterillä tehty
	// tiedostotuloste, jonka pääte windowsin tapauksessa on .prn
	// eikä ps.
	$name = ereg_replace('(\.[^\.]+)?$', '', $name).'.ps';
	$mime = 'application/postscript';
      }
    }
    // Tallennetaan tiedosto tietokantaan
    qry_tiedosto_insert($kohde_liiteid, addslashes($content), $name, $mime);
  } else {
    // Väliaikaistiedosto ei ole päätynyt serverille uploadina.
    // Todennäköisin syy on hyökkäysyritys.
    trigger_error('tiedosto_insert.php : Virhe siirrettäessä tiedostoa.'.
		  'Tiedosto "'.$HTTP_POST_FILES['tiedosto']['tmp_name'].'"');
  }
$virhekoodit = 
array(0=>"There is no error, the file uploaded with success",
      1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
      2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
      3=>"The uploaded file was only partially uploaded",
      4=>"No file was uploaded",
      6=>"Missing a temporary folder");

  // Heitetään takaisin muokkaussivulle
  $virhe = $HTTP_POST_FILES['tiedosto']['error'];
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid",
	  ( ($virhe==0) ? false : 
	    "Virhe siirrettäessä tiedostoa: '{$virhekoodit[$virhe]}'."));
?>
