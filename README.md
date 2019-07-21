[![Build Status](https://travis-ci.org/astorm/pestle.svg?branch=master)](https://travis-ci.org/astorm/pestle)
[![Documentation Status](https://readthedocs.org/projects/pestle/badge/?version=latest)](https://pestle.readthedocs.io/en/latest/?badge=latest)

What is Pestle?
--------------------------------------------------
Pestle is

- A PHP Framework for creating and organizing command line programs
- An experiment in implementing python style module imports in PHP
- A collection of command line programs, with a primary focus on Magento 2 code generation

Pestle grew out of my desire to do something about the growing number of simple PHP scripts in my `~/bin` that didn't have a real home, and my personal frustration with the direction of PHP's namespace system.

PHP doesn't **need** another command line framework.  Symfony's console does a fine job of being the de-facto framework for building modern PHP command line applications.  Sometimes though, when you start off building something no one needed, you end up with something nobody realized they wanted.

How to Use
--------------------------------------------------
The easiest way to get started is to grab the latest build using curl

    curl -LO http://pestle.pulsestorm.net/pestle.phar

You can see a list of commands with the following

    php pestle.phar list-commands

and get help for a specific command with

    php pestle.phar help generate_module

If you want to build your own `phar`, we've got a `phing` `build.xml` file setup so all you *should* need to do to build a stand alone `pestle.phar` executable is

- `git checkout git@github.com:astorm/pestle.git`
- composer.phar install
- ./build.sh (which, in turn, calls the `phing` job that builds the `phar`

If you're interested in working on the framework itself, you can use the `runner.php` in the project root.  I personally use it by dropping the following in my `~/bin`.

    #File: ~/bin/pestle_dev
    #!/usr/bin/env php
    <?php
    require_once('/Users/alanstorm/Documents/github/astorm/pestle/runner.php');

Troubleshooting Upgrades
--------------------------------------------------
If you've upgraded pestle and you're seeing the following exception

> PHP Fatal error:  Cannot redeclare Pulsestorm\Magento2\Cli\Help\pestle_cli()

Try removing the following temp folder.

    /tmp/pestle_cache

We know this isn't ideal, and we're working on a more permanat fix.


Example Command
--------------------------------------------------

Try

    $ pestle.phar generate_module

from a Magento 2 sub-directory to get an idea of what we're doing here.

How to Use Pestle Code in your Application
--------------------------------------------------
Pestle and the `pestle_import` function are a bit of an experiment, and you probably don't want to run code from `module.php` files directly in your PHP based application.  Fortunately, we have a solution for you -- with every release of pestle we build a composer compatible autoloader in `library/autoloader.php`. This loads the entire pestle library structure as a single PHP file with proper block-namespaces (currently `library/all.php`).  This means you can include pestle in your Composer based projects with

    "require": {
        "pulsestorm/pestle": "1.0.*"
    }

And then import pestle code via native PHP namespaces to your heart's content.

    //include is probably not neccesary, usually handled by your framework
    include 'vendor/autoload.php';
    \Pulsestorm\Pestle\Library\output("Hello World");

Our specific strategy around this may change in the future, but our plan is for these sorts of changes to be user-transparent.  If we ever split the generated library into multiple files, or figure out a sane way to incorporate `pestle_import` into native PHP code and you're using this project as a composer library — those changes should be transparent to you.

Do you have strong options about this sort of compilation/"transpiling"/module-importing?  We'd love to have you involved in the project. Yell at us in a GitHub issues and/or pull request.

Want to learn more?  We'll [be using the wiki](https://github.com/astorm/pestle/wiki) for documentation until we outgrow it.

Experimental Tab Completion
--------------------------------------------------
Pestle includes an experimental [tab completion script](https://github.com/astorm/pestle/blob/master/pestle-autocomplete.sh).  If used with your system's `bash_completion` sub-system, this script will allow use the `[tab]` key to auto-complete command names.

    $ pestle.phar magento2:generate:ui: (press the tab key)
    add-column-text    add-schema-column  form
    add-form-field     add-to-layout      grid

Just copy or symlink the [`pestle-autocomplete.sh`](https://github.com/astorm/pestle/blob/master/pestle-autocomplete.sh) file to your `bash_completion.d` folder and you'll be good to go.

If you're running MacOS or MacOS X, you'll need to install the modern version of `bash_completion` via Homebrew (or your package manager of choice).  Yes, this is super annoying.  We [found these instructions](https://www.simplified.guide/macos/bash-completion) useful in late mid-2018.  The simplified instructions are

1. Install [Homebrew](https://brew.sh/)
2. Run `$ brew install bash-completion` to install the bash-completion package
3. Enable the completion scripts by running `$ . /usr/local/etc/bash_completion` -- optionally adding this command (or a similar one) to your `.bash_profile`
