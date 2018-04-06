# Managing CSS and JavaScript in Chamilo.

The folder "assets" will be processed by the js library Webpack Encore and the result
will be saved in public/build folder.

In order to accomplish this task there are some prerequisites.

- Install yarn

Follow the installation instructions here https://yarnpkg.com/en/docs/install

After the installation run this command in the Chamilo root:

``yarn install``

yarn will read the dependencies in the **packages.json** file and save the dependencies in the
'node_modules' folder (this must not be committed to the Chamilo repository).

# Configuring Encore/Webpack

The behaviour of how packages will be processed is describe here: "webpack.config.js".

In order to process that file you can run:

Then to finally create the public/build contents you can run:

Compiles assets once:

``yarn run encore dev``

Recompile assets automatically when files change

``yarn run encore dev --watch``

Compile assets, but also minify & optimize them

``yarn run encore production``


For more detail information please visit:

https://symfony.com/doc/current/frontend.html
