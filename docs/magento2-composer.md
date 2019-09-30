@TODO: Look for pestle_dev
@TODO: don't use dev-master use '*'

# generate:register-package

    Usage:
        $ pestle.phar magento2:generate:register-package

    Arguments:

    Options:

    Help:
        This command will register a folder on your computers
        as the composer package for a particular module. This
        will tell pestle that files for this particular module
        should be generated in this folder.

        This command will also, if neccesary, create the module's
        registration.php file and composer.json file.

        If your module already has a composer.json, this command
        will look for a psr-4 autoload section for the module
        namespace.  If found, code will be generated in the
        configured folder. If not found, this command will add
        the `src/` folder as a psr-4 autoloader for your module
        namespace.

        If your module folder already has a regsitration.php file
        and it does not actually registers a module by the name
        you've indicated, this command will exit.

        @command magento2:generate:register-package
        @argument module What Magento module are you registering?
        [Pulsestorm_HelloWorld]
        @argument path Where will this module live? [/path/to/module/folder]
        @option package Composer package name to generate?
        @option quiet Disables Output

The `magento2:generate:register-package` command allows you to tell pestle that code for certain modules should be created in a composer package folder outside of the `app/code` folder.  You can use the command to register a _new_ folder for a module, or you can use the command to point pestle at a folder that already contains a composer package.

## Interactive Invocation

    pestle.phar magento2:generate:register-package Pulsestorm_HelloWorld
    What Magento module are you registering? (Pulsestorm_HelloWorld)]
    Where will this module live? (/path/to/module/folder)] extensions/pulsestorm-helloworld

## Argument Invocation

    pestle.phar magento2:generate:register-package Pulsestorm_HelloWorld extensions/pulsestorm-helloworld

## History

Magento 1 did not formally support composer.  Magento 1 was distributed exclusively via archives.  Community packages were distributed by a custom package management system called Magento Connect (based on PHP PEAR, although that story gets complicated quickly), and commercial extension vendors were responsible for distributing their own packages -- usually via a tar archive.

At its inception, Magento 2 tried to have it both ways and

1. Have a system that worked like Magento 1
2. But was also distributable via composer

This means there's _two_ ways to install Magento modules into a system.  The first is similar to Magento 1 -- place them in the `app/code` folder and Magento will find them.

Your second option is to create a composer package with a special `autoloader` section that loads in a Magento 2 module's `registration.php` file.  Put you module in a _composer repository_, run `composer require your/module-id` and you're good to go.

While this new option has made is easier to control the distribution of Magento module, it's made life more difficult for developers.  Should be be developing out of `app/code`, or out of a composer package?  If a composer package -- does that mean we need to commit to a repository before _every code change_?  Usually your best best is to follow the vendor's practice -- but as platform owner Magento gets to have it both ways: Magento modules [are developed in `app/code`](https://github.com/magento/magento2/tree/2.3-develop/app/code/Magento) -- but then a closed source process publishes them to the private `repo.magento.com` composer repository.  While this works for a multi-national software company eye acquisition by Adobe, it's a bit of a burden on the traditional Magento merchant or agent user.

One technique that a group of Magento developers have come up with is to use a [_path based_ composer repository](https://getcomposer.org/doc/05-repositories.md#path).  One of the really smart things the composer developers did was make the idae of a "repository" super abstract -- repositories can be a central thing like packagist.org, a version control repository, a web server of archive files, or even a simple path on your computer.

The `magento2:generate:register-package` command allows you to develop out of a composer path repository.  The rest of this document will explain how to do that.

## Register a New Module

We'll start by registering a folder for a new module.  Our first step has nothing to do with pestle: We need to create a folder in our Magento project for our module, and then tell composer about it.  To do this, run the following command from the root of your project

    $ mkdir -p extensions/pulsestorm-helloworld

And then _add_ the following top level configuration to your `composer.json` file.

    // File: composer.json

    /* ... */
    "repositories": [
        /* ... */
        {
            "type": "path",
            "url": "extensions/pulsestorm-helloworld"
        }
        /* ... */
    ]
    /* ... */

What we've done above is tell composer that the folder `extensions/pulsestorm-helloworld` [is a composer repository](https://getcomposer.org/doc/05-repositories.md).

**Important:** This folder needs to be under your Magento root folder.  While composer supports absolute file paths for path based repositories, **Magento** will get confused if you try to have a module outside of its root folder.  This folder can be named anything you like, but it's a good idea to develop a naming scheme like the one we have above so you can find individual module more easily in the future.

## Register our Folder

Next, we'll want to tell pestle to use this folder for our new module.  Run the following command.

    $ pestle.phar magento2:generate:register-package Pulsestorm_HelloWorld extensions/pulsestorm-helloworld
    Don't forget to add the following to your project's composer.json file.

    {
        "repositories": [
            /*...*/,
            {
                "type":"path",
                "url":"extensions/pulsestorm-helloworld"
            }
        ]
    }

    and to install/require your module, which will create a symlinkin your `vendor/` folder

    composer require pulsestorm/helloworld '*'

    Edited:
        Pulsestorm_HelloWorld=>extensions/pulsestorm-helloworld
        in package-folders


This command will have do things.  First, since `extensions/pulsestorm-helloworld` was an empty folder, it will create a skeleton `composer.json` file for you.

    $ cat extensions/pulsestorm-helloworld/composer.json
    {
        "name": "pulsestorm/helloworld",
        "description": "A Magento Module",
        "type": "magento2-module",
        "minimum-stability": "stable",
        "require": {},
        "autoload": {
            "files": [
                "registration.php"
            ],
            "psr4": {
                "Pulsestorm\\HelloWorld\\": "src/"
            }
        }
    }

The second this command does is add this module/path to a configuration file

    $ cat ~/.pestle/package-folders.json
    {
        "Pulsestorm_HelloWorld":"extensions\/pulsestorm-helloworld"
    }

This configuration file is what tells pestle where to generate code.

We should now be able to run some pestle generation commands targeting the `Pulsestorm_HelloWorld` module.

    $ pestle.phar magento2:generate:module
    Vendor Namespace? (Pulsestorm)]
    Module Name? (Testbed)] HelloWorld
    Version? (0.0.1)]
    Creating [/path/to/magento2/extensions/pulsestorm-helloworld/src/etc]
    Created: /path/to/magento2/extensions/pulsestorm-helloworld/src/etc/module.xml
    Created: /path/to/magento2/extensions/pulsestorm-helloworld/registration.php

