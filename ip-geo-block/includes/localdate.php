<?php
/**
 * Return local time of date.
 *
 */
function ip_geo_block_localdate( $timestamp, $format = NULL ) {
	if ( ! $format )
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	return $timestamp ? date_i18n( $format, (int)$timestamp ) : '';
}