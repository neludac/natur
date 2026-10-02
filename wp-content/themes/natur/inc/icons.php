<?php
/**
 * Iconițe SVG (desen liniar 24×24; majoritatea după Lucide, licență ISC).
 */

defined( 'ABSPATH' ) || exit;

function nt_icon_paths() {
	return array(
		'search'      => '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.2-4.2"/>',
		'user'        => '<circle cx="12" cy="8" r="4.5"/><path d="M19.5 21a7.5 7.5 0 0 0-15 0"/>',
		'bag'         => '<path d="M5.5 7.5h13l-.9 11.6a2 2 0 0 1-2 1.9H8.4a2 2 0 0 1-2-1.9z"/><path d="M9 9.5V7a3 3 0 0 1 6 0v2.5"/>',
		'menu'        => '<path d="M4 6.5h16M4 12h10M4 17.5h16"/>',
		'close'       => '<path d="M18 6 6 18M6 6l12 12"/>',
		'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-left'  => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
		'arrow-up'    => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'arrow-out'   => '<path d="M7 17 17 7M8 7h9v9"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'check'       => '<path d="M20 6 9 17l-5-5"/>',
		'chevron'     => '<path d="m6 9 6 6 6-6"/>',
		'truck'       => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
		'leaf'        => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
		'sprout'      => '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
		'coins'       => '<path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/>',
		'clock'       => '<circle cx="12" cy="12" r="9.5"/><path d="M12 7v5l3 2"/>',
		'gift'        => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
		'phone'       => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'        => '<rect width="20" height="16" x="2" y="4" rx="3"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'pin'         => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'shield'      => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
		'heart'       => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
		'home'        => '<path d="M3.5 10.2 12 3.5l8.5 6.7V19a2 2 0 0 1-2 2H15v-6H9v6H5.5a2 2 0 0 1-2-2z"/>',
		'grid'        => '<rect width="7.5" height="7.5" x="3" y="3" rx="2"/><rect width="7.5" height="7.5" x="13.5" y="3" rx="2"/><rect width="7.5" height="7.5" x="13.5" y="13.5" rx="2"/><rect width="7.5" height="7.5" x="3" y="13.5" rx="2"/>',
		'chat'        => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="m8 13 2.5-2.5L13 12l3-3"/>',
		'instagram'   => '<rect width="19" height="19" x="2.5" y="2.5" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><path d="M17.4 6.6h.01" stroke-width="2.6"/>',
		'facebook'    => '<path d="M14 8.5V6.8c0-.8.5-1 1-1h2.3V2.1L14.2 2C10.9 2 10 4.4 10 6.4v2.1H7.6V12H10v10h4V12h3l.4-3.5z" fill="currentColor" stroke="none"/>',
		'star'        => '<path d="m12 2.5 2.94 5.96 6.56.95-4.75 4.63 1.12 6.54L12 17.5l-5.87 3.08 1.12-6.54L2.5 9.41l6.56-.95z"/>',
		'pointer'     => '<path d="M9 9l5 12 1.8-5.2L21 14Z"/><path d="M7.2 2.2 8 5.1"/><path d="m5.1 8-2.9-.8"/><path d="M14 4.1 12 6"/><path d="m6 12-1.9 2"/>',
		'rotate'      => '<path d="M16.47 7.5C15.64 4.24 13.95 2 12 2 9.24 2 7 6.48 7 12s2.24 10 5 10c.34 0 .68-.07 1-.2"/><path d="m15.19 13.71 3.82 1.86-1.86 3.81"/><path d="M19 15.57c-1.8.89-4.27 1.43-7 1.43-5.52 0-10-2.24-10-5s4.48-5 10-5c4.84 0 8.87 1.72 9.8 4"/>',
		'play'        => '<path d="M7 4.5v15a1 1 0 0 0 1.5.86l12.5-7.5a1 1 0 0 0 0-1.72L8.5 3.64A1 1 0 0 0 7 4.5Z"/>',
		'hand'        => '<path d="M18 11.5V9a2 2 0 0 0-4 0v1.4"/><path d="M14 10V8a2 2 0 0 0-4 0v2"/><path d="M10 9.9V9a2 2 0 0 0-4 0v5"/><path d="M6 14a2 2 0 0 0-4 0"/><path d="M18 11a2 2 0 1 1 4 0v3a8 8 0 0 1-8 8h-4a8 8 0 0 1-8-8"/>',
		'package'     => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.7 4.4a2 2 0 0 0 2 0L20.7 7"/><path d="m7.5 4.27 9 5.15"/>',
		'copy'        => '<rect width="13" height="13" x="8.5" y="8.5" rx="2.5"/><path d="M15.5 8.5V5a2.5 2.5 0 0 0-2.5-2.5H5A2.5 2.5 0 0 0 2.5 5v8A2.5 2.5 0 0 0 5 15.5h3.5"/>',
		'trash'       =>'<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 1H8a2 2 0 0 1-2-1L5 6"/>',
		'eye'         => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
		'sparkle'     => '<path d="M12 2.5c.7 5 2.5 6.8 7.5 7.5-5 .7-6.8 2.5-7.5 7.5-.7-5-2.5-6.8-7.5-7.5 5-.7 6.8-2.5 7.5-7.5Z" fill="currentColor" stroke="none"/>',
		'flower'      => '<g fill="currentColor" stroke="none"><circle cx="12" cy="5.6" r="3.4"/><circle cx="18.1" cy="10" r="3.4"/><circle cx="15.8" cy="17.2" r="3.4"/><circle cx="8.2" cy="17.2" r="3.4"/><circle cx="5.9" cy="10" r="3.4"/></g><circle cx="12" cy="12" r="2.8" fill="var(--nt-flower-center, #FFC845)" stroke="none"/>',
		'egg'         => '<path d="M12 21.5c-4 0-7-2.9-7-7.2C5 9.2 8.2 2.5 12 2.5s7 6.7 7 11.8c0 4.3-3 7.2-7 7.2Z"/>',
		'milk'        => '<path d="M8 2h8M9 2v2.8a4 4 0 0 1-.7 2.2L7.7 8A4 4 0 0 0 7 10.2V20a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-9.8A4 4 0 0 0 16.3 8l-.6-.9A4 4 0 0 1 15 4.8V2"/><path d="M7 15a6.47 6.47 0 0 1 5 0 6.47 6.47 0 0 0 5 0"/>',
	);
}

/**
 * Iconiță SVG inline.
 *
 * @param string $name  Cheie din nt_icon_paths().
 * @param int    $size  Lățime/înălțime în px.
 * @param string $class Clase suplimentare.
 */
function nt_icon( $name, $size = 20, $class = '' ) {
	$paths = nt_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="nt-i nt-i--%1$s %2$s" width="%3$d" height="%3$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%4$s</svg>',
		esc_attr( $name ),
		esc_attr( $class ),
		(int) $size,
		$paths[ $name ]
	);
}
