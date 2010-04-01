<?php
  $pagename = 'ohjeet';
  require_once('mappi_headers.php');
  require_once('MAPPI/html.php');

function strip_dothtml($str) {
  if ( ereg('(.*)\.(html?|pdf|jpe?g|gif|doc|rtf)$', $str, $match) ) {
    return $match[1];
  } else {
    return $str;
  }
}
function to_toc($str) {
  $str = strip_dothtml($str);
  return html_make_tag('b', htmlentities(quoted_printable_decode($str))); 
}

function is_htmlfile($name) {
  return ereg('.*\.html$', $name);
}

function to_linktarget($str) {
  $str = ereg_replace('&[a-z]+;', '', $str);
  $str = strtr($str, "ƒ≈÷‰Âˆ ", "AAOaao-");
  $str = ereg_replace('[^a-zA-Z0-9-]', '', $str);
  if ( strlen($str) > 32 )
    return substr($str, 0, 15).'--'.substr($str, -15, 15);
  return $str;
}

  $helproot = 'help';
  $helproot_local = 'help-local';
  $view = safe_retrieve('PATH_INFO', '/');

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

// T‰ss‰ tulostetaan sijaintipolku
// Annettu polku palasiksi
$splitview = split('/', $view);
// URL, josta l‰hdet‰‰n kasaamaan
$url = $settings['root'].'help.php';
// Juuri
echo '<h1>';
echo html_make_a($url, to_toc('Help'));
foreach ( $splitview as $component ) {
  if ( $component != '' ) {
    $url = $url.'/'.$component;
    echo ' / ', html_make_a($url, to_toc($component));
  }
}
echo '</h1>';

if ( is_htmlfile($view) ) {
  // Handle content
  $path = ( file_exists($helproot.$view) ? 
	    $helproot.$view : $helproot_local.$view );
  $htmlfile = fopen($path, 'r');
  $content = fread($htmlfile, filesize($path));
  // Output only core html
  echo '<p style="font-size:80%;float:right;">Viimeksi muutettu ', date("j.n.Y", @filemtime($path)), '</p>';
  $title = html_extract_tag_contents($content, 'title');
  echo "<h1>$title</h1>\n";
  $body = html_extract_tag_contents($content, 'body');
  preg_match_all('/<h([123])>(.*)<\/h[123]>/misU', $body, $matches, 
		 PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
  //  print_r($matches);
  foreach ( array_reverse($matches, true) as $num => $match ) {
    $body = 
      substr_replace($body, sprintf('<a name="hdr-%d"></a><a name="%s"></a>', 
				    $num, to_linktarget($match[2][0])), 
		     $match[0][1], 0);
    $toc[] = 
      sprintf('<div style="margin-left:%dem"><a href="#%s">%s</a></div>', 
	      2*($match[1][0]-1), to_linktarget($match[2][0]), $match[2][0]);
  }
  if ( count($toc) )
    echo '<p>', join(array_reverse($toc), "\n"), '</p>';
  echo $body;
} else {
  if ( $view == '/' ) {
    $dirs = array($helproot, $helproot_local);
  } else {
    $dirs = array( file_exists($helproot.$view) ?
		   $helproot.$view : $helproot_local.$view );
  }
  foreach ( $dirs as $dir ) {
    // Handle directory
    $d = dir($dir);
    while ( $entry = $d->read() ) {
      if ( $entry[0] != '.' and
	   $entry[strlen($entry)-1] != '~' and
	   $entry[0] != '#' and 
	   $entry != 'CVS' ) {
	if ( is_htmlfile($entry) or is_dir($dir.'/'.$entry) ) {
	  echo html_make_a($url.'/'.rawurlencode($entry), to_toc($entry));
	} else {
	  echo html_make_a($settings['root'].$dir.'/'.rawurlencode($entry), 
			   to_toc($entry));
	}
	echo "<br>\n";
      }
    }
    $d->close();
    echo "<br>\n";
  }
}

  require_once('mappi_html-body-end.php');
?>
