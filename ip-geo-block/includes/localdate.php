<?php
/**
 * Return local time of date.
 *
 */
function ip_geo_block_localdate( $timestamp = FALSE, $format = NULL ) {
	static $offset = NULL;
	if ( $offset === NULL )
		$offset = (int)( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

	if ( ! $format )
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

	return date_i18n( $format, $timestamp ? (int)$timestamp + $offset : FALSE );
}