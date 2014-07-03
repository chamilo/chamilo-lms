MopaBootstrapBundle Twitters Bootstrap integration
==================================================

We decided not to take the twitter/bootstrap distribution into this repo to seperate concerns more efficently.
So you have to include twitter/bootstrap in some manner into your project, here are some examples on howto do it

Since symfony2.1 will use composer (http://www.getcomposer.org) to organize dependencies, it is highly recommended to ease your life to do it the recommended way

## Include in your project composer.json (RECOMMENDED):

### Managing twitter/bootstrap installation automatically

To have composer managing twitter/bootstrap too, you can either run it with
--install-suggests or add the following to your composer.json (recommended):

```json
{
    "require": {
        "mopa/bootstrap-bundle": "v3.0.0-beta2",
        "twitter/bootstrap": "v3.0.0"
    }
}
```

       
<h2 id="Warning">Warning</h2>
> Composer doesn't install suggests from mopa/bootstrap-bundle!
> If you need e.g knplabs menues or paginator, craue/formflow, 
> please add them to YOUR composer.json too!

```json
   {
       "require": {
           "mopa/bootstrap-bundle": "dev-master",
           "twitter/bootstrap": "master",
           "knplabs/knp-paginator-bundle": "dev-master",
           "knplabs/knp-menu-bundle": "dev-master",
           "craue/formflow-bundle": "dev-master"
       },
   }
```

To activate auto symlinking and checking after composer update/install add also to your existing scripts:

```json
{
    "scripts": {
        "post-install-cmd": [
            "Mopa\\Bundle\\BootstrapBundle\\Composer\\ScriptHandler::postInstallSymlinkTwitterBootstrap"
        ],
        "post-update-cmd": [
            "Mopa\\Bundle\\BootstrapBundle\\Composer\\ScriptHandler::postInstallSymlinkTwitterBootstrap"
        ]
    }
}
```

For Sass support, you can also use the specific command:

```json
{
    "scripts": {
        "post-install-cmd": [
            "Mopa\\Bundle\\BootstrapBundle\\Composer\\ScriptHandler::postInstallSymlinkTwitterBootstrapSass"
        ],
        "post-update-cmd": [
            "Mopa\\Bundle\\BootstrapBundle\\Composer\\ScriptHandler::postInstallSymlinkTwitterBootstrapSass"
        ]
    }
}
```
 

### (NOT RECOMMENDED) Including Bootstrap manually

To use bootstrap without less just download the zipped distribution

 http://getbootstrap.com/
 
 and unpack it e.g.
 
 in app/Resources/public/

