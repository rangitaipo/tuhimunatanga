# Tuhimunatanga v1.0 threat model

## Scope

This threat model describes the supplied standalone application. It covers browser-side encryption, PHP endpoints, the MySQL-compatible database, server configuration and the sharing of secure links and passphrases.

It is an implementation review, not a formal cryptographic proof or an independent penetration test.

## Security objectives

1. Keep paste plaintext and passphrases out of routine server storage and API requests.
2. Preserve the confidentiality and integrity of stored paste content after database-only disclosure.
3. Detect alteration of ciphertext, link-token context and profile-2 expiry policy.
4. Avoid storing raw link tokens or raw client IP addresses in application tables.
5. Restrict active content in pasted and decrypted formatted HTML.
6. Expire stored content and short-lived rate-limit records according to the selected policy.

## Assets

- Paste plaintext and formatting.
- Māori Diceware passphrases.
- Secure-link tokens.
- One-time deletion secrets.
- The application HMAC key.
- Database credentials.
- Encrypted envelopes and their metadata.
- Client IP addresses and operational logs.
- The JavaScript, PHP, CSS, wordlist and checksum distributed by the server.

## Trust boundaries

| Boundary | Trusted for | Not trusted for |
| --- | --- | --- |
| User browser | Web Crypto execution, random generation, local sanitisation and local decryption | Security when the device, browser, extension set or delivered JavaScript is compromised |
| HTTPS connection | Transport confidentiality and integrity to the configured origin | Protection after either endpoint is compromised |
| PHP application | Validation, HMAC lookup, expiry enforcement, deletion and database access | Knowledge of plaintext or passphrases during normal operation |
| Database | Availability of ciphertext and metadata | Confidentiality of stored records after database disclosure |
| Recipient and sharing channels | Keeping the link and passphrase separate and confidential | Protection when both factors are disclosed together |

## Data flow

### Creation

1. The browser downloads the application JavaScript and the checked 7,776-entry wordlist.
2. The browser generates a 7-to-22-word passphrase and a new 22-character base-62 link token with `crypto.getRandomValues()`.
3. Rich HTML is reduced to an allowlist of elements, attributes, protocols and inline CSS properties.
4. The payload is length-prefixed, padded with random bytes to a power-of-two bucket, and limited to 1 MiB before padding.
5. The browser derives an AES-256 key from the normalised passphrase with PBKDF2-SHA-256, 600,000 iterations and a fresh 16-byte salt.
6. The browser encrypts with AES-256-GCM, a fresh 12-byte IV and a 128-bit tag.
7. Profile 2 authenticated additional data binds the envelope format, profile, algorithm, KDF, iteration count, link token, expiry rule and one-time flag.
8. The browser submits the encrypted envelope, link token, expiry choice and any one-time deletion hash to PHP. It does not submit the plaintext or passphrase during normal operation.
9. PHP stores an application-keyed HMAC of the link token, the encrypted envelope, expiry timestamps, one-time flag and deletion hash.

### Retrieval

1. The link token is carried in the URL fragment, so it is not included in the browser's initial HTTP request.
2. Application JavaScript reads the fragment and posts the token to the same-origin API.
3. PHP derives the same HMAC lookup value and returns the matching encrypted envelope and policy metadata.
4. The recipient enters the separately supplied passphrase.
5. The browser derives the key, verifies AES-GCM authentication, validates profile-2 policy binding, sanitises the decrypted HTML again, and renders it in a sandboxed iframe with a restrictive embedded CSP.
6. For a one-time paste, the browser sends the deletion secret only after successful decryption. PHP deletes the record only when the derived deletion hash matches.

## Cryptographic construction

| Component | Implementation |
| --- | --- |
| Content cipher | AES-256-GCM |
| Authentication tag | 128 bits |
| Key derivation | PBKDF2-SHA-256, 600,000 iterations |
| Salt | 16 random bytes per encryption |
| IV | 12 random bytes per encryption |
| New link token | 22 random base-62 characters, approximately 131 bits before implementation and ecosystem risks |
| Legacy link token | 15 base-62 characters accepted for retrieval only |
| Application key | Exactly 32 random bytes, hexadecimal or unpadded base64url encoded in configuration |
| Lookup identifier | HMAC-SHA-256 over a domain-separated link token, stored as 64 hexadecimal characters |
| Rate-limit identifiers | Domain-separated HMAC-SHA-256 values, stored as 32 binary bytes |
| One-time deletion proof | SHA-256 over the link token, a zero separator and a 32-byte random deletion secret |

Profile 1 envelopes are accepted for legacy retrieval. Profile 1 authenticates the token and cryptographic parameters, without the profile-2 binding of expiry and one-time policy. New creation accepts profile 2 only.

## Server-visible information

During normal operation the server can observe or retain:

- Client IP addresses at the network and web-server layers.
- HMAC-derived rate-limit identifiers for up to approximately 24 hours in the application table.
- Link tokens while they are posted to the API, even though raw tokens are not stored in the application database.
- Ciphertext size, creation time, expiry time and expiry policy.
- Whether a paste is configured for one-time deletion.
- Traffic timing, request frequency and response status.
- Any information written by infrastructure, access, database or error logs.

