<?php

declare( strict_types=1 );

require_once( __DIR__ . '/tuhimunatanga.php' );

Tuhimunatanga::whakanoho_pane_haumaru();

$Tuhimunatanga = null;
$karere_whirihora = '';

try {
	$Tuhimunatanga = new Tuhimunatanga();
} catch ( Throwable $hapa ) {
	http_response_code( 500 );
	error_log( 'Tuhimunatanga v3 bootstrap failure: ' . $hapa->getMessage() );
	$karere_whirihora = $hapa instanceof RuntimeException
		? $hapa->getMessage()
		: 'The v3 application could not be started.';
}

function puta( mixed $uara ): string {
	return htmlspecialchars(
		( string ) $uara,
		ENT_QUOTES | ENT_SUBSTITUTE,
		'UTF-8'
	);
}
?>
<!doctype html>
<html lang="en-NZ">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= puta( $Tuhimunatanga?->taitara ?? 'Tuhimunatanga v3' ) ?></title>
	<link rel="stylesheet" href="kaahua.css?ver=<?= puta( ( string ) filemtime( __DIR__ . '/kaahua.css' ) ) ?>">
	<script defer src="crypto.js?ver=<?= puta( ( string ) filemtime( __DIR__ . '/crypto.js' ) ) ?>"></script>
	<script defer src="hootuhihawa.js?ver=<?= puta( ( string ) filemtime( __DIR__ . '/hootuhihawa.js' ) ) ?>"></script>
</head>
<body>
<a class="peke-ihirangi" href="#tmt-v3">Skip to content</a>

<header class="pae-runga">
	<div class="takai pane">
		<a class="waitohu" href="<?= puta( $Tuhimunatanga?->papahono_matua() ?? './' ) ?>" aria-label="Tuhimunatanga home">
			<span class="waitohu-tohu" aria-hidden="true">
				<svg viewBox="0 0 64 64" focusable="false">
					<circle cx="32" cy="32" r="27"></circle>
					<path d="M43 18c-12-2-23 7-23 19 0 8 6 14 14 14 7 0 12-5 12-11 0-6-5-10-10-10-5 0-8 4-8 8 0 3 2 6 5 7"></path>
					<path d="M20 24c7 1 13 5 16 11"></path>
				</svg>
			</span>
			<span class="waitohu-kupu">
				<strong>Tuhimunatanga</strong>
				<small>Private by design. Encrypted in your browser.</small>
			</span>
		</a>
		<div class="pane-whakapono" aria-label="Privacy features">
			<span class="tohu-whakapono">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.8 2.8 8.1 7 10 4.2-1.9 7-5.2 7-10V6l-7-3Z"></path><path d="m9 12 2 2 4-5"></path></svg>
				Client-side encryption
			</span>
			<span class="tohu-whakapono">
				<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="10" width="12" height="10" rx="2"></rect><path d="M9 10V7a3 3 0 0 1 6 0v3"></path></svg>
				Ciphertext storage
			</span>
		</div>
	</div>
</header>

<main
	id="tmt-v3"
	class="takai"
	data-mode="v3"
	data-api-url="api.php"
	data-wordlist-url="kupu.php"
	data-csrf="<?= puta( $Tuhimunatanga?->csrf ?? '' ) ?>"
