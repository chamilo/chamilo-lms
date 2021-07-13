In order to run behat tests locally with the right support for browser
and JS environments under Linux, you will need to:

- Have Java installed (see notes below)
- Download Selenium Standalone Server v3.*

http://www.seleniumhq.org/download/

And run it with the following command:

```
java -jar /my-dir/selenium-server-standalone-3.1.0.jar
```

- Download the Chrome driver, unzip and copy into /usr/bin

Check the latest version at https://sites.google.com/a/chromium.org/chromedriver/downloads,
then adapt the following command to the latest version. Use a version that matches your version of the Chrome browser.

```
cd /tmp && wget https://chromedriver.storage.googleapis.com/2.35/chromedriver_linux64.zip && unzip chromedriver_linux64.zip && sudo mv chromedriver /usr/local/bin
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

### Run tests

To run all features:

```
# /var/www/html/chamilo
cd tests/behat
 ../../vendor/behat/behat/bin/behat -v
 ```

To run an specific feature:

```
../../vendor/behat/behat/bin/behat features/course.feature
```

## Java versions

Not all java versions will work. For Ubuntu, `sudo apt install openjdk-11-jdk openjdk-11-jre` should do, but OpenJDK 17 will not work, for example.
If you have several versions installed, you can update the "active" version with `sudo update-java-alternatives -l` to see the possibilities, then `sudo update-java-alternatives -s java-1.11.0-openjdk-amd64` or something like that to set it. Beware this can have a big impact on other things you run with Java (like your IDE?) so maybe think about undoing this later on...
