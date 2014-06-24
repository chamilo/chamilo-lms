<?php

namespace Knp\Bundle\MarkdownBundle\Tests;

use Knp\Bundle\MarkdownBundle\Parser\MarkdownParser as Parser;

class FeatureTest extends \PHPUnit_Framework_TestCase
{
    public function testParser()
    {
        $parser = new Parser();

        $this->assertTrue($parser instanceof Parser);

        return $parser;
    }

    /**
     * @depends testParser
     */
    public function testEmpty($parser)
    {
        $this->assertEquals("\n", $parser->transform(''));
    }

    /**
     * @depends testParser
     */
    public function testEmphasis($parser)
    {
        $text = <<<EOF
*normal emphasis with asterisks*

_normal emphasis with underscore_

**strong emphasis with asterisks**

__strong emphasis with underscore__

This is some text *emphased* with asterisks.
EOF;
        $html = <<<EOF
<p><em>normal emphasis with asterisks</em></p>

<p><em>normal emphasis with underscore</em></p>

<p><strong>strong emphasis with asterisks</strong></p>

<p><strong>strong emphasis with underscore</strong></p>

<p>This is some text <em>emphased</em> with asterisks.</p>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testLiteralAsterisk($parser)
    {
        $text = '\*this text is surrounded by literal asterisks\*';
        $html = '<p>&#42;this text is surrounded by literal asterisks&#42;</p>
';

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testTitle($parser)
    {
        $text = <<<EOF
Titre de niveau 1 (balise H1)
=============================

Titre de niveau 2 (balise H2)
-----------------------------

# Titre de niveau 1

## Titre de niveau 2

###### Titre de niveau 6

### Close title 3 ###

#### Close title 4 ##
EOF;
        $html = <<<EOF
<h1>Titre de niveau 1 (balise H1)</h1>

<h2>Titre de niveau 2 (balise H2)</h2>

<h1>Titre de niveau 1</h1>

<h2>Titre de niveau 2</h2>

<h6>Titre de niveau 6</h6>

<h3>Close title 3</h3>

<h4>Close title 4</h4>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testQuote($parser)
    {
        $text = <<<EOF
> Ceci est un bloc de citation avec deux paragraphes.  Lorem ipsum dolor
> sit amet, consectetuer adipiscing elit. Aliquam hendrerit mi posuere
> lectus. Vestibulum enim wisi, viverra nec, fringilla in, laoreet vitae,
> risus.
>
> Donec sit amet nisl. Aliquam semper ipsum sit amet velit. Suspendisse
> id sem consectetuer libero luctus adipiscing.
EOF;

        $html = <<<EOF
<blockquote>
  <p>Ceci est un bloc de citation avec deux paragraphes.  Lorem ipsum dolor
  sit amet, consectetuer adipiscing elit. Aliquam hendrerit mi posuere
  lectus. Vestibulum enim wisi, viverra nec, fringilla in, laoreet vitae,
  risus.</p>
  
  <p>Donec sit amet nisl. Aliquam semper ipsum sit amet velit. Suspendisse
  id sem consectetuer libero luctus adipiscing.</p>
</blockquote>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testNestedQuote($parser)
    {
        $text = <<<EOF
> Ceci est le premier niveau de citation.
>
> > Ceci est un bloc de citation imbriqué.
>
> Retour au premier niveau.
EOF;
        $html = <<<EOF
<blockquote>
  <p>Ceci est le premier niveau de citation.</p>
  
  <blockquote>
    <p>Ceci est un bloc de citation imbriqué.</p>
  </blockquote>
  
  <p>Retour au premier niveau.</p>
</blockquote>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testQuotedHtml($parser)
    {
        $text = <<<EOF
> ## This is a header.
>
> 1.   This is the first list item.
> 2.   This is the second list item.
>
> Here's some example code:
>
>     return shell_exec("echo \$input | \$markdown_script");
EOF;

        $html = <<<EOF
<blockquote>
  <h2>This is a header.</h2>
  
  <ol>
  <li>This is the first list item.</li>
  <li>This is the second list item.</li>
  </ol>
  
  <p>Here's some example code:</p>

<pre><code>return shell_exec("echo \$input | \$markdown_script");
</code></pre>
</blockquote>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testUnorderedList($parser)
    {
        $text = <<<EOF
-   Red
-   Green
-   Blue
EOF;
        $html = <<<EOF
<ul>
<li>Red</li>
<li>Green</li>
<li>Blue</li>
</ul>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
+   Red
+   Green
+   Blue
EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
*   Red
*   Green
*   Blue
EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
*	Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
	Aliquam hendrerit mi posuere lectus. Vestibulum enim wisi,
	viverra nec, fringilla in, laoreet vitae, risus.
*	Donec sit amet nisl. Aliquam semper ipsum sit amet velit.
	Suspendisse id sem consectetuer libero luctus adipiscing.
EOF;
        $html = <<<EOF
<ul>
<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
Aliquam hendrerit mi posuere lectus. Vestibulum enim wisi,
viverra nec, fringilla in, laoreet vitae, risus.</li>
<li>Donec sit amet nisl. Aliquam semper ipsum sit amet velit.
Suspendisse id sem consectetuer libero luctus adipiscing.</li>
</ul>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
*   Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
Aliquam hendrerit mi posuere lectus. Vestibulum enim wisi,
viverra nec, fringilla in, laoreet vitae, risus.
*   Donec sit amet nisl. Aliquam semper ipsum sit amet velit.
Suspendisse id sem consectetuer libero luctus adipiscing.
EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testOrderedList($parser)
    {
        $text = <<<EOF
1.  Bird
2.  McHale
3.  Parish
EOF;
        $html = <<<EOF
<ol>
<li>Bird</li>
<li>McHale</li>
<li>Parish</li>
</ol>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
3. Bird
1. McHale
8. Parish
EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testInlineLink($parser)
    {
        $text = <<<EOF
This is [an example](http://example.com/ "Title") inline link.

[This link](http://example.net/) has no title attribute.
EOF;
        $html = <<<EOF
<p>This is <a href="http://example.com/" title="Title">an example</a> inline link.</p>

<p><a href="http://example.net/">This link</a> has no title attribute.</p>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testReferenceLink($parser)
    {
        $text = <<<EOF
I get 10 times more traffic from [Google] [1] than from
[Yahoo] [2] or [MSN] [3].

  [1]: http://google.com/        "Google"
  [2]: http://search.yahoo.com/  "Yahoo Search"
  [3]: http://search.msn.com/    "MSN Search"
EOF;

        $html = <<<EOF
<p>I get 10 times more traffic from <a href="http://google.com/" title="Google">Google</a> than from
<a href="http://search.yahoo.com/" title="Yahoo Search">Yahoo</a> or <a href="http://search.msn.com/" title="MSN Search">MSN</a>.</p>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
I get 10 times more traffic from [Google][] than from
[Yahoo][] or [MSN][].

  [google]: http://google.com/        "Google"
  [yahoo]:  http://search.yahoo.com/  "Yahoo Search"
  [msn]:    http://search.msn.com/    "MSN Search"
EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testAutoLink($parser)
    {
        $text = '<http://exemple.com/>';
        $html = '<p><a href="http://exemple.com/">http://exemple.com/</a></p>
';

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testInlineImage($parser)
    {
        $text = <<<EOF
![Alt text](/path/to/img.jpg)
![Alt text](/path/to/img.jpg "Optional title")
EOF;

        $html = <<<EOF
<p><img src="/path/to/img.jpg" alt="Alt text" />
<img src="/path/to/img.jpg" alt="Alt text" title="Optional title" /></p>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testReferenceImage($parser)
    {
        $text = <<<EOF
![Alt text][id]

[id]: /path/to/img.jpg  "Optional title"
EOF;

        $html = <<<EOF
<p><img src="/path/to/img.jpg" alt="Alt text" title="Optional title" /></p>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testCode($parser)
    {
        $text = 'Use the `printf()` function.';
        $html = '<p>Use the <code>printf()</code> function.</p>
';

        $this->assertSame($html, $parser->transform($text));

        $text = '``There is a literal backtick (`) here.``';
        $html = '<p><code>There is a literal backtick (`) here.</code></p>
';

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
A single backtick in a code span: `` ` ``

A backtick-delimited string in a code span: `` `foo` ``
EOF;

        $html = <<<EOF
<p>A single backtick in a code span: <code>`</code></p>

<p>A backtick-delimited string in a code span: <code>`foo`</code></p>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = 'Please don\'t use any `<blink>` tags.';
        $html = '<p>Please don\'t use any <code>&lt;blink&gt;</code> tags.</p>
';

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testCodeBlock($parser)
    {
        $text = <<<EOF
This is a normal paragraph:

    This is a code block.
EOF;

        $html = <<<EOF
<p>This is a normal paragraph:</p>

<pre><code>This is a code block.
</code></pre>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
Here is an example of AppleScript:

    tell application "Foo"
        beep
    end tell
EOF;

        $html = <<<EOF
<p>Here is an example of AppleScript:</p>

<pre><code>tell application "Foo"
    beep
end tell
</code></pre>

EOF;

        $this->assertSame($html, $parser->transform($text));

        $text = <<<EOF
    <div class="footer">
        &copy; 2004 Foo Corporation
    </div>
EOF;

        $html = <<<EOF
<pre><code>&lt;div class="footer"&gt;
    &amp;copy; 2004 Foo Corporation
&lt;/div&gt;
</code></pre>

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testHorizontalRule($parser)
    {
        $text = <<<EOF
* * *

***

*****

- - -

---------------------------------------
EOF;

        $html = <<<EOF
<hr />

<hr />

<hr />

<hr />

<hr />

EOF;

        $this->assertSame($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testHeaderId($parser)
    {
        $text = <<<EOF
Header 1            {#header1}
========

## Header 2 ##      {#header2}
EOF;

        $html = <<<EOF
<h1 id="header1">Header 1</h1>

<h2 id="header2">Header 2</h2>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testFencedCodeBlock($parser)
    {
        $text = <<<EOF
This is a paragraph introducing:

~~~~~~~~~~~~~~~~~~~~~
a one-line code block
~~~~~~~~~~~~~~~~~~~~~
EOF;

        $html = <<<EOF
<p>This is a paragraph introducing:</p>

<pre><code>a one-line code block
</code></pre>

EOF;

        $this->assertEquals($html, $parser->transform($text));

        $text = <<<EOF
1.  List item

    Not an indented code block, but a second paragraph
    in the list item

~~~~
This is a code block, fenced-style
~~~~
EOF;

        $html = <<<EOF
<ol>
<li><p>List item</p>

<p>Not an indented code block, but a second paragraph
in the list item</p></li>
</ol>

<pre><code>This is a code block, fenced-style
</code></pre>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testTable($parser)
    {
        $text = <<<EOF
First Header  | Second Header
------------- | -------------
Content Cell  | Content Cell
Content Cell  | Content Cell
EOF;

        $html = <<<EOF
<table>
<thead>
<tr>
  <th>First Header</th>
  <th>Second Header</th>
</tr>
</thead>
<tbody>
<tr>
  <td>Content Cell</td>
  <td>Content Cell</td>
</tr>
<tr>
  <td>Content Cell</td>
  <td>Content Cell</td>
</tr>
</tbody>
</table>

EOF;

        $this->assertEquals($html, $parser->transform($text));

        $text = <<<EOF
| First Header  | Second Header |
| ------------- | ------------- |
| Content Cell  | Content Cell  |
| Content Cell  | Content Cell  |
EOF;

        $this->assertEquals($html, $parser->transform($text));

        $text = <<<EOF
| Function name | Description                    |
| ------------- | ------------------------------ |
| `help()`      | Display the help window.       |
| `destroy()`   | **Destroy your computer!**     |
EOF;

        $html = <<<EOF
<table>
<thead>
<tr>
  <th>Function name</th>
  <th>Description</th>
</tr>
</thead>
<tbody>
<tr>
  <td><code>help()</code></td>
  <td>Display the help window.</td>
</tr>
<tr>
  <td><code>destroy()</code></td>
  <td><strong>Destroy your computer!</strong></td>
</tr>
</tbody>
</table>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testDefinitionList($parser)
    {
        $text = <<<EOF
Apple
:   Pomaceous fruit of plants of the genus Malus in
    the family Rosaceae.

Orange
:   The fruit of an evergreen tree of the genus Citrus.
EOF;

        $html = <<<EOF
<dl>
<dt>Apple</dt>
<dd>Pomaceous fruit of plants of the genus Malus in
the family Rosaceae.</dd>

<dt>Orange</dt>
<dd>The fruit of an evergreen tree of the genus Citrus.</dd>
</dl>

EOF;

        $this->assertEquals($html, $parser->transform($text));

        $text = <<<EOF
Apple
:   Pomaceous fruit of plants of the genus Malus in
the family Rosaceae.

Orange
:   The fruit of an evergreen tree of the genus Citrus.
EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testFootNote($parser)
    {
        $text = <<<EOF
That's some text with a footnote.[^1]

[^1]: And that's the footnote.
EOF;

        $html = <<<EOF
<p>That's some text with a footnote.<sup id="fnref:1"><a href="#fn:1" rel="footnote">1</a></sup></p>

<div class="footnotes">
<hr />
<ol>

<li id="fn:1">
<p>And that's the footnote.&#160;<a href="#fnref:1" rev="footnote">&#8617;</a></p>
</li>

</ol>
</div>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testAbbreviation($parser)
    {
        $text = <<<EOF
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
EOF;

        $html = <<<EOF
<p>The <abbr title="Hyper Text Markup Language">HTML</abbr> specification
is maintained by the <abbr title="World Wide Web Consortium">W3C</abbr>.</p>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }

    /**
     * @depends testParser
     */
    public function testInlineHtml($parser)
    {
        $text = <<<EOF
Hyper Text <span>Markup</span> Language
EOF;

        $html = <<<EOF
<p>Hyper Text <span>Markup</span> Language</p>

EOF;

        $this->assertEquals($html, $parser->transform($text));
    }
}
