<?php
namespace Pulsestorm\Magento2\Cli\Fix_Direct_Om;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\PhpDotNet\glob_recursive');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Di\injectDependencyArgumentIntoFile');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Di\defineStates');
pestle_import('Pulsestorm\Magento2\Cli\Library\getDiLinesFromMage2ClassName');
pestle_import('Pulsestorm\Magento2\Cli\Library\getVariableNameFromNamespacedClass');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

function getFiles($folder, $extension_string)
{
    if(file_exists($folder) && !is_dir($folder))
    {    
        return [$folder];
    }
    $extensions = array_filter(explode(',',$extension_string));
    
    $files = [];
    foreach($extensions as $extension)
    {
        $files = array_merge($files, glob_recursive($folder . '/*.' . $extension));
    }
    return $files;
}

function extractArguments($tokens, $index)
{
    $c = $index;
    $arguments = [];
    while(isset($tokens[$c]))
    {
        $token = $tokens[$c];
        $arguments[] = $token;
        
        if($token->token_value === ')')
        {
            break;
        }
        $c++;
    }
    
    $arguments = array_filter($arguments, function($item){
        return $item->token_value !== '(' && $item->token_value !== ')';
    });
    return array_values($arguments);
}

function reportOnMethod($token, $result)
{ 
    $result->methodCalled = $token->token_value;
    return $result;
}

function stripQuotes($string)
{
    $string   = str_replace("'",'',$string);
    $string   = str_replace('"','',$string);
    return $string;
}

function getNewPropNameFromClass($class, $tokens, $c=0)
{    
    $class   = stripQuotes($class);    
    $prop    = getVariableNameFromNamespacedClass($class);
    $prop    = str_replace('$','',$prop);

//     $parts   = explode('\\',$class);
//     $prop    = implode('',$parts);
//     $prop[0] = strToLower($prop[0]);

    if($c > 0)
    {
        $prop .= $c;
    }    
    
    $matches =  array_filter($tokens, function($item) use ($prop){
                    return $item->token_value === $prop;
                });
                              
    if(count($matches) > 0)
    {
        $c++;
        return getNewPropNameFromClass($class, $tokens, $c);
    }                

    return $prop;
}

function reportOnObjectManagerCall($tokens, $index)
{
    $result = new \stdClass;
    $result->methodCalled   = '';
    $result->arguments      = [];
    $result->class          = '';
    $result->newPropName    = '';
    $result->token_position = $index;
    $result->previous_token = $tokens[$index-1];
    
    $c = $index+1;
    $next_token = $tokens[$c];
    $result = reportOnMethod($next_token, $result);
    $arguments = extractArguments($tokens, $c+1);
    if(count($arguments) === 0)
    {
        output("        NO ARGUMENTS");
        return $result;
    }
    $first = array_shift($arguments);
    if($first)
    {
        $result->class = $first->token_value;        
    }
    
    if(count($arguments) > 0)
    {
        $result->arguments = $arguments;
    }
    else
    {
        $result->newPropName = getNewPropNameFromClass($result->class, $tokens);
    }
    return $result;
}

function warnFiles($file)
{
    $types = ['Proxy', 'Factory', 'dev/test','Interceptor', 'Test.php'];
    foreach($types as $type)
    {
        if(strpos($file, $type))
        {
            output("    WARNING: Looks like a {$type}");
        }
    }

}

function processToken($tokens, $token, $c)
{
    $result = false;
    if($c > 0)
    {
        $previous_token = $tokens[$c-1];
    }
    if($token->token_name === 'T_OBJECT_OPERATOR' && $previous_token->token_value == '_objectManager')
    {
        $result = reportOnObjectManagerCall($tokens, $c);                
    }
    return $result;
}

function tokensFilterWhitespace($tokens)
{
    foreach($tokens as $index=>$token)
    {
        $token->originalIndex = $index;
    }
    
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    $tokens = array_values($tokens); //reindexes

    return $tokens;
}

function processFile($file, $tokens_all, $tokens)
{    
    $c=0;        
    $results = [];        
    foreach($tokens as $token)
    {           
        $item = processToken($tokens, $token, $c);            
        if($item)
        {
            $results[$file][] = $item;
        }
        $c++;
    }
    return $results;
}

function outputResults($results)
{
    foreach($results as $file=>$array)
    {
        output("In $file");
        foreach($array as $result)
        {
            output("    Found {$result->previous_token->token_value} on line {$result->previous_token->token_line}");                
            output("        METHOD: {$result->methodCalled}");
            output("        CLASS: {$result->class}");
            output("        EXTRA ARGUMENTS: " . count($result->arguments));
            output("        NEW PROP: " . $result->newPropName);                        
        }
    }        

}

function validateResults($results)
{
    foreach($results as $file=>$array)
    {
        $contents = file_get_contents($file);
        if(strpos($contents, 'function __construct') === false)
        {
            output("No __construct in {$file}, I don't know what to do " . 
                    "with that, bailing");
            exit;
        }
        
        foreach($array as $result)
        {
            if($result->class[0] === '$')
            {
                output( "{$result->class} looks like a variable, not a " .
                        "class string.  I don't know what to do with " .
                        "that, bailing.");
                exit;
            }
            
            if(!in_array($result->methodCalled, ['create','get']))
            {
                output( "Called {$result->methodCalled}, I don't know what " . 
                        "to do with that, bailing");
                exit;
            }
            
            if(count($result->arguments) > 0)
            {
                output( "Found extra \$arguments, not sure what to do with " . 
                        "that, bailing ");
                exit;
            }            
        }
    }
}

