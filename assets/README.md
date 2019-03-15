# Managing CSS and JavaScript in Chamilo.

The folder "assets" will be processed by the js library Webpack Encore and the result will be saved in public/build folder.

For this, we first need to install yarn. 
Yarn is a JavaScript dependencies manager similar to Bower (that we also used for development in Chamilo 1.11.x), 
only that Bower is deprecated now.

To install yarn, follow the installation instructions here https://yarnpkg.com/en/docs/install

After the installation run this command in the Chamilo root:

``yarn install``

yarn will read the dependencies in the **packages.json** file and save the dependencies in the 'node_modules' folder (which must **NOT** be committed** to the Chamilo repository).

# Configuring Encore/Webpack

Webpack takes CSS, JS and other files and generates tidy single-files to attach to your web package.

The behaviour of how packages will be processed is describe here: "webpack.config.js".

If the public/js/fos_js_routes.json file does not exist (or if you're in doubt about the version of your PHP libs), run:
```
composer update
bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

Then, to create the public/build contents, run one of the following commands:
To compile assets just once:

``yarn run encore dev``

To compile assets and minify & optimize them:

``yarn run encore production``

For more detail information please visit:

https://symfony.com/doc/current/frontend.html
