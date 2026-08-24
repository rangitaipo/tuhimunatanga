<?php

declare( strict_types=1 );

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

require_once( dirname( __DIR__ ) . '/whiringa.php' );
require_once( dirname( __DIR__ ) . '/raraunga/raraunga.php' );

$katoa_tika = true;

function whakaatu_hua( string $ingoa, bool $tika, string $karere ): void {
	global $katoa_tika;
	$katoa_tika = $katoa_tika && $tika;
	echo ( $tika ? '[OK]   ' : '[FAIL] ' ) . $ingoa . ': ' . $karere . PHP_EOL;
}

whakaatu_hua(
	'PHP version',
	PHP_VERSION_ID >= 80100,
	PHP_VERSION
);

whakaatu_hua(
	'PDO extension',
	extension_loaded( 'pdo' ),
	extension_loaded( 'pdo' ) ? 'loaded' : 'missing'
);

whakaatu_hua(
	'pdo_mysql extension',
	extension_loaded( 'pdo_mysql' ),
	extension_loaded( 'pdo_mysql' ) ? 'loaded' : 'missing'
);

try {
	$whiringa = WhiringaTuhimunatanga::tikina();
	whakaatu_hua( 'Configuration', true, 'loaded' );

	$uara_huna = [
		'ingoa_raraunga' => ( string ) $whiringa[ 'ingoa_raraunga' ],
		'ingoa' => ( string ) $whiringa[ 'ingoa' ],
		'kii_taupaanga' => ( string ) $whiringa[ 'kii_taupaanga' ],
	];

	foreach ( $uara_huna as $ingoa => $uara ) {
		$placeholder = $uara === '' || str_starts_with( $uara, 'CHANGE_ME' );
		whakaatu_hua(
			'Configuration value ' . $ingoa,
			!$placeholder,
			$placeholder ? 'not configured' : 'configured'
		);
	}
} catch ( Throwable $hapa ) {
	whakaatu_hua( 'Configuration', false, $hapa->getMessage() );
	$whiringa = null;
}

if ( is_array( $whiringa ) && extension_loaded( 'pdo_mysql' ) ) {
	try {
		$raraunga = new Raraunga();
		$pdo = $raraunga->hono();
		whakaatu_hua( 'Database connection', true, 'connected' );

		foreach ( [ 'nga_taaurunga_v3', 'nga_ngana_v3' ] as $ripanga ) {
			$taauaki = $pdo->query( 'SHOW TABLES LIKE ' . $pdo->quote( $ripanga ) );
			$kitea = $taauaki !== false && $taauaki->fetchColumn() !== false;
			whakaatu_hua(
				'Database table ' . $ripanga,
				$kitea,
				$kitea ? 'present' : 'missing, import schema.sql'
			);
		}

		try {
			$pdo->query( 'SELECT COUNT(*) FROM `nga_taaurunga_v3`' );
			$pdo->query( 'SELECT COUNT(*) FROM `nga_ngana_v3`' );
			whakaatu_hua( 'Database SELECT permissions', true, 'available' );
		} catch ( Throwable $hapa ) {
			whakaatu_hua( 'Database SELECT permissions', false, $hapa->getMessage() );
		}
	} catch ( Throwable $hapa ) {
		$karere = $hapa->getMessage();
		if ( $hapa->getPrevious() instanceof Throwable ) {
			$karere .= ' Caused by: ' . $hapa->getPrevious()->getMessage();
		}
		whakaatu_hua( 'Database connection', false, $karere );
	}
}

$ara_kupu = dirname( __DIR__ ) . '/raraunga/7776_kupu.db';
$ara_hash = dirname( __DIR__ ) . '/raraunga/7776_kupu.sha256';
whakaatu_hua(
	'Wordlist file',
	is_file( $ara_kupu ) && is_readable( $ara_kupu ),
	is_file( $ara_kupu ) && is_readable( $ara_kupu ) ? 'readable' : 'missing or unreadable'
);

if ( is_file( $ara_kupu ) && is_file( $ara_hash ) ) {
	$hash_tūturu = hash_file( 'sha256', $ara_kupu );
	$hash_manako = trim( ( string ) file_get_contents( $ara_hash ) );
	whakaatu_hua(
		'Wordlist checksum',
		is_string( $hash_tūturu ) && hash_equals( $hash_manako, $hash_tūturu ),
		is_string( $hash_tūturu ) && hash_equals( $hash_manako, $hash_tūturu )
			? 'matches'
			: 'does not match'
	);
}

if ( is_file( $ara_kupu ) && is_readable( $ara_kupu ) ) {
	$rarangi_kupu = file( $ara_kupu, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( is_array( $rarangi_kupu ) ) {
		$waehere = [];
		$kupu = [];
		foreach ( $rarangi_kupu as $raina ) {
			if ( preg_match( '/^([1-6]{5})[\x20\t]+(.+)$/u', trim( $raina ), $hua ) !== 1 ) {
				continue;
			}
			$waehere[ $hua[ 1 ] ] = true;
			$kupu[] = trim( $hua[ 2 ] );
		}
		$tatau = array_count_values( $kupu );
		$taurua_teitei = $tatau === [] ? 0 : max( $tatau );
		$kupu_katoa = $taurua_teitei > 0
			? max( 20, ( int ) ceil( 256 / log( 7_776 / $taurua_teitei, 2 ) ) )
			: 0;
		whakaatu_hua(
			'Wordlist Diceware codes',
			count( $rarangi_kupu ) === 7_776 && count( $waehere ) === 7_776,
			count( $waehere ) . ' unique codes'
		);
		echo '[INFO] Wordlist displayed values: ' . count( $tatau ) . ' distinct; passphrase generation: ' . $kupu_katoa . ' words' . PHP_EOL;
	}
}

exit( $katoa_tika ? 0 : 1 );
