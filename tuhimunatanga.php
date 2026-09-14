<?php

declare( strict_types=1 );

require_once( __DIR__ . '/whiringa.php' );
require_once( __DIR__ . '/raraunga/raraunga.php' );

final class Tuhimunatanga {
	public const HAATEPE_ROA_TAWHITO = 15;
	public const HAATEPE_ROA = 22;
	public const PUTANGA_WHAKAMUNA = 3;
	public const KŌNAE_WHAKAMUNA_TAWHITO = 1;
	public const KŌNAE_WHAKAMUNA_HOU = 2;
	public const ARATUKA_WHAKAMUNA = 'AES-256-GCM';
	public const ARATUKA_KDF = 'PBKDF2-SHA-256';
	public const TAKAI_KDF = 600_000;
	public const RAHI_KOPAKI_TEITEI = 4_500_000;

	public string $taitara = 'Tuhimunatanga v1.0 | Browser-encrypted paste';
	public string $csrf = '';
	public string $karere_hapa = '';

	private Raraunga $raraunga;
	private string $kii_taupaanga;
	/** @var array<string,mixed> */
	private array $whiringa;

	public function __construct( ?Raraunga $raraunga = null ) {
		if ( PHP_VERSION_ID < 80100 ) {
			throw new RuntimeException( 'PHP 8.1 or newer is required.' );
		}
		if ( !extension_loaded( 'pdo_mysql' ) ) {
			throw new RuntimeException( 'The PHP pdo_mysql extension is required.' );
		}

		$this->whiringa = WhiringaTuhimunatanga::tikina();
		if ( !@date_default_timezone_set( $this->whiringa[ 'rohe_waa' ] ) ) {
			throw new RuntimeException( 'The v3 timezone is invalid.' );
		}

		$this->kii_taupaanga = $this->wetewete_kii( $this->whiringa[ 'kii_taupaanga' ] );
		$this->raraunga = $raraunga ?? new Raraunga();
		$this->whakarite_watu();
		$this->csrf = $this->tikina_csrf();
	}

	public function whakahaere_api(): never {
		$tikanga = strtoupper( ( string ) ( $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET' ) );
		$mahi = is_string( $_POST[ 'mahi' ] ?? null ) ? $_POST[ 'mahi' ] : '';

		if ( $tikanga !== 'POST' ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'method_not_allowed', 'message' => 'POST is required.' ],
				405
			);
		}
		if ( $mahi === 'hanga_v3' ) {
			$this->hanga_whakapiri();
		}
		if ( $mahi === 'tiro_v3' ) {
			$this->tikina_whakapiri();
		}
		if ( $mahi === 'mukua_v3' ) {
			$this->mukua_whakapiri();
		}

		$this->whakautu_json(
			[ 'ok' => false, 'code' => 'invalid_action', 'message' => 'The requested action is invalid.' ],
			400
		);
	}

	private function hanga_whakapiri(): never {
		$this->manatoko_csrf_api();
		$haatepe_papahono = $_POST[ 'i' ] ?? null;
		$kopaki = $_POST[ 'kopaki' ] ?? null;
		$paunga = $_POST[ 'paunga' ] ?? null;
		$mukua_hash_puutahe64 = $_POST[ 'mukua_hash' ] ?? null;

		if ( !is_string( $haatepe_papahono ) || !$this->haatepe_hanga_tika( $haatepe_papahono ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_token', 'message' => 'New secure-link tokens must contain 22 base-62 characters.' ],
				400
			);
		}
		if ( !is_string( $kopaki ) || !$this->kopaki_tika( $kopaki, true ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_envelope', 'message' => 'The browser-encrypted envelope is invalid.' ],
				400
			);
		}
		if ( !is_string( $paunga ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_expiry', 'message' => 'Choose an expiry option.' ],
				400
			);
		}

		[ $waahikee, $panui_kotahi ] = $this->whiriwhiri_paunga( $paunga );
		if ( $waahikee === false ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_expiry', 'message' => 'The expiry option is invalid.' ],
				400
			);
		}

