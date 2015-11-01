<?php

class PEAR {
	static raiseError( $msg ) {
		return false;
	}
	static isError( $data, $msgcode ) {
		return false === $data;
	}
}
?>