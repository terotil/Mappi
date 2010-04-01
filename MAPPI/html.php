<?php
/******************************************************************************
 ** Some quick and dirty html processing functions written for MAPPI.
 **
 ** Copyringht (c) 2001 Mediaseitti
 **                     Tero Tilus
 **
 ** @author Tero Tilus / Mediaseitti
 */

/******************************************************************************
 ** Extracts tag contents from string
 */
function html_extract_tag(&$string, $tagname, $end=true) {
  if ( $end ) {
    // [U]ngreedy, case [i]nsensitive, dot matches newline[s] too
    $preg_tag = "|<$tagname([^>]*)>(.*)</$tagname>|Uis";
  } else {
    // No endtag
    $preg_tag = "|<$tagname([^>]*)>|Uis";
  }
  if ( preg_match_all($preg_tag, $string, $match, PREG_SET_ORDER) ) {
    return $match;
  } 
  return false;
}

function html_extract_tag_contents(&$string, $tagname, $tag_index=0) {
  if ( $tag = html_extract_tag($string, $tagname, true) and
       count($tag) > $tag_index ) {
    return $tag[$tag_index][2];
  }
  return false;
}

function deparameterize($paramstr) {
  // FIXME: Välilyöntejä sisältävät parametrit eivät konvertoidu
  // oikein.  Regexpejä peliin!  
  // from 'key="val" key="val"' to array('key="val"', 'key="val"')
  $parr = explode(' ', $paramstr);
  $return_parr = array();
  foreach ( $parr as $par ) {
    // Split 'key="val"' to 'key' and '"val"'
    list($key, $val) = explode('=', $par, 2);
    $key = strtolower(trim($key));
    $val = str_replace('"', '', trim($val));
    $return_parr[$key] = $val;
  }
  return $return_parr;
}

function html_extract_tag_params(&$string, $tagname, $endtag=true, $tag_index=0) {
  if ( $tag = html_extract_tag($string, $tagname, $endtag) and
       count($tag) > $tag_index ) {
    return deparameterize($tag[$tag_index][1]);
  }
  return false;
}

function html_extract_tag_param(&$string, $tagname, $param_name, $tag_index=0) {
  $params = html_extract_tag_params($string, $tagname, $tag_index);
  if ( is_array($params) and
       array_haskey($params, $param_name) ) {
    return $params[$param_name];
  }
  return false;
}

function html_get_title(&$string) {
  return html_extract_tag_contents($string, 'title');
}

function html_get_body(&$string) {
  return html_extract_tag_contents($string, 'body');
}

/******************************************************************************
 ** Producing HTML
 */
function parameterize(&$item, $key) {
  if ( $item === null ) {
    $item = " $key";
  } else {
    $item = " $key=\"$item\"";
  }
}

function tag($name, $content=false, $params=array()) { 
  return html_make_tag($name, $content, $params); 
}

function html_make_tag($name, $content=false, $params=array()) {
  $param_str = '';
  if ( count($params) > 0 ) {
    array_walk($params, 'parameterize');
    $param_str = join('', $params);
  }
  if ( $content === false  ) {
    return "<$name$param_str>";
  }
  return "<$name$param_str>$content</$name>";
}

function html_make_a($href, $content, $params=array()) {
  return html_make_tag('a', 
		       $content, 
		       array_merge(array('href'=>$href), 
				   $params)
		       );
}

function html_make_option($content, $val, $isselected=false, $label=false) {
  $param = array('value'=>$val);
  if ( $isselected )
    $param['selected'] = null;
  if ( $label )
    $param['label'] = $label;
  return html_make_tag('option', $content, $param);
}

function is_selected($value, $selected) {
  if ( is_array($selected) ) {
    return in_array($value, $selected);
  }
  return ( $value == $selected );
}

function html_make_dropdown($list, $name, $selected=null, $param=array()) {
  $tmp = "\n";
  foreach ( $list as $key => $val ) {
    if ( is_array($val) ) {
      $subtmp = array();
      foreach ( $val as $subkey => $subval ) {
	$subtmp[] = html_make_option(
				     // "$key / $subkey", // mozilla ei näytä labelia
				     $subkey, // po. tuo yllä oleva
				     $subval, 
				     is_selected($subval, $selected)
				     // , $subkey // mozilla ei näytä labelia
				     );
      }
      $tmp .= html_make_tag('optgroup', "\n".implode("\n",$subtmp)."\n", 
			    array('label'=>$key));
    } else {
      // Lisätään valintatagi
      $tmp .= html_make_option($key, $val, is_selected($val, $selected));
    }
  }
  $param['name'] = $name;
  return html_make_tag('select', $tmp, $param);
}