The server does not receive paste plaintext or the passphrase in the intended unmodified flow.

## Mitigated threats

### Database-only disclosure

The database contains authenticated ciphertext rather than plaintext, and HMAC-derived lookup values rather than raw link tokens. Decryption still depends on the passphrase. Strong randomly generated passphrases materially increase resistance to offline guessing.

### Ciphertext and policy alteration

AES-GCM detects alteration of the ciphertext, IV, salt-derived key context and authenticated additional data. Profile 2 also binds the link token, expiry rule and one-time flag. A changed value causes decryption to fail.

### Stored active HTML

The browser sanitises HTML before encryption and after decryption. Scripts, forms, embedded media, SVG, MathML, frames, images and several other active elements are removed. Links are restricted to relative references, HTTP, HTTPS and mailto. Decrypted content is shown in an iframe with the `sandbox` attribute and an embedded CSP that denies all sources except inline styling.

### Raw identifier storage

The application uses a 32-byte secret application key to derive lookup and rate-limit hashes. A database-only attacker does not obtain raw link tokens or raw IP addresses from those columns.

### Cross-site request submission

State-changing and retrieval API requests require a session CSRF token and same-origin cookies. Session cookies use strict mode, HttpOnly, SameSite Strict and Secure when PHP recognises the request as HTTPS.

### Creation abuse

Creation attempts are limited over a rolling hour using two domain-separated HMAC values derived from the client address. The current thresholds are 12 and 30. Old attempt rows are deleted after 24 hours by scheduled or opportunistic cleanup.

## Residual risks and non-goals

### Compromised server or supply path

The server supplies the JavaScript that performs encryption. A compromised server can deliver modified code that captures future plaintext, passphrases or tokens. Client-side encryption does not remove this trust dependency. Reproducible builds, deployment controls, file-integrity monitoring and independent delivery verification would reduce this risk, and they are not implemented here.

### Compromised endpoint

Malware, hostile browser extensions, keyloggers, clipboard monitors, screen capture and physical access can expose plaintext or passphrases before encryption or after decryption.

### Password guessing

PBKDF2 raises the cost of each guess and does not compensate for a weak, reused or user-edited passphrase. The generated 22-word option is the configured high-strength target. The displayed quantum figure is a simplified model, not a proof that the complete application is post-quantum secure.

### Metadata and traffic analysis

Encryption does not hide IP addresses, timing, selected expiry policy, one-time status or ciphertext size. Power-of-two padding reduces exact content-length leakage and still exposes a size bucket.

### One-time deletion race

One-time mode is not an atomic read-and-delete operation. More than one client can retrieve the ciphertext before the first successful browser decryption reports deletion. If the browser closes, loses connectivity or fails before reporting completion, the server record remains.

### Backups and logs

Deleting a live database row does not delete copies in database backups, replicas, snapshots or improperly configured logs. POST bodies must not be logged because they contain link tokens, encrypted envelopes and deletion material.

### Availability

The design does not prevent denial of service, database deletion, storage exhaustion, network blocking or loss of the application key. The application key is required to find every stored paste. Losing or rotating it makes existing records unreachable.

### Traffic endpoint and DNS compromise

HTTPS depends on correct DNS, certificate issuance and origin configuration. HSTS is emitted for recognised HTTPS requests. A TLS-terminating reverse proxy must make PHP recognise HTTPS so Secure cookies and HSTS operate as intended.

### Browser and cryptographic implementation defects

The design depends on the correctness of browser Web Crypto, random-number generation, JavaScript execution, Unicode normalisation, AES-GCM usage and PBKDF2. The included tests cover known-answer compatibility, round trips and selected tampering cases. They are not a substitute for an independent cryptographic audit.

## Security invariants for future changes

- Never send or log plaintext or passphrases.
- Never place a passphrase in the link or URL fragment.
- Use a fresh random salt and IV for every encryption.
- Use `crypto.getRandomValues()` with rejection sampling for passphrase and token selection.
- Keep new creation on profile 2 or a reviewed successor that authenticates policy metadata.
- Treat changes to algorithms, KDF iterations, AAD or envelope layout as a versioned migration.
- Keep live configuration, application keys and credentials out of Git.
- Preserve both pre-encryption and post-decryption HTML sanitisation.
- Keep decrypted content in a sandboxed rendering context.
- Do not claim atomic one-time access or guaranteed secure deletion.
- Do not claim protection from hostile server-supplied JavaScript.

## Recommended assurance work

1. Independent review of the cryptographic protocol and JavaScript implementation.
2. Browser-based automated tests for sanitisation, CSP, clipboard feedback and one-time deletion races.
3. Integration tests against the supported MySQL or MariaDB version and PHP-FPM configuration.
4. Content Security Policy regression tests at the deployed origin.
5. Secret scanning and dependency-free static analysis in continuous integration.
6. A documented incident response and key-rotation procedure that accounts for invalidating existing pastes.
