<?php

declare( strict_types=1 );

require_once( dirname( __DIR__ ) . '/tuhimunatanga.php' );

$whakaata = new ReflectionClass( Tuhimunatanga::class );
$taupānga = $whakaata->newInstanceWithoutConstructor();

$haatepe_tika = $whakaata->getMethod( 'haatepe_tika' );
$haatepe_hanga_tika = $whakaata->getMethod( 'haatepe_hanga_tika' );
$kopaki_tika = $whakaata->getMethod( 'kopaki_tika' );

if ( !$haatepe_tika->invoke( $taupānga, 'Ab3De5Gh7Jk9MnP' ) ) {
	throw new RuntimeException( 'A valid 15-character legacy token was rejected.' );
}
if ( !$haatepe_tika->invoke( $taupānga, 'Ab3De5Gh7Jk9MnPq2St4Vx' ) ) {
	throw new RuntimeException( 'A valid 22-character token was rejected.' );
}
if ( $haatepe_hanga_tika->invoke( $taupānga, 'Ab3De5Gh7Jk9MnP' ) ) {
	throw new RuntimeException( 'Legacy token was accepted for new creation.' );
}
if ( !$haatepe_hanga_tika->invoke( $taupānga, 'Ab3De5Gh7Jk9MnPq2St4Vx' ) ) {
	throw new RuntimeException( 'A 22-character token was rejected for new creation.' );
}

$fixture1 = json_decode(
	( string ) file_get_contents( __DIR__ . '/fixtures/profile1.json' ),
	true,
	16,
	JSON_THROW_ON_ERROR
);
$fixture2 = json_decode(
	( string ) file_get_contents( __DIR__ . '/fixtures/profile2.json' ),
	true,
	16,
	JSON_THROW_ON_ERROR
);

if ( !$kopaki_tika->invoke( $taupānga, $fixture1[ 'envelope' ], false ) ) {
	throw new RuntimeException( 'Profile-1 envelope was rejected for retrieval.' );
}
if ( $kopaki_tika->invoke( $taupānga, $fixture1[ 'envelope' ], true ) ) {
	throw new RuntimeException( 'Profile-1 envelope was accepted for new creation.' );
}
if ( !$kopaki_tika->invoke( $taupānga, $fixture2[ 'envelope' ], false ) ) {
	throw new RuntimeException( 'Profile-2 envelope was rejected for retrieval.' );
}
if ( !$kopaki_tika->invoke( $taupānga, $fixture2[ 'envelope' ], true ) ) {
	throw new RuntimeException( 'Profile-2 envelope was rejected for new creation.' );
}

echo 'PHP profile and token validation passed.' . PHP_EOL;
