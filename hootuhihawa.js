( function () {
	'use strict';

	const TOHU_WHAAEA = new Set(
		[
			'a', 'b', 'blockquote', 'br', 'caption', 'code', 'col', 'colgroup',
			'del', 'div', 'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'hr', 'i', 'ins', 'li', 'ol', 'p', 'pre', 's', 'span', 'strike',
			'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th',
			'thead', 'tr', 'u', 'ul',
		],
	);

	const TOHU_MUKUA = new Set(
		[
			'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'frame',
			'frameset', 'iframe', 'img', 'input', 'link', 'math', 'meta', 'noscript',
			'object', 'option', 'script', 'select', 'source', 'style', 'svg',
			'template', 'textarea', 'track', 'video',
		],
	);

	const AAHUA_WHAAEA = new Set(
		[
			'background', 'background-color', 'border', 'border-bottom',
			'border-collapse', 'border-left', 'border-right', 'border-spacing',
			'border-top', 'color', 'font-family', 'font-size', 'font-style',
			'font-variant', 'font-weight', 'height', 'letter-spacing', 'line-height',
			'list-style-type', 'margin', 'margin-bottom', 'margin-left',
			'margin-right', 'margin-top', 'max-width', 'min-width', 'padding',
			'padding-bottom', 'padding-left', 'padding-right', 'padding-top',
			'text-align', 'text-decoration', 'text-indent', 'text-transform',
			'vertical-align', 'white-space', 'width',
		],
	);

	let kupuOati = null;
	let teneiKopaki = '';
	let teneiHaatepe = '';
	let teneiPanuiKotahi = false;
	let teneiKaupapaTūmau = null;
	let kupuhipaHanga = '';
	let kupuhipaHangaEntries = [];
	let kupuhipaHangaKatoa = 0;
	let kupuTatauranga = null;
	let kupuhipaHangaOati = null;
	const taihoaTaarua = new WeakMap();

	function tiki( ingoa ) {
		return document.getElementById( ingoa );
	}

	function whakaatu( kopuku, tikanga ) {
		if ( kopuku ) {
			kopuku.hidden = !tikanga;
		}
	}

	function karere( kupu, momo = 'hapa' ) {
		const pouaka = tiki( 'karere-v3' );
		if ( !pouaka ) {
			return;
		}

		pouaka.textContent = kupu;
		pouaka.className = 'kaari karere ' + momo;
		pouaka.hidden = kupu === '';
	}

	function karereApi( hua, taunoa ) {
		let kupu = typeof hua.message === 'string' && hua.message !== ''
			? hua.message
			: taunoa;

		if ( typeof hua.error === 'string' && hua.error !== '' ) {
			kupu += ' Error: ' + hua.error + '.';
		}
		if ( typeof hua.location === 'string' && hua.location !== '' ) {
			kupu += ' Location: ' + hua.location + '.';
		}
		if ( typeof hua.reference === 'string' && hua.reference !== '' ) {
			kupu += ' Reference: ' + hua.reference + '.';
		}

		return kupu;
	}

	function whakaritePatene( patene, mahi, kupu ) {
		if ( !patene ) {
			return;
		}
		patene.disabled = mahi;
		patene.textContent = mahi ? kupu : patene.dataset.kupuTuuturu || patene.textContent;
	}

	function whakaatuTaarua( patene, feedbackId = '' ) {
		if ( !patene ) {
			return;
		}

		const kupuTuuturu = patene.dataset.kupuTuuturu || patene.textContent || 'Copy';
		patene.dataset.kupuTuuturu = kupuTuuturu;
		patene.textContent = 'Copied ✓';
		patene.classList.add( 'kua-taarua' );
		patene.setAttribute( 'aria-label', kupuTuuturu + ', copied' );

		const tohu = feedbackId !== '' ? tiki( feedbackId ) : null;
		if ( tohu ) {
			tohu.classList.add( 'tohu-kua-taarua' );
		}

		const taihoaTawhito = taihoaTaarua.get( patene );
		if ( taihoaTawhito ) {
			window.clearTimeout( taihoaTawhito );
		}

		const taihoa = window.setTimeout(
			() => {
				patene.textContent = kupuTuuturu;
				patene.classList.remove( 'kua-taarua' );
				patene.removeAttribute( 'aria-label' );
				if ( tohu ) {
					tohu.classList.remove( 'tohu-kua-taarua' );
				}
				taihoaTaarua.delete( patene );
			},
			2200,
		);
		taihoaTaarua.set( patene, taihoa );
	}

	function tikinaKupuKatoa() {
		const ipu = tiki( 'kupu_katoa_v3' );
		const katoa = Number( ipu?.value || 7 );
		if ( !Number.isInteger( katoa ) || katoa < 7 || katoa > 22 ) {
			throw new Error( 'Choose a passphrase length between 7 and 22 words.' );
		}
		return katoa;
	}

	function hangaTataurangaKaha( katoa, tatauranga ) {
		const rahi = Number( tatauranga?.wordlistSize || 7776 );
		const tauruaTeitei = Number( tatauranga?.maximumMultiplicity || 1 );
		if (
			!Number.isInteger( katoa ) ||
			katoa < 7 ||
			katoa > 22 ||
			!Number.isInteger( rahi ) ||
			rahi !== 7776 ||
			!Number.isInteger( tauruaTeitei ) ||
			tauruaTeitei < 1 ||
			tauruaTeitei > rahi
		) {
			throw new Error( 'The passphrase strength calculation is invalid.' );
		}

		const ideal = katoa * Math.log2( rahi );
		const iti = katoa * Math.log2( rahi / tauruaTeitei );
		const kii = Math.min( iti, 256 );
		const quantum = kii / 2;

		return {
			katoa,
			rahi,
			tauruaTeitei,
			ideal,
			iti,
			kii,
			quantum,
		};
	}

	function tapangaKaha( tatauranga ) {
		if ( tatauranga.katoa >= 22 && tatauranga.quantum >= 128 ) {
			return {
				kupu: 'Post-quantum target reached',
				momo: 'quantum',
			};
		}
		if ( tatauranga.katoa >= 20 ) {
			return {
				kupu: 'Near post-quantum target',
				momo: 'tata',
			};
		}
		if ( tatauranga.katoa >= 15 ) {
			return {
				kupu: 'High-strength passphrase',
				momo: 'teitei',
			};
		}
		if ( tatauranga.katoa >= 10 ) {
			return {
				kupu: 'Strong passphrase',
				momo: 'kaha',
			};
		}
		return {
			kupu: 'Compact passphrase',
			momo: 'iti',
		};
	}

	function whakaatuTataurangaKaha( katoa, tatauranga = kupuTatauranga ) {
		if ( !tatauranga ) {
			return null;
		}

		const hua = hangaTataurangaKaha( katoa, tatauranga );
		const tapanga = tapangaKaha( hua );
		const tohu = tiki( 'tohu-kaha-v3' );
		const whakarāpopoto = tiki( 'tatauranga-kaha-v3' );
		const ideal = tiki( 'pangarau-ideal-v3' );
		const iti = tiki( 'pangarau-iti-v3' );
		const kii = tiki( 'pangarau-kii-v3' );
		const quantum = tiki( 'pangarau-quantum-v3' );

		if ( tohu ) {
			tohu.textContent = tapanga.kupu;
			tohu.dataset.kaha = tapanga.momo;
		}
		if ( whakarāpopoto ) {
			whakarāpopoto.textContent = hua.katoa + ' words · ' + hua.kii.toFixed( 2 ) + '-bit effective key strength';
		}
		if ( ideal ) {
			ideal.textContent = 'Ideal Diceware entropy: ' + hua.katoa + ' × log₂(' + hua.rahi.toLocaleString( 'en-NZ' ) + ') = ' + hua.ideal.toFixed( 2 ) + ' bits';
		}
		if ( iti ) {
			iti.textContent = 'Conservative displayed-word entropy: ' + hua.katoa + ' × log₂(' + hua.rahi.toLocaleString( 'en-NZ' ) + ' ÷ ' + hua.tauruaTeitei + ') = ' + hua.iti.toFixed( 2 ) + ' bits';
		}
		if ( kii ) {
			kii.textContent = 'Effective AES-256 ceiling: min(' + hua.iti.toFixed( 2 ) + ', 256) = ' + hua.kii.toFixed( 2 ) + ' bits';
		}
		if ( quantum ) {
			quantum.textContent = 'Simplified quantum-search estimate: ' + hua.kii.toFixed( 2 ) + ' ÷ 2 = ' + hua.quantum.toFixed( 2 ) + ' bits';
		}

		return hua;
	}

	function whakaatuKupuhipaHanga( tāurunga ) {
		const pouaka = tiki( 'kupuhipa-whakaatu-v3' );
		const ipu = tiki( 'kupuhipa_hanga_v3' );
		const ipuWaehere = tiki( 'kupuhipa_waehere_hanga_v3' );
		const pateneTaarua = document.querySelectorAll(
			'[data-copy-target="kupuhipa_hanga_v3"], [data-copy-target="kupuhipa_waehere_hanga_v3"]',
		);

		if ( !pouaka || !ipu || !ipuWaehere ) {
			return;
		}

		const cryptoV3 = window.TuhimunatangaV3Crypto;
		kupuhipaHangaEntries = tāurunga.map(
			( kupu ) => ( {
				code: kupu.code,
				word: kupu.word.normalize( 'NFC' ),
			} ),
		);
		kupuhipaHanga = cryptoV3.kupuhipaMaiTāurunga( kupuhipaHangaEntries );
		kupuhipaHangaKatoa = kupuhipaHangaEntries.length;
		whakaatuTataurangaKaha( kupuhipaHangaKatoa );
		ipu.value = kupuhipaHanga;
		ipuWaehere.value = kupuhipaHangaEntries
			.map( ( kupu ) => kupu.code + ' ' + kupu.word )
			.join( ' | ' );
		pouaka.replaceChildren();

		for ( const kupu of kupuhipaHangaEntries ) {
			const kāri = document.createElement( 'span' );
			kāri.className = 'kupuhipa-kupu';

			const waehere = document.createElement( 'span' );
			waehere.className = 'kupuhipa-waehere';
			waehere.textContent = kupu.code;

			const kupuWhakaatu = document.createElement( 'span' );
			kupuWhakaatu.className = 'kupuhipa-kupu-matua';
			kupuWhakaatu.textContent = kupu.word;

			kāri.append( waehere, kupuWhakaatu );
			pouaka.appendChild( kāri );
		}

		pouaka.setAttribute( 'aria-busy', 'false' );
		for ( const patene of pateneTaarua ) {
			if ( patene instanceof HTMLButtonElement ) {
				patene.disabled = false;
			}
		}
	}

	async function hangaKupuhipaHanga( takai ) {
		if ( kupuhipaHangaOati ) {
			return kupuhipaHangaOati;
		}

		kupuhipaHangaOati = ( async () => {
			const pouaka = tiki( 'kupuhipa-whakaatu-v3' );
			const pateneHou = tiki( 'patene-hou-kupuhipa-v3' );
			const ipuKatoa = tiki( 'kupu_katoa_v3' );
			const katoa = tikinaKupuKatoa();
			const pateneTaarua = document.querySelectorAll( '[data-copy-target="kupuhipa_hanga_v3"], [data-copy-target="kupuhipa_waehere_hanga_v3"]' );

			if ( pouaka ) {
				pouaka.setAttribute( 'aria-busy', 'true' );
				pouaka.innerHTML = '<span class="kupuhipa-utaina">Generating the passphrase…</span>';
			}
			for ( const patene of pateneTaarua ) {
				if ( patene instanceof HTMLButtonElement ) {
					patene.disabled = true;
				}
			}
			whakaritePatene( pateneHou, true, 'Generating…' );
			if ( ipuKatoa instanceof HTMLSelectElement ) {
				ipuKatoa.disabled = true;
			}

			try {
				const kupu = await tikinaKupu( takai.dataset.wordlistUrl );
				kupuTatauranga = kupu;
				whakaatuTataurangaKaha( katoa, kupu );
				const tāurunga = window.TuhimunatangaV3Crypto.mauKupuhipa(
					kupu.entries,
					katoa,
				);
				whakaatuKupuhipaHanga( tāurunga );
				return window.TuhimunatangaV3Crypto.kupuhipaMaiTāurunga( tāurunga );
			} catch ( hapa ) {
				kupuhipaHanga = '';
				kupuhipaHangaEntries = [];
				kupuhipaHangaKatoa = 0;
				if ( pouaka ) {
					pouaka.setAttribute( 'aria-busy', 'false' );
					pouaka.textContent = 'The passphrase could not be generated.';
				}
				throw hapa;
			} finally {
				whakaritePatene( pateneHou, false, '' );
				if ( ipuKatoa instanceof HTMLSelectElement ) {
					ipuKatoa.disabled = false;
				}
			}
		} )();

		try {
			return await kupuhipaHangaOati;
		} finally {
			kupuhipaHangaOati = null;
		}
	}

	function horoiAahua( aahua ) {
		if ( typeof aahua !== 'string' || aahua === '' ) {
			return '';
		}

		const hua = [];
		for ( const tauaki of aahua.split( ';' ) ) {
			if ( !tauaki.includes( ':' ) ) {
				continue;
			}

			const [ ingoaTaketake, ...toenga ] = tauaki.split( ':' );
			const ingoa = ingoaTaketake.trim().toLowerCase();
			const uara = toenga.join( ':' ).trim().replace( /\s+/gu, ' ' );
			if ( !AAHUA_WHAAEA.has( ingoa ) || uara === '' || uara.length > 240 ) {
				continue;
			}

			const uaraIti = uara.toLowerCase();
			if (
				uaraIti.includes( 'url(' ) ||
				uaraIti.includes( 'expression' ) ||
				uaraIti.includes( 'javascript' ) ||
				uaraIti.includes( 'vbscript' ) ||
				uaraIti.includes( 'data:' ) ||
				uaraIti.includes( '@import' ) ||
				uaraIti.includes( 'behavior' ) ||
				uaraIti.includes( '-moz-binding' ) ||
				uaraIti.includes( 'var(' ) ||
				!/^[A-Za-z0-9#(),.%\s'"/\-]+$/u.test( uara )
			) {
				continue;
			}

			hua.push( ingoa + ': ' + uara );
			if ( hua.length >= 80 ) {
				break;
			}
		}

		return hua.join( '; ' );
	}

	function horoiHononga( hononga ) {
		if ( typeof hononga !== 'string' ) {
			return '';
		}

		const hua = hononga.trim();
		if ( hua === '' || hua.length > 2048 || /[\u0000-\u0020\u007f]/u.test( hua ) ) {
			return '';
		}
		if ( hua.startsWith( '#' ) || hua.startsWith( '/' ) || hua.startsWith( './' ) || hua.startsWith( '../' ) ) {
			return hua;
		}

		try {
			const hono = new URL( hua, window.location.href );
			return [ 'http:', 'https:', 'mailto:' ].includes( hono.protocol ) ? hua : '';
		} catch ( hapa ) {
			return '';
		}
	}

	function taaruaHuanga( taawai, ma, ingoa ) {
		const reo = taawai.getAttribute( 'lang' ) || '';
		if ( /^[A-Za-z0-9-]{1,35}$/u.test( reo ) ) {
			ma.setAttribute( 'lang', reo );
		}

		const aronga = ( taawai.getAttribute( 'dir' ) || '' ).toLowerCase();
		if ( [ 'ltr', 'rtl', 'auto' ].includes( aronga ) ) {
			ma.setAttribute( 'dir', aronga );
		}

		const aahua = horoiAahua( taawai.getAttribute( 'style' ) || '' );
		if ( aahua !== '' ) {
			ma.setAttribute( 'style', aahua );
		}

		if ( ingoa === 'a' ) {
			const hononga = horoiHononga( taawai.getAttribute( 'href' ) || '' );
			if ( hononga !== '' ) {
				ma.setAttribute( 'href', hononga );
				ma.setAttribute( 'target', '_blank' );
				ma.setAttribute( 'rel', 'noopener noreferrer nofollow' );
			}
			const taitara = ( taawai.getAttribute( 'title' ) || '' )
				.replace( /[\u0000\r\n]/gu, '' )
				.trim()
				.slice( 0, 300 );
			if ( taitara !== '' ) {
				ma.setAttribute( 'title', taitara );
			}
		}

		if ( [ 'td', 'th' ].includes( ingoa ) ) {
			for ( const huanga of [ 'colspan', 'rowspan' ] ) {
				const uara = taawai.getAttribute( huanga ) || '';
				if ( /^[1-9][0-9]{0,2}$/u.test( uara ) && Number( uara ) <= 100 ) {
					ma.setAttribute( huanga, uara );
				}
			}
		}

		if ( [ 'col', 'colgroup' ].includes( ingoa ) ) {
			const uara = taawai.getAttribute( 'span' ) || '';
			if ( /^[1-9][0-9]{0,2}$/u.test( uara ) && Number( uara ) <= 100 ) {
				ma.setAttribute( 'span', uara );
			}
		}

		if ( ingoa === 'table' ) {
			for ( const huanga of [ 'border', 'cellpadding', 'cellspacing' ] ) {
				const uara = taawai.getAttribute( huanga ) || '';
				if ( /^[0-9]{1,3}$/u.test( uara ) ) {
					ma.setAttribute( huanga, uara );
				}
			}
		}

		if ( ingoa === 'ol' ) {
			const uara = taawai.getAttribute( 'start' ) || '';
			if ( /^-?[0-9]{1,6}$/u.test( uara ) ) {
				ma.setAttribute( 'start', uara );
			}
		}

		if ( ingoa === 'font' ) {
			const kanohi = ( taawai.getAttribute( 'face' ) || '' ).trim().slice( 0, 180 );
			if ( /^[A-Za-z0-9 ,\-"']+$/u.test( kanohi ) ) {
				ma.setAttribute( 'face', kanohi );
			}
			const tae = ( taawai.getAttribute( 'color' ) || '' ).trim().slice( 0, 40 );
			if ( /^[A-Za-z0-9#(),.%\s-]+$/u.test( tae ) ) {
				ma.setAttribute( 'color', tae );
			}
			const rahi = taawai.getAttribute( 'size' ) || '';
			if ( /^[1-7]$/u.test( rahi ) ) {
				ma.setAttribute( 'size', rahi );
			}
		}
	}

	function horoiKopuku( kopuku, tuhinga ) {
		if ( kopuku.nodeType === Node.TEXT_NODE ) {
			return tuhinga.createTextNode( kopuku.data );
		}
		if ( kopuku.nodeType !== Node.ELEMENT_NODE ) {
			return null;
		}

		const ingoa = kopuku.localName.toLowerCase();
		if ( TOHU_MUKUA.has( ingoa ) ) {
			return null;
		}
		if ( !TOHU_WHAAEA.has( ingoa ) ) {
			const kongakonga = tuhinga.createDocumentFragment();
			for ( const tamaiti of Array.from( kopuku.childNodes ) ) {
				const ma = horoiKopuku( tamaiti, tuhinga );
				if ( ma ) {
					kongakonga.appendChild( ma );
				}
			}
			return kongakonga;
		}

		const ma = tuhinga.createElement( ingoa );
		taaruaHuanga( kopuku, ma, ingoa );
		for ( const tamaiti of Array.from( kopuku.childNodes ) ) {
			const tamaitiMa = horoiKopuku( tamaiti, tuhinga );
			if ( tamaitiMa ) {
				ma.appendChild( tamaitiMa );
			}
		}
		return ma;
	}

	function horoiHtml( html ) {
		const tauira = document.createElement( 'template' );
		tauira.innerHTML = typeof html === 'string' ? html : '';
		const tuhingaMa = document.implementation.createHTMLDocument( '' );
		const pakiakaMa = tuhingaMa.createElement( 'div' );

		for ( const tamaiti of Array.from( tauira.content.childNodes ) ) {
			const hua = horoiKopuku( tamaiti, tuhingaMa );
			if ( hua ) {
				pakiakaMa.appendChild( hua );
			}
		}

		return pakiakaMa.innerHTML;
	}

	function kiKuputuhi( html ) {
		const tuhinga = new DOMParser().parseFromString(
			'<!doctype html><html><body>' + html + '</body></html>',
			'text/html',
		);
		return ( tuhinga.body.textContent || '' ).replace( /\s+/gu, ' ' ).trim();
	}

	function hangaTuhinga( html ) {
		return '<!doctype html><html lang="en-NZ"><head><meta charset="UTF-8">' +
			'<meta name="viewport" content="width=device-width,initial-scale=1">' +
			'<meta http-equiv="Content-Security-Policy" content="default-src &apos;none&apos;; style-src &apos;unsafe-inline&apos;; base-uri &apos;none&apos;; form-action &apos;none&apos;">' +
			'<style>html{color-scheme:light}body{margin:0;padding:1rem;color:#17212a;background:#fff;font-family:Calibri,Aptos,&quot;Segoe UI&quot;,Arial,sans-serif;line-height:1.45;overflow-wrap:anywhere}' +
			'table{max-width:100%;border-collapse:collapse}th,td{vertical-align:top}pre{white-space:pre-wrap;overflow-wrap:anywhere}' +
			'a{color:#6e4b18}blockquote{margin-left:1.25rem;border-left:3px solid #b9c4ca;padding-left:.9rem}img,svg,video,audio,iframe,object,embed,form{display:none!important}</style>' +
			'</head><body>' + html + '</body></html>';
	}

	function kuputuhiHaumaru( kuputuhi ) {
		const div = document.createElement( 'div' );
		div.textContent = kuputuhi;
		return div.innerHTML;
	}

	function whakauruHtml( html ) {
		const kowhiringa = window.getSelection();
		if ( !kowhiringa || kowhiringa.rangeCount === 0 ) {
			return;
		}

		const awhe = kowhiringa.getRangeAt( 0 );
		awhe.deleteContents();
		const tauira = document.createElement( 'template' );
		tauira.innerHTML = html;
		const kongakonga = tauira.content;
		const whakamutunga = kongakonga.lastChild;
		awhe.insertNode( kongakonga );

		if ( whakamutunga ) {
			awhe.setStartAfter( whakamutunga );
			awhe.collapse( true );
			kowhiringa.removeAllRanges();
			kowhiringa.addRange( awhe );
		}
	}

	async function tikinaKupu( hononga ) {
		if ( kupuOati ) {
			return kupuOati;
		}

		kupuOati = fetch(
			hononga,
			{
				method: 'GET',
				credentials: 'same-origin',
				cache: 'default',
				headers: { Accept: 'application/json' },
			},
		).then( async ( whakautu ) => {
			const raraunga = await whakautu.json();
			if ( !whakautu.ok || !raraunga.ok || !Array.isArray( raraunga.entries ) ) {
				throw new Error( raraunga.message || 'The passphrase wordlist could not be loaded.' );
			}
			if ( raraunga.entries.length !== 7776 ) {
				throw new Error( 'The passphrase wordlist does not contain 7,776 entries.' );
			}
			const wordlistSize = Number( raraunga.wordlist_size || raraunga.entries.length );
			const distinctWords = Number( raraunga.distinct_words );
			const maximumMultiplicity = Number( raraunga.maximum_multiplicity );
			const minimumWords = Number( raraunga.minimum_words || 7 );
			const maximumWords = Number( raraunga.maximum_words || 22 );
			const recommendedWords = Number( raraunga.recommended_words || raraunga.passphrase_words || 22 );
			if (
				wordlistSize !== 7776 ||
				!Number.isInteger( distinctWords ) ||
				distinctWords < 1 ||
				distinctWords > wordlistSize ||
				!Number.isInteger( maximumMultiplicity ) ||
				maximumMultiplicity < 1 ||
				minimumWords !== 7 ||
				maximumWords !== 22 ||
				!Number.isInteger( recommendedWords ) ||
				recommendedWords < minimumWords ||
				recommendedWords > maximumWords
			) {
				throw new Error( 'The passphrase strength metadata supplied by the wordlist endpoint is invalid.' );
			}
			return {
				entries: raraunga.entries,
				wordlistSize,
				distinctWords,
				maximumMultiplicity,
				minimumWords,
				maximumWords,
				recommendedWords,
			};
		} );

		return kupuOati;
	}

	async function tukuApi( api, raraunga ) {
		const puka = new URLSearchParams();
		for ( const [ ingoa, uara ] of Object.entries( raraunga ) ) {
			puka.set( ingoa, String( uara ) );
		}

		const whakautu = await fetch(
			api,
			{
				method: 'POST',
				credentials: 'same-origin',
				cache: 'no-store',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
				},
				body: puka.toString(),
			},
		);

		let hua;
		try {
			hua = await whakautu.json();
		} catch ( hapa ) {
			throw new Error( 'The server returned an invalid response.', { cause: hapa } );
		}

		return { whakautu, hua };
	}

	function whakaatuWaa( waitohuwaa ) {
		if ( waitohuwaa === null || waitohuwaa === undefined ) {
			return 'This paste does not expire automatically.';
		}
		return 'Expiry: ' + new Date( waitohuwaa * 1000 ).toLocaleString( 'en-NZ' );
	}

	function whakaatuHuaHanga( hononga, kupuhipa, waitohuwaa, panuiKotahi ) {
		tiki( 'papahono_huri_v3' ).value = hononga;
		tiki( 'kupuhipa_v3' ).value = kupuhipa;
		const kaha = whakaatuTataurangaKaha( kupuhipaHangaKatoa );
		const kahaHua = tiki( 'kupuhipa_kaha_hua_v3' );
		if ( kahaHua && kaha ) {
			const tapanga = tapangaKaha( kaha );
			kahaHua.textContent = tapanga.kupu + ': ' + kaha.katoa + ' words, ' + kaha.iti.toFixed( 2 ) + ' bits of conservative passphrase entropy, ' + kaha.quantum.toFixed( 2 ) + ' bits under the simplified quantum-search estimate.';
		}
		tiki( 'taipitopito_hua_v3' ).textContent = panuiKotahi
			? 'This paste will be deleted from the server after the first successful browser decryption that reports completion.'
			: whakaatuWaa( waitohuwaa );

		whakaatu( tiki( 'hanga-v3' ), false );
		whakaatu( tiki( 'wetemuna-v3' ), false );
		whakaatu( tiki( 'hua-hanga-v3' ), true );
		karere( 'The paste was encrypted in this browser and the ciphertext was saved.', 'angitu' );
	}

	async function hangaWhakapiri( takai ) {
		const puka = tiki( 'puka-v3' );
		const etita = tiki( 'haatepe_etita' );
		const patene = tiki( 'patene-hanga-v3' );
		const paunga = tiki( 'paunga_v3' ).value;
		const cryptoV3 = window.TuhimunatangaV3Crypto;

		karere( '' );
		whakaritePatene( patene, true, 'Encrypting…' );

		try {
			cryptoV3.manatokoTaiao();
			const html = horoiHtml( etita.innerHTML );
			if ( kiKuputuhi( html ) === '' ) {
				throw new Error( 'Paste or enter content to encrypt.' );
			}

			const katoa = tikinaKupuKatoa();
			const kupuhipa = kupuhipaHanga !== '' && kupuhipaHangaKatoa === katoa
				? kupuhipaHanga
				: await hangaKupuhipaHanga( takai );
			const panuiKotahi = paunga === 'burn';
			const kaupapa = { expiry_rule: paunga, one_time: panuiKotahi };
			const mukuaMuna = panuiKotahi ? cryptoV3.raraungaTupono( 32 ) : null;
			const ihirangi = {
				type: 'TUHIMUNATANGA-RICH-HTML',
				version: 2,
				html,
				expiry_rule: paunga,
				one_time: panuiKotahi,
				delete_secret: mukuaMuna ? cryptoV3.puutahe64Url( mukuaMuna ) : null,
			};

			let nganatanga = 0;
			while ( nganatanga < 3 ) {
				nganatanga++;
				const haatepe = cryptoV3.mauHaatepe( 22 );
				const kopaki = await cryptoV3.whakamuna( ihirangi, kupuhipa, haatepe, kaupapa );
				let mukuaHash = '';
				if ( mukuaMuna ) {
					mukuaHash = cryptoV3.puutahe64Url(
						await cryptoV3.mauMukuaHash( haatepe, mukuaMuna ),
					);
				}

				const { whakautu, hua } = await tukuApi(
					takai.dataset.apiUrl,
					{
						mahi: 'hanga_v3',
						csrf: takai.dataset.csrf,
						i: haatepe,
						kopaki,
						paunga,
						mukua_hash: mukuaHash,
					},
				);

				if ( whakautu.status === 409 && hua.code === 'token_collision' ) {
					continue;
				}
				if ( !whakautu.ok || !hua.ok ) {
					throw new Error(
						karereApi( hua, 'The encrypted paste could not be saved.' ),
					);
				}

				etita.replaceChildren();
				puka.reset();
				whakaatuHuaHanga( hua.link, kupuhipa, hua.expires, hua.one_time );
				return;
			}

			throw new Error( 'A secure-link token could not be allocated. Try again.' );
		} catch ( hapa ) {
			karere( hapa instanceof Error ? hapa.message : 'The paste could not be encrypted.' );
		} finally {
			whakaritePatene( patene, false, '' );
		}
	}

	async function tikinaWhakapiri( takai, haatepe ) {
		karere( 'Loading the encrypted envelope…', 'angitu' );
		try {
			const { whakautu, hua } = await tukuApi(
				takai.dataset.apiUrl,
				{
					mahi: 'tiro_v3',
					csrf: takai.dataset.csrf,
					i: haatepe,
				},
			);

			if ( !whakautu.ok || !hua.ok ) {
				throw new Error(
					karereApi( hua, 'The encrypted paste could not be loaded.' ),
				);
			}

			teneiKopaki = hua.envelope;
			teneiHaatepe = haatepe;
			teneiPanuiKotahi = Boolean( hua.one_time );
			teneiKaupapaTūmau = {
				expiry_rule: String( hua.expiry_rule || '' ),
				one_time: teneiPanuiKotahi,
			};
			tiki( 'taipitopito-wetemuna-v3' ).textContent = teneiPanuiKotahi
				? 'This paste is deleted from the server only after a successful browser decryption reports completion.'
				: whakaatuWaa( hua.expires );

			whakaatu( tiki( 'hanga-v3' ), false );
			whakaatu( tiki( 'hua-hanga-v3' ), false );
			whakaatu( tiki( 'wetemuna-v3' ), true );
			karere( '' );
			tiki( 'ipu_kupuhika_v3' ).focus();
		} catch ( hapa ) {
			whakaatu( tiki( 'hanga-v3' ), false );
			karere( hapa instanceof Error ? hapa.message : 'The encrypted paste could not be loaded.' );
		}
	}

	async function wetemunaWhakapiri( takai ) {
		const patene = tiki( 'patene-wetemuna-v3' );
		const ipu = tiki( 'ipu_kupuhika_v3' );
		const cryptoV3 = window.TuhimunatangaV3Crypto;
		karere( '' );
		whakaritePatene( patene, true, 'Decrypting…' );

		try {
			const huaWetemuna = await cryptoV3.wetemuna(
				teneiKopaki,
				ipu.value,
				teneiHaatepe,
				teneiKaupapaTūmau,
			);
			const ihirangi = huaWetemuna.content;
			if (
				!ihirangi ||
				ihirangi.type !== 'TUHIMUNATANGA-RICH-HTML' ||
				![ 1, 2 ].includes( ihirangi.version ) ||
				typeof ihirangi.html !== 'string'
			) {
				throw new Error( 'The decrypted content format is invalid.' );
			}
			if ( huaWetemuna.profile === 2 && !huaWetemuna.policy_matches ) {
				throw new Error( 'The stored policy metadata does not match the encrypted policy.' );
			}

			const html = horoiHtml( ihirangi.html );
			const kuputuhi = kiKuputuhi( html );
			const anga = tiki( 'anga-wetemuna-v3' );
			anga.srcdoc = hangaTuhinga( html );
			tiki( 'karere_wetemunahia_v3' ).value = kuputuhi;
			tiki( 'karere_wetemunahia_html_v3' ).value = html;
			ipu.value = '';

			let karereAngitu = 'The paste was decrypted entirely in this browser.';
			if ( teneiPanuiKotahi ) {
				if ( typeof ihirangi.delete_secret !== 'string' ) {
					throw new Error( 'The one-time deletion secret is missing from the decrypted content.' );
				}

				const { whakautu, hua } = await tukuApi(
					takai.dataset.apiUrl,
					{
						mahi: 'mukua_v3',
						csrf: takai.dataset.csrf,
						i: teneiHaatepe,
						mukua_muna: ihirangi.delete_secret,
					},
				);

				if ( !whakautu.ok || !hua.ok ) {
					karereAngitu = 'The paste was decrypted in this browser. Server deletion could not be confirmed.';
				} else {
					karereAngitu = 'The paste was decrypted in this browser and permanently deleted from the server.';
				}
			}

			whakaatu( tiki( 'wetemuna-v3' ), false );
			whakaatu( tiki( 'hua-wetemuna-v3' ), true );
			karere( karereAngitu, 'angitu' );
		} catch ( hapa ) {
			karere( hapa instanceof Error ? hapa.message : 'The encrypted paste could not be decrypted.' );
		} finally {
			whakaritePatene( patene, false, '' );
		}
	}

	async function taaruaUara( id, patene = null, feedbackId = '' ) {
		const ipu = tiki( id );
		if ( !ipu || typeof ipu.value !== 'string' || ipu.value === '' ) {
			karere( 'There is nothing to copy.' );
			return;
		}

		let kuaTaarua = false;
		try {
			await navigator.clipboard.writeText( ipu.value );
			kuaTaarua = true;
		} catch ( hapa ) {
			try {
				ipu.focus( { preventScroll: true } );
				ipu.select();
				ipu.setSelectionRange( 0, ipu.value.length );
				kuaTaarua = document.execCommand( 'copy' );
			} catch ( hapaTuarua ) {
				kuaTaarua = false;
			}
		}

		if ( !kuaTaarua ) {
			karere( 'The value could not be copied automatically.' );
			return;
		}

		whakaatuTaarua( patene, feedbackId );
	}

	async function taaruaWhaihanga( htmlId, textId, patene = null, feedbackId = '' ) {
		const html = tiki( htmlId )?.value || '';
		const text = tiki( textId )?.value || '';
		try {
			if ( window.ClipboardItem && navigator.clipboard.write ) {
				await navigator.clipboard.write(
					[
						new ClipboardItem(
							{
								'text/html': new Blob( [ html ], { type: 'text/html' } ),
								'text/plain': new Blob( [ text ], { type: 'text/plain' } ),
							},
						),
					],
				);
				whakaatuTaarua( patene, feedbackId );
				return;
			}
			await navigator.clipboard.writeText( text );
			whakaatuTaarua( patene, feedbackId );
		} catch ( hapa ) {
			karere( 'The content could not be copied automatically.' );
		}
	}

	function whakariteTaarua() {
		for ( const patene of document.querySelectorAll( '[data-copy-target]' ) ) {
			patene.addEventListener(
				'click',
				() => taaruaUara(
					patene.dataset.copyTarget,
					patene,
					patene.dataset.copyFeedbackTarget || '',
				),
			);
		}
		for ( const patene of document.querySelectorAll( '[data-copy-html-target]' ) ) {
			patene.addEventListener(
				'click',
				() => taaruaWhaihanga(
					patene.dataset.copyHtmlTarget,
					patene.dataset.copyTextTarget,
					patene,
					patene.dataset.copyFeedbackTarget || '',
				),
			);
		}
	}

	function whakariteWhakapiri() {
		const etita = tiki( 'haatepe_etita' );
		if ( !etita ) {
			return;
		}

		etita.addEventListener(
			'paste',
			( takahanga ) => {
				const html = takahanga.clipboardData?.getData( 'text/html' ) || '';
				const text = takahanga.clipboardData?.getData( 'text/plain' ) || '';
				takahanga.preventDefault();
				if ( html !== '' ) {
					whakauruHtml( horoiHtml( html ) );
					return;
				}
				whakauruHtml( '<pre>' + kuputuhiHaumaru( text ) + '</pre>' );
			},
		);
	}

	function tikinaHaatepeMaiHash() {
		const hash = window.location.hash.startsWith( '#' )
			? window.location.hash.slice( 1 )
			: window.location.hash;
		const uara = new URLSearchParams( hash ).get( 'i' ) || '';
		return /^[A-Za-z0-9]{15}(?:[A-Za-z0-9]{7})?$/u.test( uara ) ? uara : '';
	}

	function timata() {
		whakariteTaarua();
		whakariteWhakapiri();

		const takai = tiki( 'tmt-v3' );
		if ( !takai || takai.dataset.mode !== 'v3' ) {
			return;
		}

		try {
			window.TuhimunatangaV3Crypto.manatokoTaiao();
		} catch ( hapa ) {
			whakaatu( tiki( 'hanga-v3' ), false );
			karere( hapa instanceof Error ? hapa.message : 'Browser-side encryption is unavailable.' );
			return;
		}

		const ipuKupuKatoa = tiki( 'kupu_katoa_v3' );
		if ( ipuKupuKatoa instanceof HTMLSelectElement ) {
			ipuKupuKatoa.addEventListener(
				'change',
				() => {
					kupuhipaHanga = '';
					kupuhipaHangaEntries = [];
					kupuhipaHangaKatoa = 0;
					try {
						whakaatuTataurangaKaha( tikinaKupuKatoa() );
					} catch ( hapa ) {
						karere( hapa instanceof Error ? hapa.message : 'The passphrase length is invalid.' );
						return;
					}
					hangaKupuhipaHanga( takai ).catch( ( hapa ) => {
						karere( hapa instanceof Error ? hapa.message : 'The passphrase could not be generated.' );
					} );
				},
			);
		}

		const pateneHouKupuhipa = tiki( 'patene-hou-kupuhipa-v3' );
		if ( pateneHouKupuhipa ) {
			pateneHouKupuhipa.addEventListener(
				'click',
				() => {
					hangaKupuhipaHanga( takai ).catch( ( hapa ) => {
						karere( hapa instanceof Error ? hapa.message : 'The passphrase could not be generated.' );
					} );
				},
			);
		}

		const pukaHanga = tiki( 'puka-v3' );
		if ( pukaHanga ) {
			pukaHanga.addEventListener(
				'submit',
				( takahanga ) => {
					takahanga.preventDefault();
					hangaWhakapiri( takai );
				},
			);
		}

		const pukaWetemuna = tiki( 'puka-wetemuna-v3' );
		if ( pukaWetemuna ) {
			pukaWetemuna.addEventListener(
				'submit',
				( takahanga ) => {
					takahanga.preventDefault();
					wetemunaWhakapiri( takai );
				},
			);
		}

		const haatepe = tikinaHaatepeMaiHash();
		if ( haatepe !== '' ) {
			tikinaWhakapiri( takai, haatepe );
			return;
		}

		hangaKupuhipaHanga( takai ).catch( ( hapa ) => {
			karere( hapa instanceof Error ? hapa.message : 'The passphrase could not be generated.' );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', timata );
} )();
