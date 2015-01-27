Custom pages
=============

CustomPages looks for alternatives in this directory, and displays them if present.
The user-provided custom pages must exactly be named as such :

- index-logged.php for the general landing page before login
- index-unlogged.php for the general landing page when already logged-in
- registration.php for the registration form
- registration-feedback.php for the registration success feedback
- lostpassword.php for the password recovery form


### Installation

- Enable the use_custom_pages setting
- Create your own modifications based in the files with the suffix "-dist.php"

### Important notes

- Do not replace the images in the images/ directory.
  Instead, create new images, as the current ones will be overwritten
  by each Chamilo upgrade.

