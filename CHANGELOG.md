# Changelog

This file records the verified state of the supplied source. Earlier release history was not included in the fileset and cannot be reconstructed reliably.

## 1.0

### Application state

- Browser-side AES-256-GCM encryption and decryption.
- PBKDF2-SHA-256 with 600,000 iterations.
- Encryption profile 2 binds link-token and expiry-policy metadata through authenticated additional data.
- Retrieval compatibility for legacy profile 1 envelopes.
- New 22-character base-62 tokens, with legacy 15-character retrieval support.
- Māori Diceware generation selectable from 7 to 22 words, defaulting to 7. Passphrases shorter than 22 words do not resist a large-scale quantum-computer attack.
- Diceware codes displayed alongside words.
- Ideal, duplicate-aware conservative, AES-ceiling and simplified quantum-search calculations.
- Rich formatted-text input with allowlist sanitisation before encryption and after decryption.
- Sandboxed decrypted-content display and formatted clipboard output.
- Visible copied-state feedback for links, passphrases and formatted content.
- Timed expiry, no automatic expiry and browser-confirmed one-time deletion.
- Rate limits on paste retrieval and one-time deletion endpoints.
- One-time pastes without an explicit expiry are enforced to a maximum age of one year.
- HTTPS detection includes the `X-Forwarded-Proto` header for reverse-proxy deployments.
- Time-based rate-limit record cleanup replaces random sampling.
- Wordlist `If-None-Match` check evaluates the cached ETag before reading the full wordlist file.
- HMAC-derived lookup and rate-limit identifiers.
- Command-line diagnostics, key generation and cleanup utilities.
- Static, known-answer, round-trip, tamper, compatibility, entropy and wordlist tests.

### Repository completion

- Added the database schema required by the PDO layer.
- Added installation, configuration and test instructions.
- Added a deployment and production-verification guide.
- Added an implementation-specific threat model.
- Added a wordlist validation report with duplicate and curator-review findings.
- Removed the live production INI configuration from the GitHub fileset.
- Expanded `.gitignore` to cover secrets, local configuration, logs, dependencies, caches, editor files and release archives.
- Removed the stale whole-fileset checksum manifest. The runtime wordlist checksum remains required and tracked.
- Updated the fileset test to require the safe INI example instead of a live secret-bearing configuration.
