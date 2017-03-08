In order to run tests locally:

- An administrator user should be created with:
    - Username "admin" 
    - Password "admin"
    - First name John
    - Last name Doe
    - user_id = 1 

- Edit file tests/behat/behat.yml
  Update with your Chamilo local URL.
  
- The main platform language must be in English.

- Social network tool must be available.

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

Run:

```
cd tests/behat
 ../../vendor/behat/behat/bin/behat -v
 ```
 
Or for a specific feature:

```
../../vendor/behat/behat/bin/behat features/createCourse.feature
```