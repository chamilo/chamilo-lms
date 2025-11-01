# Creating a Custom Theme in Chamilo LMS

This guide explains how to properly create your own theme for Chamilo.

## Folder Structure

CSS files must be placed in both locations to work correctly (as of version 1.9+):

1. `app/Resources/public/css/themes/{your_theme}/`
2. `web/css/{your_theme}/`

> Replace `{your_theme}` with your theme name, in lowercase.

## Naming Rules
- CSS files **must be lowercase** and use the `.css` extension.
- Example: `default.css`, `learnpath.css`

## Notes
Currently, Chamilo duplicates CSS files in both locations, but one location may be removed in future versions.  
Refer to the [Chamilo Developer Documentation](https://docs.chamilo.org/en/) for future updates.
