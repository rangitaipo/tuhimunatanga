import fs from 'node:fs';
import vm from 'node:vm';
import { webcrypto } from 'node:crypto';
import { TextEncoder, TextDecoder } from 'node:util';

const context = {
	window: {
		isSecureContext: true,
		crypto: webcrypto,
	},
	crypto: webcrypto,
	TextEncoder,
	TextDecoder,
	Uint8Array,
	Uint32Array,
	DataView,
	ArrayBuffer,
	Number,
	String,
	JSON,
	Math,
	Error,
	Object,
	RegExp,
	btoa: ( value ) => Buffer.from( value, 'binary' ).toString( 'base64' ),
	atob: ( value ) => Buffer.from( value, 'base64' ).toString( 'binary' ),
};
context.window.window = context.window;
context.window.TextEncoder = TextEncoder;
context.window.TextDecoder = TextDecoder;
context.window.btoa = context.btoa;
context.window.atob = context.atob;

vm.createContext( context );
vm.runInContext(
	fs.readFileSync( new URL( '../crypto.js', import.meta.url ), 'utf8' ),
	context,
);

const tmt = context.window.TuhimunatangaV3Crypto;
const fixture = JSON.parse(
	fs.readFileSync( new URL( './fixtures/profile1.json', import.meta.url ), 'utf8' ),
);
const fixture2 = JSON.parse(
	fs.readFileSync( new URL( './fixtures/profile2.json', import.meta.url ), 'utf8' ),
);

function whakarerekē( uara ) {
	const tuatahi = uara.charAt( 0 );
	return ( tuatahi === 'A' ? 'B' : 'A' ) + uara.slice( 1 );
}

async function meWhakakāhore( mahi, ingoa ) {
	let kuaKāhore = false;
	try {
		await mahi();
	} catch ( hapa ) {
		kuaKāhore = true;
	}
	if ( !kuaKāhore ) {
		throw new Error( ingoa + ' was not rejected.' );
	}
}

const profile1 = await tmt.wetemuna(
	fixture.envelope,
	fixture.passphrase,
	fixture.token,
);
if (
	profile1.profile !== 1 ||
	JSON.stringify( profile1.content ) !== JSON.stringify( fixture.payload )
) {
	throw new Error( 'Profile-1 compatibility fixture did not decrypt.' );
}

const profile2Fixture = await tmt.wetemuna(
	fixture2.envelope,
	fixture2.passphrase,
	fixture2.token,
	fixture2.policy,
);
if (
	profile2Fixture.profile !== 2 ||
	JSON.stringify( profile2Fixture.content ) !== JSON.stringify( fixture2.payload )
) {
	throw new Error( 'Profile-2 known-answer fixture did not decrypt.' );
}

const token = tmt.mauHaatepe();
if ( token.length !== 22 ) {
	throw new Error( 'New token length is not 22 characters.' );
}
if ( tmt.mauHaatepe( 15 ).length !== 15 ) {
	throw new Error( 'Legacy token generation test failed.' );
}

const passphraseNfc = 'āio aroha awa kāinga kupu mana mātauranga moana mokopuna noho ora pāpā rangi reo tangata tapu tikanga wai waka whenua';
const passphraseNfd = passphraseNfc.normalize( 'NFD' );
const secret = tmt.raraungaTupono( 32 );
const policy = { expiry_rule: '604800', one_time: false };
const payload = {
	type: 'TUHIMUNATANGA-RICH-HTML',
	version: 2,
	html: '<p>He whakamātautau <strong>profile 2</strong>.</p>',
	expiry_rule: policy.expiry_rule,
	one_time: policy.one_time,
	delete_secret: null,
};

const envelope = await tmt.whakamuna( payload, passphraseNfd, token, policy );
const parsed = JSON.parse( envelope );
if ( parsed.profile !== 2 ) {
	throw new Error( 'New envelope does not declare profile 2.' );
}
const decrypted = await tmt.wetemuna( envelope, passphraseNfc, token, policy );
if (
	decrypted.profile !== 2 ||
	decrypted.policy_matches !== true ||
	JSON.stringify( decrypted.content ) !== JSON.stringify( payload )
) {
	throw new Error( 'Profile-2 round trip mismatch.' );
}

await meWhakakāhore(
	() => tmt.wetemuna( envelope, passphraseNfc + ' hē', token, policy ),
	'Incorrect passphrase',
);
await meWhakakāhore(
	() => tmt.wetemuna( envelope, passphraseNfc, whakarerekē( token ), policy ),
	'Altered token',
);
await meWhakakāhore(
	() => tmt.wetemuna( envelope, passphraseNfc, token, { expiry_rule: '86400', one_time: false } ),
	'Altered expiry rule',
);
await meWhakakāhore(
	() => tmt.wetemuna( envelope, passphraseNfc, token, { expiry_rule: 'burn', one_time: true } ),
	'Altered one-time policy',
);

for ( const field of [ 'salt', 'iv', 'ct' ] ) {
	const changed = { ...parsed, [ field ]: whakarerekē( parsed[ field ] ) };
	await meWhakakāhore(
		() => tmt.wetemuna( JSON.stringify( changed ), passphraseNfc, token, policy ),
		'Altered ' + field,
	);
}

await meWhakakāhore(
	() => tmt.wetemuna(
		JSON.stringify( { ...parsed, profile: 1 } ),
		passphraseNfc,
		token,
		policy,
	),
	'Altered profile',
);
await meWhakakāhore(
	() => tmt.wetemuna(
		JSON.stringify( { ...parsed, profile: 99 } ),
		passphraseNfc,
		token,
		policy,
	),
	'Unsupported profile',
);
await meWhakakāhore(
	() => tmt.wetemuna(
		JSON.stringify( { ...parsed, iter: 600001 } ),
		passphraseNfc,
		token,
		policy,
	),
	'Unsupported iteration count',
);

const deleteHash = await tmt.mauMukuaHash( token, secret );
if ( deleteHash.length !== 32 ) {
	throw new Error( 'Deletion hash length is invalid.' );
}

const wordlistEntries = fs.readFileSync(
	new URL( '../raraunga/7776_kupu.db', import.meta.url ),
	'utf8',
).trim().split( /\r?\n/u ).map( ( line ) => {
	const match = line.match( /^([1-6]{5})[\t ]+(.+)$/u );
	if ( !match ) {
		throw new Error( 'The test wordlist contains an invalid row.' );
	}
	return { code: match[ 1 ], word: match[ 2 ] };
} );
if ( tmt.mauKupuhipa( wordlistEntries, 7 ).length !== 7 ) {
	throw new Error( 'Seven-word passphrase generation failed.' );
}
if ( tmt.mauKupuhipa( wordlistEntries, 22 ).length !== 22 ) {
	throw new Error( 'Twenty-two-word passphrase generation failed.' );
}
await meWhakakāhore(
	async () => tmt.mauKupuhipa( wordlistEntries, 6 ),
	'Six-word passphrase request',
);
await meWhakakāhore(
	async () => tmt.mauKupuhipa( wordlistEntries, 23 ),
	'Twenty-three-word passphrase request',
);

console.log( 'Profile compatibility, tamper tests and 7-to-22-word generation passed.' );
