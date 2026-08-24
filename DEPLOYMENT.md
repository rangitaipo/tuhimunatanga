# Tuhimunatanga v3.1.2 deployment guide

## Deployment position

Deploy Tuhimunatanga as a dedicated HTTPS application. Treat the PHP and JavaScript as security-sensitive code. A release should be reviewed, tested and promoted unchanged rather than edited directly on the production server.

If a database password or application key has entered Git, a shared archive, a ticket, chat, email or logs, rotate it before deployment. Application-key rotation makes all records created under the earlier key unreachable.

## 1. Prepare the host

Install:

- PHP 8.1 or newer.
- PHP-FPM or an equivalent supported PHP handler.
- PHP PDO and `pdo_mysql`.
- A MySQL-compatible database with InnoDB.
- Apache with the required override modules, or Nginx.
- A valid TLS certificate for the public origin.
- Node.js on a build or test host, not necessarily production.

Keep the database and PHP patched through the operating system's supported update channel.

## 2. Create the database

Create a database using an administrative account, then import [schema.sql](schema.sql):

```sh
mysql -u DATABASE_ADMIN -p DATABASE_NAME < schema.sql
```

Create a separate application account limited to the required operations. Adapt the host and identifiers to the deployment:

```sql
GRANT SELECT, INSERT, DELETE
ON `DATABASE_NAME`.*
TO 'APPLICATION_USER'@'APPLICATION_HOST';
```

Do not give the application account `CREATE`, `ALTER`, `DROP`, `FILE`, `PROCESS`, `SUPER` or user-administration privileges.

## 3. Install application files

Place the repository in its final document-root location. Recommended permissions are:

- Directories: `0755`.
- Public code and assets: `0644`.
- Secret configuration: `0600`, or the narrowest permissions that still allow the PHP service account to read it.
- Application tree owned by a deployment account, not writable by the web-server process.

The application does not require upload or cache directories. The web-server account should not have general write access to the project tree.

## 4. Configure secrets

### Preferred approach

Store secrets outside the document root and set:

```text
TUHIMUNATANGA_V3_CONFIG=/absolute/private/path/tuhimunatanga.ini
```

Start from `raraunga/whiringa_whakapiri.ini.example`, or inject every setting through the environment variables listed in [README.md](README.md).

PHP-FPM commonly clears environment variables. If environment injection is used, explicitly pass the `TUHIMUNATANGA_V3_*` values through the pool or service configuration and confirm them with `bin/diagnose.php`.

### Local fallback

```sh
cp raraunga/whiringa_whakapiri.ini.example raraunga/whiringa_whakapiri.ini
chmod 600 raraunga/whiringa_whakapiri.ini
```

The real file is ignored by Git. Confirm it is absent from `git status` and from any release source archive.

### Application key

Generate a unique key for this deployment:

```sh
php bin/hanga_kii.php
```

The output is 32 random bytes encoded as 64 hexadecimal characters. Store it in `TUHIMUNATANGA_V3_APP_KEY` or `kii_taupaanga`.

Back up the key in a controlled secret store. Loss or rotation of the key makes existing paste rows unreachable because the server stores only HMAC-derived lookup identifiers.

### Debug mode

Set `TUHIMUNATANGA_V3_DEBUG=0` or `patuiro = "0"` in production. Debug responses can disclose exception details and source locations.

## 5. Configure HTTPS and the web server

### Apache

The supplied `.htaccess` requires the server configuration to permit its directives. Confirm that:

- Directory indexes are disabled.
- `mod_rewrite`, `mod_headers` and the appropriate authorisation module are available.
- Requests are redirected to HTTPS.
- Direct access to `raraunga`, `bin` and `tests` is denied.
- Dotfiles, INI, database, checksum, SQL, Markdown, lock and log files cannot be downloaded.

Test denial rules rather than assuming `AllowOverride` is active.

### Nginx

Nginx ignores `.htaccess`. Copy and adapt the rules in `nginx.conf.example` inside the relevant server block. Replace all occurrences of `/tuhimunatanga-v3-standalone/` with the real URL prefix, set the correct document root, and select the installed PHP-FPM socket.

Test that all of these return 403 or 404:

```text
/raraunga/whiringa_whakapiri.ini.example
/raraunga/7776_kupu.db
/bin/diagnose.php
/tests/ui_preview.html
/schema.sql
/README.md
/.gitignore
```

