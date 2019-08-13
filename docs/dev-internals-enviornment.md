# Getting Started with Pestle Internals

So -- you want to work on pestle's core features?  Or perhaps you're just Alan, setting up a new computer, and full of regret about how you started this project.

Regardless -- this document will get you up and running and ready to contribute to pestle's core code.

## Check out the Source, Install the Dependencies

Step 1!  Pestle's source is [kept on GitHub](https://github.com/astorm/pestle).  The first thing you'll want to do is clone this repository.  You'll need [to have git installed](https://help.github.com/en/articles/set-up-git) and then run one of the following (depending on whether you're [cloning with http](https://help.github.com/en/articles/which-remote-url-should-i-use/#cloning-with-https-urls-recommended) via [ssh](https://help.github.com/en/articles/which-remote-url-should-i-use#cloning-with-ssh-urls))

```
$ git clone https://github.com/astorm/pestle

// or

$ git clone git@github.com:astorm/pestle.git
Cloning into 'pestle'...
remote: Enumerating objects: 90, done.
remote: Counting objects: 100% (90/90), done.
remote: Compressing objects: 100% (57/57), done.
remote: Total 4531 (delta 42), reused 52 (delta 27), pack-reused 4441
Receiving objects: 100% (4531/4531), 1.10 MiB | 1005.00 KiB/s, done.
Resolving deltas: 100% (2133/2133), done.
Checking connectivity... done.
```

Once you've cloned the repository you'll need to [install composer](https://getcomposer.org/download/), and then use composer to install pestle's dependencies.

```
$ cd pestle
$ composer install
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
// ...
Generating autoload files
```

Composer will run for a bit of time (between 20 seconds and 10 minutes, depending on the state of your network and composer cache), and will install the dependencies to the `vendor/` folder.

With this done, you'll have a fully downloaded pestle source!

## Running Development Programs

When you're working on pestle's source, you'll need a way to invoke pestle that points at the source repo instead of pointing at the code in the standard `pestle.phar` file.  There's a number of ways to do this -- but here's what we recommend.

Within the root pestle folder, there's a script named `pestle_dev`.  You can use this to invoke pestle and it it load code from the cloned repository.

```
$ ./pestle_dev
                  _   _
                 | | | |
  _ __   ___  ___| |_| | ___
 | '_ \ / _ \/ __| __| |/ _ \
 | |_) |  __/\__ \ |_| |  __/
 | .__/ \___||___/\__|_|\___|
 | |
 |_|
pestle by Pulse Storm LLC

Usage:
  pestle command_name [options] [arguments]

Available commands:
//...
```

Depending on what you're doing, this may be all your need to work on your feature.

However, if you need to work with the development version of pestle **outside** of your project directory, then we recomend

1. Creating a new PHP shell script somewhere in your unix path (`/usr/local/bin`, `~/bin`, `etc`)

2. Have this PHP shell script point to the `runner.php` with an absolute path.

The `runner.php` file has the PHP code needed to bootstrap pestle's enviornment.

So, your shell might look something like this after creating the file for this new shell script.

```
$ pwd
/path/to/pestle-internals/pestle

$ touch /usr/local/bin/pestle_dev
$ chmod +x /usr/local/bin/pestle_dev
$ which pestle_dev
/usr/local/bin/pestle_dev
```

and then you would add the following code to the `/usr/local/bin/pestle_dev` file

```
#!/usr/bin/env php
<?php
require_once('/path/to/pestle-internals/pestle/runner.php');
```

With this script in place, (and in your unix path), you should be able to invoke the development version of pestle from any folder.

```
$ cd ~/Sites/some-magento-system.localhost
$ pestle_dev
                  _   _
                 | | | |
  _ __   ___  ___| |_| | ___
 | '_ \ / _ \/ __| __| |/ _ \
 | |_) |  __/\__ \ |_| |  __/
 | .__/ \___||___/\__|_|\___|
 | |
 |_|
pestle by Pulse Storm LLC

Usage:
  pestle command_name [options] [arguments]

Available commands:
//...
```

## Running Unit Tests

You've got your code checked out.  You can invoke a version of pestle that uses the source code.  Next up?  You'll need to know how to run Pestle's tests.

Pestle uses the ["somehow it's still free" Travis CI](https://travis-ci.org/) as its continuous integration system.  i.e. Travis is what runs Pestle's tests automatically before and after every code checkin.  This means the `.travis.yml` in the project root will always have the most up to date information on running Pestle's tests.

As of this writing, you can run Pestle's units tests by invoking the following

```
$ vendor/bin/phpunit tests
PHPUnit 4.8.36 by Sebastian Bergmann and contributors.

................................................................. 65 / 88 ( 73%)
.......................

Time: 2.7 seconds, Memory: 8.00MB

OK (88 tests, 95 assertions)
```

We're also experimenting with including a test script in `composer.json`, mimicking a pattern that's common in npm based javascript development.

```
$ composer.phar test
PHPUnit 4.8.36 by Sebastian Bergmann and contributors.

//...
```

## Running Integration Tests

The bitrot in the integration tests integration tests is real.  When we [get around to fixing that](https://github.com/astorm/pestle/issues/471), we'll update this section

## Writing Unit Tests

Pestle uses [the PHPUnit framework](https://phpunit.de/) for its unit tests. To create a new test, pick a class-name, and then create a PHP Unit class file in the `tests` folder that looks something like this

``` php
    #File: tests/YourClassNameTest.php
    <?php
    namespace Pulsestorm\Pestle\Tests;
    require_once 'PestleBaseTest.php';
    use function Pulsestorm\Pestle\Importer\pestle_import;

    class YourCassNameTest extends PestleBaseTest {
        public function setupTest() {
            $this->assertEquals(
                'A simple test, to see that everything is running right.',
                'A simple test, to see that everything is running right.'
            );
        }
    }
```

All pestle tests extend the `Pulsestorm\Pestle\Tests\PestleBaseTest` class, and this class needs to be manually `require_once`d in.  This bit of non-elegance is due, in part, to the fact that Pestle was being worked on while PHP was still getting PSR-0 and PSR-4 sorted out.  We regret the decision and [are working on a fix](https://github.com/astorm/pestle/issues/470).

The test runner and pestle are smart enough to use the `pestle_import` function to pull in individual functions from pestle's module sources.  This means you can use `pestle_import` to pull in a function and test it.  You can see an example of this here, where we're testing the standard pestle `output` function.

```php
#File: pestle/tests/NamespaceRefactoringTest.php
use function Pulsestorm\Pestle\Importer\pestle_import;
/* ... */
pestle_import('Pulsestorm\Pestle\Library\output');
/* ... */
class NamespaceRefactoringTest extends PestleBaseTest
{
    public function testsOutput()
    {
        ob_start();
        output("Hello");
        $results = ob_get_clean();
        $this->assertEquals(trim($results), "Hello");
    }

    /* ... */
}
```

## Writing Integration Tests

The bitrot in the integration tests integration tests is real.  When we [get around to fixing that](https://github.com/astorm/pestle/issues/471), we'll update this section
