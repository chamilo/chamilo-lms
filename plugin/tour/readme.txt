<h1>Chamilo Tour Plugin</h1>
<p>Shows people how to use your Chamilo LMS</p>
<h2>Set the blocks for the tour</h2>
<p>Edit the plugin/tour/config/tour.json file adding the page classes and steps</p>
<p>To set the steps in a page, add a object like this:</p>
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
Then set the language variables inside the `plugin/tour/lang/$language.php` file<br>
<br>
