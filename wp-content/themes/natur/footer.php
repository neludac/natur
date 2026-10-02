<?php
/**
 * Subsolul site-ului + panourile laterale (meniu, coș, căutare) și bara de navigare pentru mobil.
 */

defined( 'ABSPATH' ) || exit;
?>
		<?php astra_content_bottom(); ?>
		</div><!-- .ast-container -->
	</div><!-- #content -->
	<?php
	astra_content_after();
	astra_footer_before();
	get_template_part( 'parts/footer' );
	astra_footer_after();
	?>
</div><!-- #page -->
<?php
get_template_part( 'parts/overlays' );
astra_body_bottom();
wp_footer();
?>
</body>
</html>
