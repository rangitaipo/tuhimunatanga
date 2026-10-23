<?php

declare( strict_types=1 );

// CLI only. As a web request this script has no STDOUT/STDERR constants and
// would delete rows with nothing but the web-server rules standing in front
// of it.
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

require_once( dirname( __DIR__ ) . '/raraunga/raraunga.php' );

try {
	$raraunga = new Raraunga();
	$inaianei = time();
	$mukua = $raraunga->mukua_nga_mea_kua_pau( $inaianei );
	$ngana = $raraunga->whakapai_ngana( $inaianei - 86_400 );
	fwrite( STDOUT, 'Expired pastes removed: ' . $mukua . PHP_EOL );
	fwrite( STDOUT, 'Old rate-limit rows removed: ' . $ngana . PHP_EOL );
} catch ( Throwable $hapa ) {
	fwrite( STDERR, 'Tuhimunatanga v3 cleanup failed: ' . $hapa->getMessage() . PHP_EOL );
	exit( 1 );
}
