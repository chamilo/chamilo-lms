<h1>Chamilo Tour Plugin</h1>
<p>Shows people how to use your Chamilo LMS</p>
<h2>Set the blocks for the tour</h2>
<p>Edit the <code>plugin/tour/config/tour.json</code> file adding the page classes and steps</p>
<p>To set the steps in a page, add an object like this:</p>
<pre>
{
    "pageClass": "page unique class selector",
    "steps": [
        {
            "elementSelector": "element class or id",
            "message": "LanguageVariable"
        },
        {
            "elementSelector": "element class or id",
            "message": "LanguageVariable"
        },
    ]
}
</pre>
Then set the language variables inside the <code>plugin/tour/lang/$language.php</code> file<br>
<h2>Set a region to plugin</h2>
<p>You must assign a Region to Tour plugin in the Configuration Settings</p>
<p>Choose preferably <code>header_right</code></p>
<br>