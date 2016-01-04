[![Build Status](https://travis-ci.org/astorm/pestle.svg?branch=master)](https://travis-ci.org/astorm/pestle)

You probably don't want to be here.  This project is in the very early stages.  Things are broken, everything will change.  Havoc will be wrought, etc.

What is Pestle?
--------------------------------------------------
Pestle is

- A PHP Framework for creating and organizing command line programs
- An experiment in implementing python style module imports in PHP
- A collection of command line programs, with a primary focus on Magento 2 code generation

Pestle grew out of my desire to do something about the growing number of simple PHP scripts in my `~/bin` that didn't have a real home, and my personal frustration with the direction of PHP's namespace system. 

PHP doesn't **need** another command line framework.  Symfony's console does a fine job of being the de-facto framework for building modern PHP command line applications.  Sometimes though, when you start off building something no one needed, you end up with someone nobody realized they wanted. 

How to Use
--------------------------------------------------
Can you not read?  The havoc furnaces having been building throughout the night.

We've got a `phing` `build.xml` file setup now, so all you *should* need to do to build a stand along `pestle.phar` executable is 

- `git checkout git@github.com:astorm/pestle.git`
- composer.phar install
- ./build.sh (which, in turn, calls the `phing` job that builds the `phar`

If you're not sure what to do with a `phar`, we may not be ready for you yet.

If you're interested in working on the framework itself, the `runner.php` file in the root is where you want to be.  I personally use it by dropping the folloing in my `~/bin`

    #File: ~/bin/pestle_dev
    #!/usr/bin/env php
    <?php
    require_once('/Users/alanstorm/Documents/github/astorm/pestle/runner.php');    

Example Command
--------------------------------------------------

Try 

    $ pestle.phar generate_module
    
from a Magento 2 sub-directory to get an idea of what we're doing here.  
