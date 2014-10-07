<?php
/**
 * Return local time of date.
 *
 */
function ip_geo_block_localdate( $timestamp ) {
	if ( $timestamp )
		return date_i18n(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			(int)$timestamp
		);
	else
		return '';
}