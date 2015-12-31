<?php
namespace Pulsestorm\Magento2\Cli\List_Commands;
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\getAtCommandFromDocComment');
pestle_import('Pulsestorm\Magento2\Cli\Library\getDocCommentAsString');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
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
    foreach($commands as $command)
    {
        output("Name");
        output("    ", $command['command']);
        output('');
        output("Description");
        output(preg_replace('%^%m','    $0',wordwrap($command['help'],70)));
        output('');
        output('');
    }
}