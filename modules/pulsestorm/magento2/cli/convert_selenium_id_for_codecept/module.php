<?php
namespace Pulsestorm\Magento2\Cli\Convert_Selenium_Id_For_Codecept;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\indexOrInput');

function getCommandAndTwoArgs($string)
{
    $parts = preg_split('%[\r\n]%',$string);
    
    foreach($parts as $part)
    {
        if(!$part){continue;}
        preg_match('%<td>(.*)</td>%six', $part, $matches);
        if(!$matches){continue;}
        if(count($matches) < 1)
        {
            var_dump($part);
            exit(__FUNCTION__);
        }
        $stuff[] = str_replace('&gt;','>',$matches[1]);
        // $part = ltrim($part, '<td>');        
        // $part = rtrim($part, '</td>');                
        // $part = rtrim('</td>', $part);
        // output($part);
    }
    
    return [
        'command'   =>$stuff[0],
        'arg1'      =>$stuff[1],
        'arg2'      =>$stuff[2],
    ];
}

function parseIntoCommands($contents)
{
    $parts  = explode('<tbody>', $contents);
    $contents = array_pop($parts);
    $parts  = preg_split('%</tr>%six', $contents);
    array_pop($parts);
    $all    = [];
    foreach($parts as $part)
    {
        $all[] = getCommandAndTwoArgs($part);
        
    }
    return $all;
}

function getCodeceptionTemplate()
{
    return '$I-><$methodName$>(<$args$>);';
}

function convertCommandPause($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','wait',$template);
    $template = str_replace('<$args$>',($info['arg1'] / 1000),$template);    
    return $template;
    // return '$I->wait('.$info['arg1'].');';
}

function getDefaultTimeoutInSeconds()
{
    return 30;
}

function convertCommandOpen($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','amOnPage',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "'",$template);    
    return $template;
}

function convertCommandClickandwait($info)
{
    $template = getCodeceptionTemplate();
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = str_replace('<$methodName$>','click',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'",$template); 
    $template .=  "\n" . convertInfoArray(['command'=>'waitForElementPresent',
                                'arg1'=>'css=body','arg2'=>'']);    
    return $template;
}

function convertCommandWaitfortext($info)
{
    $template = getCodeceptionTemplate();
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = str_replace('<$methodName$>','selectOption',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "',".$timeout.",'" . $info['arg2'] . "'",$template);    
    return $template;

}

function convertCommandSelect($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','selectOption',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "','" . $info['arg2'] . "'",$template);    
    return $template;
}

function convertCommandType($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','fillField',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "','" . $info['arg2'] . "'",$template);    
    return $template;
}

function convertCommandWaitforelementpresent($info)
{
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','waitForElement',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'," . $timeout,$template);    
    return $template; 
}

function convertCommandClick($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','click',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'",$template);    
    return $template; 
}

function convertInfoArray($info)
{
    $method = 'convertCommand' . 
        ucwords(strtolower($info['command']));
    $method = '\Pulsestorm\Magento2\Cli\Convert_Selenium_Id_For_Codecept\\' . $method;
    return call_user_func($method, $info);        
    // return '$I-><$methodName$>(<$args$>);';
}

/**
* Converts a selenium IDE html test for conception
* @todo serialize numbers as string
* @todo unescape HTML in args
* @todo remove css= ... convert id= to #
* @todo waitfortext is selectOption, has incorrect arguments, reversed
* @todo name= is not a thinge either
* @todo label= is not a thing
* @command convert_selenium_id_for_codecept
*/
function pestle_cli($argv)
{
    $file = indexOrInput('Which Selenium IDE test?', 'codecept.html', $argv, 0);
    $contents = file_get_contents($file);    
    
    $commands_and_args = parseIntoCommands($contents);
    
    $final = array_map(function($info){                
        return convertInfoArray($info);
    }, $commands_and_args);
    
    output(implode("\n", $final));
}