### Reverse proxies

`Tuhimunatanga::he_https()` recognises PHP's `HTTPS` value or server port 443. It does not directly trust `X-Forwarded-Proto`. When TLS terminates at a reverse proxy, configure the trusted proxy and PHP-FPM hand-off so PHP receives `HTTPS=on` or an equivalent port-443 indication.

Without this, the application may omit the Secure cookie flag, HSTS and CSP upgrade instruction even if the public connection uses HTTPS.

The code emits HSTS with `includeSubDomains` for recognised HTTPS requests. Confirm that every affected subdomain is HTTPS-capable before using this host under a shared parent domain.

## 6. Set PHP limits

The included `.user.ini` sets:

- `post_max_size = 6M`
- `upload_max_filesize = 6M`
- `memory_limit = 128M`
- strict, cookie-only PHP sessions
- HttpOnly and SameSite Strict session cookies

The application limits the plaintext JSON payload to 1 MiB and the submitted encrypted envelope to 4,500,000 characters. Confirm that PHP-FPM, the web server, reverse proxy and any web application firewall allow valid requests while enforcing a modest upper bound.

## 7. Run pre-deployment tests

From the release root:

```sh
node tests/entropy_test.mjs
node tests/crypto_roundtrip.mjs
node tests/interface_test.mjs
php tests/file_set_test.php
php tests/php_profile_test.php
php tests/wordlist_test.php
php bin/diagnose.php
```

Do not promote the release if a required test or diagnostic fails.

Also perform a browser test over the final HTTPS origin:

1. Generate a 7-word and 22-word passphrase.
2. Confirm the Diceware codes and copied-state feedback.
3. Paste formatted text containing headings, colours and a table.
4. Create, retrieve and decrypt a timed paste.
5. Confirm that an incorrect passphrase fails.
6. Confirm that changing the link token fails authentication.
7. Create and decrypt a one-time paste, then confirm a second retrieval fails.
8. Confirm expired content is removed.
9. Inspect response headers and session-cookie attributes.
10. Confirm every private path listed above is inaccessible.

## 8. Schedule cleanup

Run `bin/cleanup.php` at a fixed interval. Example:

```cron
*/15 * * * * cd /absolute/path/to/tuhimunatanga && /usr/bin/php bin/cleanup.php >> /var/log/tuhimunatanga-cleanup.log 2>&1
```

The script removes expired paste rows and rate-limit rows older than 24 hours. Store the log outside the web root, restrict its permissions and rotate it.

Opportunistic cleanup also runs after approximately 5 percent of successful creation requests. It is supplementary and is not a replacement for the scheduled job.

## 9. Logging and monitoring

- Never log request bodies for `api.php`.
- Avoid query logging that records bound values.
- Restrict access to PHP, database, proxy and web-server logs.
- Monitor repeated HTTP 429, 403, 409 and 500 responses.
- Alert on changes to PHP, JavaScript, CSS, `.htaccess`, the wordlist and its checksum.
- Monitor cleanup failures and database capacity.
- Retain logs only for a defined operational period.

The URL fragment is not present in the initial HTTP request. The JavaScript later posts the token to the API, so request-body logging would defeat that privacy advantage.

## 10. Backups and recovery

Back up the database and application key under separate controls. Database backups contain ciphertext and metadata. The application key is required to find records by token, while passphrases are still required to decrypt content.

Define how expiry and deletion obligations apply to backups and replicas. Removing a live row does not erase a historical snapshot.

Test recovery in an isolated environment. A recovery test must not expose production secrets or make old one-time pastes publicly reachable.

## 11. Release checklist

- [ ] All automated tests pass.
- [ ] `php bin/diagnose.php` passes against production configuration.
- [ ] A new deployment-specific application key is installed.
- [ ] Database credentials are least-privileged and absent from Git.
- [ ] Debug mode is disabled.
- [ ] HTTPS recognition works through every proxy layer.
- [ ] Private directories and file types are denied.
- [ ] PHP and proxy request-size limits are compatible.
- [ ] POST bodies and bound SQL values are not logged.
- [ ] Cleanup is scheduled and monitored.
- [ ] Backup retention and deletion behaviour are documented.
- [ ] Browser creation, retrieval, expiry and one-time flows pass.
- [ ] Response headers, CSP, HSTS and session-cookie flags are verified.
- [ ] The exact tested release is the release deployed.
