import fs from 'node:fs';

const index = fs.readFileSync( new URL( '../index.php', import.meta.url ), 'utf8' );
const script = fs.readFileSync( new URL( '../hootuhihawa.js', import.meta.url ), 'utf8' );
const style = fs.readFileSync( new URL( '../kaahua.css', import.meta.url ), 'utf8' );

const requirements = [
	[ index.includes( 'id="kupu_katoa_v3"' ), 'The passphrase-length selector is missing.' ],
	[ index.includes( '<option value="7" selected>7 words</option>' ), 'The 7-word default is missing.' ],
	[ index.includes( 'Post-quantum target reached' ) || index.includes( 'Not quantum-resistant at this length' ), 'The strength label is missing.' ],
	[ index.includes( 'pangarau-ideal-v3' ) && index.includes( 'pangarau-quantum-v3' ), 'The entropy-equation display is missing.' ],
	[ script.includes( 'Math.log2( rahi )' ), 'Ideal Diceware entropy is not calculated.' ],
	[ script.includes( 'Math.log2( rahi / tauruaTeitei )' ), 'Conservative displayed-word entropy is not calculated.' ],
	[ script.includes( 'const quantum = kii / 2' ), 'The simplified quantum-search estimate is missing.' ],
	[ script.includes( 'kupuhipaHangaKatoa === katoa' ), 'The selected word count is not bound to the generated passphrase.' ],
	[ index.includes( 'Copy passphrase + codes' ), 'Copy passphrase + codes control is missing.' ],
	[ index.includes( 'kupuhipa_waehere_hanga_v3' ), 'The coded passphrase copy field is missing.' ],
	[ !style.includes( 'content: attr(data-tau)' ), 'Ordinal number badges remain in the stylesheet.' ],
	[ style.includes( 'grid-template-columns: repeat( auto-fit' ), 'The responsive passphrase card grid is missing.' ],
	[ script.includes( "waehere.className = 'kupuhipa-waehere'" ), 'Diceware codes are not rendered.' ],
	[ script.includes( "kupuWhakaatu.className = 'kupuhipa-kupu-matua'" ), 'Māori words are not rendered as the primary card value.' ],
	[ script.includes( "patene.textContent = 'Copied ✓'" ), 'Visible copied-state feedback is missing.' ],
	[ script.includes( "const haatepe = cryptoV3.mauHaatepe( 22 )" ), 'New creation does not generate 22-character tokens.' ],
	[ script.includes( '/^[A-Za-z0-9]{15}(?:[A-Za-z0-9]{7})?$/' ), 'Legacy and new retrieval token lengths are not accepted.' ],
];

for ( const [ passed, message ] of requirements ) {
	if ( !passed ) {
		throw new Error( message );
	}
}

console.log( 'Interface and token static tests passed.' );
