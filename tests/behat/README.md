In order to run behat tests locally with the right support for browser
and JS environments under Linux, you will need to:

- Download the Chrome driver, unzip and copy into /usr/bin

Check the latest `chromedriver` version at [https://googlechromelabs.github.io/chrome-for-testing/](https://googlechromelabs.github.io/chrome-for-testing/) that matches your configuration (linux64 for Ubuntu), then adapt the following command to the latest version. Use a version that matches your version of the Chrome browser.

```
cd /tmp && wget https://storage.googleapis.com/chrome-for-testing-public/143.0.7499.40/linux64/chromedriver-linux64.zip && unzip chromedriver_linux64.zip && sudo mv chromedriver_linux64/chromedriver /usr/local/bin/
```

- Have the Google Chrome browser installed (or any other browser that supports headless mode, but instructions and configurations below will change accordingly).

### Chamilo configuration

- An administrator user should be created with these parameters:
    - Username "admin"
    - Password "admin"
    - First name "John"
    - Last name "Doe"
    - user_id = 1 (this one is set when you install Chamilo, but just in case...)

- Edit the tests/behat/behat.yml file and update the base_url param with your own Chamilo local URL and make sure your machine understands it (e.g. add it to your /etc/hosts file).
- The main platform language and the admin user's language must be English (`platform_language = 'en_US'` in the `settings` table and admin user profile)
- Social network tool must be available (`allow_social_tool = 'true'` in the `settings` table)
- Student can register to the system (`allow_registration = 'true'` in the `settings` table)
- Teacher can register to the system (`allow_registration_as_teacher = 'true'` in the `settings` table)

### Launch the chromedriver
```
chromedriver --port=4444
```
If you want to run the tests with a different base_url (see behat.yml), you will need to ask chromedriver to allow calls from remote IPs. This has serious security implications if the testing machine is exposed to the internet, so please be careful.
```
chromedriver --port=4444 --allowed-origins='*' --allowed-ips={your-ip-on-the-local-network}
```

### Run tests

To run all features (including the installation of Chamilo - which you might want to disable in features/actionInstall.feature if you already have Chamilo installed):

```
# /var/www/html/chamilo
cd tests/behat
 ../../vendor/behat/behat/bin/behat -v
 ```

To run a specific feature:
```
../../vendor/behat/behat/bin/behat features/course.feature
```
