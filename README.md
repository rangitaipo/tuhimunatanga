# Tuhimunatanga

Tuhimunatanga v1.0 is a standalone browser-encrypted paste service. It accepts formatted text, sanitises it, encrypts it in the user's browser, and stores only an authenticated encrypted envelope and operational metadata on the server.

The repository contains plain PHP, JavaScript and CSS. It has no Composer or npm runtime dependencies.

## Security boundary

Client-side encryption protects stored content from a database-only compromise. It does not protect a user from a compromised browser, malicious extension, keylogger, or a server that has been altered to deliver hostile JavaScript. Read [THREAT_MODEL.md](THREAT_MODEL.md) before deploying or representing the service as secure.

## Main features

- AES-256-GCM encryption and decryption through the browser Web Crypto API.
- PBKDF2-SHA-256 with 600,000 iterations and a fresh 16-byte salt.
- Fresh 12-byte AES-GCM IVs and 128-bit authentication tags.
- New 22-character base-62 link tokens, with retrieval compatibility for legacy 15-character tokens.
- Māori Diceware passphrases selectable from 7 to 22 words; the default is 7 words. Passphrases shorter than 22 words do not resist a large-scale quantum-computer attack.
- Diceware codes displayed beside their corresponding words.
- Conservative entropy calculations based on duplicate displayed values.
- Formatted text, tables, colours and a restricted set of inline styles.
- HTML sanitisation before encryption and after decryption.
- Sandboxed rendering of decrypted formatted content.
- Expiry choices from 10 minutes to one year, plus one-time deletion and no automatic expiry.
- HMAC-derived database lookup keys and rate-limit identifiers.
- No plaintext or passphrase submission during normal operation.

## Requirements

- PHP 8.1 or newer.
- PHP PDO and `pdo_mysql`.
- A MySQL-compatible database using InnoDB.
- HTTPS.
- A current browser with JavaScript, Web Crypto, `TextEncoder`, `TextDecoder` and Unicode normalisation.
- Node.js for the JavaScript test suite.
- The PHP `intl` extension is optional. When available, the wordlist endpoint normalises words to Unicode NFC.

## Installation

### 1. Create the database tables

Create an empty database, select it, and import [schema.sql](schema.sql). Use a separate administrative database account for the import.

```sh
mysql -u DATABASE_ADMIN -p DATABASE_NAME < schema.sql
```

The application account needs `SELECT`, `INSERT` and `DELETE` on `nga_taaurunga_v3` and `nga_ngana_v3`. It does not need permission to create or alter tables during normal operation.

### 2. Configure the application

Environment variables take precedence over values in the INI file.

| Variable | Purpose | Default |
| --- | --- | --- |
| `TUHIMUNATANGA_V3_CONFIG` | Absolute path to an alternative INI file | `raraunga/whiringa_whakapiri.ini` |
| `TUHIMUNATANGA_V3_DB_HOST` | Database host | `127.0.0.1` |
| `TUHIMUNATANGA_V3_DB_PORT` | Database port | `3306` |
| `TUHIMUNATANGA_V3_DB_NAME` | Database name | Required |
| `TUHIMUNATANGA_V3_DB_USER` | Application database user | Required |
| `TUHIMUNATANGA_V3_DB_PASSWORD` | Application database password | No safe production default |
| `TUHIMUNATANGA_V3_APP_KEY` | 32 random bytes encoded as 64 hexadecimal characters or unpadded base64url | Required |
| `TUHIMUNATANGA_V3_BASE_URL` | Public application URL used when links are generated | Derived from the current script when empty |
| `TUHIMUNATANGA_V3_TIMEZONE` | PHP timezone | `Pacific/Auckland` |
| `TUHIMUNATANGA_V3_DEBUG` | Include internal error details in API responses | `0` |

Generate a new application key from the command line:

```sh
php bin/hanga_kii.php
```

The preferred production arrangement is to inject environment variables or point `TUHIMUNATANGA_V3_CONFIG` to a secret file outside the web root. The local fallback is:

