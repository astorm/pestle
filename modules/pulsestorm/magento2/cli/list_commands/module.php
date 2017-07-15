<?php
namespace Pulsestorm\Magento2\Cli\List_Commands;
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\getAtCommandFromDocComment');
pestle_import('Pulsestorm\Pestle\Library\getDocCommentAsString');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Build_Command_List\includeAllModuleFiles');
pestle_import('Pulsestorm\Pestle\Runner\commandNameToDocBlockParts');
/**
* Lists help
* Read the doc blocks for all commands, and then
* outputs a list of commands along with thier doc
* blocks.  
* @option is-machine-readable pipable/processable output?
* @command list-commands
*/
function pestle_cli($argv, $options)
{
    includeAllModuleFiles();
    
    $user = get_defined_functions()['user'];
    $executes = array_filter($user, function($function){
        $parts = explode('\\', $function);
        $function = array_pop($parts);
        return strpos($function, 'pestle_cli') === 0 &&
               strpos($function, 'pestle_cli_export') === false;
    });
    
        
    $commands = array_map(function($function){
        $r       = new ReflectionFunction($function);
        $command = getAtCommandFromDocComment($r);
        return [
            'command'=>$command,
            'help'=>getDocCommentAsString($r->getName()),
        ];
    }, $executes);

    // var_dump($commands);
    $command_to_check = array_shift($argv);        

    if($command_to_check)
    {
        $commands = array_filter($commands, function($s) use ($command_to_check){
            return $s['command'] === $command_to_check || 
                $s['command'] === str_replace('_','-',$command_to_check);
        });
    }

    if(array_key_exists('is-machine-readable', $options) && !is_null($options['is-machine-readable'])){
        $docBlockAndCommand = commandNameToDocBlockParts($command_to_check);
        foreach ($docBlockAndCommand['docBlockParts']['argument'] as $argument){
            output(getAtArguementType($argument));
        }
        return;
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

function getAtArguementType($arguement){
    preg_match('/^[a-zA-Z0-9_]+/', $arguement, $matches);
    if(count($matches) < 1){
        return '';
    }

    if(count($matches) > 1){
        throw new Exception('Multiple types found for arguement');
    }

    return $matches[0];
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

/**
 * We started pestle without the magento2:generate namespace
 * These commands were the original generation commands. We
 * eventually replaced them with magento2:generate:module style
 * commands by having the magento2:generate:module command
 * call into the original generate_module module's pestle_cli
 * function.  The generate_module style commands still exist, 
 * for backwards compatability with code and docs, but we hide
 * them from the list.  
 */
function getCommandsToHide()
{
    return [
        'generate_module',
        'generate_acl',
        'generate_command',
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
        'wp_export_xml',         
        'wp_urls',
        'generate_pestle_command',
        'pestle_clear_cache',
        'generate-module',
        'generate-acl',
        'generate-command',
        'generate-config-helper',
        'generate-crud-model',
        'generate-di',
        'generate-install',
        'generate-layout-xml',
        'generate-menu',
        'generate-observer',
        'generate-plugin-xml',
        'generate-psr-log-level',
        'generate-registration',
        'generate-route',
        'generate-theme',
        'generate-view',     
        'wp-export-xml',         
        'wp-urls',
        'generate-pestle-command',
        'pestle-clear-cache',        
    ];
}

function sortCommandsIntoSection($commands)
{
    $toHide = getCommandsToHide();
    $commandSections = [];    
    foreach($commands as $command)
    {                
        if(in_array($command['command'], $toHide))
        {
            continue;
        }    
        $section = 'Uncategorized';
        if(strpos($command['command'], ':') !== false)
        {
            $parts = explode(':', $command['command']);
            $section = ucWords(array_shift($parts));
            if(count($parts) > 1)
            {
                $section .= ' ' . ucWords(array_shift($parts));
            }
        }        
        $commandSections[$section][] = $command;
    }
    ksort($commandSections);
    foreach($commandSections as $section=>$commands)
    {
        usort($commands, function($a, $b){
            if ($a['command'] == $b['command']) {
                return 0;
            }
            return ($a['command'] < $b['command']) ? -1 : 1;        
        });
        $commandSections[$section] = $commands;
    }
    return $commandSections;
}

function outputWithShellColor($toOutput, $colorCode=33)
{
    output(
        getStringWrappedWithShellColor($toOutput, $colorCode)
    );
}

function getStringWrappedWithShellColor($string, $colorCode)
{
    return "\033[{$colorCode}m" . $string . "\033[0m";
}

function outputSectionHeader($section)
{
    output('');
    outputWithShellColor($section, 33);
}

function outputCommandListing($command, $commands)
{
    $lines = preg_split('%[\r\n]%',$command['help']);
    $firstLine = array_shift($lines);
    if(!$firstLine)
    {
        $firstLine = 'NULL Command?  Fix this pls.';
    }    
    output( '  ', 
            getStringWrappedWithShellColor($command['command'], 32),
            getWhitespaceForCommandList($commands, $command['command']), 
            $firstLine);
}

function shouldSkipShowingCommand($command)
{
    $toHide = getCommandsToHide();
    if(in_array(trim($command['command']), ['library']))
    {
        return true;
    }

    if(in_array($command['command'], $toHide))
    {
        return true;
    }    
    
    return false;
}

function outputAvaiableCommandsBySection($commandSections, $commands)
{
   
    foreach($commandSections as $section=>$commandsSorted)
    {
        $commandsSorted = $commandSections[$section];
        outputSectionHeader($section);
        foreach($commandsSorted as $command)
        {
            if(shouldSkipShowingCommand($command))
            {
                continue;
            }        
            outputCommandListing($command, $commands);
        }
    }
}

function outputAvaiableCommands($commands)
{    
    output('Available commands:');
    $commandSections = [
        'Uncategorized'=>$commands
    ];
    $commandSections = sortCommandsIntoSection($commands);
    outputAvaiableCommandsBySection($commandSections, $commands);
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