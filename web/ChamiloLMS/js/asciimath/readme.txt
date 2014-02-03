ASCIIMathML.js

Brief Instructions (2007-9-28)

This script can be used in any html page and also in systems like Moodle.
The license is LGPL (see top of ASCIIMathML.js for more info).

In a html page, just add the following line near the top of your file:
<script type="text/javascript" src="ASCIIMathML.js"></script>

To install in Moodle, just move this asciimath-x.y.z folder into moodle/filter,
rename the folder "asciimath" and go to the admin panel Modules->Filters
to switch it on.

Then try some ASCIIMath on your webpages: `x/y`

or LaTeX $\sqrt{x}$ and $$\int_0^1 x^2 dx$$ (only a \emph{subset} works,
including a few environments like \begin{theorem}... and \begin{proof})

Try some graphics like agraph plot(sin(x)) endagraph or
\begin{graph}plot(sin(x))\end{graph}

Try the auto-math-recognize mode: amath here we can mix x^2 and text endamath

All this is supposed to work in Firefox on many platforms (recommended;
you may be asked to install math fonts) and in Internet Explorer (only
on WinXP and you have to install MathPlayer and Adobe SVGview).

For more examples, see http://www.chapman.edu/~jipsen/asciimath.html and
http://mathcs.chapman.edu/~jipsen/math

If you use this script on the web, please send an email to jipsen@chapman.edu
and put a link to http://www.chapman.edu/~jipsen/asciimath.html on your page.

Best wishes with ASCIIMathML.js

Peter Jipsen