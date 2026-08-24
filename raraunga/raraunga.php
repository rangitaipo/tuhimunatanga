<?php

declare( strict_types=1 );

require_once( dirname( __DIR__ ) . '/whiringa.php' );

final class Raraunga {
	private static ?PDO $tuuhononga = null;

	public function hono(): PDO {
		if ( self::$tuuhononga instanceof PDO ) {
			return self::$tuuhononga;
		}

		$whiringa = WhiringaTuhimunatanga::tikina();
		$dsn = sprintf(
			'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
			$whiringa[ 'raraunga_waahitau_tukutuku' ],
			$whiringa[ 'tauranga' ],
			$whiringa[ 'ingoa_raraunga' ]
		);

		try {
			self::$tuuhononga = new PDO(
				$dsn,
				$whiringa[ 'ingoa' ],
				$whiringa[ 'kupuhipa' ],
				[
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_STRINGIFY_FETCHES => false,
				]
			);
		} catch ( PDOException $hapa ) {
			error_log( 'Tuhimunatanga v3 database connection failure: ' . $hapa->getMessage() );
			throw new RuntimeException(
				'The v3 database connection could not be established.',
				0,
				$hapa
			);
		}

		return self::$tuuhononga;
	}

	/**
	 * @param array{
	 *     haatepe:string,
	 *     rarangi_huna:string,
	 *     mukua_hash:?string,
	 *     waitohuwaa_hanga:int,
	 *     waitohuwaa_mutu:?int,
	 *     panui_kotahi:int
	 * } $rarangi
	 */
	public function hanga( array $rarangi ): void {
		$taauaki = $this->hono()->prepare(
			'INSERT INTO `nga_taaurunga_v3`
				(`haatepe`, `rarangi_huna`, `mukua_hash`, `waitohuwaa_hanga`, `waitohuwaa_mutu`, `panui_kotahi`)
			 VALUES
				(:haatepe, :rarangi_huna, :mukua_hash, :waitohuwaa_hanga, :waitohuwaa_mutu, :panui_kotahi)'
		);
		$taauaki->bindValue( ':haatepe', $rarangi[ 'haatepe' ], PDO::PARAM_STR );
		$taauaki->bindValue( ':rarangi_huna', $rarangi[ 'rarangi_huna' ], PDO::PARAM_STR );
		$taauaki->bindValue(
			':mukua_hash',
			$rarangi[ 'mukua_hash' ],
			$rarangi[ 'mukua_hash' ] === null ? PDO::PARAM_NULL : PDO::PARAM_LOB
		);
		$taauaki->bindValue( ':waitohuwaa_hanga', $rarangi[ 'waitohuwaa_hanga' ], PDO::PARAM_INT );
		$taauaki->bindValue(
			':waitohuwaa_mutu',
			$rarangi[ 'waitohuwaa_mutu' ],
			$rarangi[ 'waitohuwaa_mutu' ] === null ? PDO::PARAM_NULL : PDO::PARAM_INT
		);
		$taauaki->bindValue( ':panui_kotahi', $rarangi[ 'panui_kotahi' ], PDO::PARAM_INT );
		$taauaki->execute();
	}

	/** @return array<string,mixed>|null */
	public function tikina( string $haatepe ): ?array {
		$taauaki = $this->hono()->prepare(
			'SELECT `haatepe`, `rarangi_huna`, `mukua_hash`, `waitohuwaa_hanga`,
				`waitohuwaa_mutu`, `panui_kotahi`, `created_at`
			 FROM `nga_taaurunga_v3`
			 WHERE `haatepe` = :haatepe
			 LIMIT 1'
		);
		$taauaki->bindValue( ':haatepe', $haatepe, PDO::PARAM_STR );
		$taauaki->execute();
		$rarangi = $taauaki->fetch();
		return is_array( $rarangi ) ? $rarangi : null;
	}

