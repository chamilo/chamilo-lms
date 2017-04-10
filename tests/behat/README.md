In order to run behat tests locally you need:

- Install Selenium 3
 
http://www.seleniumhq.org/download/

And run with:

```
java -jar /my-dir/selenium-server-standalone-3.1.0.jar
```

- Install Chrome driver, unzip and copy into /usr/bin

https://sites.google.com/a/chromium.org/chromedriver/downloads

```
 - wget https://chromedriver.storage.googleapis.com/2.27/chromedriver_linux64.zip && unzip chromedriver_linux64.zip && sudo mv chromedriver /usr/bin 
```

### Chamilo configuration

- An administrator user should be created with this parameters:
    - Username "admin" 
    - Password "admin"
    - First name John
    - Last name Doe
    - user_id = 1 

- Edit file tests/behat/behat.yml
  Update with your Chamilo local URL.
  
- The main platform language must be in English (platformLanguage = english)
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
../../vendor/behat/behat/bin/behat features/createCourse.feature
```