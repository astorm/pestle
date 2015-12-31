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

We don't have a good story for new user setup, the project's still in the "crappy first draft" stage where we're meandering towards a final goal.  For now dropping the following in your `~/bin`

    #File: ~/bin/mage2
    
    #!/usr/bin/env php
    <?php
    require_once('/path/to/pestle/runner.php');    
    
should be enough to get you going.  

Use at your own risk, more to come, etc.

    