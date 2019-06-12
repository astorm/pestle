## generate:module

    Usage:
        $ pestle.phar magento2:generate:module

    Arguments:

    Options:

    Help:
        Generates new module XML, adds to file system
        This command generates the necessary files and configuration
        to add a new module to a Magento 2 system.

        pestle.phar magento2:generate:module Pulsestorm TestingCreator 0.0.1

        @argument namespace Vendor Namespace? [Pulsestorm]
        @argument name Module Name? [Testbed]
        @argument version Version? [0.0.1]
        @command magento2:generate:module

The `magento2:generate:module` command is the first you'll run when creating a new module from scratch.  It creates the basic folder structure and the `module.xml` and `registration.php` file needed to add a code module to Magento 2.

**Interactive Invocation**

    $ pestle.phar magento2:generate:module
    Vendor Namespace? (Pulsestorm)] Pulsestorm
    Module Name? (Testbed)] Pestle
    Version? (0.0.1)] 0.0.1

    Created: /path/to/m2/app/code/Pulsestorm/Pestle/etc/module.xml
    Created: /path/to/m2/app/code/Pulsestorm/Pestle/registration.php

**Argument Invocation**

    $ pestle.phar magento2:generate:module Pulsestorm Pestle 0.0.1

    Created: /path/to/m2/app/code/Pulsestorm/Pestle/etc/module.xml
    Created: /path/to/m2/app/code/Pulsestorm/Pestle/registration.php

The `magento2:generate:module` command asks for a package name, a short module name, and a version number.  It will create the long standing `Packagename/Modulename` folder structure in `app/code`, and also create an `etc/module.xml` and `regsitration.php` file.

As of this writing, this command **does not** create a `composer.json` file for you, as this is not required to run your module from `app/code`.

## generate:registration

    Usage:
        $ pestle.phar magento2:generate:registration

    Arguments:

    Options:

    Help:
        Generates registration.php
        This command generates the PHP code for a
        Magento module registration.php file.

        $ pestle.phar magento2:generate:registration Foo_Bar
        <?php
        \Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        'Foo_Bar',
        __DIR__
        );

        @command magento2:generate:registration
        @argument module_name Which Module? [Vendor_Module]

TODO: WRITE THE DOCS!

## generate:command

    Usage:
        $ pestle.phar magento2:generate:command

    Arguments:

    Options:

    Help:
        Generates bin/magento command files
        This command generates the necessary files and configuration
        for a new command for Magento 2's bin/magento command line program.

        pestle.phar magento2:generate:command Pulsestorm_Generate Example

        Creates
        app/code/Pulsestorm/Generate/Command/Example.php
        app/code/Pulsestorm/Generate/etc/di.xml

        @command magento2:generate:command
        @argument module_name In which module? [Pulsestorm_Helloworld]
        @argument command_name Command Name? [Testbed]

TODO: WRITE THE DOCS!
