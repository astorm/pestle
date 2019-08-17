# Interacting with the User

Pestle's a system for building structured command line programs.  This means there are sub-systems and library functions available to help you with common tasks you'll need when writing command line programs.  This document will explain how to give your pestle program command line arguments and options, and how to handle basic input and output via library functions.

## Arguments

An _argument_ is a value you pass to your command line program.

```
    $ pestle.phar some-command Hello World "You are Great"
```

The above pestle invocation runs the `some-command` program, and passes it three individual arguments, _Hello_, _World_, and the full string _You are Great_.

Pestle is just PHP under the hood. You could just [grab the global `$argv` array](https://www.php.net/manual/en/reserved.variables.argv.php) and access the raw arguments.  However, pestle gives you a system for defining the sort or arguments you program takes, and receiving them in a less-global way.

In the PHP DocBlock for your `pestle_cli` function, the `@argument` lines define which arguments your program expects.

``` php
    /**
    * This is my program, there are many like it.
    *
    * @command some-command
    * @argument my_first_arg Enter a Greeting [Hello]
    * @argument another_arg Enter a Noun [World]
    * @argument final_arg Enter a Compliment [You Are Great]
    */
    function pestle_cli($argv)
    {
        output($argv['my_first_arg']);
        output($argv['another_arg']);
        output($argv['final_arg']);
    }
```

If you ran the above program you would expect output similar to this

    $ pestle.phar some-command Hello World "You are Great"
    Hello
    World
    You are Great

An `@argument` line requires three parts.

    * @argument arg_id Description [Default]

The first part is an identifier -- `arg_id` above.  This identifier should be alpha numeric, no spaces, plus underscores and dashes.  Stick to PHP variable naming rules (plus dashes) here and you'll be fine.

The second part is a a text description of the argument (`Description` above). This is for humans to read.

The third part is a default value, surrounded by `[]` brackets (`Default` above).

When you define your arguments this way, pestle will pass your `pestle_cli` function an array (`$argv` above) populated with key names that are your argument names, and key-values that are the passed in arguments.

Additionally, if a user fails to provide an argument, pestle will use your description to _ask_ the user for a value.  For example, if you invoke the above program with only two arguments

```
    $ pestle.phar some-command Hello World
    Enter a Compliment (You Are Great)]
```

pestle will ask for the third.  If the user enters a value, pestle will populate the first argument to `pestle_cli` with that value.  If the user presses return without entering a value, pestle will use with the default value.

## Options

Sometimes you want to allow the user to pass an _optional_ value to your program.  Unix has a long history of options to command line programs.  If you do a lot of command line work you probably use options on `ls` all the time.

```
    $ ls -l
```

Unix commands have evolved to be more than a single letter (`-l` above). For example, the following two curl commands are equivalent

```
    $ curl -L http://example.com
    $ curl --location http://example.com
```

The command line program curl has a short option syntax, and a long option syntax.  Generally, single letter option names are preceded by a single dash (`-`), and full-word option names are preceded by a double dash (`--`).

Of course, unix being unix, you have the odd outlier like `find`, where the full-word options use a single dash.

```
    $ find . -name 'file.txt`
```

Pestle supports **only** the double dash option format.  Similar to `@arguments`, you can define your options via the `@option` doc-block line of your `pestle_cli` function.

``` php
    /**
    * Showing you options
    *
    * @command some-command
    * @option some-option An Example Option
    */
    function pestle_cli($argv, $options)
    {
        var_dump($options['some-option']);
    }
```

An `@option` line has two parts.  The first, `some-option` above, is an identifier for your option.  This identifier should be alpha numeric, no spaces, plus underscores and dashes.  Stick to PHP variable naming rules (plus dashes) here and you'll be fine.

The second part is a description of your option.  This is for humans to read and understand what your option is for.

For options, pestle will pass a _second_ array to the `pestle_cli` function that's populated with your option keys.  If you don't use an option, the key will be set to `NULL`.

```
    $ pestle.phar some-command
    NULL
```

But if you _do_ pass an option, its value will be set.

```
    $ pestle.phar some-command --some-option foo
    string(3) "foo"

    $ pestle.phar some-command --some-option=foo
    string(3) "foo"
```

Pestle supports both a space and an `=` equal sign between the option identifier and the passed option value.

## Callback Arguments

There's one advanced feature of the `@arguments` directive we haven't discussed yet.  That's the `@callback` argument.

A `@callback` argument allows you to invoke a custom function that will ask users for their argument value.

``` php
function someLocalFunction() {
    return 'calculated-value';
}
/**
* Showing you @callback arguments
*
* @command some-command
* @argument identifier @callback someLocalFunction
*/
function pestle_cli($argv, $options)
{
    output($argv['identifier']);
}
```

A `@callback` argument still requires a PHP identifier (`identifier`) above.  However, instead of a description, the second part should be `@callback` -- this lets pestle know it's dealing with a callback argument. The third part of a `@callback` argument, `someLocalFunction` above, is a PHP function available in the local scope.

With the above configuration, if a user fails to pass a value for argument, pestle will call `someLocalFunction` and use its return value to populate the arguments array pestle passes to the `pestle_cli` function.

## Input and Output Functions

The default `@argument` and `@option` mechanisms are, by themselves, often enough to give your program enough user interface to get its job done.  For those commands where you need/want additional input or output, pestle provides a number of importable functions.

These are particularly useful when implementing `@callback` arguments, but can be used anywhere you'd like in your program.

### output

The output function is similar to PHP's `print` and `echo` in that it will allow you to send output to a user's terminal.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    output("Hello World");

    # prints "Hello World\n"
```

The `output` function accepts multiple arguments -- each one will be send to a user's terminal.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    output("Hello", "World");
    // prints "HelloWorld"
```

The `output` function also accepts arrays, and will `var_dump` their contents.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    $array = [1,2,3];
    output($array);
    // prints
    // array(3) {
    //   [0]=>
    //   int(1)
    //   [1]=>
    //   int(2)
    //   [2]=>
    //   int(3)
    // }
```

### exitWithErrorMessage

When something goes wrong with your program, sometimes it's best to throw up your hands and just leave. In other words, `exit` the program. The `exitWithErrorMessage` function allows to you

1. Send a message to your terminal's STDERR stream (i.e. display an error in the user's terminal)
2. Exit the program.

``` php
    pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
    //...

    if($badSceneMan) {
        exitWithErrorMessage("ERROR: something bad happened, we're bailing");
    }
```


### input

If you want to ask your user a question, and then process their answer, use the `input` function.

``` php
    pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
    //...

    $answer = input('How old are you?', 35);

    output("Wow, you don't look a day over ", $answer - 8);
```

The first argument to `input` is the question you want to ask your user.  The second argument is the default value to use if someone just presses return instead of entering a value.

Use this function with care -- once you **require** input from a user your program can't be used in more complex shell scripts (without a human there to press the buttons).  Ideally, limit your use of the `input` function to the `@callback` arguments described earlier in this document.
