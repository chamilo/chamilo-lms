In order to run behat tests locally with the right support for browser
and JS environments under Linux, you will need to:

- Have Java (or OpenJDK) installed.
```
# On Ubuntu, this might look like this:
sudo apt install openjdk-11-jre-headless
```

- Download Selenium Standalone Server v3.\*. Check http://www.seleniumhq.org/download/ for the latest version, download it and run it with the following command:
```
#Example:
wget https://selenium-release.storage.googleapis.com/3.141/selenium-server-standalone-3.141.59.jar
java -jar selenium-server-standalone-3.141.59.jar
```
This has to keep running for the duration of your tests, so launch it in a separate terminal, in a screen or in any equivalent context.

- Install google-chrome (stable version, not beta or dev).
```
# on Ubuntu, you might use something like this:
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add 
sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list'
sudo apt update
sudo apt install google-chrome-stable
# check in the download info which version of the browser you are downloading,
# as the chromedriver in the next step needs to be the same version
```

- Download the Chrome driver, unzip and copy into /usr/bin. Check the latest version at https://sites.google.com/chromium.org/driver/downloads,
then adapt the following command to the latest version:
```
cd /tmp && wget https://chromedriver.storage.googleapis.com/108.0.5359.71/chromedriver_linux64.zip && unzip chromedriver_linux64.zip && sudo mv chromedriver /usr/local/bin
```

- Test if chromedriver is working correctly, the result should be something like:

```
chromedriver --version
ChromeDriver 92.0.4515.43 (8c61b7e2989f2990d42f859cac71319137787cce-refs/branch-heads/4515@{#306})
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
../../vendor/behat/behat/bin/behat features/course.feature
```
