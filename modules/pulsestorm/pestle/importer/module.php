<?php
namespace Pulsestorm\Pestle\Importer;
use function Pulsestorm\Pestle\Runner\getBaseProjectDir;
define('PULSESTORM_PESTLE_VERSION', '1.5.3');
use ReflectionFunction;
use ReflectionClass;
use Exception;
use Phar;

/**
 * One Line Description
 * @param string $thing_to_import ex. \Namespace\To\someFunction
 * @return boolean
 */
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

/**
 * @param string funtion_name full namespace "\Namespace\To\someFunction"
 */
function extractFunction($function_name)
{
    $code = createFunctionForGlobalExport($function_name);
    return $code;
}

/**
 * @param string funtion_name full namespace "\Namespace\To\someFunction"
 */
function createFunctionForGlobalExport($function_name)
{
    /**
     * Namespace and function, extracted
     *
     * [
     *     'short_name'=>$short_name,
     *     'namespace'=>$namespace,
     * ]
     *
     * @var array $info
     */
    $info = extractFunctionNameAndNamespace($function_name);

    $code =
    'function ' . $info['short_name'] . '(){
        $args = func_get_args();
        return call_user_func_array(\'\\'.$function_name.'\', $args);
    }';
    return $code;
}

function extractFunctionNameAndNamespace($full)
{
    $full       = strToLower($full);
    $parts      = explode('\\', $full);
    $short_name = array_pop($parts);
    $namespace  = implode('/',$parts);

    return [
        'short_name'=>$short_name,
        'namespace' =>$namespace
    ];
}

function getHomeDirectory() {
    $home = trim(`echo ~`);
    if(!is_dir($home)) {
        $home = trim(`echo %HOMEPATH%`);
    }
    if(!is_dir($home)) {
        throw new Exception("Could not find home directory.");
    }
    return $home;
}

function getModuleFolders()
{
    $pathExtra = '/modules/';
    $paths = [getBaseProjectDir() . $pathExtra];
    $home = getHomeDirectory();
    $pathConfig = $home . '/.pestle/module-folders.json';
    if(is_dir($home) && file_exists($pathConfig) && $config = json_decode(file_get_contents($pathConfig)))
    {
        $moduleFolders = array_map(function($item) use ($pathExtra) {
            return $item . $pathExtra;
        }, $config->{'module-folders'});
        $paths = array_merge($paths, $moduleFolders);
    }
    return $paths;
}

function getPathFromFunctionName($function_name)
{
    $function_name = strToLower($function_name);
    $parts         = explode('\\', $function_name);
    $short_name    = array_pop($parts);
    $namespace     = implode('/',$parts);
    $file          = $namespace . '/module.php';

    $folders = getModuleFolders();

    foreach($folders as $folder)
    {
        $fullPath = $folder . '/' . $file;
        if(file_exists($fullPath))
        {
            return $fullPath;
        }
    }

    exit("Could not find $file in any folder.\n");
    // return getBaseProjectDir() . '/modules/' . $file;
}

/**
 * Turns the full function name into a file path and then
 * require_once's it.
 *
 * @param string funtion_name full namespace "\Namespace\To\someFunction"
 */
function includeModule($function_name)
{
    $function_name = strToLower($function_name);
    $parts         = explode('\\', $function_name);
    $short_name    = array_pop($parts);
    $namespace     = implode('/',$parts);
    $file          = $namespace . '/module.php';
    return require_once(getPathFromFunctionName($function_name));
}

function functionCollidesWithPhpGlobalSpace($namespace)
{
    $parts      = explode('\\', $namespace);
    $short_name = array_pop($parts);

    $results = in_array($short_name, get_defined_functions()['internal']);
    if($results)
    {
        return $short_name;
    }
    return $results;
}

