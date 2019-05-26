<?php
namespace Pulsestorm\Gs\Combine;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');


function getPathToGhostScript()
{
    return '/usr/local/bin/gs';
}

function generateGhostscriptCombine($pdfs, $outputFile)
{
    $pdfs = array_map(function($pdf){
        return realpath($pdf);
    }, $pdfs);
    $cmd = getPathToGhostScript() . ' -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite ' .
        '-dPDFSETTINGS=/prepress -sOutputFile=' . $outputFile . ' ' . 
        implode(' ', $pdfs);    
        
    return $cmd;        
}

function runCommand($cmd)
{
    echo "Running $cmd","\n";
    $results = `$cmd`;
    echo $results,"\n";    
    return $results;
}

function combinePdfs($pdfs, $outputFile)
{
    $cmd = generateGhostscriptCombine($pdfs, $outputFile);
    runCommand($cmd);
}

/**
* One Line Description
*
* @command gs:combine
* @argument pdfs Comma seperated list [one.pdf,two.pdf]
* @option output-file Output file [out.pdf]
*/
function pestle_cli($argv, $options)
{
    $outputFile = isset($options['output-file']) ? $options['output-file'] : '/tmp/output.pdf';
    $pdfs       = explode(',', $argv['pdfs']);    
    $results    = combinePdfs($pdfs, $outputFile);
}
