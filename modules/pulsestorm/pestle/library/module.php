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
                $option_value = $argv[$i+1];
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