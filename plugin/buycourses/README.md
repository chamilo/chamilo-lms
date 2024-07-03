Buy Courses (course sales) plugin
=================================
This plugin transforms your Chamilo installation in an online shop by adding a catalogue
 of courses and sessions that you have previously configured for sales.

If the user is not registered or logged in, he/she will be requested to register/login
before he/she can resume buying items.

Do not enable this plugin in any "Region". This is a known issue, but it works without 
region assignation.

Once the course or session is chosen, the plugin displays the available payment methods
and lets the user proceed with the purchase.
Currently, the plugin allows users to pay through:
 - PayPal (requires a merchant account on PayPal at configuration time)
 - Bank payments (requires manual confirmation of payments' reception)
 - RedSys payments (Spanish payment gateway) (requires the download of an external file)
 - Stripe payments (requieres a merchant account oin Stripe at configuration time)
 - Cecabank payments (Spanish payment gateway)

The user receives an e-mail confirming the purchase and she/he can immediately 
access to the course or session.

We recommend using sessions as this gives you more time-related availability options
(in the session configuration).

Updates
=========

You must load the update.php script for installations that were in 
production before updating the code, as it will update the database structure to
enable new features.

Please note that updating Chamilo does *NOT* automatically update the plugins
structure.

You can find a history of changes in the [CHANGELOG.md file](../../plugin/buycourses/CHANGELOG.md)
