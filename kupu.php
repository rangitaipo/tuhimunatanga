<?php

declare( strict_types=1 );

$ara_kupu = __DIR__ . '/raraunga/7776_kupu.db';
$ara_haatepe = __DIR__ . '/raraunga/7776_kupu.sha256';

try {
	if ( !is_file( $ara_kupu ) || !is_readable( $ara_kupu ) ) {
		throw new RuntimeException( 'The passphrase wordlist could not be read.' );
	}
	$haatepe_tūturu = hash_file( 'sha256', $ara_kupu );
	$haatepe_whakarite = is_file( $ara_haatepe )
		? strtolower( trim( ( string ) file_get_contents( $ara_haatepe ) ) )
		: '';
	if (
		!is_string( $haatepe_tūturu ) ||
		preg_match( '/^[0-9a-f]{64}$/D', $haatepe_whakarite ) !== 1 ||
		!hash_equals( $haatepe_whakarite, strtolower( $haatepe_tūturu ) )
	) {
		throw new RuntimeException( 'The passphrase wordlist checksum does not match.' );
	}

	$rarangi = file( $ara_kupu, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( !is_array( $rarangi ) ) {
		throw new RuntimeException( 'The passphrase wordlist could not be loaded.' );
	}

	$waehere = [];
	$kupu = [];
	$tāurunga = [];
	foreach ( $rarangi as $tau => $raina ) {
		if ( preg_match( '/^([1-6]{5})[\x20\t]+(.+)$/u', trim( $raina ), $hua ) !== 1 ) {
			throw new RuntimeException( 'The passphrase wordlist contains an invalid row at line ' . ( $tau + 1 ) . '.' );
		}
		$waehere_kupu = $hua[ 1 ];
		$kupu_kotahi = trim( $hua[ 2 ] );
		if ( isset( $waehere[ $waehere_kupu ] ) ) {
			throw new RuntimeException( 'The passphrase wordlist contains a duplicate Diceware code.' );
		}
		if ( $kupu_kotahi === '' || preg_match( '//u', $kupu_kotahi ) !== 1 ) {
			throw new RuntimeException( 'The passphrase wordlist contains an invalid value.' );
		}
		if ( class_exists( 'Normalizer' ) ) {
			$kupu_whakarite = Normalizer::normalize( $kupu_kotahi, Normalizer::FORM_C );
			if ( is_string( $kupu_whakarite ) ) {
				$kupu_kotahi = $kupu_whakarite;
			}
		}
		$waehere[ $waehere_kupu ] = true;
		$kupu[] = $kupu_kotahi;
		$tāurunga[] = [
			'code' => $waehere_kupu,
			'word' => $kupu_kotahi,
		];
	}
	if ( count( $tāurunga ) !== 7_776 || count( $waehere ) !== 7_776 ) {
		throw new RuntimeException( 'The passphrase wordlist does not contain exactly 7,776 unique Diceware codes.' );
	}

	$tatau_kupu = array_count_values( $kupu );
	$kupu_takitahi = count( $tatau_kupu );
	$taurua_teitei = max( $tatau_kupu );
	$teitei_kupu = log( 7_776 / $taurua_teitei, 2 );
	$kupu_katoa = min( 22, max( 7, ( int ) ceil( 256 / $teitei_kupu ) ) );

	$etag = '"' . hash( 'sha256', "v3.1.2-entry-api\0" . $haatepe_tūturu ) . '"';
	if ( ( string ) ( $_SERVER[ 'HTTP_IF_NONE_MATCH' ] ?? '' ) === $etag ) {
		http_response_code( 304 );
		exit;
	}
	header( 'Content-Type: application/json; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=86400' );
	header( 'ETag: ' . $etag );
	header( 'X-Content-Type-Options: nosniff' );
	echo json_encode(
		[
			'ok' => true,
			'entries' => $tāurunga,
			'sha256' => $haatepe_tūturu,
			'distinct_words' => $kupu_takitahi,
			'maximum_multiplicity' => $taurua_teitei,
			'wordlist_size' => count( $tāurunga ),
			'minimum_words' => 7,
			'maximum_words' => 22,
			'recommended_words' => $kupu_katoa,
			'passphrase_words' => $kupu_katoa,
		],
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
	);
} catch ( Throwable $hapa ) {
	error_log( 'Tuhimunatanga v3 wordlist failure: ' . $hapa->getMessage() );
	http_response_code( 500 );
	header( 'Content-Type: application/json; charset=UTF-8' );
	header( 'Cache-Control: no-store' );
	echo json_encode(
		[ 'ok' => false, 'message' => 'The passphrase wordlist could not be loaded.' ],
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
}