function generateCodeForReflectionStrategy($short_name, $thing_to_import) {
    $code =
    'use function Pulsestorm\Pestle\Importer\functionRegister;'         . "\n";
    $code .= 'use function Pulsestorm\Pestle\Importer\getNamespaceCalledFromForGenerated;' . "\n";
    $code .= "function $short_name(){"                                     . "\n" .
    '   $function   = functionRegister(__FUNCTION__, getNamespaceCalledFromForGenerated());'            . "\n" .
    '   $args       = func_get_args();'                           . "\n" .
    '   return (new \ReflectionFunction($function))->invokeArgs($args);' . "\n" .
    '}';

    return $code;
}

function generateCacheFilePathForReflectionStrategy($short_name, $thing_to_import, $code) {
    $cache_dir  = getCacheDir();
    $full_dir   = $cache_dir    . '/' . str_replace('\\','/',strToLower($thing_to_import));
    $filename   = md5($short_name . $code);
    // $full_path  = $full_dir . '/'  . $filename . '.php';
    $full_path  = $cache_dir    . '/reflection-strategy/'  . $filename . '.php';
    return $full_path;
}
/**
 * @param string $thing_to_import \Namespace\To\someFunction
 * @param string $nd_called_from \Namespace\Called\From
 */
function includeCodeReflectionStrategy($thing_to_import, $ns_called_from)
{
    $parts      = explode('\\', $thing_to_import);
    $short_name = array_pop($parts);

    functionRegister($short_name, $ns_called_from, $thing_to_import);
    generateOrIncludeExecutorFunction($short_name, $thing_to_import);
}

function generateOrIncludeExecutorFunction($short_name, $thing_to_import) {

    $code = generateCodeForReflectionStrategy($short_name, $thing_to_import);
    $full_path = generateCacheFilePathForReflectionStrategy(
        $short_name, $thing_to_import, $code);

    /**
     * If file's already been generated, return it
     */
    if(file_exists($full_path))
    {
        // require_once getModulePathToFullyNamespacedFunction($thing_to_import);
        require_once $full_path;    //require the exported file
        return;
    }

    /**
     * If we have a conflict with a global PHP function, rename it in $code
     */
    if($short_name = functionCollidesWithPhpGlobalSpace($thing_to_import))
    {
        //export with a pestle_prefix
        $code = replaceFirstInstanceOfFunctionName($code, $short_name);
    }

    /**
     * Create a directory if we need it
     */
    if(!is_dir(dirname($full_path)))
    {
        mkdir(dirname($full_path), 0755, true);
    }

    /**
     * Wrap the code in a <?php, and add an exported for
     * comment for debugging purposes
     */
    if(!file_exists($full_path))
    {
        //exported as function global function
        file_put_contents($full_path,
            '<' . '?' . 'php' . "\n" .
            $code . "\n" .
            '##exported for '      . $thing_to_import . "\n");
    }

    require_once $full_path ;
}
function includeCodeFullExportStrategy($namespace, $code)
{
    $cache_dir = getCacheDir();
    $full_dir  = $cache_dir . '/' . str_replace('\\','/',strToLower($namespace));
    $full_path = $full_dir . '/'  . md5($code) . '.php';

    if(file_exists($full_path))
    {
        require_once $full_path;
        return;
    }

    if(!is_dir($full_dir))
    {
        mkdir($full_dir, 0755, true);
    }

    if($short_name = functionCollidesWithPhpGlobalSpace($namespace))
    {
        //export with a pestle_prefix
        $code = replaceFirstInstanceOfFunctionName($code, $short_name);
    }

    //exported as function global function
    file_put_contents($full_path,
        '<' . '?' . 'php' . "\n" .
        $code . "\n" .
        '##exported for '      . $namespace . "\n");
    require_once $full_path;
}

/**
 * @param string $thing_to_import \Namespace\To\someFunction
 * @param string $nd_called_from \Namespace\Called\From
 */
function includeCode($thing_to_import, $ns_called_from)
{
    includeCodeReflectionStrategy($thing_to_import, $ns_called_from);
    /**
     * A string that's a function definition that looks like
     * function somefunction() {
     *     $args = func_get_args();
     *     return call_user_func_array('\Namespace\To\someFunction', $args);
     * }
     * @var string $function
     */
    // $code = extractFunction($thing_to_import);
    // includeCodeFullExportStrategy($thing_to_import, $code);
}

