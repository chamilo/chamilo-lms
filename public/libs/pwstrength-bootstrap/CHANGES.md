# CHANGELOG

## 2.2.1

- Bugfix in the common passwords rule.

## 2.2.0

- Add new rule to penalize common passwords.

## 2.1.4

- Thai localization.
- Fix typo in German localization.
- Activate by default the extra security rules.
- Make the invalid chars optional rule configurable.

## 2.1.3

- Bugfix, call `onScore` when zxcvbn is in use too.

## 2.1.2

- Fix errors in Portuguese localization.
- Fix French localization capitalization.
- Fix ruleIsMet issues with wordMin and wordMax rules.
- Don't allow verdict to break line when inside progress bar.

## 2.1.1

- Add missing rule, needed by the `ruleIsMet` method.
- Add `wordMaxLength` and `wordInvalidChar` optional rules to the engine.

## 2.1.0

- Slovak translation.
- Add a new `ruleIsMet` method that returns a boolean value indicating if all
  password inputs in the page pass a specific rule.

## 2.0.8

- Fix showing the strength of the password through the status of the field.

## 2.0.7

- Add new option `progressExtraCssClasses` to be able to customize the
  container of the progress bar.
- Updated development dependencies.

## 2.0.6

- Updated development dependencies.
- Bootstrap 4 alpha 6 support.

## 2.0.5

- Italian localization.

## 2.0.4

- French localization.
- Don't use Math.log2 since IE doesn't support it.

## 2.0.3

- German localization.
- Polish localization.

## 2.0.2

- Add a `onScore` callback to allow for a final score modification.
- Turkish localization.

## 2.0.1

- Fix bad assignment in the plugin initialization.
- Russian localization.
- New option to control the events the plugin listen to.

## 2.0.0

- Use six possible verdicts and six possible css classes, so they match one
  to one making it possible to configure each class for each verdict level.
- Properly manage the paste event so the meter updates when the user pastes the
  password.
- Add a new option to display the password score.
- Translations support, ahora hablamos idiomas.
- New option to set the minimum possible percentage filled in the progress bar
  when the password field is not empty.
- New option to set the minimum possible percentage filled in the progress bar
  when the password field is empty.
- New option for extra CSS classes to be added to the generated progress bar.

### Breaking changes

- There are 6 verdicts and css classes now, instead of 5.
- `verdicts` and `errorMessages` options have been removed. Now they rely on
  the translations system.

## 1.2.10

- Replace entropy call with log2 of guesses for zxcvbn because entropy property
  is removed in zxcvbn v4.0.1, and it was just log2 of guesses.

## 1.2.9

- No changes, I forgot to add the built files into the 1.2.8, so I'm releasing
  the same again.

## 1.2.8

- Updated to work with Bootstrap 4. Bootstrap 3 is still the default mode.
- Allow to establish the placement of the popover through an option.
- Make the css classes added to the bar and verdicts customizable.
- Bugfix in the progress bar percentage calculation for a score of zero.

## 1.2.7

- Bugfix: escape special characters in username for regex.

## 1.2.6

- More sensible default score for sequences rule.
- Publish plugin in npm.

## 1.2.5

- Bugfix when using zxcvbn and form inputs with empty values.
- New option to specify a list of banned words for zxcvbn.

## 1.2.4

- New option to add a class in verdict element.
- If there is text in the password field, don't show the progress bar empty.
- Empty verdict for an empty password field.
- Support html in the verdicts content.

## 1.2.3

- New option to customize the html of the popover with the errors.
- Bugfix in special char regex.

## 1.2.2

- Every rule can have associated error messages.

## 1.2.1

- Improve documentation.
- Fix typo in alphabetical sequence.
- Use the not minified version of the library in bower as main file.

## 1.2.0

- Listen also to the `change` and `onpaste` events, not only to the `onkeyup`.
- Show the lowest verdict when the score is below zero.
- New option to pass more input fields content to the zxcvbn library.
- Don't show the verdicts inside the popover if they are being showed inside
  the progressbar.

## 1.1.5

- Better Bower configuration.
- Pass also the verdict level to the "on key up" event handler.
- Add a basic usage section to the readme.

## 1.1.4

- Bower support.

## 1.1.3

- Pass the score and the verdict to the "on key up" event handler.

## 1.1.2

- Upgrade dev dependencies: grunt plugins and jquery
- Bugfix in sequences lookup
- New tests for sequences lookup

## 1.1.1

- Pass the username field content to the zxcvbn function, so zxcvbn takes it
  into consideration when scoring the password.
- Add a debug option, so the score gets printed in the JS console.
- Check reversed sequences too in the sequences rule.
- Fix the popover flickering.

## 1.1.0

- Support zxcvbn for password scoring.
- Support showing the password strength as a validation status in the password
  field.
- Support hiding the progress bar, making it optional.
- Support showing the verdicts inside the progress bar.

## 1.0.2

- Bugfix in UI initialization.
- Fix typo in readme.

## 1.0.1

- Separate source file in several smaller files.
- Add Grunt support for creating a bundle and a minified version.
- Add tests for the rules engine, and continuos integration with Travis.

## 1.0.0

- Complete refactor of the code. This is a cleaner version, easier to extend
  and mantain.
- Broke backwards compatibility. Bootstrap 3 is the default option now, other
  options default values have changed. Options structure has changed too.
- Old tests have been renamed to examples, which is what they really are. Leave
  room for real tests.

## 0.7.0

- New rule to check for sequences in the password. It penalizes finding
  sequences of consecutive numbers, consecutive characters in the alphabet or
  in the qwerty layout. Active by default.

## 0.6.0

- New feature: support showing the verdicts and errors in a Bootstrap popover.
- Hide the verdicts and errors when the input is empty.
- Remove _showVerdictsInitially_ option, is not needed anymore.

## 0.5.0

- Support to activate/deactivate rules using the _rules_ object inside the
  _options_ object.
- Two new rules added, deactivated by default. Check for too many character
  repetitions, and check for number of character classes used.

## 0.4.5

- Fix error message when the password contains the username.
- Check if the password is an email, and mark as weak.
- Add a _container_ option, it will be used to look for the viewports.

## 0.4.4

- Bad version in plugin manifest.

## 0.4.3

- Change jQuery plugin name to avoid conflict with an existing one.

## 0.4.2

- New option to choose if the verdicts should be displayed before the user
  introduces a letter. New default behaviour: don't show them.
- Bugfix with progress bar color and Bootstrap 2.
- Improve code quality.

## 0.4.1

- jQuery plugins registry support.

## 0.4.0

- Bootstrap 3.0.0 support.
