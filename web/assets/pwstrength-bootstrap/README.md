# jQuery Password Strength Meter for Twitter Bootstrap

[![Build Status](https://travis-ci.org/ablanco/jquery.pwstrength.bootstrap.png?branch=master)](https://travis-ci.org/ablanco/jquery.pwstrength.bootstrap)
[![Code Climate](https://codeclimate.com/github/ablanco/jquery.pwstrength.bootstrap.png)](https://codeclimate.com/github/ablanco/jquery.pwstrength.bootstrap)
[![devDependency Status](https://david-dm.org/ablanco/jquery.pwstrength.bootstrap/dev-status.png)](https://david-dm.org/ablanco/jquery.pwstrength.bootstrap#info=devDependencies)

The jQuery Password Strength Meter is a plugin for Twitter Bootstrap that
provides rulesets for visualy displaying the quality of a users typed in
password.

Dual licensed under the MIT and GPL licenses. You can choose the one that
suits your purposes better.

[npm entry](https://www.npmjs.com/package/pwstrength-bootstrap)


## Requirements

* jQuery 1.7 or higher
* Bootstrap 2, 3 or 4

### Not using Bootstrap?

This plugin currently relies heavily on Bootstrap and it is not possible to
use it with another framework without making big changes in the code or
forgetting completely about the UI feedback.

Forks to use it with another frameworks that I know of:

* [Zurb Foundation fork by edtownend](https://github.com/edtownend/jquery.pwstrength.foundation)


## How to use it

Get the latest version through [Bower](http://bower.io/search/?q=pwstrength-bootstrap),
[npm](https://www.npmjs.com/package/pwstrength-bootstrap), or just download it
from this [repository](https://github.com/ablanco/jquery.pwstrength.bootstrap/tree/master/dist).
Load it into your HTML after your original bootstrap and jQuery javascript files:

```html
<script type="text/javascript" src="dist/pwstrength-bootstrap.min.js"></script>
```

Then just invoke the plugin on the password fields you want to attach a strength
meter to. For example, to use it on all the password fields with the default
examples:

```javascript
    $(':password').pwstrength();
```

To apply it only to one input and change the options:

```javascript
    $('#passwd1').pwstrength({
        ui: { showVerdictsInsideProgressBar: true }
    });
```

## Options

Click here to find [the complete list of options for the plugin](OPTIONS.md).

If you are looking for options to change or add new texts, please have a look
at the internationalization section.


## Methods

Once the plugin has been initialized, it is possible to interact with it
through the methods.


### Force an update

It is possible to force an update on a password strength meter. It will force
a new score calculation and an update of the UI elements, the `onKeyUp`
callback will be called.

```javascript
$("#passwdfield").pwstrength("forceUpdate");
```


### Remove the strength meter

This will remove the data associated to the meter, and the UI elements.

```javascript
$("#passwdfield").pwstrength("destroy");
```


### Adding Custom Rules

The plugin comes with the functionality to easily define your own custom rules.
The format is as follows:

```javascript
$("#passwdfield").pwstrength("addRule", "ruleName", function (options, word, score) {}, rule_score, rule_enabled);
```

Example:

```javascript
$("#passwdfield").pwstrength("addRule", "testRule", function (options, word, score) {
    return word.match(/[a-z].[0-9]/) && score;
}, 10, true);
```


### Change the score associated to a rule

It is possible to change the score given by a rule. It works like this:

```javascript
$("#passwdfield").pwstrength("changeScore", "wordSequences", -100);
```

That would penalize even more the presence of sequences in the password.


### Activate and deactivate rules

It is also possible to activate or deactivate rules. It as simple as:

```javascript
$("#passwdfield").pwstrength("ruleActive", "wordSequences", false);
```

That would avoid looking for sequences in the password being tested.


## Callback Functions

The plugin provides three callback functions, onLoad, onKeyUp, and scoreCalculated.  You can use
them like this:

```javascript
$(document).ready(function () {
    var options = {};
    options.common = {
        onLoad: function () {
            $('#messages').text('Start typing password');
        },
        onKeyUp: function (evt, data) {
            $("#length-help-text").text("Current length: " + $(evt.target).val().length + " and score: " + data.score);
        },
        onScore: function (options, word, totalScoreCalculated) {
            // If my word meets a specific scenario, I want the min score to
            // be the level 1 score, for example.
            if (word.length === 20 && totalScoreCalculated < options.ui.scores[1]) {
                // Score doesn't meet the score[1]. So we will return the min
                // numbers of points to get that score instead.
                return options.ui.score[1]
            }
            // Fall back to the score that was calculated by the rules engine.
            // Must pass back the score to set the total score variable.
            return totalScoreCalculated;
        }
    };
    $(':password').pwstrength(options);
});
```


## Extra security

The plugin comes with two validation rules deactivated by default. One checks
for too many character repetitions, and the other checks the number of
character classes used. An easy way to increase the security of the passwords
is to activate this two rules:

```javascript
$(document).ready(function () {
    var options = {};
    options.rules = {
        activated: {
            wordTwoCharacterClasses: true,
            wordRepetitions: true
        }
    };
    $(':password').pwstrength(options);
});
```


## Internationalization (i18n)

The plugin has support for internationalization. It also comes with some
example translations, you can find them in the [locales folder](locales).

The plugin provides a default implementation of the translation function, but
you can override it using the option `i18n.t`.

The default implementation will try to make use of the popular
[i18next front-end translation tool](http://i18next.com/). If you happen to
use it, then you only need to add the translations into your resources and
load them. The plugin will automatically make use of it. You can find more
details about and how to use it i18next in their website. There is also an
example in the repository that uses that library.

In case the i18next library is not available, then the default behavior is
to return the english texts as a fallback.

### What are the translatable texts?

You can find the non-rules texts in any of the
[provided translation example files](locales), and besides what you find there,
every rule name is a valid key for the translation file. __You can use this to
add new error messages (or remove them) for the engine rules__.

### How to customize the translation function

If you want to manage translations yourself or you don't use i18next you can
override the default translation function like this:

```javascript
$(document).ready(function () {
    var options = {};
    options.i18n = {
        t: function (key) {
            var result = translateThisThing(key); // Do your magic here

            return result === key ? '' : result; // This assumes you return the
            // key if no translation was found, adapt as necessary
        }
    };
    $(':password').pwstrength(options);
});
```

You can find an example of some keys and translations in the
[locales folder](locales).


## Examples

There are some examples in the `examples` directory. Just serve them with any
webserver and check them in your browser. Make sure you serve the `examples`
directory as the site root. For example:

```bash
cd examples
python -m SimpleHTTPServer
```

And go to [localhost:8000](http://localhost:8000).

Alternatively, you can check-out the examples in a [hosted demo](https://cdn.rawgit.com/ablanco/jquery.pwstrength.bootstrap/master/examples/index.html).

## Build and Test

The build and testing processes rely on [Grunt](http://gruntjs.com/). To use
them you need to have [node.js](http://nodejs.org/) and grunt-cli installed on
your system. Assuming you have node.js in your Linux system, you'll need to do
something like this:

```bash
sudo npm install -g grunt-cli
```

Now you have the grunt command line utility installed globally.


### Bundle and minified

To generate the bundle and the minified file you only need to execute this in
the project directory:

```bash
npm install -d
grunt
```

It will check the source files, and build a minified version with its
corresponding source map. The generated files will be available in the `dist`
directory.


### Testing

To run the tests the only thing you need to do is execute this in the project
directory:

```bash
npm install -d
grunt test
```

It will check all the source files with [JSLint](http://jslint.com) and run the
tests, which are written with [Jasmine](http://jasmine.github.io/). You'll find
the tests source code in the `spec` directory.

[Travis](https://travis-ci.org/ablanco/jquery.pwstrength.bootstrap) is being
used for continuos integration. You can check there if the tests are passing.
