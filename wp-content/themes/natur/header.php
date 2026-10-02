<?php
/**
 * Antetul site-ului (înlocuiește antetul Astra; păstrează cârligele Astra pentru compatibilitate).
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content">Salt la conținut</a>

<div id="page" class="hfeed site">
	<?php
	astra_header_before();
	get_template_part( 'parts/header' );
	astra_header_after();
	astra_content_before();
	?>
	<div id="content" class="site-content">
		<div class="ast-container">
		<?php astra_content_top(); ?>