function functionRegisterGet(&$functions, $short_name,$ns_called_from)
{
    if(!array_key_exists($short_name, $functions))
    {
        exit("No such function [$short_name] imported.");
    }
    if(!array_key_exists($ns_called_from, $functions[$short_name]))
    {
        exit("No such function [$short_name] imported for namespace [$ns_called_from]");
    }
    return $functions[$short_name][$ns_called_from];
}

function functionRegisterSet(&$functions, $short_name, $ns_called_from, $namespaced_function)
{
    $functions[$short_name][$ns_called_from] = $namespaced_function;
}

/**
 * Manages state in static $functions.
 *
 * If $namespaced_function is false/not-passed, then items
 * is fetched from static $functions
 *
 * If $namespaced_function is set/truthy, then items
 * are set.
 *
 * The static $functions array is a multi-dimensional array
 * that points a short function name (dimension 1) to a
 * specific fully namespaced PHP function (value) within
 * a particular namespace (dimension 2).
 *
 * If the following import happens
 *
 *     namespace Foo\Bar\Baz;
 *     pestle_import('Zip\Zap\someFunction')
 *
 * then the following runs (functionRegisterSet)
 *
 * $functions['someFunction']['Foo\Bar\Baz'] = 'Zip\Zap\someFunction';
 *
 * This allows the code in generateCodeForReflectionStrategy to fetch
 * the full PHP function, and then invoke it via reflection.
 *
 * @param string $short_name someFunction
 * @param string $ns_called_from Namespace\Called\From
 * @param string $namespace_function Namespace\Of\someFunction
 */
function functionRegister($short_name,$ns_called_from=false,$namespaced_function=false)
{
    static $functions=[];
    if(!$namespaced_function) {
        return functionRegisterGet($functions, $short_name,$ns_called_from);
        // return $functions[$short_name][$ns_called_from];
    }
    return functionRegisterSet($functions,$short_name,$ns_called_from,$namespaced_function);
    //     echo "Registering `$short_name` as `$namespaced_function`
    // for calls from `$ns_called_from`","\n";

}
function replaceFirstInstanceOfFunctionName($code, $short_name)
{
    return str_replace('function ' . $short_name,
    'function pestle_' . $short_name, $code);
}

function getCacheDir()
{
    $cache_dir = '/tmp/pestle_cache/' . md5(
        getBaseProjectDir() . Phar::running() . PULSESTORM_PESTLE_VERSION
    );

    if(!is_dir($cache_dir)){
        mkdir($cache_dir, 0755, true);
    }
    return $cache_dir;
}

function getNamespaceCalledFromForGenerated()
{
    return getNamespaceCalledFrom();
}

function getNamespaceCalledFromRequireOrInclude($item)
{
    if(!array_key_exists('function', $item))
    {
        return null;
    }

    if(!in_array($item['function'], ['include','include_once', 'require','require_once']))
    {
        return null;
    }

    static $cache = [];

    if(array_key_exists($item['file'], $cache))
    {
        return $cache[$item['file']];
    }
    return convertAbsoluteFilePathIntoNamespace($item['file']);
}

function convertAbsoluteFilePathIntoNamespace($path)
{
    $contents = file_get_contents($path);
    preg_match('%namespace[\s]*(.+);%', $contents, $matches);
//     var_dump($path);
    if(array_key_exists(1, $matches))
    {
        $namespace = $matches[1];
        return $namespace;
    }
    return false;
    // $namespace = splitPopDiscard('\\', $namespace);


}

function splitShiftDiscard($char, $string)
{
    $parts = explode($char, $string);
    array_shift($parts);
    return implode($char, $parts);
}

function splitPop($char, $string)
{
    $parts = explode($char, $string);
    return array_pop($parts);
}

function splitPopDiscard($char, $string)
{
    $parts = explode($char, $string);
    array_pop($parts);
    return implode($char, $parts);
}

