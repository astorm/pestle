<?php
namespace Pulsestorm\Pestle\Exportassymfony;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Pestle\Runner\loadSerializedCommandListFromCache');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionNamesFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\getPestleImportsFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromCode');

pestle_import('Pulsestorm\Pestle\Importer\getPathFromFunctionName');

function getSymfonyConsoleBaseUse()
{
    return '
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;    
';    
}

function getSymfonyConsoleBaseBody()
{
    return '
    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }    
';    
}

function getFunctionBodiesFromCommandModueContents($contents)
{
    $functions     = getFunctionNamesFromCode($contents);        
    $functionNameToBody = [];
    foreach($functions as $function)
    {
        $functionName = $function->token_value;
        $functionNameToBody[$functionName] = getFunctionFromCode($contents, $functionName);
    }
    
    return $functionNameToBody;    
}

function getPestleImportsFunctionBodiesFromContents($contents)
{
    $imports            = getPestleImportsFromCode($contents);    
    $importedFunctionsToFiles   = array_combine($imports, 
        array_map(function($function){
            return getPathFromFunctionName($function);
        }, $imports));    
    $importedFunctionsToContents = array_map(function($file){
        return file_get_contents($file);
    }, $importedFunctionsToFiles);        
    
    $functionsFromImport = [];
    foreach($importedFunctionsToContents as $function=>$contents)
    {
        $parts = explode('\\', $function);
        $functionName = array_pop($parts);
        $functionsFromImport[$functionName] = getFunctionFromCode($contents, $functionName);
    }
    return $functionsFromImport;
}

/**
* One Line Description
*
* @command pestle:export-as-symfony
* @argument command_to_export Export Which Pestle Command? [hello-world]
* @argument full_class_name Full Symfony Class Name [Pulsestorm\SymfonyConsole\TestCommand]
*/
function pestle_cli($argv)
{
    output("@TODO: replace function calls with $this->functionCall");
    output("@TODO: Functions from pestle import: Need to rename to avoid naming conflicts");
    output("       Maybe use a slashless full namespace name?");
    // $list = loadSerializedCommandListFromCache();
    $commandModule = loadSerializedCommandListFromCache()[$argv['command_to_export']];
    $contents      = file_get_contents($commandModule);
        
    $functions              = getFunctionBodiesFromCommandModueContents($contents);
    $functionsFromImports   = getPestleImportsFunctionBodiesFromContents($contents);
    
    $classBody = getSymfonyConsoleBaseBody() . 
        implode("\n\n", $functions) .                         
        implode("\n\n", $functionsFromImports);

    $class = createClassTemplateWithUse($argv['full_class_name'], 'Command');
    $class = str_replace('<$use$>'  , getSymfonyConsoleBaseUse(), $class);
    $class = str_replace('<$body$>' , $classBody, $class);
    output($class);
    output("Done");
}
