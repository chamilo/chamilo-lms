Google Authenticator
====================

[![Build Status](https://secure.travis-ci.org/sonata-project/GoogleAuthenticator.png)](https://secure.travis-ci.org/#!/sonata-project/GoogleAuthenticator)

Ported from http://code.google.com/p/google-authenticator/

You can use the Google Authenticator app from here
http://www.google.com/support/accounts/bin/answer.py?hl=en&answer=1066447
to generate One Time Passwords/Tokens and check them with this little
PHP app (Of course, you can also create them with this).

### Installation using Composer

Add the dependency:

```bash
php composer.phar require sonata-project/google-authenticator
```

If asked for a version, type in 'dev-master' (unless you want another version):

```bash
Please provide a version constraint for the sonata-project/google-authenticator requirement: dev-master
```

## Usage

See example.php for how to use it.

There's a little web app showing how it works in web/, please make users.dat
writeable for the webserver, doesn't really work otherwise (it can't save the
secret). Try to login with chregu/foobar.

What's missing in the example:

 * Prevent replay attacks. One token should only be used once
 * Show QR Code only when providing password again (or not at all)
 * Regenerate secret


### Google Groups

For questions and proposals you can post on this google groups

* [Sonata Users](https://groups.google.com/group/sonata-users): Only for user questions
* [Sonata Devs](https://groups.google.com/group/sonata-devs): Only for devs
