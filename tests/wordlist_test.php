<?php

declare( strict_types=1 );

$ara = dirname( __DIR__ ) . '/raraunga/7776_kupu.db';
$rarangi = file( $ara, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
if ( !is_array( $rarangi ) ) {
	fwrite( STDERR, "Wordlist could not be read.\n" );
	exit( 1 );
}

$waehere = [];
$kupu = [];
$kupu_hē = [];
foreach ( $rarangi as $tau => $raina ) {
	if ( preg_match( '/^([1-6]{5})[\x20\t]+(.+)$/u', trim( $raina ), $hua ) !== 1 ) {
		fwrite( STDERR, 'Invalid row: ' . ( $tau + 1 ) . PHP_EOL );
		exit( 1 );
	}
	if ( isset( $waehere[ $hua[ 1 ] ] ) ) {
		fwrite( STDERR, 'Duplicate Diceware code: ' . $hua[ 1 ] . PHP_EOL );
		exit( 1 );
	}

	$kupu_kotahi = trim( $hua[ 2 ] );
	if ( class_exists( 'Normalizer' ) ) {
		$kupu_whakarite = Normalizer::normalize( $kupu_kotahi, Normalizer::FORM_C );
		if ( is_string( $kupu_whakarite ) ) {
			$kupu_kotahi = $kupu_whakarite;
		}
	}
	if ( preg_match( '//u', $kupu_kotahi ) !== 1 ) {
		fwrite( STDERR, 'Invalid UTF-8 at line ' . ( $tau + 1 ) . PHP_EOL );
		exit( 1 );
	}

	$roa = preg_match_all( '/./us', $kupu_kotahi );
	if ( $roa === false || $roa < 2 || $roa > 13 || preg_match( '/^\p{L}+$/u', $kupu_kotahi ) !== 1 ) {
		$kupu_hē[] = [
			'line' => $tau + 1,
			'code' => $hua[ 1 ],
			'word' => $kupu_kotahi,
		];
	}

	$waehere[ $hua[ 1 ] ] = true;
	$kupu[] = $kupu_kotahi;
}

if ( count( $kupu ) !== 7_776 || count( $waehere ) !== 7_776 ) {
	fwrite( STDERR, "Expected 7,776 entries and unique codes.\n" );
	exit( 1 );
}

$tatau = array_count_values( $kupu );
$taurua_teitei = max( $tatau );
$teitei_kupu = log( 7_776 / $taurua_teitei, 2 );
$kupu_katoa = min( 22, max( 7, ( int ) ceil( 256 / $teitei_kupu ) ) );

if ( $kupu_katoa < 7 || $kupu_katoa > 22 ) {
	fwrite( STDERR, "Calculated passphrase length is outside the supported range.\n" );
	exit( 1 );
}

echo 'Entries: 7,776' . PHP_EOL;
echo 'Unique codes: 7,776' . PHP_EOL;
echo 'Distinct displayed values: ' . count( $tatau ) . PHP_EOL;
echo 'Maximum displayed-value multiplicity: ' . $taurua_teitei . PHP_EOL;
echo 'Supported generated words: 7 to 22' . PHP_EOL;
echo 'Recommended target words: ' . $kupu_katoa . PHP_EOL;
echo 'Entries awaiting curator review: ' . count( $kupu_hē ) . PHP_EOL;

$entropy_22 = 22 * $teitei_kupu;
$effective_22 = min( $entropy_22, 256 );
$quantum_22 = $effective_22 / 2;
if ( $quantum_22 < 128 ) {
	fwrite( STDERR, "The 22-word option does not reach the 128-bit simplified quantum-search target.\n" );
	exit( 1 );
}
echo '22-word conservative entropy: ' . number_format( $entropy_22, 2, '.', '' ) . ' bits' . PHP_EOL;
echo '22-word simplified quantum-search estimate: ' . number_format( $quantum_22, 2, '.', '' ) . ' bits' . PHP_EOL;
