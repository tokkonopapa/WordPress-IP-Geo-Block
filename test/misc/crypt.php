<?php
/**
 * Test of Encrypt / Decrypt
 *
 */
require '../../classes/class-ip-geo-block-util.php';
require dirname( __FILE__ ) . '/class-ip-geo-block-crpt.php';

define( 'NONCE_KEY',  '5bh{!&X0+]Sd6)m,)AXlGT<^R`mNacD#rx2mK7j&c$NA{N~(-A&_}KZv?QxH%nn#' );
define( 'NONCE_SALT', '3bvZ|xD``*.4_mVGnFZ:=^?Y]5WNf`LP/)XeTY^=Vh]B]p)HF]s1c;NC!p9]eEt{' );

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test of Encrypt / Decrypt</title>
</head>
<body>
	<pre>
<?php
	echo base64_encode( convert_uuencode( "I love PHP!" ) ), "\n";
	echo convert_uudecode( base64_decode( "KzIyIUw7VzlFKCUhKDQiJGAKYAo=" ) ), "\n";

	$data = 'Test of Encrypt / Decrypt';
	echo "Original: $data\n";

	$crpt = new IP_Geo_Block_Crypt;
	$encrypted = $crpt->encrypt( $data, NONCE_KEY );
	echo "Encrypted: $encrypted\n";

	$crpt = new IP_Geo_Block_Crypt;
	$decrypted = $crpt->decrypt( $encrypted, NONCE_KEY );
	echo "Decrypted: $decrypted\n";

	print_r( $crpt->get_algorithms() );

// https://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc/
// https://gist.github.com/joshhartman/10342187
// https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong
// http://www.cryptofails.com/post/121201011592/reasoning-by-lego-the-wrong-way-to-think-about

/*
 * This code is copied from 
 * http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc/
 * to demonstrate an attack against it. Specifically, we simulate a timing leak
 * in the MAC comparison which, in a Mac-then-Encrypt (MtA) design, we show
 * breaks confidentiality.
 *
 * Slight modifications such as making it not serialize/unserialize and removing
 * the trim() call were made to simplify the attack (the original code is left
 * in comments). The attack still works, in theory, when these modifications are
 * not made, but it is more involved.
 */

// Define a 32-byte (64 character) hexadecimal encryption key
// Note: The same encryption key used to encrypt the data must be used to decrypt the data
define('ENCRYPTION_KEY', 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282');

// Encrypt Function
function mc_encrypt($encrypt, $key){
    //$encrypt = serialize($encrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
    $key = pack('H*', $key);
    $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
    $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
    $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
    return $encoded;
}

// Decrypt Function
function mc_decrypt($decrypt, $key){
    $decrypt = explode('|', $decrypt.'|');
    $decoded = base64_decode($decrypt[0]);
    $iv = base64_decode($decrypt[1]);
    if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
    $key = pack('H*', $key);
    //$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
    $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv);
    $mac = substr($decrypted, -64);
    $decrypted = substr($decrypted, 0, -64);
    $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
    // if($calcmac!==$mac){ return false; }
    // Simulate the timing side channel with 1-byte granularity.
    if($calcmac!==$mac){ if ($calcmac[0] == $mac[0]) { return 1; } else { return 0; } }
    //$decrypted = unserialize($decrypted);
    return $decrypted;
}

/*
 * Attack code. By Taylor Hornby (@DefuseSec), June 08, 2015.
 */

// The message we want to decrypt.
$message = "Cryptography is harder than you think! You can't assemble it like Lego!";
// Encrypt the message.
$ciphertext = mc_encrypt($message, ENCRYPTION_KEY);

// From here on, all we're allowed to do is (1) Get known plaintexts, and (2)
// Use the (simulated) timing oracle.

