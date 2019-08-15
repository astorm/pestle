# Input, Output, Arguments, Options

Pestle's a system for building structured command line programs.  This means there are systems and library functions available to help you with common tasks you'll need when writing command line programs.  This document will explain how to give you pestle command command line arguments and options, and how to handle basic command Input/Output.

## Arguments

An _argument_ is a value you pass to your command line program.

    $ pestle.phar some-command Hello World "You are Great"

The above pestle invocation runs the `some-command` program, and passes it three individual arguments, _Hello_, _World_, and the full string _You are Great_.

While Pestle is just PHP under the hood, and you could just grab the global `$argv` array to manipulate your arguments, pestle give you a system for defining the sort or arguments you program takes.

In the PHP doc-block for your `pestle_cli` function, the `@argument` lines define which arguments your program expects.

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

And `@argument` line requires three parts.

    * @argument arg_id Description [Default]

The first part is a PHP identifier -- `arg_id` above.  This identifier should be alpha numeric, no spaces, plus underscores and dashes.  Stick to PHP variable naming rules (plus dashes) here and you'll be fine.

The second part of a text description of the variable (`Description` above).

The third part is a default value, surrounded by `[]` brackets (`Default` above).

When you define your arguments this way, pestle will pass your `pestle_cli` function with an `$argv` populated with keys names that are your argument names, and key-values that are the passed in arguments.

Additionally, if a user fails to provide an argument, pestle will use your description to _ask_ the user for a value.  For example, if you invoke the above program with only two arguments

```
    $ pestle.phar some-command Hello World
    Enter a Compliment (You Are Great)]
```

Pestle will ask for the third.  If the user enters a value, pestle will populate the first argument to `pestle_cli` with that value.  If the user hits return without entering a value, pestle will populate the first argument to `pestle_cli` the the _default_ argument.

## Options

Sometimes you want to allow the user to pass an _optional_ value to your program.  Unix has a long history of options.  If you do a lot of command line work you probably use options on `ls` all the time.

```
    $ ls -l
```

Unix command have evolved to be more than a single letter. For example, the following two curl commands are equivalent

```
    $ curl -L http://example.com
    $ curl --location http://example.com
```

The command line program curl has a short option syntax, and a long option syntax.  Generally, single letter option names are preceded by a single dash (`-`), and full-word option names are preceded by a double dash (`--`).

Of course, unix being unix, you had the odd outlier like `find`, where the full-word options, only have a single dash.

```
    $ find . -name 'file.txt`
```

Pestle support **only** the double dash option format.  Similar to arguments, you can define your options via the `@option` doc-block line of your `pestle_cli` function.

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

Pestle will pass a _second_ value to `pestle_cli` that's an array populated with your options keys.  If you don't use an option, the key will be set to `NULL`.

```
    $ pestle.phar some-command
    NULL
```

But if you _do_ pass an option, it's value will be set.

```
    $ pestle.phar some-command --some-option foo
    string(3) "foo"

    $ pestle.phar some-command --some-option=foo
    string(3) "foo"
```

Pestle supports both a space and an `=` sign between option value and the passed value.

## Advanced Arguments

There's a few advanced feature of the `@arguments` directive you'll want to be aware of.

First -- if you name an argument `password`

    @argument password What is your password? []

pestle will attempt to keep the value secret by not outputting the characters entered.

WARNING: This command may or may not work in your terminal or shell -- if there's truly secret information your program needs you should consider using a different mechanism to get it into your program.

The other advanced argument feature are `@callback` arguments.  A `@callback` argument allows you to invoke a custom function that will ask users for their argument value.

    //...
    function someLocalFunction() {
        output("Ok, this one is complicated")
        // input/output function to collect information

    }

    @argument identifier @callback someLocalFunction

A `@callback` argument still require a PHP identifier (`identifier`) above.  However, instead of a description, the next work should be `@callback` -- this lets pestle know its dealing with a callback argument. The third part of a `@callback` argument, `someLocalFunction` above.

With the above configuration, if a user fails to provide a value for argument, pestle will **call `someLocalFunction`.  What value is returned by the `@callback` function will be the value of the argument passed to your program.

## Input and Output Functions

The default `@argument` and `@option` handling are, by themselves, often enough to give you program enough user interface to get it's job done.  For those commands where you need/want additional input or output, pestle provides a number of importable-able functions.

### output

The output function work similarly to PHP's `print` and `echo` statement, and will allow you to send output to a user's terminal.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    output("Hello World");
```

The `output` function accepts multiple arguments -- each one will be send to a user's terminal.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    output("Hello", "World");
```

The `output` function also accepts arrays and objects, and will `var_dump` their contents.

``` php
    pestle_import('Pulsestorm\Pestle\Library\output');
    //...

    $array = [1,2,3];
    $object = (object) ['foo'=>'bar']
    output($array, $object);
```

### exitWithErrorMessage

When something goes wrong with your program, sometimes it's best to throw up your hands and just exit the program.  The `exitWithErrorMessage` function allows to you

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

### inputPassword

If you want to ask your user a question, but don't want their keystrokes to be seen in the terminal, use the `inputPassword` method.

``` php
    pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
    //...

    $answer = inputPassword('Tell me your secret?');

    // we would never tell your secret
    // output("HEY DID YOU SAY ", $answer);
```

The first argument to `inputPassword` is the question you'll ask a user.

WARNING: This command may or may not work in your terminal or shell -- if there's truly secret information your program needs you should consider using a different mechanism to get it into your program.
