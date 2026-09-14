<?php

declare( strict_types=1 );

$take = dirname( __DIR__ );
$kōnae = [
	'.htaccess', '.user.ini', 'nginx.conf.example', 'index.php', 'api.php', 'kupu.php',
	'tuhimunatanga.php', 'whiringa.php', 'crypto.js', 'hootuhihawa.js', 'kaahua.css',
	'schema.sql', 'raraunga/raraunga.php', 'raraunga/7776_kupu.db',
	'raraunga/7776_kupu.sha256', 'raraunga/whiringa_whakapiri.ini.example',
	'bin/cleanup.php', 'bin/diagnose.php', 'bin/hanga_kii.php', 'README.md',
	'THREAT_MODEL.md', 'DEPLOYMENT.md', 'WORDLIST_VALIDATION_REPORT.md', 'CHANGELOG.md',
	'VERSION', 'tests/crypto_roundtrip.mjs', 'tests/interface_test.mjs', 'tests/entropy_test.mjs',
	'tests/php_profile_test.php', 'tests/wordlist_test.php',
	'tests/fixtures/profile1.json', 'tests/fixtures/profile2.json',
];
foreach ( $kōnae as $ingoa ) {
	if ( !is_file( $take . '/' . $ingoa ) ) {
		fwrite( STDERR, 'Missing file: ' . $ingoa . PHP_EOL );
		exit( 1 );
	}
}
echo 'Standalone v1.0 file set complete.' . PHP_EOL;
