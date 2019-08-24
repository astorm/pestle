# What is pestle_import?

Here's the basic problem pestle needs to solve.  When a user calls the `pestle_import` function

```php
namespace My\Programs\FullNamespace;
pestle_import('FullNamespace\To\someFunction');
```

pestle needs to ensure that calls to `someFunction` in the _local_ namespace will behave as though `someFunction` were called via its full name.

Put another way, this program

```php
namespace My\Programs\FullNamespace;
pestle_import('FullNamespace\To\someFunction');

someFunction();
```

needs to behave like this program

```php
namespace My\Programs\FullNamespace;
require_once 'modules/fullnamespace/to/module.php'
FullNamespace\To\someFunction();
```

Put a third way, `pestle_import` needs to

1. Define a local function named `someFunction`
2. Ensure any un-namespaced symbols in `someFunction` behave as though they're being called from the original namespace of `someFunction`

It's this second part that's the tricky one.  If `someFunction` looked like this

```php
function someFunction() {
    echo "Hello Function";
}
```

we could just define a new function named `someFunction` in the `My\Programs\FullNamespace` namespace and be done.  However, if the function looks like this

```php
function someFunction() {
    return someOtherFunction();
}
```

we can't just include it into `My\Programs\FullNamespace`, because then PHP would attempt to call `My\Programs\FullNamespace\someOtherFunction`, which doesn't exist.  We need to make sure `someOtherFunction` still calls the function from its original namespace.

