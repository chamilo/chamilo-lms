In order to run tests locally:

- An administrator user should be created with:
Username "admin" and password "admin".  

- Edit file tests/behat/behat.yml
  An update your Chamilo URL.
  
- The main platform language must be in English.

After executing a composer update 

Run:

```
cd tests/behat
 ../../vendor/behat/behat/bin/behat -v
 ```
 
Or for a specific feature:

```
../../vendor/behat/behat/bin/behat features/createCourse.feature
```