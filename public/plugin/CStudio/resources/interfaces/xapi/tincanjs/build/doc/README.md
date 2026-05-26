A JavaScript library for implementing Tin Can API.

[![Build Status](https://travis-ci.org/RusticiSoftware/TinCanJS.png)](https://travis-ci.org/RusticiSoftware/TinCanJS)

For hosted API documentation, basic usage instructions, supported version listing, etc. visit the main project website at:

http://rusticisoftware.github.io/TinCanJS/

For more information about the Tin Can API visit:

http://tincanapi.com/

Browser Usage
-------------

TinCanJS is available via Bower.

The browser environment is well tested and supports two kinds of Cross Origin requests which
is sufficient to cover most versions of Chrome, FireFox, Safari as well as IE 8+. IE 6+ are
supported for non-CORS (because they don't support it).

Include *one* of build/tincan-min.js or build/tincan.js as follows:

    <script src="build/tincan-min.js"></script>

Node.js Usage
-------------

TinCanJS is available via `npm`.

The `Environment/Node.js` wrapper used in this version has a dependency on the 'xhr2' module
which is also available via `npm`. It is used to allow the interfaces to the underlying LRS
requests to have the same API. As such currently there is no support for synchronous requests
when using this environment.

Install via:

    npm install tincanjs

And within code:

    var TinCan = require('tincanjs');

Environments
------------

Implementing a new Environment should be straightforward and requires overloading a couple
of methods on the `TinCan.LRS` prototype. There are currently two examples, `Environment/Browser`
and `Environment/Node`.
