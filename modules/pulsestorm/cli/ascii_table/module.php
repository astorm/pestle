<?php
namespace Pulsestorm\Cli\Ascii_Table;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

function textTableHashSum($accounts) {
    $sum = array_sum($accounts);

    arsort($accounts);
    $accounts = array_map(function($item) {
        return number_format($item, 2);
    }, $accounts);

    $longestKey = max(array_map(function($item){
        return strLen($item);
    }, array_keys($accounts)));

    $longestLine = 0;
    foreach($accounts as $key=>$value) {
        $length = strlen($key) + strlen($value);
        $longestLine = ($length > $longestLine) ? $length : $longestLine;
    }

    $longestLine += 5;

    $containerLine = '+'.str_repeat('-', $longestLine) . '+';
    output($containerLine);

    foreach($accounts as $key=>$value) {
        $paddingKey   = str_repeat(' ', $longestKey - strlen($key) + 1);
        $paddingValue = str_repeat(' ',
            $longestLine - strlen($paddingKey . $key . $value . ' | '));

        // $paddingValue = ' ';
        output( '| ' .
                $key .
                $paddingKey . '| '.
                $value .
                $paddingValue . '|');
    }
    output($containerLine);
    $print  = 'SUM: ' . number_format($sum);
    $length = $longestLine - strlen($print) - 1;
    $padding = str_repeat(' ', $length);
    // $padding = ' ';
    output('| ' . $print . $padding . '|');
    output($containerLine);
}

/**
* @command library
*/
function pestle_cli()
{
}
