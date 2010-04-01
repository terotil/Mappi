<?php // ;Henkil�kohtaiset;Vaihda salasana;
// T�t� ei saa laittaa paluupinoon, vaikkei t�m� nyt v�ltt�m�tt�
// forwardisivu olekaan.
  $forwardpage = true;  
  require_once('MAPPI/html.php');
  $pagename = html_make_a('asetukset.php', 'Asetukset').' &gt; Salasana';
  require_once('mappi_headers.php');

  foreach ( array('old', 'new1', 'new2') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  if ( $old == '' && $new1 == '' ) {
    // N�ytet��n lomake 
    require_once('mappi_html-head.php');
    require_once('mappi_html-body-begin.php');
?><form method="post" action="passwd.php">
<dl>
<dt>Vanha salasana</dt>
  <dd><input type="password" name="old" size="30"></dd>
<dt>Uusi salasana</dt>
  <dd><input type="password" name="new1" size="30"></dd>
<dt>Uusi toiseen kertaan</dt>
  <dd><input type="password" name="new2" size="30"></dd>
</dl>
<input type="submit" value="Vaihda salasana"><?php
  } else {
    // Vaihdetaan salasana passwd -funktiolla.  Sit� varten pit��
    // selvitt�� sen login -tiedon kohde_liiteid, jonka perusteella
    // ollaan kirjautuneena (ja jonne se salasana siis pit��
    // p�ivitt��).
    $llist = qry_login_lista();
    if ( passwd($llist[sessionvar_get('uname')][0]['klid'], $old, $new1, $new2) ) {
      // Salasanan vaihto onnistui.  Palataan sivulle, jolta salasanan
      // vaihtoon tultiin.
      forward(back_url(), 'Salasana vaihdettu.');
      exit;
    } else {
      // Jotain meni pieleen, tulostetaan sivu virheen kera
      require_once('mappi_html-head.php');
      require_once('mappi_html-body-begin.php');
      print_errmsg('Salasanan vaihto ep�onnistui.  Tarkista, ett� kirjoitit vanhan salasanasi oikein ja uuden samalla tavalla molempiin kenttiin.');
    }
  }
  require_once('mappi_html-body-end.php');
?>

