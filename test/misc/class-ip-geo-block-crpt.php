<?php
/**
 * IP Geo Block - Encrypt / Decrypt (insecure)
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @see       https://php.net/manual/en/function.mcrypt-module-open.php
 * @see       https://github.com/defuse/php-encryption
 */

class IP_Geo_Block_Crypt {

	private $td; // encryption descriptor
	private $iv; // initialization vector
	private $key;

	public function __construct() {
		if ( extension_loaded( 'mcrypt' ) && ( $this->td = mcrypt_module_open( MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB /*MCRYPT_MODE_CBC*/, '' ) ) ) {
			// Create the IV and determine the keysize length (Prior to 5.3.0, MCRYPT_RAND was the only one supported on Windows)
			if ( $this->iv = mcrypt_create_iv( mcrypt_enc_get_iv_size( $this->td ), MCRYPT_DEV_URANDOM ) ) {
				// The maximum supported keysize of the opened mode
				$ks = mcrypt_enc_get_key_size( $this->td );

				// Create key (PHP 5 >= 5.1.2, PHP 7, PECL hash >= 1.1)
				$this->key = substr( IP_Geo_Block_Util::hash_hmac( 'md5', NONCE_KEY, NONCE_SALT ), 0, $ks );
			}
		}
	}

	public function __destruct() {
		if ( $this->td ) {
			mcrypt_generic_deinit( $this->td ); // Deinitializes an encryption module
			mcrypt_module_close( $this->td );   // Closes the mcrypt module
		}
	}

	public function encrypt( $input ) {
		if ( ! $this->td || ! $this->iv )
			return FALSE;

		// Intialize encryption
		mcrypt_generic_init( $this->td, $this->key, $this->iv );

		// Encrypt data
		$encrypted = mcrypt_generic( $this->td, base64_encode( $input ) );

		return base64_encode( $encrypted );
	}

	public function decrypt( $encrypted ) {
		if ( ! $this->td || ! $this->iv )
			return FALSE;

		// Intialize encryption
		mcrypt_generic_init( $this->td, $this->key, $this->iv );

		// Decrypt encrypted string
		$encrypted = base64_decode( $encrypted, TRUE );
		$decrypted = mdecrypt_generic( $this->td, $encrypted );

		return base64_decode( $decrypted, TRUE );
	}

	public function get_algorithms() {
		return mcrypt_list_algorithms();
	}

}