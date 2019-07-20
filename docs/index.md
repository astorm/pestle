## Introduction

Pestle is two, (or maybe three), different things.

**Number 1**: Pestle is a set of code generation tools for the PHP framework that powers the open source version of Magento 2.

**Number 2**: Pestle is, itself, a framework for writing command line PHP programs and modules that can import **functions** from other programs and modules _w

**Number 3**: Pestle is how I, Alan Storm, organize many of the the one-off command line PHP Programs that have helped me throughout my career.

This documentation is also split into three sections.

First, we'll cover [Pestle's code and configuration generation ability w/r/t Magento two](https://pestle.readthedocs.io/en/latest/magento2-introduction/).

Second, we'll cover some of the [one-off Magento 2 scripts](https://pestle.readthedocs.io/en/latest/magento2-other-introduction/) that ship with pestle.  Many of these aren't as robust as they might be, but still offer useful functionality or a good start **towards** a more robust solution.

Finally, we'll close with some information about developing the core pestle code base itself -- both how to *use* pestle for your own command line programs, as well as work with the internals of pestle's framework code.

## Getting Started

The easiest way to get started is to grab the latest build using curl

    $ curl -LO http://pestle.pulsestorm.net/pestle.phar

Pestle is distributed as a PHP `phar` file -- [that's short for PHP archive](https://www.php.net/manual/en/book.phar.php).  A `phar` file allows you to bundle up a bunch of PHP and distribute it as a single program.  You should be able to run a `phar` with a specific version of PHP, *or* execute the `phar` file itself.

    $ php pestle.phar
    $ chmod +x pestle.phar
    $ ./pestle.phar
    $ mv pestle.phar /usr/local/bin
    $ pestle.phar                   # assumes /usr/local/bin is in your $PATH

Most of these docs will presume you're executing `pestle.phar` directly via a directory that's in your shell's path.

You can see a list of available commands with the following

    $ pestle.phar list-commands

and get help for a specific command (`magento2:generate:module` below) with

    $ pestle.phar help magento2:generate:module
