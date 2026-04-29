# Lightweight Decorator for Textareas
## In browser live syntax highlighting

LDT aims to provide a simple, lightweight, and highly extensible alternative to existing in-browser live syntax highlighting solutions by leveraging clever CSS and native functionality. Other solutions often re-implement large parts of the user interaction, or use inconsistent pseudo-standard features such as contentEditable and designMode. This results in either a lack of native functionality or a large code-base to compensate or both.

It behaves (mostly) like a textarea because it *is* a (transparent) textarea! The decorator maintains a styled copy of the content in a display layer which is aligned underneath the real textarea. By using a real textarea, we get all the native functionality for free! This usually includes keyboard input (one would hope), navigating with a blinking cursor, making selections, cut, copy, & paste; sometimes drag & drop and undo & redo*.

The idea of displaying content under a (semi) transparent input is by no means a new idea. In fact, Google uses this technique to offer suggestions in their search bar. Facebook used it to highlight the name of a friend while composing a post. Other live syntax highlighting solutions have also used this, such as EditArea and to a degree CodeMirror 2.

In a sense, LDT takes the UNIX approach; make small programs that do one thing really well. LDT is modular, consisting of the decorator object which maintains the display layer for the editor, and an optional parser object which tells the decorator how to style the content. I try not to do anything above providing a syntax highlighting in a native textarea. This way we keep it as lightweight as possible, but still extensible so it may be used for many applications. Since it is still really a textarea at heart, you can still hook in extended functionality. Two such modules are included, SelectHelper for selection utilities and modifying content programmatically, and Keybinder for mapping hotkeys.

The optional Parser is included to make it easy to generate fast highlightings using regular expressions. All you have to do is provide a mapping of CSS class names to RegExp objects. In your CSS you can specify styles to apply to each token class. You can also apply multiple classes to a token, just provide a space separated list in quotes. You can also write your own parser if you have your own way of generating tokens, just follow the parser interface.

LDT was developed by Colin Kuebler originally as part of *The Koala Project*. Special thanks to the *Rensselaer Center for Open Source* for their support.

*\* Undo & redo has been known to break when you modify the textarea's contents programmatically (which is why LDT doesn't do this by default). It might be possible to regain this functionality by implementing your own undo stack.*

## Using LDT
Making an auto highlighting `textarea` is easy with LDT. Make sure to include the modules you need either directly in your code (less server requests) or using the HTML `script` tag. Minify in production for bandwidths sake. Below is a simple example of LDT usage. See `examples` directory for more.
### HTML
```html
<!-- normal textarea fall-back, add an id to access it from javascript -->
<textarea id='codeArea' class='ldt'></textarea>
<noscript>Please enable JavaScript to allow syntax highlighting.</noscript>
```
### JS
```js
// create a parser with a mapping of css classes to regular expressions
// everything must be matched, so 'whitespace' and 'other' are commonly included
var parser = new Parser(
  { whitespace: /\s+/,
    comment: /\/\/[^\r\n]*/,
    other: /\S/ } );
// get the textarea with $ (document.getElementById)
// pass the textarea element and parser to LDT
var ldt = new TextareaDecorator( $('codeArea'), parser );
```
### CSS
```css
/* editor styles */
.ldt {
	width: 400px;
	height: 300px;
	border: 1px solid black;
}
/* styles applied to comment tokens */
.ldt .comment {
    color: silver;
}
```

## Browser Support
LDT has been tested on

 * Firefox 3.6 - 80
 * Internet Explorer 8 - 11
 * Chromium & Google Chrome 16 - 85
 * Midori 4.1
 * Opera 11.61
 * Epiphany

## API
### TextareaDecorator

 + `new TextareaDecorator( textarea, parser )` Converts a HTML `textarea` element into an auto highlighting TextareaDecorator. `parser` is used to determine how to subdivide and style the content. `parser` can be any object which defines the `tokenize` and `identify` methods as described in the Parser API below.
 + `.input` The input layer of the LDT, a `textarea` element.
 + `.output` The output layer of the LDT, a `pre` element.
 + `.update()` Updates the highlighting of the LDT. It is automatically called on user input. You shouldn't need to call this unless you programmatically changed the contents of the `textarea`.

### Parser

 + `new Parser( [rules], [i] )` Creates a parser. `rules` is an object whose keys are CSS classes and values are the regular expressions which match each token. `i` is a boolean which determines if the matching is case insensitive, it defaults to `false`.
 + `.add( rules )` Adds a mapping of CSS class names to regular expressions.
 + `.tokenize( string )` Splits `string` into an array of tokens as defined by `.rules`.
 + `.identify( string )` Finds the CSS class name associated with the token `string`.

### Keybinder
This is a singleton, you do not need to instantiate this object.

 + `.bind( element, [keymap] )` Adds Keybinder methods to `element`, optionally setting the element's `keymap`.
 + `element.keymap` A mapping of key names to callbacks.

### SelectHelper
This is a singleton, you do not need to instantiate this object.

 + `.add( element )` Adds SelectHelper methods to `element`.
 + `element.insertAtCursor( string )` Inserts `string` into the `element` before the current cursor position.

## Contributing
You can help by testing browser compatibility, submitting bug reports and fixes, and providing any sort of feedback. Optionally let me know if you end up using LDT, I would love to see what you do with it. Thank you for supporting open source software!

## License
LDT is open sourced under GPL v3 and MIT. Full text for both licenses should be available in this directory.