// The same as previous, key-value pairs are given as table and colnames
function html_make_dropdown_tab($list, $keyname, $valname, $name, $selected=null, $param=array(), $groupname=false) {
  if ( $groupname ) {
    foreach ( $list as $record ) {
      $newlist[$record[$groupname]][$record[$keyname]] = $record[$valname];
    }
  } else {
    foreach ( $list as $record ) {
      $newlist[$record[$keyname]] = $record[$valname];
    }
  }
  return html_make_dropdown($newlist, $name, $selected, $param);
}

function html_make_form($method, $action, $content='', 
			$hidden_params = array(), 
			$form_tag_params = array()) {
  return html_make_tag('form',
		       "\n".
		       array_to_params($hidden_params, 'POST').
		       $content.
		       "\n",
		       array_merge(array('method' => $method, 'action' => $action),
				   $form_tag_params)
		       );
}

function html_make_input($type, $name, $value='', $params=array()) {
  return html_make_tag('input', '', 
		       array_merge(array('type'=>$type, 'name'=>$name,
					 'value'=>$value), $params));
}

function html_make_button($url='', $text='Reload', $params = array()) {
  return html_make_form('GET', 
			$url, 
			html_make_tag('input', 
				      null, 
				      array('type'=>'submit', 
					    'value'=>$text)
				      ),
			$params
			);
}

function html_controlgroup($title, $content) {
  /*
  return "<div class=\"controlgroup\">\n".
    "<div class=\"labelcontainer\"><h2>$title</h2></div>\n$content</div>\n";
  */
  return html_make_tag('fieldset', html_make_tag('legend', $title) . $content,
		       array('class'=>'controlgroup'));
}

function html_toolform($title, $method, $action, $content, 
		       $hidden_params = array(),
		       $form_tag_params = array()) {
  return html_controlgroup($title, 
			   html_make_form($method, $action, $content, 
					  $hidden_params, $form_tag_params));
}

function html_make_table($list, $params=array()) {
  // Alustetaan taulukon sisältö tyhjäksi
  $tmp_tab = "\n";

  // Tarkistetaan, että kyseessä on taulukko
  if ( is_array($list) ) {
    // Käydään läpi rivit
    foreach ( $list as $row ) {
      // Alustetaan rivin sisältö tyhjäksi
      $tmp_row = '';
      // Tarkistetaan, että kyseessä on taulukko
      if ( is_array($row) ) {
	// Käydään läpi yhden rivin sarakkeet
	foreach ( $row as $field ) {
	  $tmp_row .= html_make_tag('td', $field);
	}
      }
      $tmp_tab .= html_make_tag('tr', $tmp_row) . "\n";
    }
  }
  return html_make_tag('table', $tmp_tab, $params);
}