function getNamespaceCalledFromRegularFunction($item)
{
    if(array_key_exists('function', $item) && $item['function'] !== 'require_once')
    {
        $r = new ReflectionFunction($item['function']);
        return $r->getNamespaceName();
    }
    return null;
}

function getItemAfterPestleImportFromCallstack()
{
    return getItemAfterFunctionFromCallstack('Pulsestorm\Pestle\Importer\pestle_import');
}

function getItemAfterCallFromGeneratedFromCallstack($offset=0)
{
    return getItemAfterFunctionFromCallstack(
        'Pulsestorm\Pestle\Importer\getNamespaceCalledFromForGenerated', $offset);
}

function getItemAfterFunctionFromCallstack($function, $offset=0)
{
    $info   = debug_backtrace();
    $count  = count($info);
    for($i=0;$count;$i++)
    {
        $item = $info[$i];
        if(array_key_exists('function', $item) && $item['function'] == $function)
        {
            return $info[($i + 1 + $offset)];
        }
    }
    return false;
}
function getNamespaceFromFileOfNonNamespaceFunctionCall($item)
{
    if(!array_key_exists('function', $item))
    {
        return;
    }
    if(strpos($item['function'], '\\') !== false)
    {
        return;
    }
    $namespace = convertAbsoluteFilePathIntoNamespace($item['file']);
    return $namespace;
}

function getCanidateStackFramesForNamespaceHeuristics()
{
    $items      = [];
    $items['getItemAfterPestleImportFromCallstack']         = getItemAfterPestleImportFromCallstack();
    $items['getItemAfterCallFromGeneratedFromCallstack']    = getItemAfterCallFromGeneratedFromCallstack();
    return $items;
}

function getModulePathToFullyNamespacedFunction($namespaced_function)
{
    $namespace = splitPopDiscard('\\',$namespaced_function);
    $function  = splitPop('\\',$namespaced_function);

    $namespace = strToLower(str_replace('\\','/',$namespace));
    $path = getBaseProjectDir() . '/modules/' . $namespace . '/module.php';
    return $path;
}

function getNamespaceCalledFromInsideAFile()
{
    //start pestle_import from inside a file
    $trace = debug_backtrace();
    $found = false;
    $i     = 0;
    foreach($trace as $item)
    {
        $i++;
        if(!isset($item['function'])) { continue; };
        if($item['function'] !== 'Pulsestorm\Pestle\Importer\pestle_import') { continue; }
        $found = true;
        break;
    }
    if($found && $i > 0 && array_key_exists($i, $trace))
    {
        $namespace = convertAbsoluteFilePathIntoNamespace($item['file']);
        if(!$namespace) //for stand alone files
        {
            $namespace = convertAbsoluteFilePathIntoNamespace($trace[$i]['file']);
        }
        if($namespace)
        {
            return $namespace;
        }
    }
    //end pestle_import from inside a file
}

function ifItemExistsThenReturnNamespaceFromFile($item)
{
    if($item)
    {
        $namespace = convertAbsoluteFilePathIntoNamespace($item['file']);
    }
    return $namespace;
}

function getNamespaceCalledFromCallFromGenerateFunction()
{
    //start callFromGenereate usage
    $item = getItemAfterCallFromGeneratedFromCallstack();
    $namespace = ifItemExistsThenReturnNamespaceFromFile($item);
    if($namespace)
    {
        return $namespace;
    }
    //for running stand alone file
    $item = getItemAfterCallFromGeneratedFromCallstack(1);
    $namespace = ifItemExistsThenReturnNamespaceFromFile($item);
    return $namespace;
    //end callFromGenereate usage
}

function getNamespaceCalledFrom()
{
    $trace = debug_backtrace();
    $found = false;

    $namespace = getNamespaceCalledFromInsideAFile();
    if($namespace)
    {
        return $namespace;
    }

    $namespace = getNamespaceCalledFromCallFromGenerateFunction();
    if($namespace)
    {
        return $namespace;
    }

    var_dump("Could not determine where pestle_include was callled from");
    var_dump($trace);
    exit;
}

/**
* @command library
*/
function pestle_cli($argv)
{
}
