<?php
namespace Pulsestorm\Phpdotnet;
/**
* Function found on php.net.  
* @copyright original authors
*/


/**
* @command library
*/
function pestle_cli($argv)
{
}

if ( ! function_exists('glob_recursive'))
{

    /**
    * Does not support flag GLOB_BRACE
    * http://php.net/manual/en/function.glob.php#106595
    */    
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        
        return $files;
    }
}
