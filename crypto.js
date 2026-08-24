( function () {
	'use strict';

	const PUTANGA = 3;
	const KŌNAE_TAWHAITO = 1;
	const KŌNAE_HOU = 2;
	const ARATUKA = 'AES-256-GCM';
	const KDF = 'PBKDF2-SHA-256';
	const TAKAI = 600000;
	const RAHI_KARERE_TEITEI = 1048576;
	const RAHI_KUPUHIPA_TEITEI = 2048;
	const RETA_HAATEPE = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	const PAUNGA_TIKA = new Set(
		[ 'burn', '600', '3600', '86400', '604800', '2635200', '31557600', 'never' ],
	);

	function manatokoTaiao() {
		if ( !window.isSecureContext ) {
			throw new Error( 'Browser-side encryption requires HTTPS.' );
		}
		if ( !window.crypto || !window.crypto.subtle ) {
			throw new Error( 'This browser does not provide the Web Crypto API.' );
		}
	}

	function whakawaehere( kuputuhi ) {
		return new TextEncoder().encode( kuputuhi );
	}

	function wetewaehere( raraunga ) {
		return new TextDecoder( 'utf-8', { fatal: true } ).decode( raraunga );
	}

	function whakariteKupuhipa( kupuhipa ) {
		if ( typeof kupuhipa !== 'string' ) {
			throw new Error( 'The passphrase is invalid.' );
		}

		let hua = kupuhipa.normalize( 'NFC' ).trim().replace( /\s+/gu, ' ' );
		if ( hua === '' || whakawaehere( hua ).byteLength > RAHI_KUPUHIPA_TEITEI ) {
			throw new Error( 'The passphrase is invalid.' );
		}

		return hua;
	}

	function puutahe64Url( raraunga ) {
		const tirohanga = raraunga instanceof Uint8Array
			? raraunga
			: new Uint8Array( raraunga );
		let aho = '';

		for ( let i = 0; i < tirohanga.length; i += 0x8000 ) {
			aho += String.fromCharCode.apply(
				null,
				tirohanga.subarray( i, Math.min( i + 0x8000, tirohanga.length ) ),
			);
		}

		return btoa( aho )
			.replace( /\+/g, '-' )
			.replace( /\//g, '_' )
			.replace( /=+$/g, '' );
	}

	function wetewetePuutahe64Url( aho ) {
		if ( typeof aho !== 'string' || !/^[A-Za-z0-9_-]+$/u.test( aho ) ) {
			throw new Error( 'Invalid base64url data.' );
		}

		const toenga = aho.length % 4;
		const whakakii = toenga === 0 ? '' : '='.repeat( 4 - toenga );
		const raraunga = atob(
			aho.replace( /-/g, '+' ).replace( /_/g, '/' ) + whakakii,
		);
		const hua = new Uint8Array( raraunga.length );

		for ( let i = 0; i < raraunga.length; i++ ) {
			hua[ i ] = raraunga.charCodeAt( i );
		}

		return hua;
	}

	function raraungaTupono( roa ) {
		const hua = new Uint8Array( roa );
		window.crypto.getRandomValues( hua );
		return hua;
	}

	function tauTupono( teitei ) {
		if ( !Number.isInteger( teitei ) || teitei < 1 || teitei > 0x100000000 ) {
			throw new Error( 'The random range is invalid.' );
		}

		const rohe = Math.floor( 0x100000000 / teitei ) * teitei;
		const raraunga = new Uint32Array( 1 );
		let tau = 0;

		do {
			window.crypto.getRandomValues( raraunga );
			tau = raraunga[ 0 ];
		} while ( tau >= rohe );

		return tau % teitei;
	}

	function haatepeTika( haatepe ) {
		return typeof haatepe === 'string' && /^[A-Za-z0-9]{15}(?:[A-Za-z0-9]{7})?$/u.test( haatepe );
	}

	function mauHaatepe( roa = 22 ) {
		if ( ![ 15, 22 ].includes( roa ) ) {
			throw new Error( 'The secure-link token length is invalid.' );
		}

		let hua = '';
		for ( let i = 0; i < roa; i++ ) {
			hua += RETA_HAATEPE[ tauTupono( RETA_HAATEPE.length ) ];
		}
		return hua;
	}

	function manatokoTāurungaKupu( tāurunga ) {
		if (
			!tāurunga ||
			typeof tāurunga !== 'object' ||
			typeof tāurunga.code !== 'string' ||
			typeof tāurunga.word !== 'string' ||
			!/^[1-6]{5}$/u.test( tāurunga.code ) ||
			tāurunga.word === ''
		) {
			throw new Error( 'The passphrase wordlist contains an invalid entry.' );
		}

		return {
			code: tāurunga.code,
			word: tāurunga.word.normalize( 'NFC' ),
		};
	}

	function mauKupuhipa( kupu, katoa = 20 ) {
		if ( !Array.isArray( kupu ) || kupu.length !== 7776 ) {
			throw new Error( 'The passphrase wordlist does not contain 7,776 entries.' );
		}
		if ( !Number.isInteger( katoa ) || katoa < 7 || katoa > 22 ) {
			throw new Error( 'The passphrase length is invalid.' );
		}

		const hua = [];
		for ( let i = 0; i < katoa; i++ ) {
			hua.push( manatokoTāurungaKupu( kupu[ tauTupono( kupu.length ) ] ) );
		}

		return hua;
	}

	function kupuhipaMaiTāurunga( tāurunga ) {
		if ( !Array.isArray( tāurunga ) || tāurunga.length === 0 ) {
			throw new Error( 'The generated passphrase is empty.' );
		}
		return whakariteKupuhipa(
			tāurunga.map( ( kupu ) => manatokoTāurungaKupu( kupu ).word ).join( ' ' ),
		);
	}

	function honoRaraunga( ...wahanga ) {
		const roa = wahanga.reduce( ( tapeke, uara ) => tapeke + uara.length, 0 );
		const hua = new Uint8Array( roa );
		let turanga = 0;

		for ( const uara of wahanga ) {
			hua.set( uara, turanga );
			turanga += uara.length;
		}

		return hua;
	}

	function kaupapaTika( kaupapa ) {
		if (
			!kaupapa ||
			typeof kaupapa !== 'object' ||
			typeof kaupapa.expiry_rule !== 'string' ||
			!PAUNGA_TIKA.has( kaupapa.expiry_rule ) ||
			typeof kaupapa.one_time !== 'boolean' ||
			( kaupapa.expiry_rule === 'burn' ) !== kaupapa.one_time
		) {
			throw new Error( 'The encrypted policy metadata is invalid.' );
		}

		return {
			expiry_rule: kaupapa.expiry_rule,
			one_time: kaupapa.one_time,
		};
	}

	function hangaAadKōnae1( haatepe ) {
		return whakawaehere(
			'TMT3\n' +
			ARATUKA + '\n' +
			KDF + '\n' +
			String( TAKAI ) + '\n' +
			haatepe,
		);
	}

	function hangaAadKōnae2( haatepe, kaupapa ) {
		const kaupapaWhakarite = kaupapaTika( kaupapa );
		return whakawaehere(
			'TMT3\n' +
			'PROFILE-2\n' +
			ARATUKA + '\n' +
			KDF + '\n' +
			String( TAKAI ) + '\n' +
			haatepe + '\n' +
			kaupapaWhakarite.expiry_rule + '\n' +
			( kaupapaWhakarite.one_time ? '1' : '0' ),
		);
	}

	async function mauKii( kupuhipa, tote, takai = TAKAI ) {
		if ( takai !== TAKAI ) {
			throw new Error( 'The encrypted envelope uses an unsupported iteration count.' );
		}
		const kupuhipaWhakarite = whakariteKupuhipa( kupuhipa );
		const kiiMatua = await window.crypto.subtle.importKey(
			'raw',
			whakawaehere( kupuhipaWhakarite ),
			'PBKDF2',
			false,
			[ 'deriveKey' ],
		);

		return window.crypto.subtle.deriveKey(
			{
				name: 'PBKDF2',
				salt: tote,
				iterations: takai,
				hash: 'SHA-256',
			},
			kiiMatua,
			{
				name: 'AES-GCM',
				length: 256,
			},
			false,
			[ 'encrypt', 'decrypt' ],
		);
	}

	function takaiKarere( karere ) {
		const raraunga = whakawaehere( karere );
		if ( raraunga.length > RAHI_KARERE_TEITEI ) {
			throw new Error( 'The formatted content exceeds the 1 MiB limit.' );
		}

		const roa = raraunga.length;
		let rahi = 256;
		while ( rahi < roa + 4 ) {
			rahi *= 2;
		}

		const hua = raraungaTupono( rahi );
		const tirohanga = new DataView( hua.buffer );
		tirohanga.setUint32( 0, roa, false );
		hua.set( raraunga, 4 );
		return hua;
	}

	function weteweteKarere( papa ) {
		if ( papa.length < 4 ) {
			throw new Error( 'The decrypted payload is incomplete.' );
		}

		const tirohanga = new DataView(
			papa.buffer,
			papa.byteOffset,
			papa.byteLength,
		);
		const roa = tirohanga.getUint32( 0, false );
		if ( roa > RAHI_KARERE_TEITEI || roa > papa.length - 4 ) {
			throw new Error( 'The decrypted payload length is invalid.' );
		}

		return wetewaehere( papa.subarray( 4, 4 + roa ) );
	}

	function manatokoKopaki( kopaki ) {
		if (
			!kopaki ||
			kopaki.v !== PUTANGA ||
			kopaki.alg !== ARATUKA ||
			kopaki.kdf !== KDF ||
			kopaki.iter !== TAKAI ||
			typeof kopaki.salt !== 'string' ||
			typeof kopaki.iv !== 'string' ||
			typeof kopaki.ct !== 'string'
		) {
			throw new Error( 'The encrypted envelope format is invalid.' );
		}

		const kōnae = kopaki.profile === undefined ? KŌNAE_TAWHAITO : kopaki.profile;
		if ( ![ KŌNAE_TAWHAITO, KŌNAE_HOU ].includes( kōnae ) ) {
			throw new Error( 'The encrypted envelope profile is unsupported.' );
		}

		const tote = wetewetePuutahe64Url( kopaki.salt );
		const pa = wetewetePuutahe64Url( kopaki.iv );
		const karerehuna = wetewetePuutahe64Url( kopaki.ct );

		if ( tote.length !== 16 || pa.length !== 12 || karerehuna.length < 16 ) {
			throw new Error( 'The encrypted envelope contains invalid field lengths.' );
		}

		return { kōnae, tote, pa, karerehuna };
	}

	async function whakamuna( ihirangi, kupuhipa, haatepe, kaupapa ) {
		manatokoTaiao();
		if ( !haatepeTika( haatepe ) || haatepe.length !== 22 ) {
			throw new Error( 'The secure-link token is invalid.' );
		}

		const kaupapaWhakarite = kaupapaTika( kaupapa );
		const karere = JSON.stringify( ihirangi );
		const papa = takaiKarere( karere );
		const tote = raraungaTupono( 16 );
		const pa = raraungaTupono( 12 );
		const kii = await mauKii( kupuhipa, tote );
		const karerehuna = await window.crypto.subtle.encrypt(
			{
				name: 'AES-GCM',
				iv: pa,
				additionalData: hangaAadKōnae2( haatepe, kaupapaWhakarite ),
				tagLength: 128,
			},
			kii,
			papa,
		);

		return JSON.stringify(
			{
				v: PUTANGA,
				profile: KŌNAE_HOU,
				alg: ARATUKA,
				kdf: KDF,
				iter: TAKAI,
				salt: puutahe64Url( tote ),
				iv: puutahe64Url( pa ),
				ct: puutahe64Url( new Uint8Array( karerehuna ) ),
			},
		);
	}

	async function wetemuna( kopakiAho, kupuhipa, haatepe, kaupapaTūmau = null ) {
		manatokoTaiao();
		if ( typeof kopakiAho !== 'string' || kopakiAho.length > 4500000 ) {
			throw new Error( 'The encrypted envelope is too large.' );
		}
		if ( !haatepeTika( haatepe ) ) {
			throw new Error( 'The secure-link token is invalid.' );
		}

		let kopaki;
		try {
			kopaki = JSON.parse( kopakiAho );
		} catch ( hapa ) {
			throw new Error( 'The encrypted envelope is not valid JSON.', { cause: hapa } );
		}

		const { kōnae, tote, pa, karerehuna } = manatokoKopaki( kopaki );
		const kii = await mauKii( kupuhipa, tote, kopaki.iter );
		const kaupapaWhakarite = kōnae === KŌNAE_HOU
			? kaupapaTika( kaupapaTūmau )
			: null;
		const aad = kōnae === KŌNAE_HOU
			? hangaAadKōnae2( haatepe, kaupapaWhakarite )
			: hangaAadKōnae1( haatepe );
		let papa;

		try {
			papa = await window.crypto.subtle.decrypt(
				{
					name: 'AES-GCM',
					iv: pa,
					additionalData: aad,
					tagLength: 128,
				},
				kii,
				karerehuna,
			);
		} catch ( hapa ) {
			throw new Error(
				'The passphrase is incorrect, the encrypted paste is damaged, or its stored policy metadata has changed.',
				{ cause: hapa },
			);
		}

		const karere = weteweteKarere( new Uint8Array( papa ) );
		let ihirangi;
		try {
			ihirangi = JSON.parse( karere );
		} catch ( hapa ) {
			throw new Error( 'The decrypted content format is invalid.', { cause: hapa } );
		}

		if ( kōnae === KŌNAE_HOU ) {
			if (
				!ihirangi ||
				ihirangi.version !== 2 ||
				typeof ihirangi.expiry_rule !== 'string' ||
				typeof ihirangi.one_time !== 'boolean'
			) {
				throw new Error( 'The decrypted profile-2 content format is invalid.' );
			}
			if (
				ihirangi.expiry_rule !== kaupapaWhakarite.expiry_rule ||
				ihirangi.one_time !== kaupapaWhakarite.one_time
			) {
				throw new Error( 'The stored policy metadata does not match the encrypted policy.' );
			}
		}

		return {
			profile: kōnae,
			content: ihirangi,
			policy_matches: true,
		};
	}

	async function mauMukuaHash( haatepe, mukuaMuna ) {
		if ( !haatepeTika( haatepe ) ) {
			throw new Error( 'The secure-link token is invalid.' );
		}
		if ( !( mukuaMuna instanceof Uint8Array ) || mukuaMuna.length !== 32 ) {
			throw new Error( 'The one-time deletion secret is invalid.' );
		}

		const raraunga = honoRaraunga(
			whakawaehere( haatepe ),
			new Uint8Array( [ 0 ] ),
			mukuaMuna,
		);
		const hash = await window.crypto.subtle.digest( 'SHA-256', raraunga );
		return new Uint8Array( hash );
	}

	window.TuhimunatangaV3Crypto = Object.freeze(
		{
			PUTANGA,
			KŌNAE_TAWHAITO,
			KŌNAE_HOU,
			ARATUKA,
			KDF,
			TAKAI,
			manatokoTaiao,
			whakariteKupuhipa,
			puutahe64Url,
			wetewetePuutahe64Url,
			raraungaTupono,
			mauHaatepe,
			mauKupuhipa,
			kupuhipaMaiTāurunga,
			whakamuna,
			wetemuna,
			mauMukuaHash,
		},
	);
} )();