In other words, we're dealing with a class of problem that's traditionally [solved by a linker](https://en.wikipedia.org/wiki/Linker_(computing)).

## Incremental Strategies

This is a non-trivial problem to solve robustly, especially from PHP userland.   So far pestle has taken non-optimal, incremental approaches towards the problem.  This allows us to build up a body of code that runs via the `pestle_import` pattern. Building up that body of code means we can experiment with syntax and features before committing to a robust solution.

Right now, pestle currently uses a reflection based strategy to implement local importing of functions. Specifically, (at a high level), Pestle keeps a registry of functions that are imported in each namespace, indexed by their local/short name.  For each of these functions pestle will generate a local PHP include file that defines an un-namespaced function. This un-namespaced function will reference the registry and invoke the full PHP function via reflection.

## Pestle Import Lifecycle: 10,000 foot view

From a very high level, the `pestle_import` function has three distinct stages.

First, it must load the original function under its original namespace.

Second, it must register the short function name as being called from a particular namespace and pointing at the original function.

Third, it must generate, cache, and include an "executor function" which will be loaded into the current namespace and do the work of calling the original function.

That's pretty high level though -- let's take a look at what all that actually means.

## Pestle Import

We're going to follow a  `pestle_import` call that looks like this

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

With this call, the end-user-programmer is saying

> Hey pestle, I want to use the `inputOrIndex` function from the `Pulsestorm\Pestle\Library` module/namespace in my program.

Here's the entry point of a `pestle_import` call

```php
#File: astorm/pestle/modules/pulsestorm/pestle/importer/module.php
function pestle_import($thing_to_import, $as=false)
{
    /**
     * @var string $ns_called_from ex. \Namespace\Called\From
     */
    $ns_called_from  = getNamespaceCalledFrom();
    $thing_to_import = trim($thing_to_import, '\\');

    includeModule($thing_to_import);
    includeCode($thing_to_import, $ns_called_from);
    return true;
}
```

The first line,

```php
$ns_called_from  = getNamespaceCalledFrom();
```

fetches the namespace that `pestle_import` **was called from**.  In the above example this will be `Foo\Baz\Bar`.  The `getNamespaceCalledFrom` function uses information from PHP's built-in [`debug_backtrace`](http://php.net/debug_backtrace) function to determine where `pestle_import` was called from.

Next, the `includeModule` code will `require` in  `Pulsestorm\Pestle\Library\inputOrIndex`'s source  module.

```php
#File: astorm/pestle/modules/pulsestorm/pestle/importer/module.php
function includeModule($function_name)
{
    $function_name = strToLower($function_name);
    $parts         = explode('\\', $function_name);
    $short_name    = array_pop($parts);
    $namespace     = implode('/',$parts);
    $file          = $namespace . '/module.php';
    return require_once(getPathFromFunctionName($function_name));
}
```

This is where the we can find the code that converts a PHP namespace into a pestle module file path.  The `getPathFromFunctionName` function will search through pestle's default modules, as well as any configured modules, until it finds a `module.php` file.

Once `includeModule` is done, pestle will call the `includeCode` function, which is a wrapper function for the current pestle import strategy.

```php
function includeCode($thing_to_import, $ns_called_from)
{
    includeCodeReflectionStrategy($thing_to_import, $ns_called_from);
    // ... older strategies are often left, commented out ...
}
```

## Reflection Strategy: Registering the Function

The `includeCodeReflectionStrategy` has two jobs -- the first is to register the function/symbol we want to import, and the second is to ensure an _executor_ function is loaded.

```php
#File: modules/pulsestorm/pestle/importer/module.php
function includeCodeReflectionStrategy($thing_to_import, $ns_called_from)
{
    $parts      = explode('\\', $thing_to_import);
    $short_name = array_pop($parts);

    functionRegister($short_name, $ns_called_from, $thing_to_import);
    generateOrIncludeExecutorFunction($short_name, $thing_to_import);
}
```

Registering a function means adding the function we want to import to a global registry that keeps track of the following two things

1. Which namespace the function's being imported from
2. The actual, fully namespaced, PHP function that this imported function points at

The `functionRegister` function uses a classless getter/setter registry pattern.

```php
function functionRegister($short_name,$ns_called_from=false,$namespaced_function=false)
{
    static $functions=[];
    if(!$namespaced_function) {
        return functionRegisterGet($functions, $short_name,$ns_called_from);
        // return $functions[$short_name][$ns_called_from];
    }
    return functionRegisterSet($functions,$short_name,$ns_called_from,$namespaced_function);
}
```

The function registry uses the [`static` variable](https://www.php.net/manual/en/language.variables.scope.php) `$functions` to store its values.  If users call `functionRegister` with the `$namespaced_function` argument, `functionRegister` will _set_ a value.  If this argument is not used, `functionRegister` will _get_ a value from its registry.

The `functionRegister` call in the `includeCodeReflectionStrategy` function

```php
functionRegister($short_name, $ns_called_from, $thing_to_import);
```

is _setting_ a value.

```
function functionRegisterSet(&$functions, $short_name, $ns_called_from, $namespaced_function)
{
    $functions[$short_name][$ns_called_from] = $namespaced_function;
}
```

Again, this registry needs to keep track of all the namespaces a short function name is imported from, and what actual PHP function name it points at.  So something like this --

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

Will result in the following registered values

```php
$functions['inputOrIndex']['Foo\Baz\Bar'] = 'Pulsestorm\Pestle\Library\inputOrIndex';
```

## Reflection Strategy: Generating the Executor

After registering the function, we need to include an executor function in the local namespace.

```php
generateOrIncludeExecutorFunction($short_name, $thing_to_import);
```

"Executor Function" is our own term.  When an end-user-programmer says something like this

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

We need to ensure there's a function named `inputOrIndex` in the local namespace.  That is, the user needs to be able to call that function

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');

//...

inputOrIndex();
```

Since `pestle_import` is running in the context of this local namespace (`Foo\Baz\Bar`), if we `include` a file with a function definition that _has no namespace_, PHP will define a function in the the same namespace that we called  `pestle_import` from.  So part of `generateOrIncludeExecutorFunction`'s job is to generate an include file (stored in a local, file based cache), and then include it.

The _other_ part of `generateOrIncludeExecutorFunction`'s job is making sure that the code it generates will result in the _original_ PHP function being called.  You can see all the executor functions pestle's generated by looking in your `/tmp/pestle_cache` folder -- here's one example.

```php
#File: /private/tmp/pestle_cache/[cache-key]/reflection-strategy/[2nd-cache-key].php
<?php
use function Pulsestorm\Pestle\Importer\functionRegister;
use function Pulsestorm\Pestle\Importer\getNamespaceCalledFromForGenerated;
function inputOrIndex(){
   $function   = functionRegister(__FUNCTION__, getNamespaceCalledFromForGenerated());
   $args       = func_get_args();
   return (new \ReflectionFunction($function))->invokeArgs($args);
}
##exported for Pulsestorm\Pestle\Library\inputOrIndex
```

This code was generated when an end user programmer said

```php
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

The executor function (`function replaceTypeHintsWithNewTypeHints`) will

1. Fetch the fully namespaced PHP function from the function registry for the function `inputOrIndex`, for the namespace that the executor function was called from (fetched with `getNamespaceCalledFromForGenerated`)

2. Using PHP's Reflection API, _call_ that function.

When a pestle user calls a function like this

```php
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
//...
inputOrIndex(...)
```

The function they're actually calling is the executor function.

## One More Time

That's a lot to take in. Let's do it one more time, but jumping back to a 10,000 foot view with a few more details filled in.

Consider a pestle program that calls `pestle_import` from the `Foo\Baz\Bar` namespace.

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

The first thing that happens?  In `includeModule`, we make sure the PHP file with `inputOrIndex` is `require`d.

```php
require_once('modules/pulsestorm/pestle/library/module.php');
```

Next, in `includeCode`, we'll register the function, ensuring that that static `$functions` array in `getFunction` has the following keys set

```php
$functions['inputOrIndex']['Foo\Baz\Bar'] = 'Pulsestorm\Pestle\Library\inputOrIndex';
```

Finally, `includeCode` will also (the first time this function is `pestle_import`ed) generate and cache "an executor function" that looks something like this

```php
#File: /tmp/pestle_cache/[pestle-key]/reflection-strategy/[function-key].php
<?php
use function Pulsestorm\Pestle\Importer\functionRegister;
use function Pulsestorm\Pestle\Importer\getNamespaceCalledFromForGenerated;
function inputOrIndex(){
   $function   = functionRegister(__FUNCTION__, getNamespaceCalledFromForGenerated());
   $args       = func_get_args();
   return (new \ReflectionFunction($function))->invokeArgs($args);
}
##exported for Pulsestorm\Cli\Token_Parse\replaceTypeHintsWithNewTypeHints
```

and then load this executor.

```php
require_once('/tmp/pestle_cache/[pestle-key]/reflection-strategy/[function-key].php')
```

Future calls will skip the generation and load this file directly from the cache.

The end result?  Back up here

```php
namespace Foo\Baz\Bar;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
```

after `pestle_import` finishes, there will be a newly defined and available `Foo\Baz\Bar\inputOrIndex` function which, when called, will actually call the requested function, `Pulsestorm\Pestle\Library\inputOrIndex`

## Downsides and Future Plans

The main downside of the current strategy is it still requires us to require in all of the original namespace in order for the executor function to do its work.  We're just hiding this detail from the end-user-programmer.

However, with this system in place we should be able to move on to implementing features like

1. Importing other symbols (Classes, constants, and ???)
2. Importing multiple symbols at one

and building up a library of code that uses these features.  This library of code will/could serve as the ultimate test case when/if we attempt to change `pestle_import` so it's only loading the code each symbol needs to run and the cached executor files don't use reflection.