and see that our files _are not_ generate in `app/code` -- instead they're generated in the `extensions/pulsestorm-helloworld` folder.

## Requiring the Package

We now have a composer package, in our path based repository, and our project knows about this path based repository.  There's one last step we'll need to take for Magento to see our module: we need to require the package into our project

    $ composer require pulsestorm/helloworld '*'

Running the above command tells composer to look for the `...` composer package and require it in the project.  When composer finds a module in a path repository, it will _symlink_ a folder in vendor.  You can see this by running the following after composer is done.

    $ ls -lh vendor/pulsestorm/
    total 0
    lrwxr-xr-x  1 user  group    38B Sep 29 13:32 helloworld -> ../../extensions/pulsestorm-helloworld

The `*` is required to ensure composer finds your package irrespective of its version and your project's stability settings.  This comes right from the composer docs [on path repositories](https://getcomposer.org/doc/05-repositories.md#path), when you can  see the `*` used in place of a package version for `path` repositories.

**Important:** This symlink is the reason we need our path repository to be under the Magento root. Magento gets very confused if you try to symlink certain module folders outside of your project root.  You will also want to make sure that

    Stores -> Configuration

is enabled on your system if you're developing out of a path based repository folder -- otherwise your templates may not render correctly.

Congratulations!  You're now working with a local, composer based, module.

## Registering existing project

This technique isn't limited to from scratch modules -- you can use it with any modules that includes a Magento `composer.json` file.

Let's consider this [randomly chosen Magento 2 module related to SMTP](https://github.com/mageplaza/magento-2-smtp/).  If we were going to add features to this module with pestle and manage it as a composer module, first we'd configure a path repository for it in our Magento system's `composer.json` file

    #File: compser.json

    "repositories": [
        /* ... */
        {
            "type": "path",
            "url": "extensions/magento-2-smtp"
        }
    ],

Then, we'll clone the project repository to get a copy of the source locally.

    $ mkdir extensions
    $ git clone git@github.com:mageplaza/magento-2-smtp.git extensions/magento-2-smtp

Once we have the module downloaded, we'll confirm the composer package name by looking at the package's `composer.json` file

    $ cat extensions/magento-2-smtp/composer.json | grep 'name'
        "name": "mageplaza/module-smtp",

And then use that package name (`mageplaza/module-smtp`) to require the main project.

    $ cd /path/to/magento
    $ composer require mageplaza/module-smtp '*'

Once we've done this, we'll confirm that the module is symlinked.

    $ ls -l vendor/mageplaza/
    total 0

    lrwxr-xr-x   1 alanstorm  staff   31 Sep 30 08:34 module-smtp -> ../../extensions/magento-2-smtp

This step is extra important for a module that's also host up on packagist.org.  If you've incorrectly configured your `path` repository composer will still successfully install the package -- it just won't have your version installed.

Finally, with all the above done, we'll register the package folder with pestle.

    $ pestle.phar magento2:generate:register-package Mageplaza_Smtp extensions/magento-2-smtp/

and then start generating code



- Intro
- Checkout GitHub Repo
- Register folder
- Run some commands

## Command Internals

- Cover configuration
- Cover single module path function
- Cover generated composer.json
- Cover Autoloader
