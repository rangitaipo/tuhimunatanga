<?php

declare( strict_types=1 );

require_once( __DIR__ . '/tuhimunatanga.php' );

try {
	$Tuhimunatanga = new Tuhimunatanga();
	$Tuhimunatanga->whakahaere_api();
} catch ( Throwable $hapa ) {
	$tohu_hapa = strtoupper(
		substr( hash( 'sha256', $hapa->getMessage() . "\0" . microtime( true ) ), 0, 12 )
	);
	error_log(
		'Tuhimunatanga v3 bootstrap failure [' . $tohu_hapa . ']: ' .
		get_class( $hapa ) . ': ' . $hapa->getMessage()
	);
	if ( !headers_sent() ) {
		http_response_code( 500 );
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Cache-Control: no-store' );
		header( 'X-Content-Type-Options: nosniff' );
	}
	echo json_encode(
		[
			'ok' => false,
			'code' => 'bootstrap_error',
			'message' => 'The v3 service could not be started.',
			'reference' => $tohu_hapa,
		],
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
}
