<?php

declare( strict_types=1 );

final class WhiringaTuhimunatanga {
	/**
	 * Lowest accepted patch release for each PHP branch, keyed by branch id
	 * (80200 is PHP 8.2). Each value is a security release: an older patch
	 * level of the same branch is refused even though its branch is newer
	 * than the floor.
	 */
	public const PHP_TAKAI_ITI = [
		80200 => 80235,
		80300 => 80336,
		80400 => 80427,
		80500 => 80512,
	];

	public const PHP_TAKAI_ITI_O_KOOTOA = 80235;

	public static function php_he_haumaru(): bool {
		return self::php_id_he_haumaru( PHP_VERSION_ID );
	}

	public static function php_id_he_haumaru( int $id ): bool {
		if ( $id < self::PHP_TAKAI_ITI_O_KOOTOA ) {
			return false;
		}

		$wae = intdiv( $id, 100 ) * 100;
		$iti = self::PHP_TAKAI_ITI[ $wae ] ?? self::PHP_TAKAI_ITI_O_KOOTOA;

		return $id >= $iti;
	}

	public static function php_karere(): string {
		return sprintf(
			'PHP %s is not supported. This release requires PHP 8.2.35 or newer, ' .
			'and at least 8.3.36, 8.4.27 or 8.5.12 on those branches.',
			PHP_VERSION
		);
	}

	/** @return array<string,mixed>|null */
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
			'poutoko_whirinaki' => self::uara(
				'TUHIMUNATANGA_V3_TRUSTED_PROXIES',
				$kōnae[ 'poutoko_whirinaki' ] ?? '127.0.0.1/8, ::1'
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
