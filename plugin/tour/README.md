Chamilo Tour Plugin
===================

Shows people how to use your Chamilo LMS

# Set the blocks for the tour

Edit the plugin/tour/config/tour.json file adding the page classes and steps

To set the steps in a page, add a object like this:
```
{
    "pageClass": "page unique class selector",
    "steps": [
        {
            "elementSelector": "element class or id",
            "message": "Language variable"
        },
        {
            "elementSelector": "element class or id",
            "message": "Language variable"
        },
    ]
}
```