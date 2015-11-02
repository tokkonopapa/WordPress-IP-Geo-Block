<?php

class PEAR {
	public static function raiseError( $msg ) {
		return false;
	}
	public static function isError( $data, $msgcode ) {
		return false === $data;
	}
}
?>