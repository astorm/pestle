
Here's the basic problem pestle needs to solve.  When a user calls the `pestle_import` function

    namespace My\Programs\FullNamespace;
    pestle_import('FullNamespace\To\someFunction');

pestle needs to ensure that calls to `someFunction` in the _local_ namespace will behave as though `someFunction` were called via it's full name.  i.e. this program

    namespace My\Programs\FullNamespace;
    pestle_import('FullNamespace\To\someFunction');

    someFunction();

will behave like this programs

    namespace My\Programs\FullNamespace;
    require_once 'modules/fullnamespace/to/module.php'
    FullNamespace\To\someFunction();

Put another way, `pestle_import` needs to

1. Define a local function named `someFunction`
2. Ensure any un-namespaced symbols in `someFunction` behave as though they're being called from the original namespace of `someFunction`

It's this second part that's the tricky one.  If `someFunction` looked like this

    function someFunction() {
        echo "Hello Function";
    }

We could just define a new function named `someFunction` in the `My\Programs\FullNamespace` namespace and be done.  However, if the function looks like this

    function someFunction() {
        return someOtherFunction();
    }

we can't just include it into `My\Programs\FullNamespace`, because then the code would attempt to call `My\Programs\FullNamespace\someOtherFunction`, which doens't exist.  We need to make sure `someOtherFunction` still calls the function from its original namespace.

In other words, we're dealing with a class problem that's traditionally [solved by a linker](https://en.wikipedia.org/wiki/Linker_(computing)).

## Incremental Strategies

This is a non-trivial problem to solve robustly.  So far, Pestle's strategy has been to develop non-optimal, incremental approaches towards the problem.  This allows us to build up a body of code that runs via the `pestle_import` pattern. Building up that body of code means we can experiment with syntax and features before committing to a robust solution.

Right now, pestle currently uses a reflection based strategy to implement local importing of functions. Specifically, (at a high level), Pestle keeps a registry of functions that are imported in each namespace, indexed by their local/short name.  For each of these functions pestle will generate a local PHP include file that defines an un-namespaced function which references this registry and invokes the full PHP function via reflection.


## Pestle Import

Let's trace out a  `pestle_import` call that looks like this

    namespace Foo\Baz\Bar;
    pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');

With this call, the end-user-programmer is saying

> Hey pestle, I want to use the `inputOrIndex` function in my program.

Here's the entry point of a `pestle_import` call

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

The first line,

    $ns_called_from  = getNamespaceCalledFrom();

fetches the namespace that `pestle_import` **was called from**.  In the above example this will be `Foo\Baz\Bar`.  The `getNamespaceCalledFrom` function uses information in PHP's built in `debug_backtrace` function to determine where `pestle_import` was called from.

Next, the `includeModule` code will `require` in the source module for `Pulsestorm\Pestle\Library\inputOrIndex`.

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

This is where the logic lives that converts a PHP namespace into a pestle module path lives.  The `getPathFromFunctionName` function will search through pestle's default modules, as well as any configured modules, until it finds `module.php` file.

Once `includeModule` is done, pestle will call the `includeCode` function, which is a wrapper function for the current pestle import strategy.

    function includeCode($thing_to_import, $ns_called_from)
    {
        includeCodeReflectionStrategy($thing_to_import, $ns_called_from);
        // ... older strategies are often left, commented out ...
    }

## Reflection Strategy





Downsides

- still requires including in full module
gliff@hamza%
