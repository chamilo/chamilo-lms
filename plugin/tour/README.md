Chamilo Tour Plugin
===================

Shows people how to use your Chamilo LMS

# Set the blocks for the tour

Edit the `plugin/tour/config/tour.json` file adding the page classes and steps

To set the steps in a page, add an object like this:
```
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
```
Then set the language variables inside the `plugin/tour/lang/$language.php` file

# Set a region to plugin

You must assign a Region to Tour plugin in the Configuration Settings
Choose preferably `header_right`