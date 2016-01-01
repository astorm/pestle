<?php
namespace Pulsestorm\Pestle\Library;

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