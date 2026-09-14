# Māori Diceware wordlist validation report

## Report scope

This report records the validation state of `raraunga/7776_kupu.db` supplied with Tuhimunatanga v1.0.

The application treats the Diceware code and displayed word as separate values. Macrons and other valid Unicode letters are preserved. Words are normalised to Unicode NFC by PHP when the optional `intl` extension is available, and by JavaScript when passphrases are generated.

## File identity

| Property | Result |
| --- | --- |
| File | `raraunga/7776_kupu.db` |
| SHA-256 | `9879bdd5505fdab8fb5e0c974acc7b0fae753652a723e191efd8121cefbd626e` |
| Checksum source | `raraunga/7776_kupu.sha256` |
| Parsed entries | 7,776 |
| Unique Diceware codes | 7,776 |
| Code format | Five digits, each from 1 to 6 |
| Distinct displayed values | 7,774 |
| Maximum displayed-value multiplicity | 2 |
| Values not already in Unicode NFC | 0 |

The wordlist has no duplicate Diceware codes. Two displayed values occur twice:

| Displayed value | Diceware codes |
| --- | --- |
| `peretu` | `43511`, `43512` |
| `pirangi` | `44262`, `44265` |

These duplicate displayed values matter because a recipient normally types the words rather than the Diceware codes. The entropy display therefore uses maximum multiplicity two as a conservative bound.

## Curator-review entries

The automated PHP wordlist test reports, without rejecting the file, entries that are shorter than two characters, longer than 13 Unicode code points, or contain anything other than Unicode letters. Two supplied entries meet those review conditions:

| Line | Code | Current value | Reason |
| ---: | --- | --- | --- |
| 4,912 | `45534` | `pupuawaiawaitanga` | 17 Unicode code points, above the current 13-character review threshold |
| 6,799 | `62361` | `tuanui tuani / tuaniwha` | Contains spaces and a slash, and is above the review-length threshold |

This report does not silently change either value. Any correction must be made by the wordlist curator, assigned a deliberate canonical spelling, reviewed for code stability, and followed by a checksum update and compatibility decision.

## Entropy method

For `n` generated words:

```text
Ideal Diceware entropy:
H_ideal(n) = n × log2(7776)

Conservative displayed-word entropy:
H_conservative(n) = n × log2(7776 / 2)

Effective symmetric-key ceiling:
H_effective(n) = min(H_conservative(n), 256)

Simplified quantum-search estimate:
H_quantum(n) = H_effective(n) / 2
```

The per-word values are approximately 12.9248 ideal bits and 11.9248 conservative bits.

| Words | Ideal bits | Conservative bits | Effective AES-256 ceiling | Simplified quantum-search estimate |
| ---: | ---: | ---: | ---: | ---: |
| 7 | 90.47 | 83.47 | 83.47 | 41.74 |
| 8 | 103.40 | 95.40 | 95.40 | 47.70 |
| 12 | 155.10 | 143.10 | 143.10 | 71.55 |
| 20 | 258.50 | 238.50 | 238.50 | 119.25 |
| 22 | 284.35 | 262.35 | 256.00 | 128.00 |

The 22-word option is the first supported option that exceeds 256 conservative bits under this duplicate-aware calculation. The interface labels it as reaching the configured post-quantum target.

That label is limited. Dividing symmetric strength by two is a simplified square-root-search model. It does not account for compromised endpoints, hostile JavaScript, implementation faults, password reuse, word substitution, side channels, future cryptanalysis or the complete cost model of a quantum attack against PBKDF2 and AES-GCM.

## Runtime validation controls

`kupu.php` refuses to serve the wordlist unless:

- The wordlist is present and readable.
- Its SHA-256 value matches `raraunga/7776_kupu.sha256`.
- Every non-empty row contains a five-die code and a non-empty UTF-8 value.
- Every Diceware code is unique.
- Exactly 7,776 entries and 7,776 unique codes are present.

The endpoint returns the entry list and strength metadata, including distinct displayed values and maximum multiplicity. The browser rejects unexpected wordlist size, invalid strength metadata and passphrase lengths outside 7 to 22 words.

## Test coverage

- `tests/wordlist_test.php` checks entry syntax, code uniqueness, Unicode validity, curator-review conditions and the 22-word target.
- `tests/entropy_test.mjs` locks the 7-word and 22-word calculations and the 256-bit effective ceiling.
- `tests/crypto_roundtrip.mjs` loads the wordlist and confirms generation at the supported 7-word and 22-word boundaries while rejecting 6 and 23.
- `tests/interface_test.mjs` confirms the selector, equations, Diceware codes, copied-state feedback and 22-word default remain present.

## Validation conclusion

The supplied file has the required 7,776 unique Diceware codes and matches its committed SHA-256 checksum. It contains 7,774 distinct displayed values, two duplicated displayed words and two entries awaiting curator review. The conservative entropy calculation accounts for the maximum duplicate multiplicity and reaches the configured 256-bit effective ceiling at 22 generated words.
