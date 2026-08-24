<?php

declare( strict_types=1 );

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