// We're going to get the first byte of each decrypted block, so split the
// target ciphertext into blocks.
$ct_parts = explode('|', $ciphertext);
$ct_blocks = str_split(base64_decode($ct_parts[0]), 32);
// Add the IV in front. We need it later and having it in index zero makes the
// code below more conveinient. 
array_unshift($ct_blocks, base64_decode($ct_parts[1]));
// Remove the MAC blocks off the end (we don't care what they decrypt to).
$ct_blocks = array_slice($ct_blocks, 0, -2);

// This array will hold our known plaintexts. We want one known plaintext for
// each byte value \x00 though \xFF. The respective plaintext should be a single
// block that decrypts to a block having that value as its first byte (BEFORE
// the CBC mode XOR).
$firstbytes = array();

// Just keep getting known plaintexts until we have all the byte values.
while (count($firstbytes) < 256) {
    // We're choosing the plaintext here, but that's not necessary. The contents
    // don't matter, as long as we know what the first byte is.
    $kp = mc_encrypt(str_repeat("a", 32), ENCRYPTION_KEY);

    // Break the ciphertext apart.
    $parts = explode('|', $kp);
    $iv = base64_decode($parts[1]);
    $ct = substr(base64_decode($parts[0]), 0, 32);

    // We know the decrypted result after the CBC-mode XOR is "a", so the value
    // just after decryption but before the CBC-mode XOR is this:
    $firstbyte = ord($iv[0]) ^ ord("a");

    // Remember this block as decrypting to block that starts with that byte. 
    // We might have already found one for this byte value, if that's the case
    // we just overwrite it.
    $firstbytes[$firstbyte] = $ct;
}


// Loop over all of the ciphertext blocks we want to decrypt the first byte of.
// (Note: Index 0 is the IV, as we set above).
for ($i = 1; $i < count($ct_blocks); $i++) {
    $ct_block = $ct_blocks[$i];

    // We'll use zero IVs for our oracle queries. We could use anything really.
    $zero_block = str_repeat("\x00", 32);

    // Some padding to be the last (of two) blocks of the MAC.
    $p = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);

    // Find a collision in the first byte of the computed MAC and "included MAC".
    $r = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);
    $ct = base64_encode($r . $ct_block . $p) . "|" . base64_encode($zero_block);
    while (mc_decrypt($ct, ENCRYPTION_KEY) !== 1) {
        $r = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);
        $ct = base64_encode($r . $ct_block . $p) . "|" . base64_encode($zero_block);
    }

    // In the above, the "computed MAC" is the MAC of whatever $r decrypts to
    // with a null IV. The "included MAC" is the first byte of the decrypted
    // value of $ct_block (before the CBC-XOR) XORed with $r[0].

    // So a collision means two things:
    // 1. Decrypting $ct_block with $r as an "IV" makes the first byte a hex
    //    digit, and
    // 2. That hex digit is the same as the one that the MAC of whatever $r
    //    decrypts to starts with.

    // If only we could find out what that hex digit is... then we could find
    // out what the first byte of the decrypted $ct_block is. Let's use our
    // known plaintexts to do just that...

    // Try decrypting with our known-first-byte blocks in place of $ct_block.
    for ($first_byte = 0; $first_byte <= 255; $first_byte++) {
        $ct = base64_encode($r . $firstbytes[$first_byte] . $p) . "|" . base64_encode($zero_block);
        if (($res = mc_decrypt($ct, ENCRYPTION_KEY)) === 1) {
            // The computed MAC value won't have changed, and we know the first
            // byte still matches so that means the first byte (after decryption
            // but before XOR) of our known plaintext block is the same as the
            // first byte (after decryption but before XOR) of $ct_block.

            // We know what that byte is! So let's XOR it with the first byte of
            // the previous block in the whole ciphertext we're working on to
            // get the proper (after CBC-XOR) decryption.

            $iv_decrypted_byte = ord($ct_blocks[$i-1][0]) ^ $first_byte;
            echo "Block $i's first byte is: " . chr($iv_decrypted_byte) . "\n";
            break;
        }
    }
    
}
?>
	</pre>
</body>
</html>
