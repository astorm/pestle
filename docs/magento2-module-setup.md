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

**Further Reading**

-  [Introduction to Magento 2 — No More MVC](https://alanstorm.com/magento_2_mvvm_mvc/)

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

The `magento2:generate:registration` command will geneate the contents of a Magento 2 Module registration.php file and send it to the standard output device (i.e. print it to your screen).

**Interactive Invocation**

    $ pestle.phar magento2:generate:registration
    Which Module? (Vendor_Module)] Pulsestorm_Pestle
    <?php
        \Magento\Framework\Component\ComponentRegistrar::register(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            'Pulsestorm_Pestle',
            __DIR__
        );

**Argument Invocation**

    $ pestle.phar magento2:generate:registration Pulsestorm_Pestle
    <?php
        \Magento\Framework\Component\ComponentRegistrar::register(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            'Pulsestorm_Pestle',
            __DIR__
        );

The `magento2:generate:registration` command probably isn't releavnt to most modern Magento 2 workflows.  It exists because the `registration.php` files became a part of Magento relatively late, and I needed a way to add a bunch of registration.php files to modules quickly and accurately.

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

Magento 2 ships with an unnamed command line application that's usually referred to as either `console`, `bin/console`, or (in more modern versions of Magento 2), `bin/magento`.  The command line application is based on Symfony Console, and contains a number of commands related to developing and administering your Magento 2 system.

The `magento2:generate:command` command will generate a PHP class file that adds a new command to this `console` application.

**Interactive Invocation**

```
$ pestle.phar magento2:generate:command
In which module? (Pulsestorm_Helloworld)] Pulsestorm_Pestle
Command Name? (Testbed)] Yourcommand
/path/to/m2/app/code/Pulsestorm/Pestle
```

**Argument Invocation**

```
pestle.phar magento2:generate:command Pulsestorm_Pestle Yourcommand
/path/to/m2/app/code/Pulsestorm/Pestle
```

The first argument is the name of the module you want to to generate a command in.  The second argument is the short class name for your command class.

The above invocations would generate the following class file.

```
    $ cat app/code/Pulsestorm/Pestle/Command/Yourcommand.php
    <?php
    namespace Pulsestorm\Pestle\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class Yourcommand extends Command
    {
        protected function configure()
        {
            $this->setName("ps:yourcommand");
            $this->setDescription("A command the programmer was too lazy to enter a description for.");
            parent::configure();
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $output->writeln("Hello World");
        }
    } $
```

And either generate or edit following `di.xml` configuration file in order to add the class as a `console` command.

    $ cat app/code/Pulsestorm/Pestle/etc/di.xml
    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
        <type name="Magento\Framework\Console\CommandList">
            <arguments>
                <argument name="commands" xsi:type="array">
                    <item name="pulsestorm_pestle_command_yourcommand" xsi:type="object">Pulsestorm\Pestle\Command\Yourcommand</item>
                </argument>
            </arguments>
        </type>
    </config>

By default, your command will be named `ps:yourcommand`.

    $ php bin/magento ps:yourcommand
    Hello World

You'll probably want to change this by editing the following line of your class file.

    $ cat app/code/Pulsestorm/Pestle/Command/Yourcommand.php
    $this->setName("ps:yourcommand");   // remove ps:your command and
                                        // add your own name
