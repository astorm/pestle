<?php
namespace Pulsestorm\Magento2\Cli\List_Commands;
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\getAtCommandFromDocComment');
pestle_import('Pulsestorm\Pestle\Library\getDocCommentAsString');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Build_Command_List\includeAllModuleFiles');
/**
* Lists help
* Read the doc blocks for all commands, and then
* outputs a list of commands along with thier doc
* blocks.  
* @command list_commands
*/
function pestle_cli($argv)
{
    includeAllModuleFiles();
    
    $user = get_defined_functions()['user'];
    $executes = array_filter($user, function($function){
        $parts = explode('\\', $function);
        $function = array_pop($parts);
        return strpos($function, 'pestle_cli') === 0;
    });
    
        
    $commands = array_map(function($function){
        $r       = new ReflectionFunction($function);
        $command = getAtCommandFromDocComment($r);
        return [
            'command'=>$command,
            'help'=>getDocCommentAsString($r->getName()),
        ];
        // $function = str_replace('execute_', '', $function);
        // $parts = explode('\\', $function);
        // return array_pop($parts);
        // return $function;
    }, $executes);

    $command_to_check = array_shift($argv);        

    if($command_to_check)
    {
        $commands = array_filter($commands, function($s) use ($command_to_check){
            return $s['command'] === $command_to_check;
        });
    }
    output('');
    
    if(count($commands) > 1)
    {
        outputTitle();
        outputCredits();
        outputUsage();
        output('');
        outputAvaiableCommands($commands);
        return;    
    }
    
    //only single commands left
    foreach($commands as $command)
    {
        output("Usage: ");
        output("    $ pestle.phar ", $command['command']);
        output('');
        output('Arguments:');
        output('');        
        output('Options:');
        output('');
        
        output("Help:");
        output(preg_replace('%^%m','    $0',wordwrap($command['help'],70)));
        output('');
        output('');
    }
}

function getWhitespaceForCommandList($commands, $command_name)
{
    static $longest;
    if(!$longest)
    {
        $longest = 0;
        foreach($commands as $command)
        {
            $length = strlen($command['command']);
            if($length > $longest)
            {
                $longest = $length;
            }
        }
    }
    
    $numberOfSpaces = ($longest - strlen($command_name)) + 2;
    return str_repeat(' ', $numberOfSpaces);
}

function getCommandsToHide()
{
    return [
        'generate_module',
        'generate_acl',
//         'generate_command',
        'generate_config_helper',
        'generate_crud_model',
        'generate_di',
        'generate_install',
        'generate_layout_xml',
        'generate_menu',
        'generate_observer',
        'generate_plugin_xml',
        'generate_psr_log_level',
        'generate_registration',
        'generate_route',
        'generate_theme',
        'generate_view',     
    ];
}

function outputAvaiableCommands($commands)
{
    $toHide = getCommandsToHide();
    output('Available commands:');
    foreach($commands as $command)
    {
        if(in_array(trim($command['command']), ['library']))
        {
            continue;
        }
        
        if(in_array($command['command'], $toHide))
        {
            continue;
        }
//         output("Name");
        output('  ', $command['command'],
            getWhitespaceForCommandList($commands, $command['command']), 
            substr(preg_replace('%[\r\n]%',' ',$command['help']),0, 30)
        );
//         output('');
//         output("Description");
//         output(preg_replace('%^%m','    $0',wordwrap($command['help'],70)));
//         output('');
//         output('');
    }
}

function outputUsage()
{
    output('Usage:');
    output('  pestle command_name [options] [arguments]');
}

function outputCredits()
{
    output('pestle by Pulse Storm LLC');
    output('');
}

function outputTitle()
{
    $logo = <<<LOGO
                  _   _      
                 | | | |     
  _ __   ___  ___| |_| | ___ 
 | '_ \ / _ \/ __| __| |/ _ \
 | |_) |  __/\__ \ |_| |  __/
 | .__/ \___||___/\__|_|\___|
 | |                         
 |_|    
LOGO;
    output($logo);
}