	public function mukua( string $haatepe ): void {
		$taauaki = $this->hono()->prepare(
			'DELETE FROM `nga_taaurunga_v3` WHERE `haatepe` = :haatepe'
		);
		$taauaki->bindValue( ':haatepe', $haatepe, PDO::PARAM_STR );
		$taauaki->execute();
	}

	public function mukua_ki_te_hash( string $haatepe, string $mukua_hash ): bool {
		$taauaki = $this->hono()->prepare(
			'DELETE FROM `nga_taaurunga_v3`
			 WHERE `haatepe` = :haatepe
			   AND `panui_kotahi` = 1
			   AND `mukua_hash` = :mukua_hash'
		);
		$taauaki->bindValue( ':haatepe', $haatepe, PDO::PARAM_STR );
		$taauaki->bindValue( ':mukua_hash', $mukua_hash, PDO::PARAM_LOB );
		$taauaki->execute();
		return $taauaki->rowCount() === 1;
	}

	public function mukua_nga_mea_kua_pau( int $inaianei ): int {
		$taauaki = $this->hono()->prepare(
			'DELETE FROM `nga_taaurunga_v3`
			 WHERE `waitohuwaa_mutu` IS NOT NULL
			   AND `waitohuwaa_mutu` <= :inaianei'
		);
		$taauaki->bindValue( ':inaianei', $inaianei, PDO::PARAM_INT );
		$taauaki->execute();
		return $taauaki->rowCount();
	}

	/** @return array{matua:int,wa_ip:int} */
	public function tatau_ngana(
		string $momo,
		string $matua_hash,
		string $wa_ip_hash,
		int $mai
	): array {
		$taauaki = $this->hono()->prepare(
			'SELECT
				COALESCE(SUM(CASE WHEN `matua_hash` = :matua_hash THEN 1 ELSE 0 END), 0) AS `matua`,
				COALESCE(SUM(CASE WHEN `wa_ip_hash` = :wa_ip_hash THEN 1 ELSE 0 END), 0) AS `wa_ip`
			 FROM `nga_ngana_v3`
			 WHERE `momo` = :momo AND `waitohuwaa` >= :mai'
		);
		$taauaki->bindValue( ':matua_hash', $matua_hash, PDO::PARAM_LOB );
		$taauaki->bindValue( ':wa_ip_hash', $wa_ip_hash, PDO::PARAM_LOB );
		$taauaki->bindValue( ':momo', $momo, PDO::PARAM_STR );
		$taauaki->bindValue( ':mai', $mai, PDO::PARAM_INT );
		$taauaki->execute();
		$hua = $taauaki->fetch();
		return [
			'matua' => ( int ) ( $hua[ 'matua' ] ?? 0 ),
			'wa_ip' => ( int ) ( $hua[ 'wa_ip' ] ?? 0 ),
		];
	}

	public function tuhia_ngana(
		string $momo,
		string $matua_hash,
		string $wa_ip_hash,
		int $waitohuwaa
	): void {
		$taauaki = $this->hono()->prepare(
			'INSERT INTO `nga_ngana_v3` (`momo`, `matua_hash`, `wa_ip_hash`, `waitohuwaa`)
			 VALUES (:momo, :matua_hash, :wa_ip_hash, :waitohuwaa)'
		);
		$taauaki->bindValue( ':momo', $momo, PDO::PARAM_STR );
		$taauaki->bindValue( ':matua_hash', $matua_hash, PDO::PARAM_LOB );
		$taauaki->bindValue( ':wa_ip_hash', $wa_ip_hash, PDO::PARAM_LOB );
		$taauaki->bindValue( ':waitohuwaa', $waitohuwaa, PDO::PARAM_INT );
		$taauaki->execute();
	}

	public function whakapai_ngana( int $mai ): int {
		$taauaki = $this->hono()->prepare(
			'DELETE FROM `nga_ngana_v3` WHERE `waitohuwaa` < :mai'
		);
		$taauaki->bindValue( ':mai', $mai, PDO::PARAM_INT );
		$taauaki->execute();
		return $taauaki->rowCount();
	}
}
