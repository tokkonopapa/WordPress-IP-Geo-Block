<?php
/**
 * Return local time of day.
 *
 */
function ip_geo_block_localdate( $timestamp = FALSE, $fmt = NULL ) {
	static $offset = NULL;
	static $format = NULL;

	if ( NULL === $offset )
		$offset = wp_timezone_override_offset() * HOUR_IN_SECONDS;

	if ( NULL === $format )
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

	return date_i18n( $fmt ? $fmt : $format, $timestamp ? (int)$timestamp + $offset : FALSE );
}