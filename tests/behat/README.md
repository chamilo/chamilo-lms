In order to run behat tests locally with the right support for browser and JS environments under Linux, you will need to:

- Download Selenium Standalone Server v3.*
 
http://www.seleniumhq.org/download/

And run it with the following command:

```
java -jar /my-dir/selenium-server-standalone-3.1.0.jar
```

- Download the Chrome driver, unzip and copy into /usr/bin

Check the latest version at https://sites.google.com/a/chromium.org/chromedriver/downloads, then adapt the following command to the latest version:

```
cd /tmp && wget https://chromedriver.storage.googleapis.com/2.29/chromedriver_linux64.zip && unzip chromedriver_linux64.zip && sudo mv chromedriver /usr/bin 
```

### Chamilo configuration

- An administrator user should be created with these parameters:
    - Username "admin" 
    - Password "admin"
    - First name "John"
    - Last name "Doe"
    - user_id = 1 (this one is set when you install Chamilo, but just in case...)

- Edit the tests/behat/behat.yml file and update the base_url param with your own Chamilo local URL.
- The main platform language and the admin user's language must be English (platformLanguage = english and admin user profile)
- Social network tool must be available (allow_social_tool = true)
- Student can register to the system (allow_registration = yes)
- Teacher can register to the system (allow_registration_as_teacher = yes)
- The CHECK_PASS_EASY_TO_FIND in app/config/profile.conf.php must be set to false

### Run tests

To run all features:

```
# /var/www/html/chamilo
cd tests/behat
 ../../vendor/behat/behat/bin/behat -v
 ```
 
To run an specific feature:

```
../../vendor/behat/behat/bin/behat features/createCourse.feature
```
