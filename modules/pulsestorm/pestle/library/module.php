<?php
namespace Pulsestorm\Pestle\Library;
use ReflectionFunction;
use ReflectionClass;
use function Pulsestorm\Xml_Library\formatXmlString;

function exitWithErrorMessage($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function getShortClassNameFromClass($class)
{
    $parts = explode('\\', $class);
    return array_pop($parts);
}

function parseDocCommentAts($r)
{
    $comment = $r->getDocComment();
    $comment = preg_replace(['%^\*/%m', '%^/\*\*%m','%^\* %m','%^\*%m'], '', $comment);
    $parts   = explode('@', $comment);
    array_shift($parts);
    $parsed  = [];
    foreach($parts as $part)
    {
        $part = trim($part);
        $parts2 = preg_split('%\s%', $part);
        $name   = array_shift($parts2);
        $parsed[$name] = implode('',$parts2);
    }
    $parsed = array_map(function($thing){
        return trim($thing);
    }, $parsed);

    return $parsed;
}

function getNewClassDeclaration($class, $extends, $include_start_bracket=true)
{
    $parts = [];
    $parts[] = 'namespace';
    $parts[] = $class['namespace'] . ';';
    $parts[] = "\n";

    if($extends['class'])
    {
        $parts[] = 'use';
        if($extends['class'] === 'Abstract')
        {
            $parts[] = $extends['full_class'] . ' as AbstractClass;';
            $extends['class'] = 'AbstractClass';
        }
        else
        {
            $parts[] = $extends['full_class'] .';';
        }
    }

    $parts[] = "\n";

    $parts[] = 'class';
    $parts[] = $class['class'];
    if($extends['class'])
    {
        $parts[] = 'extends';
        $parts[] = $extends['class'];
    }
    $parts[] = "\n";
    if($include_start_bracket)
    {
        $parts[] = "{";
    }

    return preg_replace('%^ *%m', '', implode(' ', $parts));
}

function getClassFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'class');
}

function getExtendsFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'extends');
}

function getPartFromDeclaration($class, $part)
{
    $flag = false;
    foreach(explode(' ', $class) as $item)
    {
        if($flag)
        {
            return $item;
        }
        if($item === $part)
        {
            $flag = true;
        }
    }
    return null;
}

function writeStringToFile($path, $contents)
{
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path),0755,true);
    }
    $path_backup = $path . '.' . uniqid() . '.bak.php';
    if(file_exists($path))
    {
        output('Backing existing file: ' . $path_backup);
        copy($path, $path_backup);
    }
    file_put_contents($path, $contents);
    return $path;
}

function writeFormattedXmlStringToFile($path, $contents){
    return writeStringToFile($path, formatXmlString($contents));
}

function bail($message)
{
    output($message);
    exit(1);
}

function getDocCommentAsString($function)
{
    $r = new ReflectionFunction($function);
    $lines = explode("\n", $r->getDocComment());

    if(count($lines) > 2)
    {
        array_shift($lines);
        array_pop($lines);
        $lines = array_map(function($line){
            return trim(trim($line),"* ");
        }, $lines);
    }

    return trim( implode("\n", $lines) );
}

function isAboveRoot($path)
{
    $parts = explode('..', $path);
    $real  = array_shift($parts);
    $parts_real = explode('/',$real);
    array_unshift($parts, '/');
    $parts      = array_filter($parts,      __NAMESPACE__ . '\notEmpty');
    $parts_real = array_filter($parts_real, __NAMESPACE__ . '\notEmpty');

    return count($parts) > count($parts_real);
}

function notEmpty($item)
{
    return (boolean) $item;
}

function inputRawPhp()
{
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    fclose($handle);
    return $line;
}

function inputReadline($prompt=null)
{
    if(is_null($prompt))
    {
        return readline();
    }

    $parts = explode("\n", $prompt);
    $prompt = array_pop($parts);
    $preamble = implode("\n", $parts);
    if($preamble) { echo $preamble,"\n"; }
    return readline($prompt);
}

function inputPassword($prompt='')
{
    echo $prompt;
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    // add a new line since the users CR didn't echo
    output('');
    return $password;
}

function input($string, $default='')
{
    $prompt =  $string . " (".$default.")] ";
    if(!function_exists('readline'))
    {
        echo($prompt);
        $line = inputRawPhp();
    }
    else
    {
        $line = inputReadline($prompt);
    }
    if(trim($line))
    {
        return trim($line);
    }
    return $default;

}

function inputOrIndex($question, $default, $argv, $index)
{
    if(array_key_exists($index, $argv))
    {
        return $argv[$index];
    }

    return input($question, $default);
}

function getAtCommandFromDocComment($r)
{
    $props = parseDocCommentAts($r);
    if(array_key_exists('command', $props))
    {
        return $props['command'];
    }
    return null;
}

function output($string)
{
    foreach(func_get_args() as $arg)
    {
        if(is_object($string) || is_array($string))
        {
            var_dump($string);
            continue;
        }
        echo $arg;
    }
    echo "\n";
}


/* moved stuff above this line */

function isOption($string)
{
    if(strlen($string) > 2 && $string[0] === '-' && $string[0] === '-')
    {
        return true;
    }
    return false;
}

function cleanDocBlockLine($line)
{
    $parts = explode('*', $line);
    array_shift($parts);
    $line = implode('', $parts);
    return trim($line);
}

function parseDocBlockIntoParts($string)
{
    $return = [
        'one-line'      => '',
        'description'   => '',
    ];

    $lines = preg_split('%[\r\n]%', $string);
    $start_block = trim(array_shift($lines));
    if($start_block !== '/**')
    {
        return $return;
    }

    while($line = array_shift($lines))
    {
        $line = cleanDocBlockLine($line);
        if($line && $line[0] === '@')
        {
            array_unshift($lines, $line);
            break;
        }
        if(!$line) { continue;}

        if(!$return['one-line'])
        {
            $return['one-line'] = $line;
        }
        else
        {
            $return['description'] .= $line . ' ';
        }
    }
    $return['description'] = trim($return['description']);

    $all = implode("\n",$lines);
    preg_match_all('%^.*?@([a-z0-1]+?)[ ](.+?$)%mix', $all, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $return[$match[1]][] = trim($match[2]);
    }
    return $return;
}

function parseArgvIntoCommandAndArgumentsAndOptions($argv)
{
    $script  = array_shift($argv);
    $command = array_shift($argv);

    $arguments = [];
    $options   = [];
    $length = count($argv);
    for($i=0;$i<$length;$i++)
    {
        $arg = $argv[$i];
        if(isOption($arg))
        {
            $option = str_replace('--', '', $arg);

            if(preg_match('%=$%', $option))
            {
                $option = substr($option, 0,
                    strlen($option)-1);
                $option_value = $argv[$i+1];
                $i++;
            }
            else if(preg_match('%=.%', $option))
            {
                list($option, $option_value) = explode('=', $option, 2);
            }
            //the boolean options
            else if(preg_match('%^(use|is)-%', $option))
            {
                $option_value = '';
            }
            else
            {
                $option_value = '';
                if(array_key_exists($i+1, $argv))
                {
                    $option_value = $argv[$i+1];
                }
                $i++;
            }


            $options[$option] = $option_value;

        }
        else
        {
            $arguments[] = $arg;
        }
    }

    return [
        'command'   => $command,
        'arguments' => $arguments,
        'options'   => $options
    ];
}

/**
* @command library
*/
function pestle_cli($argv)
{

}