>
	<section class="wharangi-upoko">
		<div>
			<p class="kupu-runga">Tuhimunatanga v1.0</p>
			<h1>Create a secure paste</h1>
			<p>Encrypt formatted content in this browser. The server receives an authenticated encrypted envelope, never the plaintext or passphrase during normal operation.</p>
		</div>
		<a class="hononga-awhina" href="#me-pehea">
			<span aria-hidden="true">ⓘ</span>
			How it works
		</a>
	</section>

	<?php if ( $karere_whirihora !== '' ): ?>
		<section class="kaari karere hapa" role="alert">
			<h2>Configuration error</h2>
			<p><?= puta( $karere_whirihora ) ?></p>
			<p>Edit <code>raraunga/whiringa_whakapiri.ini</code>, or configure the v3 environment variables described in <code>README.md</code>.</p>
		</section>
	<?php elseif ( $Tuhimunatanga instanceof Tuhimunatanga ): ?>
		<section id="me-pehea" class="whakataki" aria-label="How Tuhimunatanga protects a paste">
			<div class="whakataki-take">
				<span class="whakataki-tohu" aria-hidden="true">1</span>
				<div>
					<strong>Encrypt locally</strong>
					<span>AES-256-GCM encryption occurs in this browser.</span>
				</div>
			</div>
			<div class="whakataki-take">
				<span class="whakataki-tohu" aria-hidden="true">2</span>
				<div>
					<strong>Store ciphertext</strong>
					<span>PHP stores the envelope, expiry rule and lookup metadata.</span>
				</div>
			</div>
			<div class="whakataki-take">
				<span class="whakataki-tohu" aria-hidden="true">3</span>
				<div>
					<strong>Decrypt locally</strong>
					<span>The recipient enters the Māori Diceware passphrase in their browser.</span>
				</div>
			</div>
		</section>

		<section id="karere-v3" class="kaari karere hapa" role="status" hidden></section>

		<section id="hanga-v3" class="hanga-matapihi">
			<form id="puka-v3" autocomplete="off">
				<noscript>
					<p class="whakatupato">JavaScript is required because v3 encryption occurs in the browser.</p>
				</noscript>

				<section class="kaari kupuhipa-whakarite" aria-labelledby="taitara-kupuhipa-hanga-v3">
					<div class="pane-kupuhipa">
						<div class="upoko-kaari">
							<span class="upoko-kaari-tohu" aria-hidden="true">
								<svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.8 2.8 8.1 7 10 4.2-1.9 7-5.2 7-10V6l-7-3Z"></path><path d="M9 11h6v5H9z"></path><path d="M10 11V9a2 2 0 0 1 4 0v2"></path></svg>
							</span>
							<div>
								<h2 id="taitara-kupuhipa-hanga-v3">Your encryption passphrase</h2>
								<p>Generated in this browser. Save this passphrase now and keep it separate from the secure link.</p>
							</div>
						</div>
						<button
							id="patene-hou-kupuhipa-v3"
							type="button"
							class="patene-tuarua patene-iti patene-hou"
							data-kupu-tuuturu="Generate another"
						>Generate another</button>
					</div>

					<div class="kupuhipa-kaha" aria-labelledby="taitara-kaha-kupuhipa-v3">
						<div class="kupuhipa-whiriwhiri">
							<label id="taitara-kaha-kupuhipa-v3" for="kupu_katoa_v3">Passphrase length</label>
							<select id="kupu_katoa_v3">
							<option value="7" selected>7 words</option>
								<option value="8">8 words</option>
								<option value="9">9 words</option>
								<option value="10">10 words</option>
								<option value="11">11 words</option>
								<option value="12">12 words</option>
								<option value="13">13 words</option>
								<option value="14">14 words</option>
								<option value="15">15 words</option>
								<option value="16">16 words</option>
								<option value="17">17 words</option>
								<option value="18">18 words</option>
								<option value="19">19 words</option>
								<option value="20">20 words</option>
								<option value="21">21 words</option>
							<option value="22">22 words</option>
							</select>
							<p>Choose 22 words for the post-quantum strength target. Shorter passphrases are faster but do not resist a large-scale quantum-computer attack.</p>
						</div>
						<div class="kupuhipa-kaha-whakarāpopoto">
						<span id="tohu-kaha-v3" class="tohu-kaha" data-kaha="iti">Not quantum-resistant at this length</span>
							<strong id="tatauranga-kaha-v3">Calculating strength…</strong>
						</div>
						<div id="pangarau-kaha-v3" class="kupuhipa-pangarau" aria-live="polite">
							<span id="pangarau-ideal-v3"></span>
							<span id="pangarau-iti-v3"></span>
							<span id="pangarau-kii-v3"></span>
							<span id="pangarau-quantum-v3"></span>
						</div>
						<p class="kupuhipa-kaha-kupu">The quantum figure uses a simplified square-root search model and is a strength target, not a guarantee against a compromised browser or server-supplied JavaScript.</p>
					</div>

					<div
						id="kupuhipa-whakaatu-v3"
						class="kupuhipa-kari"
						aria-live="polite"
						aria-busy="true"
					>
						<span class="kupuhipa-utaina">Generating the passphrase…</span>
					</div>
					<textarea
						id="kupuhipa_hanga_v3"
						class="huna"
						readonly
						spellcheck="false"
						aria-label="Generated encryption passphrase"
					></textarea>
					<textarea
						id="kupuhipa_waehere_hanga_v3"
						class="huna"
						readonly
						spellcheck="false"
						aria-label="Generated passphrase with Diceware codes"
					></textarea>
					<div class="kupuhipa-raro">
						<div class="raina-patene kupuhipa-patene">
							<button
								type="button"
								class="patene-matua"
								data-copy-target="kupuhipa_hanga_v3"
								data-copy-feedback-target="kupuhipa-whakaatu-v3"
								data-kupu-tuuturu="Copy passphrase"
								disabled
							>Copy passphrase</button>
							<button
								type="button"
								class="patene-tuarua"
								data-copy-target="kupuhipa_waehere_hanga_v3"
								data-copy-feedback-target="kupuhipa-whakaatu-v3"
								data-kupu-tuuturu="Copy passphrase + codes"
								disabled
							>Copy passphrase + codes</button>
						</div>
						<div class="kupuhipa-whakamaumahara">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.8 2.8 8.1 7 10 4.2-1.9 7-5.2 7-10V6l-7-3Z"></path><path d="M12 8v5"></path><circle cx="12" cy="16" r=".7"></circle></svg>
							<p><strong>Store this passphrase safely.</strong><span>Without it, the encrypted content cannot be recovered.</span></p>
						</div>
					</div>
				</section>

				<section class="kaari etita-kaari" aria-labelledby="tapanga-haatepe">
					<div class="upoko-kaari etita-upoko">
						<span class="upoko-kaari-tohu" aria-hidden="true">
							<svg viewBox="0 0 24 24"><path d="M5 4h10l4 4v12H5z"></path><path d="M15 4v5h4M8 13h8M8 16h8M8 10h4"></path></svg>
						</span>
						<div>
							<h2 id="tapanga-haatepe">Content to encrypt</h2>
							<p>Paste formatted text from Word or another document. Tables, colours and supported formatting are retained.</p>
						</div>
					</div>
					<div
						id="haatepe_etita"
						class="etita-whaihanga"
						contenteditable="true"
						role="textbox"
						aria-labelledby="tapanga-haatepe"
						aria-multiline="true"
						data-placeholder="Paste formatted text from Word or another document here"
						spellcheck="true"
					></div>
				</section>

				<section class="kaari tautuhinga-kaari">
					<div class="tautuhinga-matapihi">
						<div class="tautuhinga-āpure">
							<label for="paunga_v3">Expiry rule</label>
							<select id="paunga_v3" required>
								<option value="" disabled selected>Choose an expiry rule</option>
								<option value="burn">Delete from the server after the first successful browser decryption that reports completion</option>
								<option value="600">10 minutes</option>
								<option value="3600">1 hour</option>
								<option value="86400">1 day</option>
								<option value="604800">1 week</option>
								<option value="2635200">1 month</option>
								<option value="31557600">1 year</option>
								<option value="never">Never expire automatically</option>
							</select>
						</div>
						<div class="tautuhinga-whakamārama">
							<strong>Before you encrypt</strong>
							<span>Save the passphrase separately. The secure link alone cannot decrypt the paste.</span>
						</div>
						<button id="patene-hanga-v3" class="patene-whakamuna" type="submit" data-kupu-tuuturu="Encrypt in browser">Encrypt in browser</button>
					</div>
				</section>
			</form>
		</section>

		<section id="hua-hanga-v3" class="hua-matapihi" hidden>
			<section class="kaari hua-hononga">
				<div class="upoko-kaari">
					<span class="upoko-kaari-tohu" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M9.5 14.5 14.5 9"></path><path d="M7.5 17.5 5 20a4.2 4.2 0 0 1-6-6l4-4a4.2 4.2 0 0 1 6 0"></path><path d="m16.5 6.5 2.5-2.5a4.2 4.2 0 0 1 6 6l-4 4a4.2 4.2 0 0 1-6 0"></path></svg>
					</span>
					<div>
						<h2>Your secure link</h2>
						<p>Share this link with someone you trust.</p>
					</div>
				</div>
				<label class="tapanga-iti" for="papahono_huri_v3">Secure link</label>
				<div class="raina-taarua">
					<input id="papahono_huri_v3" type="text" readonly>
					<button type="button" class="patene-tuarua" data-copy-target="papahono_huri_v3" data-copy-feedback-target="papahono_huri_v3" data-kupu-tuuturu="Copy link">Copy link</button>
				</div>
				<label class="tapanga-iti" for="kupuhipa_v3">Passphrase</label>
				<textarea id="kupuhipa_v3" class="whangaonokupu" readonly rows="4" spellcheck="false"></textarea>
				<p id="kupuhipa_kaha_hua_v3" class="kupuhipa-kaha-hua"></p>
				<div class="raina-patene">
					<button type="button" class="patene-tuarua" data-copy-target="kupuhipa_v3" data-copy-feedback-target="kupuhipa_v3" data-kupu-tuuturu="Copy passphrase">Copy passphrase</button>
				</div>
				<p class="whakatupato">Do not send the link and passphrase through the same channel.</p>
				<p id="taipitopito_hua_v3" class="iti"></p>
			</section>
			<aside class="kaari hua-tūmataiti">
				<div class="tūmataiti-taitara">
					<span aria-hidden="true">✓</span>
					<h2>Your privacy is protected</h2>
				</div>
				<ul>
					<li><span aria-hidden="true">⌁</span>Encryption happens in your browser.</li>
					<li><span aria-hidden="true">◇</span>Plaintext and the passphrase are not submitted to PHP.</li>
					<li><span aria-hidden="true">▣</span>The server stores ciphertext and expiry metadata.</li>
				</ul>
			</aside>
		</section>

		<section id="wetemuna-v3" class="kaari wetemuna-kaari" hidden>
			<div class="upoko-kaari">
				<span class="upoko-kaari-tohu" aria-hidden="true">
					<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
				</span>
				<div>
					<h2>Decrypt this v3 paste</h2>
					<p id="taipitopito-wetemuna-v3">Enter the passphrase supplied separately from the link.</p>
				</div>
			</div>
			<form id="puka-wetemuna-v3" autocomplete="off">
				<label for="ipu_kupuhika_v3">Passphrase</label>
				<textarea
					id="ipu_kupuhika_v3"
					class="Kupuhipa"
					rows="5"
					maxlength="2048"
					spellcheck="false"
					required
				></textarea>
				<button id="patene-wetemuna-v3" class="patene-whakamuna" type="submit" data-kupu-tuuturu="Decrypt in browser">Decrypt in browser</button>
			</form>
		</section>

		<section id="hua-wetemuna-v3" class="kaari hua-wetemuna" hidden>
			<div class="upoko-kaari">
				<span class="upoko-kaari-tohu" aria-hidden="true">✓</span>
				<div>
					<h2>Decrypted content</h2>
					<p>The content was decrypted locally in this browser.</p>
				</div>
			</div>
			<iframe
				id="anga-wetemuna-v3"
				class="puta-whaihanga"
				title="Decrypted formatted paste"
				sandbox
				referrerpolicy="no-referrer"
			></iframe>
			<div class="raina-patene">
				<button
					type="button"
					class="patene-tuarua"
					data-copy-html-target="karere_wetemunahia_html_v3"
					data-copy-text-target="karere_wetemunahia_v3"
					data-copy-feedback-target="anga-wetemuna-v3"
					data-kupu-tuuturu="Copy formatted content"
				>Copy formatted content</button>
			</div>
			<textarea id="karere_wetemunahia_v3" class="huna" readonly></textarea>
			<textarea id="karere_wetemunahia_html_v3" class="huna" readonly></textarea>
		</section>
	<?php endif; ?>
</main>

<footer>
	<div class="takai waewae">
		<p><strong>Tuhimunatanga v1.0</strong></p>
		<p>Standalone browser-encrypted paste service.</p>
	</div>
</footer>
</body>
</html>