///////////////////////////////////////////////////////////////////////////////
// Tulostetaan kyselyn vastaus taulukkona
///////////////////////////////////////////////////////////////////////////////
// $res    = kaksiulotteinen taulukko, jossa rivit ovat keskenään
//           identtisesti indeksoitu.  Esim. qry* -funktioiden 
//           paluuarvot.
// $attrib = merkkijono.  Taulukko alkaa "<table $attrib>"
// $names  = näytettävät nimet taulukon sarakkeille, oletuksena 
//           sarakkeiden indeksointinimet
// $hide   = lista piilotettavien sarakkeiden indekseistä
// $code   = lista taulukon sarakkeiden muotoilukoodeista.  
//           solu tulostetaan echo eval('return '.$code[$key])."\n"; 
//           käytössä on $row ja $key.  Oletusmuotoilu on 
//           <td>$row[$key]&nbsp;</td>
///////////////////////////////////////////////////////////////////////////////
function print_as_table($res, $attrib='', $names='', $hide='', $code='') {
  // Tarkistetaan, että tavaraa on
  if ( count($res) == 0 ) { return 0; }

  // Asetetaan parametrien oletusarvot

  // Luodaan lista tulostaulukon idekseistä
  $rowkeys = array_keys($res);
  $keys = array_keys($res[$rowkeys[0]]);

  // Lista piilotettavista sarakkeista, oletuksena tyhjä = kaikki
  // näytetään
  if ( $hide == '' ) {
    $hide = array();
  }

  // Oletuksena sarakkeiden nimet ovat samat, kuin tulostaulukon
  // ideksointi
  if ( $names === '' ) $names = array();
  $names_keys = array_keys($names);
  foreach ( $keys as $key ) 
    if ( (!in_array($key, $names_keys)) or ($names[$key] == '') )
      $names[$key] = ucfirst($key);

  // Solun sisällön muotoilukoodi.  Samalla tavalla tulostaulun rivien
  // kanssa indeksoituna.  Tyhjät tai puuttuvat korvataan oletuksella.
  if ( $code === '' ) $code = array();
  $code_keys = array_keys($code);
  foreach ( $keys as $key ) 
    if ( (!in_array($key, $code_keys)) or ($code[$key] == '') )
      $code[$key] = "\"<td>\".\$row['$key'].\"&nbsp;</td>\";";

  // Lähdetään tulostamaan taulukkoa
  echo "\n<table $attrib>\n<tr>\n";

  // Otsikko
  foreach ( $keys as $key )
    if ( ! in_array($key, $hide) )
      echo "\t<th>". $names[$key] ."</th>\n";
  echo "</tr>\n";

  // Datarivit
  foreach ( $res as $row ) {
    $alter = ( $i++ % 2 ) ? 'even' : 'odd';
    echo "<tr class=\"$alter\">\n";
    foreach ( $keys as $key ) 
      if ( ! in_array($key, $hide) )
	echo eval('return '.$code[$key])."\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}
// Wrapper for print_as_table()
function html_make_table2($res, $attrib='', $names='', $hide='', $code='') {
  ob_start();
  print_as_table($res, $attrib, $names, $hide, $code);
  $printed = ob_get_contents();
  ob_end_clean();
  return $printed;
}

///////////////////////////////////////////////////////////////////////////////
// Tulostetaan kyselytulos valintalistana
///////////////////////////////////////////////////////////////////////////////
// $res      = kyselytulos, josta valintalista tulostetaan
// $name     = valintalistan nimi
// $val      = arvosarakkeen nimi (oletuksena sarake 0)
// $key      = näytettävän tekstin sisältävän sarakkeen nimi (ol. 1)
// $selected = oletuksena valittu arvo (tai lista valituista)
// $options  = valintalistan parametrit
///////////////////////////////////////////////////////////////////////////////
// FIXME: DEPRECATED, KÄYTÄ html_make_dropdown_tab()
function print_as_dropdown($res, $name, $val='', $key='', 
			   $selected='', $options='', $groupname=false) {
  echo html_make_dropdown_tab($res, $key, $val, $name, 
			      $selected, deparameterize($options), $groupname);
  return;
}

// Render an editor widget for $mime in $context having form field
// $name and initial $value
function get_editor($mime, $context, $name, $value) {
  global $settings;
  // find assigned editor
  if ( array_key_exists($mime, $settings['editors']) ) {
    $editor = $settings['editors'][$mime];
  } else {
    $editor = 'textfield';
  }
  // find widget render function
  // first try if there is a context specific render function
  if ( function_exists($editor.'_'.$context) ) {
    return call_user_func($editor.'_'.$context, $name, $value);
  }
  // then try if there is a general render function
  if ( function_exists($editor) ) {
    return call_user_func($editor, $name, $value);
  }
  // no render function found
  return errmsg("Sisältömuodolle '$mime' ei löytynyt muokkainta. ".
		"Yritettiin kutsua funktioita '{$editor}_{$context}' ja ".
		"'$editor', mutta molemmat olivat määrittelemättömiä.").
    // try not to loose field value
    html_make_tag('input', false, array('type'=>'hidden', 'name'=>$name,
					'value'=>htmlspecialchars($value)));
}

// Default editor
function textfield($name, $value, $rows=false, $cols=false) {
  if ( !$rows ) {
    $rows = 3+round(strlen($value)/40);
    $rows = ($rows>30) ? 30 : $rows; 
  }
  if ( !$cols ) {
    $cols = 60;
  }
  return tag('div', tag('textarea', ($value?htmlspecialchars($value):''), 
			array('name'=>$name, 'rows'=>$rows, 'cols'=>$cols,
			      'wrap'=>'virtual', 'id'=>'editor-'.$name)));
}

function plain2html($text) {
  return // kovat rivinvaihdot, klikattavat linkit ja quoting
    nl2br(preg_replace('#(^|\s)([a-z]+://[^\s]+[^\s,\.\:])([\s,\.\:\?\!]|$)#',
		       '\1<a href="\2">\2</a>\3', htmlspecialchars($text)));
}

?>