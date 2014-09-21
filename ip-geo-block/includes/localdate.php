<?php
/**
 * Return local time of date.
 *
 */
function ip_geo_block_localdate( $timestamp ) {
	if ( $timestamp )
		return date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			intval( $timestamp )
		);
	else
		return '';
}