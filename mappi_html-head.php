<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title>Mappi &gt; <?php 
echo $settings['name']; 
echo ' &gt; ', strip_tags($pagename); ?></title>
<?php if ($settings['css'] != ''): ?>
<link rel="stylesheet" href="<?php echo $settings['css']; ?>" type="text/css">
<?php endif; ?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="epoch/epoch_styles.css" />
<script type="text/javascript">function getDateFormat() { return 'Y-m-d'; }</script>
<script type="text/javascript" src="epoch/epoch_classes.js"></script>
<?php echo $extrahead; ?>
</head>