```sh
cp raraunga/whiringa_whakapiri.ini.example raraunga/whiringa_whakapiri.ini
chmod 600 raraunga/whiringa_whakapiri.ini
```

Replace every `CHANGE_ME` value. The live INI file is ignored by Git and must remain untracked.

The application key is used to derive database lookup and rate-limit hashes. Rotating it makes existing stored pastes unreachable because their raw link tokens are not stored. Plan key rotation as an invalidation of all existing pastes.

### 3. Configure the web server

For Apache, serve the directory with `AllowOverride` configured so [.htaccess](.htaccess) is honoured. The rules force HTTPS, disable directory indexes, deny direct access to private directories and file types, and add browser security headers.

For Nginx, adapt [nginx.conf.example](nginx.conf.example) to the actual URL prefix, document root and PHP-FPM socket. Nginx does not read `.htaccess`.

Do not deploy over HTTP. Web Crypto encryption is rejected outside a secure browser context.

### 4. Diagnose the installation

Run the CLI-only diagnostic from the project root:

```sh
php bin/diagnose.php
```

It checks the PHP version, required extensions, configuration, database connection, table presence, database read access, wordlist readability and the wordlist checksum. It must not be exposed as a web endpoint.

### 5. Schedule cleanup

Run the cleanup script regularly, for example every 15 minutes:

```cron
*/15 * * * * cd /absolute/path/to/tuhimunatanga && /usr/bin/php bin/cleanup.php >> /var/log/tuhimunatanga-cleanup.log 2>&1
```

The script deletes expired pastes and rate-limit records older than 24 hours. Keep its log outside the web root and configure log rotation.

## Tests

Run from the repository root:

```sh
node tests/entropy_test.mjs
node tests/crypto_roundtrip.mjs
node tests/interface_test.mjs
php tests/file_set_test.php
php tests/php_profile_test.php
php tests/wordlist_test.php
```

`tests/ui_preview.html` is a static manual interface preview. It is denied by the supplied web-server rules and is not part of the production interface.

## Passphrase strength

The wordlist contains 7,776 unique Diceware codes, 7,774 distinct displayed values, and a maximum displayed-value multiplicity of two. The interface therefore presents both ideal entropy and a conservative bound:

```text
ideal bits       = words × log2(7776)
conservative bits = words × log2(7776 / 2)
effective bits   = min(conservative bits, 256)
quantum estimate = effective bits / 2
```

At 22 words the conservative result is 262.35 bits, capped at the AES-256 ceiling of 256 bits. The displayed 128-bit quantum figure is a simplified square-root-search estimate, not a complete claim of post-quantum security. See [WORDLIST_VALIDATION_REPORT.md](WORDLIST_VALIDATION_REPORT.md).

## Repository map

| Path | Purpose |
| --- | --- |
| `index.php` | Main interface |
| `api.php` | Create, retrieve and delete API entry point |
| `tuhimunatanga.php` | Server controller, validation, sessions, headers and rate limiting |
| `whiringa.php` | Configuration loader |
| `crypto.js` | Browser cryptography and passphrase generation |
| `hootuhihawa.js` | Interface controller and rich-HTML sanitiser |
| `kupu.php` | Validated wordlist endpoint |
| `raraunga/raraunga.php` | PDO database layer |
| `raraunga/7776_kupu.db` | Māori Diceware wordlist |
| `raraunga/7776_kupu.sha256` | Required wordlist checksum |
| `schema.sql` | Database schema |
| `bin/` | CLI key generation, diagnostics and cleanup |
| `tests/` | Static, cryptographic, compatibility and wordlist tests |

## Operational cautions

- Keep the link and passphrase in separate channels.
- Do not enable debug responses in production.
- Do not log POST bodies. They contain link tokens, encrypted envelopes and one-time deletion material.
- Database backups contain ciphertext and metadata. Expiry and one-time deletion cannot remove copies retained in old backups.
- One-time deletion occurs only after the browser successfully decrypts and reports completion. It is not an atomic read-and-delete operation.
- No licence file is included. Decide and document the licence before accepting outside contributions or representing reuse terms.

Further deployment detail is in [DEPLOYMENT.md](DEPLOYMENT.md).
