<?php
/**
 * Șterge datele create de testele e2e: comenzi și clienți (e-mail e2e-*@example.com),
 * produse „E2E …” și categorii „E2E …”.
 * Rulare: ddev wp eval-file tools/tests/cleanup.php
 */
$n = 0;
foreach ( wc_get_orders( array( 'limit' => -1, 'billing_email' => '' ) ) as $order ) {
	if ( preg_match( '/^(e2e-|test-)[^@]*@example\.com$/', $order->get_billing_email() ) ) {
		$order->delete( true );
		$n++;
	}
}
$p = 0;
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 's' => 'E2E' ) ) as $post ) {
	if ( 0 === strpos( $post->post_title, 'E2E ' ) ) {
		wc_get_product( $post->ID )->delete( true );
		$p++;
	}
}
$c = 0;
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'name__like' => 'E2E' ) ) as $term ) {
	if ( 0 === strpos( $term->name, 'E2E ' ) ) {
		wp_delete_term( $term->term_id, 'product_cat' );
		$c++;
	}
}
$u = 0;
foreach ( get_users( array( 'search' => '*@example.com', 'search_columns' => array( 'user_email' ) ) ) as $user ) {
	if ( preg_match( '/^(e2e-|client\d+)/', $user->user_email ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user->ID );
		$u++;
	}
}
WP_CLI::success( "Șterse: $n comenzi, $p produse, $c categorii, $u utilizatori de test." );
