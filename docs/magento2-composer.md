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

    //...

## Argument Invocation

    //...

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

    ...

And then _add_ the following top level configuration to your `composer.json` file.

    ...

What we've done above is tell cmposer that the folder `...` [is a composer repository](https://getcomposer.org/doc/05-repositories.md).

**Important:** This folder needs to be under your Magento root folder.  While composer support absolute file paths for path based repositories, **Magento** will get super-confused if you try to have a module outside of its root folder.  This folder can be named anything you like, but it's a good idea to develop a naming scheme like the one we have above so you can find individual module more easily in the future.

## Register our Folder

Next, we'll want to tell pestle to use this folder for our new module.  Run the following command.

    //...

This command will have two things.  First, since `...` was an empty folder, it will create a skeleton `composer.json` file for you.

    //...

Congratulations!  Your `path` repository now has a composer package inside of it.

The second this command does is add this module/path to a configuration file

    //...

This configuration file is what tells pestle where to generate code.

We should now be able to run some pestle generation commands.

    //...

and see that our files _are not_ generate in `app/code` -- instead they're generated in `...`

## Requiring the Package

We now have a composer package, in our path baed repository, and our project knows about this path based repository.  There's one last step we'll need to take for Magento to see our module: we need to require the project into our project

    //...

Running the above command tells composer to look for the `...` composer package and require it in the project.  When composer finds a module in a path repository, it will _symlink_ a folder in vendor.  You can see this by running the following after composer is done.

    //...

The `dev-master` is required to ensure composer finds your package irrespective of its version and your project's stability settings.  You don't _need_ to use it, but if you don't you'll need to manage your module's version numbers.

**Important:** This symlink is the reason we need our path repostiroy to be under the Magento root. Magento gets very confused if you try to symlink certain module folders outside of your project root.  You will also want to make sure that

    //...

is enabled on your system if you're developing out of a path based repository folder.

Congratulations!  You're now working with a local, composer based, module.

## Registering existing project

- Intro
- Checkout GitHub Repo
- Register folder
- Run some commands

## Command Internals

- Cover configuration
- Cover single module path function
- Cover generated composer.json
- Cover Autoloader
