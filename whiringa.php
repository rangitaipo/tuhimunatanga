<?php

declare( strict_types=1 );

final class WhiringaTuhimunatanga {
	/** @var array<string,mixed>|null */
	private static ?array $whiringa = null;

	/** @return array<string,mixed> */
	public static function tikina(): array {
		if ( is_array( self::$whiringa ) ) {
			return self::$whiringa;
		}

		$ara = getenv( 'TUHIMUNATANGA_V3_CONFIG' );
		if ( !is_string( $ara ) || $ara === '' ) {
			$ara = __DIR__ . '/raraunga/whiringa_whakapiri.ini';
		}

		$kōnae = [];
		if ( is_file( $ara ) && is_readable( $ara ) ) {
			$hua = parse_ini_file( $ara, false, INI_SCANNER_RAW );
			if ( is_array( $hua ) ) {
				$kōnae = $hua;
			}
		}

		$whiringa = [
			'raraunga_waahitau_tukutuku' => self::uara(
				'TUHIMUNATANGA_V3_DB_HOST',
				$kōnae[ 'raraunga_waahitau_tukutuku' ] ?? '127.0.0.1'
			),
			'tauranga' => ( int ) self::uara(
				'TUHIMUNATANGA_V3_DB_PORT',
				$kōnae[ 'tauranga' ] ?? '3306'
			),
			'ingoa_raraunga' => self::uara(
				'TUHIMUNATANGA_V3_DB_NAME',
				$kōnae[ 'ingoa_raraunga' ] ?? ''
			),
			'ingoa' => self::uara(
				'TUHIMUNATANGA_V3_DB_USER',
				$kōnae[ 'ingoa' ] ?? ''
			),
			'kupuhipa' => self::uara(
				'TUHIMUNATANGA_V3_DB_PASSWORD',
				$kōnae[ 'kupuhipa' ] ?? ''
			),
			'kii_taupaanga' => self::uara(
				'TUHIMUNATANGA_V3_APP_KEY',
				$kōnae[ 'kii_taupaanga' ] ?? ''
			),
			'papa_hononga' => self::uara(
				'TUHIMUNATANGA_V3_BASE_URL',
				$kōnae[ 'papa_hononga' ] ?? ''
			),
			'rohe_waa' => self::uara(
				'TUHIMUNATANGA_V3_TIMEZONE',
				$kōnae[ 'rohe_waa' ] ?? 'Pacific/Auckland'
			),
			'patuiro' => in_array(
				strtolower(
					self::uara(
						'TUHIMUNATANGA_V3_DEBUG',
						$kōnae[ 'patuiro' ] ?? '0'
					)
				),
				[ '1', 'true', 'yes', 'on' ],
				true
			),
		];

		if ( $whiringa[ 'ingoa_raraunga' ] === '' || $whiringa[ 'ingoa' ] === '' ) {
			throw new RuntimeException( 'The v3 database configuration is incomplete.' );
		}
		if ( $whiringa[ 'tauranga' ] < 1 || $whiringa[ 'tauranga' ] > 65535 ) {
			throw new RuntimeException( 'The v3 database port is invalid.' );
		}
		if ( $whiringa[ 'kii_taupaanga' ] === '' ) {
			throw new RuntimeException( 'The v3 application key is not configured.' );
		}
		if ( $whiringa[ 'papa_hononga' ] !== '' ) {
			$papa = filter_var( $whiringa[ 'papa_hononga' ], FILTER_VALIDATE_URL );
			$kawa = strtolower( ( string ) parse_url( $whiringa[ 'papa_hononga' ], PHP_URL_SCHEME ) );
			if ( $papa === false || !in_array( $kawa, [ 'https', 'http' ], true ) ) {
				throw new RuntimeException( 'The v3 base URL is invalid.' );
			}
		}

		self::$whiringa = $whiringa;
		return self::$whiringa;
	}

	private static function uara( string $ingoa, mixed $taunoa ): string {
		$uara = getenv( $ingoa );
		if ( is_string( $uara ) && $uara !== '' ) {
			return $uara;
		}
		return is_scalar( $taunoa ) ? ( string ) $taunoa : '';
	}
}