function replaceObjectManager($file, $array, $tokens_all)
{
    $indexAndPropNames = array_map(function($result){
        $item           = new \stdClass;
        $item->index    = $result->previous_token->originalIndex;
        $item->propName = $result->newPropName;
        $item->method   = $result->methodCalled;
        return $item;
    }, $array);

    $tokensNew = [];   
    $state     = TOKEN_BASELINE;
    $propName  = '';
    $method    = '';
    foreach($tokens_all as $index=>$token)
    {
        if($state === TOKEN_BASELINE)
        {
            $thing = array_filter($indexAndPropNames, function($item) use ($index){
                return $item->index === $index;
            });
            $thing = array_shift($thing);
        
            //if we couldn't extract anything, add the token
            if(!$thing) 
            {
                $tokensNew[] = $token;
                continue;
            }
            $state = TOKEN_REMOVING_OM;
            $propName = $thing->propName;
            $method   = $thing->method;
        }
        if($state === TOKEN_REMOVING_OM && $token->token_value === ')')
        {
            $tmp = new \stdClass;
            $state = TOKEN_BASELINE;
            $tmp->token_value = $propName;
            if($method === 'create')
            {
                $tmp->token_value .= '->create()';
            }
            $tokensNew[] = $tmp;
        }
    }
    $tokenValues = array_map(function($token){
        return $token->token_value;
    }, $tokensNew);
    writeStringToFile($file,implode('',$tokenValues));
}

function performInjection($file, $array)
{
    $alreadyInjected = [];
    foreach($array as $result)
    {
        $class = stripQuotes($result->class);            
        if(in_array($class, $alreadyInjected)) { continue; }            
        injectDependencyArgumentIntoFile(
            $class, $file, '$' . $result->newPropName);
        $alreadyInjected[] = $class;                                    
    }        
}

function prepareResultsIfCreateFactoryIsNeeded($array)
{
    $new = [];
    foreach($array as $result)
    {
        $tmp = clone $result;
        if($result->methodCalled === 'create')
        {
            $tmp->newPropName .= 'Factory';
            $tmp->class       .= 'Factory';
        }
        $new[] = $tmp;
    }
    return $new;
}

function performInjectionAndReplaceObjectManager($results, $tokens_all)
{
    foreach($results as $file=>$array)
    {        
        $array = prepareResultsIfCreateFactoryIsNeeded($array);
        replaceObjectManager($file, $array, $tokens_all);                             
        performInjection($file, $array);        
    }
}

function getBaseMagentoDirFromFile($dir)
{
    $dir    = realpath($dir);
    $split  = '/app/code/';
    $parts  = explode($split, $dir);
    if(count($parts) === 1)
    {
        $split = '/vendor/';
        $parts = explode($split, $dir);   
    }
    return array_shift($parts) . rtrim($split,'/');
}

function extractFullClassExtends($tokens)
{
    $c=0;
    $flag = false;
    $all = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_EXTENDS')
        {
            $flag = true;
            continue;
        }
        
        if($flag && !in_array($token->token_name, ['T_STRING','T_NS_SEPARATOR']))
        {
            break;
        }
        
        if($flag)
        {
            $all[] = $token;
        }        
        $c++;        
    }
    
    return implode('',array_map(function($item){
        return $item->token_value;
    }, $all));        
}

function getBaseConstructor($file, $tokens)
{
    $base       = getBaseMagentoDirFromFile($file);
    $class      = extractFullClassExtends($tokens);

    $base_file  = $base . str_replace('\\','/',$class) . '.php';
    
    $base_contents = file_get_contents($base_file);
    $function   = getFunctionFromCode($base_contents, '__construct');
}

/**
* ALPHA: Fixes direct use of PHP Object Manager
* argument foobar @callback exampleOfACallback
* @command magento2:fix_direct_om
* @argument folder Folder to scan
* @argument extensions File extensions? [php, phtml]
*/
function pestle_cli($arguments, $options)
{
    output("TODO: When there's not an existing __construct");
    output("TODO: When file doesn't exist");
    output("TODO: Flag to ask if you want to replace a file");
    output("TODO: Prop Name \Foo\Bar\Splat\Baz\Boo ->barBazBoo");    
    
    defineStates(); 
    define('TOKEN_BASELINE',    0);
    define('TOKEN_REMOVING_OM', 1);
    
    extract($arguments);

    $files = getFiles($folder, $extensions);   
    foreach($files as $file)
    {                
        // output('.');        
        if(preg_match('%.bak.php%', $file))
        {
            // output("{$file} looks like a backup, skipping.");
            continue;
        }
        
        // output($file);
        $tokensAll  = pestle_token_get_all(file_get_contents($file));
        $tokens     = tokensFilterWhitespace($tokensAll);                
        
        // getBaseConstructor($file, $tokens);
        
        
        $results    = processFile($file, $tokensAll, $tokens);        
        outputResults($results);  
        
        //do the fixing
        validateResults($results);
        #performInjectionAndReplaceObjectManager($results, $tokensAll);
    }
    output("Done");
}
