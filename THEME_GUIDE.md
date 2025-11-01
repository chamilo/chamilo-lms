# Theme Guide for Chamilo 2.0

This guide explains how to create and customize your own theme for **Chamilo 2.0**.

## Folder Structure

In Chamilo 2.0, themes are stored in the new directory layout:

1. `public/themes/{your_theme}/`
2. `resources/views/themes/{your_theme}/`

> Replace `{your_theme}` with your theme’s name, in lowercase.

## Files Overview
- **CSS/SCSS files** go inside `public/themes/{your_theme}/css/`
- **Images** go inside `public/themes/{your_theme}/images/`
- **Twig templates** (HTML structure) go inside `resources/views/themes/{your_theme}/`

## Naming Rules
- File names should be **lowercase** and use `.css` or `.scss` extensions.
- Example: `main.css`, `dashboard.scss`

## Enabling the Theme
1. Log in as **administrator**.
2. Go to **Administration → Configuration → Look and Feel → Themes**.
3. Select your theme from the dropdown and save.

## Notes
- Chamilo 2.0 introduces a simplified and modular theme system.  
- Legacy folders like `main/template/` and `main/css/` are no longer used.  
- Refer to the [Chamilo 2.0 Developer Documentation](https://docs.chamilo.org/en/) for updates.

> Note: This guide applies to Chamilo 2.0.  
> For Chamilo 1.11.x, please see the previous version of this document.

