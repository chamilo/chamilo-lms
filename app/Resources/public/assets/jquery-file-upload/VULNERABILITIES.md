# List of fixed vulnerabilities

## Contents

- [Potential vulnerabilities with PHP+ImageMagick](#potential-vulnerabilities-with-phpimagemagick)
- [Remote code execution vulnerability in the PHP component](#remote-code-execution-vulnerability-in-the-php-component)
- [Open redirect vulnerability in the GAE components](#open-redirect-vulnerability-in-the-gae-components)
- [Cross-site scripting vulnerability in the Iframe Transport](#cross-site-scripting-vulnerability-in-the-iframe-transport)

## Potential vulnerabilities with PHP+ImageMagick

> Mitigated: 2018-10-25 (GMT)

The sample [PHP upload handler](server/php/UploadHandler.php) before
[v9.25.1](https://github.com/blueimp/jQuery-File-Upload/releases/tag/v9.25.1)
did not validate file signatures before invoking
[ImageMagick](https://www.imagemagick.org/) (via
[Imagick](https://php.net/manual/en/book.imagick.php)).  
Verifying those
[magic bytes](https://en.wikipedia.org/wiki/List_of_file_signatures) mitigates
potential vulnerabilities when handling input files other than `GIF/JPEG/PNG`.

Please also configure ImageMagick to only enable the coders required for
`GIF/JPEG/PNG` processing, e.g. with the sample
[ImageMagick config](SECURITY.md#imagemagick-config).

**Further information:**

- Commit containing the mitigation:
  [fe44d34](https://github.com/blueimp/jQuery-File-Upload/commit/fe44d34be43be32c6b8d507932f318dababb25dd)
- [ImageTragick](https://imagetragick.com/)
- [CERT Vulnerability Note VU#332928](https://www.kb.cert.org/vuls/id/332928)
- [ImageMagick CVE entries](https://cve.mitre.org/cgi-bin/cvekey.cgi?keyword=imagemagick)

## Remote code execution vulnerability in the PHP component

> Fixed: 2018-10-23 (GMT)

The sample [PHP upload handler](server/php/UploadHandler.php) before
[v9.24.1](https://github.com/blueimp/jQuery-File-Upload/releases/tag/v9.24.1)
allowed to upload all file types by default.  
This opens up a remote code execution vulnerability, unless the server is
configured to not execute (PHP) files in the upload directory
(`server/php/files`).

The provided [.htaccess](server/php/files/.htaccess) file includes instructions
for Apache to disable script execution, however
[.htaccess support](https://httpd.apache.org/docs/current/howto/htaccess.html)
is disabled by default since Apache `v2.3.9` via
[AllowOverride Directive](https://httpd.apache.org/docs/current/mod/core.html#allowoverride).

**You are affected if you:**

1. A) Uploaded jQuery File Upload < `v9.24.1` on a Webserver that executes files
   with `.php` as part of the file extension (e.g. "example.php.png"), e.g.
   Apache with `mod_php` enabled and the following directive (_not a recommended
   configuration_):
   ```ApacheConf
   AddHandler php5-script .php
   ```
   B) Uploaded jQuery File Upload < `v9.22.1` on a Webserver that executes files
   with the file extension `.php`, e.g. Apache with `mod_php` enabled and the
   following directive:
   ```ApacheConf
   <FilesMatch \.php$>
     SetHandler application/x-httpd-php
   </FilesMatch>
   ```
2. Did not actively configure your Webserver to not execute files in the upload
   directory (`server/php/files`).
3. Are running Apache `v2.3.9+` with the default `AllowOverride` Directive set
   to `None` or another Webserver with no `.htaccess` support.

**How to fix it:**

1. Upgrade to the latest version of jQuery File Upload.
2. Configure your Webserver to not execute files in the upload directory, e.g.
   with the [sample Apache configuration](SECURITY.md#apache-config)

**Further information:**

- Commits containing the security fix:
  [aeb47e5](https://github.com/blueimp/jQuery-File-Upload/commit/aeb47e51c67df8a504b7726595576c1c66b5dc2f),
  [ad4aefd](https://github.com/blueimp/jQuery-File-Upload/commit/ad4aefd96e4056deab6fea2690f0d8cf56bb2d7d)
- [Full disclosure post on Hacker News](https://news.ycombinator.com/item?id=18267309).
- [CVE-2018-9206](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2018-9206)
- [OWASP - Unrestricted File Upload](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload)

## Open redirect vulnerability in the GAE components

> Fixed: 2015-06-12 (GMT)

The sample Google App Engine upload handlers before
v[9.10.1](https://github.com/blueimp/jQuery-File-Upload/releases/tag/9.10.1)
accepted any URL as redirect target, making it possible to use the Webserver's
domain for phishing attacks.

**Further information:**

- Commit containing the security fix:
  [f74d2a8](https://github.com/blueimp/jQuery-File-Upload/commit/f74d2a8c3e3b1e8e336678d2899facd5bcdb589f)
- [OWASP - Unvalidated Redirects and Forwards Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html)

## Cross-site scripting vulnerability in the Iframe Transport

> Fixed: 2012-08-09 (GMT)

The [redirect page](cors/result.html) for the
[Iframe Transport](js/jquery.iframe-transport.js) before commit
[4175032](https://github.com/blueimp/jQuery-File-Upload/commit/41750323a464e848856dc4c5c940663498beb74a)
(_fixed in all tagged releases_) allowed executing arbitrary JavaScript in the
context of the Webserver.

**Further information:**

- Commit containing the security fix:
  [4175032](https://github.com/blueimp/jQuery-File-Upload/commit/41750323a464e848856dc4c5c940663498beb74a)
- [OWASP - Cross-site Scripting (XSS)](https://owasp.org/www-community/attacks/xss/)