		$mukua_hash = null;
		if ( $panui_kotahi ) {
			if ( !is_string( $mukua_hash_puutahe64 ) ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'missing_delete_proof', 'message' => 'The one-time deletion proof is missing.' ],
					400
				);
			}
			try {
				$mukua_hash = $this->wetewete_puutahe64_url( $mukua_hash_puutahe64 );
			} catch ( Throwable ) {
				$mukua_hash = '';
			}
			if ( strlen( $mukua_hash ) !== 32 ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'invalid_delete_proof', 'message' => 'The one-time deletion proof is invalid.' ],
					400
				);
			}
		}

		$inaianei = time();
		$wa_ip = $this->tikina_wa_ip();
		$wa_ip_hash = $this->haatepe_tūmataiti( 'ip', $wa_ip, true );
		$matua_hash = $this->haatepe_tūmataiti( 'create', $wa_ip, true );

		$waahanga = 'checking the creation rate limit';

		try {
			if ( $this->kua_aukati( 'create', $matua_hash, $wa_ip_hash, $inaianei - 3600, 12, 30 ) ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'rate_limit', 'message' => 'Too many pastes have been created from this address. Try again later.' ],
					429
				);
			}

			$waahanga = 'hashing the secure-link token';
			$haatepe = $this->haatepe_rapu( $haatepe_papahono );

			$waahanga = 'preparing the expiry metadata';
			if ( $panui_kotahi && $waahikee === null ) {
				$waahikee = 31_557_600;
			}
			$waitohuwaa_mutu = is_int( $waahikee ) ? $inaianei + $waahikee : null;

			$waahanga = 'saving the encrypted envelope';
			$this->raraunga->hanga(
				[
					'haatepe' => $haatepe,
					'rarangi_huna' => $kopaki,
					'mukua_hash' => $mukua_hash,
					'waitohuwaa_hanga' => $inaianei,
					'waitohuwaa_mutu' => $waitohuwaa_mutu,
					'panui_kotahi' => $panui_kotahi ? 1 : 0,
				]
			);

			$waahanga = 'recording the creation attempt';
			$this->raraunga->tuhia_ngana( 'create', $matua_hash, $wa_ip_hash, $inaianei );

			$waahanga = 'removing expired v3 pastes';
			$this->raraunga->mukua_nga_mea_kua_pau( $inaianei );

			$waahanga = 'cleaning old rate-limit records';
			$this->whakapai_ngana_tupono( $inaianei );

			$waahanga = 'building the success response';
			$this->whakautu_json(
				[
					'ok' => true,
					'version' => self::PUTANGA_WHAKAMUNA,
					'profile' => self::KŌNAE_WHAKAMUNA_HOU,
					'link' => $this->hanga_hononga( $haatepe_papahono ),
					'expires' => $waitohuwaa_mutu,
					'one_time' => $panui_kotahi,
				],
				201
			);
		} catch ( PDOException $hapa ) {
			if ( ( string ) $hapa->getCode() === '23000' ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'token_collision', 'message' => 'A secure-link collision occurred. Try again.' ],
					409
				);
			}
			$this->whakaatu_hapa( $waahanga, $hapa );
		} catch ( Throwable $hapa ) {
			$this->whakaatu_hapa( $waahanga, $hapa );
		}

	}

	private function tikina_whakapiri(): never {
		$this->manatoko_csrf_api();
		$haatepe_papahono = $_POST[ 'i' ] ?? null;
		if ( !is_string( $haatepe_papahono ) || !$this->haatepe_tika( $haatepe_papahono ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_token', 'message' => 'This secure link is invalid.' ],
				400
			);
		}

		try {
			$inaianei = time();
			$wa_ip = $this->tikina_wa_ip();
			$wa_ip_hash = $this->haatepe_tūmataiti( 'ip', $wa_ip, true );
			$matua_hash = $this->haatepe_tūmataiti( 'tiro', $wa_ip, true );
			if ( $this->kua_aukati( 'tiro', $matua_hash, $wa_ip_hash, $inaianei - 3600, 50, 100 ) ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'rate_limit', 'message' => 'Too many paste retrievals have been attempted from this address. Try again later.' ],
					429
				);
			}
			$haatepe = $this->haatepe_rapu( $haatepe_papahono );
			$rarangi = $this->raraunga->tikina( $haatepe );
			if ( $rarangi === null ) {
				$this->raraunga->tuhia_ngana( 'tiro', $matua_hash, $wa_ip_hash, $inaianei );
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'not_found', 'message' => 'No v3 paste was found for this secure link.' ],
					404
				);
			}
			if ( $rarangi[ 'waitohuwaa_mutu' ] !== null && ( int ) $rarangi[ 'waitohuwaa_mutu' ] <= $inaianei ) {
				$this->raraunga->mukua( $haatepe );
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'expired', 'message' => 'This paste has expired.' ],
					410
				);
			}

			$kopaki_hua = $this->wetewete_kopaki( ( string ) $rarangi[ 'rarangi_huna' ] );
			$this->raraunga->tuhia_ngana( 'tiro', $matua_hash, $wa_ip_hash, $inaianei );
			$this->whakautu_json(
				[
					'ok' => true,
					'version' => self::PUTANGA_WHAKAMUNA,
					'profile' => $kopaki_hua[ 'profile' ],
					'envelope' => ( string ) $rarangi[ 'rarangi_huna' ],
					'created' => ( int ) $rarangi[ 'waitohuwaa_hanga' ],
					'expires' => $rarangi[ 'waitohuwaa_mutu' ] === null ? null : ( int ) $rarangi[ 'waitohuwaa_mutu' ],
					'expiry_rule' => $this->ture_paunga( $rarangi ),
					'one_time' => ( ( int ) $rarangi[ 'panui_kotahi' ] ) === 1,
				],
				200
			);
		} catch ( Throwable $hapa ) {
			$this->whakaatu_hapa( 'loading the v3 paste', $hapa );
		}
	}

	private function mukua_whakapiri(): never {
		$this->manatoko_csrf_api();
		$haatepe_papahono = $_POST[ 'i' ] ?? null;
		$mukua_muna_puutahe64 = $_POST[ 'mukua_muna' ] ?? null;
		if ( !is_string( $haatepe_papahono ) || !$this->haatepe_tika( $haatepe_papahono ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_token', 'message' => 'This secure link is invalid.' ],
				400
			);
		}
		if ( !is_string( $mukua_muna_puutahe64 ) ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_delete_secret', 'message' => 'The one-time deletion secret is missing.' ],
				400
			);
		}
		try {
			$mukua_muna = $this->wetewete_puutahe64_url( $mukua_muna_puutahe64 );
		} catch ( Throwable ) {
			$mukua_muna = '';
		}
		if ( strlen( $mukua_muna ) !== 32 ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'invalid_delete_secret', 'message' => 'The one-time deletion secret is invalid.' ],
				400
			);
		}

		try {
			$haatepe = $this->haatepe_rapu( $haatepe_papahono );
			$wa_ip = $this->tikina_wa_ip();
			$wa_ip_hash = $this->haatepe_tūmataiti( 'ip', $wa_ip, true );
			$matua_hash = $this->haatepe_tūmataiti( 'mukua', $wa_ip, true );
			$inaianei_mukua = time();
			if ( $this->kua_aukati( 'mukua', $matua_hash, $wa_ip_hash, $inaianei_mukua - 3600, 10, 20 ) ) {
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'rate_limit', 'message' => 'Too many deletion attempts have been made from this address. Try again later.' ],
					429
				);
			}
			$mukua_hash = hash( 'sha256', $haatepe_papahono . "\0" . $mukua_muna, true );
			$mukua = $this->raraunga->mukua_ki_te_hash( $haatepe, $mukua_hash );
			if ( !$mukua ) {
				$this->raraunga->tuhia_ngana( 'mukua', $matua_hash, $wa_ip_hash, $inaianei_mukua );
				$this->whakautu_json(
					[ 'ok' => false, 'code' => 'not_deleted', 'message' => 'The one-time paste was not deleted. It may already be gone.' ],
					409
				);
			}
			$this->whakautu_json( [ 'ok' => true, 'deleted' => true ], 200 );
		} catch ( Throwable $hapa ) {
			$this->whakaatu_hapa( 'deleting the one-time v3 paste', $hapa );
		}
	}

	private function manatoko_csrf_api(): void {
		if ( !$this->manatoko_csrf() ) {
			$this->whakautu_json(
				[ 'ok' => false, 'code' => 'csrf', 'message' => 'The form expired. Reload and try again.' ],
				403
			);
		}
	}

	private function kopaki_tika( string $kopaki, bool $hanga = false ): bool {
		try {
			$hua = $this->wetewete_kopaki( $kopaki );
		} catch ( Throwable ) {
			return false;
		}

		return !$hanga || $hua[ 'profile' ] === self::KŌNAE_WHAKAMUNA_HOU;
	}

	/** @return array{profile:int,salt:string,iv:string,ct:string} */
	private function wetewete_kopaki( string $kopaki ): array {
		if ( $kopaki === '' || strlen( $kopaki ) > self::RAHI_KOPAKI_TEITEI ) {
			throw new RuntimeException( 'The encrypted envelope is too large.' );
		}
		$hua = json_decode( $kopaki, true, 16, JSON_THROW_ON_ERROR );
		if (
			!is_array( $hua ) ||
			( $hua[ 'v' ] ?? null ) !== self::PUTANGA_WHAKAMUNA ||
			( $hua[ 'alg' ] ?? null ) !== self::ARATUKA_WHAKAMUNA ||
			( $hua[ 'kdf' ] ?? null ) !== self::ARATUKA_KDF ||
			( $hua[ 'iter' ] ?? null ) !== self::TAKAI_KDF ||
			!is_string( $hua[ 'salt' ] ?? null ) ||
			!is_string( $hua[ 'iv' ] ?? null ) ||
			!is_string( $hua[ 'ct' ] ?? null )
		) {
			throw new RuntimeException( 'The encrypted envelope format is invalid.' );
		}

		$kōnae = array_key_exists( 'profile', $hua )
			? $hua[ 'profile' ]
			: self::KŌNAE_WHAKAMUNA_TAWHITO;
		if (
			!is_int( $kōnae ) ||
			!in_array(
				$kōnae,
				[ self::KŌNAE_WHAKAMUNA_TAWHITO, self::KŌNAE_WHAKAMUNA_HOU ],
				true
			)
		) {
			throw new RuntimeException( 'The encrypted envelope profile is unsupported.' );
		}

		$tote = $this->wetewete_puutahe64_url( $hua[ 'salt' ] );
		$pa = $this->wetewete_puutahe64_url( $hua[ 'iv' ] );
		$karerehuna = $this->wetewete_puutahe64_url( $hua[ 'ct' ] );
		if (
			strlen( $tote ) !== 16 ||
			strlen( $pa ) !== 12 ||
			strlen( $karerehuna ) < 16 ||
			strlen( $karerehuna ) > 2_100_000
		) {
			throw new RuntimeException( 'The encrypted envelope contains invalid field lengths.' );
		}

		return [
			'profile' => $kōnae,
			'salt' => $hua[ 'salt' ],
			'iv' => $hua[ 'iv' ],
			'ct' => $hua[ 'ct' ],
		];
	}

	/** @param array<string,mixed> $rarangi */
	private function ture_paunga( array $rarangi ): string {
		$panui_kotahi = ( ( int ) ( $rarangi[ 'panui_kotahi' ] ?? 0 ) ) === 1;
		if ( $panui_kotahi ) {
			return 'burn';
		}
		if ( ( $rarangi[ 'waitohuwaa_mutu' ] ?? null ) === null ) {
			return 'never';
		}

		$roa = ( int ) $rarangi[ 'waitohuwaa_mutu' ] - ( int ) $rarangi[ 'waitohuwaa_hanga' ];
		$paunga = ( string ) $roa;
		if ( !in_array( $paunga, [ '600', '3600', '86400', '604800', '2635200', '31557600' ], true ) ) {
			throw new RuntimeException( 'The stored expiry policy is invalid.' );
		}
		return $paunga;
	}

	/** @return array{0:int|false|null,1:bool} */
	private function whiriwhiri_paunga( string $paunga ): array {
		return match ( $paunga ) {
			'burn' => [ null, true ],
			'600' => [ 600, false ],
			'3600' => [ 3600, false ],
			'86400' => [ 86400, false ],
			'604800' => [ 604800, false ],
			'2635200' => [ 2635200, false ],
			'31557600' => [ 31557600, false ],
			'never' => [ null, false ],
			default => [ false, false ],
		};
	}

	private function kua_aukati(
		string $momo,
		string $matua_hash,
		string $wa_ip_hash,
		int $mai,
		int $teitei_matua,
		int $teitei_wa_ip
	): bool {
		$tatau = $this->raraunga->tatau_ngana( $momo, $matua_hash, $wa_ip_hash, $mai );
		return $tatau[ 'matua' ] >= $teitei_matua || $tatau[ 'wa_ip' ] >= $teitei_wa_ip;
	}

	private function whakapai_ngana_tupono( int $inaianei ): void {
		$whakamutunga = $_SESSION[ 'whakapai_ngana_tupono' ] ?? 0;
		if ( $inaianei - ( int ) $whakamutunga >= 900 ) {
			$this->raraunga->whakapai_ngana( $inaianei - 86_400 );
			$_SESSION[ 'whakapai_ngana_tupono' ] = $inaianei;
		}
	}

	private function haatepe_rapu( string $haatepe_papahono ): string {
		return bin2hex(
			hash_hmac( 'sha256', "lookup\0" . $haatepe_papahono, $this->kii_taupaanga, true )
		);
	}

	private function haatepe_tūmataiti( string $momo, string $uara, bool $taahuurua = false ): string {
		$hua = hash_hmac( 'sha256', $momo . "\0" . $uara, $this->kii_taupaanga, true );
		return $taahuurua ? $hua : bin2hex( $hua );
	}

	private function haatepe_hanga_tika( string $haatepe ): bool {
		return strlen( $haatepe ) === self::HAATEPE_ROA &&
			preg_match( '/^[A-Za-z0-9]{22}$/D', $haatepe ) === 1;
	}

	private function haatepe_tika( string $haatepe ): bool {
		return in_array(
			strlen( $haatepe ),
			[ self::HAATEPE_ROA_TAWHITO, self::HAATEPE_ROA ],
			true
		) && preg_match( '/^[A-Za-z0-9]+$/D', $haatepe ) === 1;
	}

	private function hanga_hononga( string $haatepe ): string {
		return $this->papahono_matua() . '#i=' . rawurlencode( $haatepe );
	}

	public function papahono_matua(): string {
		$papa = rtrim( ( string ) $this->whiringa[ 'papa_hononga' ], '?&' );
		if ( $papa !== '' ) {
			return $papa;
		}
		$ara = ( string ) ( $_SERVER[ 'SCRIPT_NAME' ] ?? '/' );
		return $ara !== '' ? $ara : '/';
	}

	private function tikina_wa_ip(): string {
		$wa_ip = ( string ) ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' );
		return filter_var( $wa_ip, FILTER_VALIDATE_IP ) ? $wa_ip : 'unknown';
	}

	private function wetewete_kii( string $uara ): string {
		if ( preg_match( '/^[0-9a-fA-F]{64}$/D', $uara ) === 1 ) {
			$kii = hex2bin( $uara );
		} else {
			$kii = $this->wetewete_puutahe64_url( $uara );
		}
		if ( !is_string( $kii ) || strlen( $kii ) !== 32 ) {
			throw new RuntimeException( 'The v3 application key must encode exactly 32 random bytes.' );
		}
		return $kii;
	}

	private function wetewete_puutahe64_url( string $taauru ): string {
		if ( $taauru === '' || preg_match( '/^[A-Za-z0-9_-]+$/D', $taauru ) !== 1 ) {
			throw new RuntimeException( 'Invalid base64url data.' );
		}
		$toenga = strlen( $taauru ) % 4;
		$whakakii = $toenga === 0 ? '' : str_repeat( '=', 4 - $toenga );
		$hua = base64_decode(
			strtr( $taauru, '-_', '+/' ) . $whakakii,
			true
		);
		if ( !is_string( $hua ) ) {
			throw new RuntimeException( 'Invalid base64url data.' );
		}
		return $hua;
	}

	private function whakarite_watu(): void {
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			return;
		}
		ini_set( 'session.use_strict_mode', '1' );
		ini_set( 'session.use_only_cookies', '1' );
		ini_set( 'session.cookie_httponly', '1' );
		ini_set( 'session.cookie_samesite', 'Strict' );
		ini_set( 'session.cookie_secure', self::he_https() ? '1' : '0' );
		session_name( 'tuhimunatanga_v3_session' );
		session_start();
	}

	private function tikina_csrf(): string {
		if (
			!isset( $_SESSION[ 'csrf_v3' ] ) ||
			!is_string( $_SESSION[ 'csrf_v3' ] ) ||
			strlen( $_SESSION[ 'csrf_v3' ] ) !== 64
		) {
			$_SESSION[ 'csrf_v3' ] = bin2hex( random_bytes( 32 ) );
		}
		return $_SESSION[ 'csrf_v3' ];
	}

	private function manatoko_csrf(): bool {
		$csrf = $_POST[ 'csrf' ] ?? null;
		return is_string( $csrf ) && hash_equals( $this->csrf, $csrf );
	}

	private function whakaatu_hapa( string $waahanga, Throwable $hapa ): never {
		$karere_hapa = $this->karere_hapa( $hapa );
		$tohu_hapa = strtoupper(
			substr(
				hash( 'sha256', $waahanga . "\0" . $karere_hapa . "\0" . microtime( true ) ),
				0,
				12
			)
		);

		error_log(
			'Tuhimunatanga v3 failure [' . $tohu_hapa . '] at ' . $waahanga . ': ' .
			get_class( $hapa ) . ': ' . $karere_hapa . ' in ' .
			$hapa->getFile() . ':' . $hapa->getLine() . PHP_EOL .
			$hapa->getTraceAsString()
		);

		$whakautu = [
			'ok' => false,
			'code' => 'server_error',
			'message' => 'The v3 request failed.',
			'stage' => $waahanga,
			'reference' => $tohu_hapa,
		];

		if ( ( $this->whiringa[ 'patuiro' ] ?? false ) === true ) {
			$whakautu[ 'message' ] = 'The v3 request failed at ' . $waahanga . '.';
			$whakautu[ 'error' ] = $karere_hapa;
			$whakautu[ 'exception' ] = get_class( $hapa );
			$whakautu[ 'location' ] = basename( $hapa->getFile() ) . ':' . $hapa->getLine();
		}

		$this->whakautu_json( $whakautu, 500 );
	}

	private function karere_hapa( Throwable $hapa ): string {
		$karere = [];
		$hohonu = 0;

		do {
			$uara = trim( preg_replace( '/\s+/u', ' ', $hapa->getMessage() ) ?? '' );
			if ( $uara === '' ) {
				$uara = 'No exception message was provided.';
			}
			$karere[] = get_class( $hapa ) . ': ' . $uara;
			$hapa = $hapa->getPrevious();
			$hohonu++;
		} while ( $hapa instanceof Throwable && $hohonu < 5 );

		return implode( ' | Caused by: ', $karere );
	}

	/** @param array<string,mixed> $raraunga */
	private function whakautu_json( array $raraunga, int $waehere ): never {
		if ( !headers_sent() ) {
			http_response_code( $waehere );
			header( 'Content-Type: application/json; charset=UTF-8' );
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Referrer-Policy: no-referrer' );
		}
		echo json_encode(
			$raraunga,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
		);
		exit;
	}

	public static function whakanoho_pane_haumaru(): void {
		if ( headers_sent() ) {
			return;
		}
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: DENY' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );
		$csp = "default-src 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-src 'self'";
		if ( self::he_https() ) {
			$csp .= '; upgrade-insecure-requests';
			header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains' );
		}
		header( 'Content-Security-Policy: ' . $csp );
	}

	public static function he_https(): bool {
		return ( !empty( $_SERVER[ 'HTTPS' ] ) && strtolower( ( string ) $_SERVER[ 'HTTPS' ] ) !== 'off' ) ||
			( string ) ( $_SERVER[ 'SERVER_PORT' ] ?? '' ) === '443' ||
			strtolower( ( string ) ( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ?? '' ) ) === 'https';
	}

	public function __destruct() {
		if ( isset( $this->kii_taupaanga ) && $this->kii_taupaanga !== '' ) {
			$this->kii_taupaanga = str_repeat( "\0", strlen( $this->kii_taupaanga ) );
		}
	}
}
