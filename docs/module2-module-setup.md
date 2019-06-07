## magento2:generate:module

TODO: WRITE THE DOCS!

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


## magento2:generate:registration

TODO: WRITE THE DOCS!

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


## magento2:generate:command

TODO: WRITE THE DOCS!

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


