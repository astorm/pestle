Creating Pestle Modules and Commands

While pestle is most well known as a Magento 2 code generation tool, it started life as a way to manage large sets of command line programs in a way that made it easy to share code between the various programs.

This document will decribe how you can create your own pestle programs, as well as configure a location on your computer to serve as a _module source_.

## Creating your First Command

First, you'll want to create a folder somewhere on your computer where your pestle based programs will live.  This can be anywhere on your computer.  We'll use a folder named `pestle-programs`.

```
$ mkdir /path/to/pestle-programs
$ cd pestle-programs
```

Once you've created this folder and `cd`'ed into it, run the following pestle command.

### Interactive Invocation

    $ pestle.phar pestle:generate-command
    New Command Name? (foo_bar)] your-command-name
    Create in PHP Namespace? (Pulsestorm)] YourPhpNamespace
    Creating the following module

    //...

### Argument Invocation

    $ pestle.phar pestle:generate-command your-command-name YourPhpNamespace

The `pestle:generate-command` command will create a new pestle program, which we'll also refer to as a pestle module.  The above command will create your program at the following location, with some basic boilerplate code.

    $ cat modules/yourphpnamespace/yourcommandname/module.php
    <?php
    namespace YourPhpNamespace\Yourcommandname;
    use function Pulsestorm\Pestle\Importer\pestle_import;
    pestle_import('Pulsestorm\Pestle\Library\output');

    pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

    /**
    * One Line Description
    *
    * @command your-command-name
    */
    function pestle_cli($argv)
    {
        output("Hello Sailor");
    }

## Configuration a Module Source

If you try to run your new command, you'll run into a problem

    $ pestle.phar your-command-name
    Can't find [your_command_name] or [your-command-name] in Pulsestorm\Pestle\Runner\includeLibraryForCommand

We need to tell the `pestle.phar` runtime that this folder is a *module source*.  A module source is a folder on your computer that contains pestle module.

To configure your folder as a pestle module source, create or edit the following file in your home directory.

    #File: ~/.pestle/module-folders.json
    {
        "module-folders":[
            "/path/to/pestle/programs"
        ]
    }

This file contains a list of all the folders on your computer that contain pestle programs.  With the above file in place, try running your program again.

    $ pestle.phar your-command-name
    Hello Sailor

Congradulations, you just created your first pestle program.

## Anatomy of a Pestle Program

If we take a look at our program again.

    $ cat modules/yourphpnamespace/yourcommandname/module.php
    <?php
    namespace YourPhpNamespace\Yourcommandname;
    use function Pulsestorm\Pestle\Importer\pestle_import;
    pestle_import('Pulsestorm\Pestle\Library\output');

    pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

    /**
    * One Line Description
    *
    * @command your-command-name
    */
    function pestle_cli($argv)
    {
        output("Hello Sailor");
    }

We'll see a few things worth noting on.

First, every pestle program exists in its own namespace

    namespace YourPhpNamespace\Yourcommandname;

You pick the top-level namespace when you create your command, pestle generated the rest of it from your chosen command name.

Second, a module's namespace determines where it exists in a pestle module source.

   namespace YourPhpNamespace\Yourcommandname;
   modules/yourphpnamespace/yourcommandname/module.php

That is -- the file path to the program's `module.php` file is just a version of your program's namespace.

Third -- you'll use a PHP function named `Pulsestorm\Pestle\Importer\pestle_import` to import functions from other modules.

    use function Pulsestorm\Pestle\Importer\pestle_import;
    pestle_import('Pulsestorm\Pestle\Library\output');

The above code imports the `output` function from the `Pulsestorm\Pestle\Library` library/module/program.

Fourth -- the `pestle_cli` function is your program's main entry point.

    /**
    * One Line Description
    *
    * @command your-command-name
    */
    function pestle_cli($argv)
    {
        output("Hello Sailor");
    }

The `@command your-command-name` doc block is signifigant -- it determines the name of your command.  Without it, you won't be able to run your program

    $ pestle.phar your-command-name

The idealized workflow in a pestle program is to just start writing functions, classes, and whatever else your program needs in this single namespace.  For example, if you wanted to move the _Hello X_ message into a separate function, try modifying your program like this.

    /* ... */

    function getHelloMessage() {
        return "Hello Pestle";
    }

    /**
    * One Line Description
    *
    * @command your-command-name
    */
    function pestle_cli($argv)
    {
        output(
            getHelloMessage()
        );
    }

## Importing Functions from Other Modules

Let's try creating a second command --

    $ pestle.phar pestle:generate-command second-cmd YourPhpNamespace
    /* ... */

    $ pestle.phar second-cmd
    Hello Sailor

Where pestle starts to distinguish itself is in it's ability to selectivly share code between modules.  For example, if we wanted to use the `getHelloMessage` function in our first program in this program, we'd use the `pestle_import` function

    /* ... */

    pestle_import('YourPhpNamespace\Secondcmd\getHelloMessage');
    /**
    * One Line Description
    *
    * @command second-cmd
    */
    function pestle_cli($argv)
    {
        output("Hello Sailor");
        output(getHelloMessage());
    }

If we run our program, you'll see we're successfully imported `getHelloMessage` from our first program

    $ pestle.phar second-cmd
    Hello Sailor
    Hello Pestle

The `pestle_import` function accepts a string that's

1. A PHP namespace
2. Appended with function name

When you call `pestle_import`, you're asking pestle to import that function name from the provided PHP namespace.

    pestle_import('YourPhpNamespace\Secondcmd\getHelloMessage');
