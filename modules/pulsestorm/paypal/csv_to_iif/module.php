<?php
namespace Pulsestorm\Paypal\Csv_To_Iif;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
use function extract;
use Exception;

function getProcessFunctionFromFirstLine($line)
{
    $line = array_map('trim', $line);
    if((strpos($line[0],'Name') !== false) && $line[1] === 'Storm, Alan')
    {
        return __NAMESPACE__ . '\processPaypal';
    }
    throw new Exception("Unknown Process Function");
}

function joinHeadersAndValue($headers, $values)
{
    if(count($headers) != count($values))
    {
        throw new Exception("Header and value coutn don't match");
    }
    
    $new = [];
    for($i=0;$i<count($headers);$i++)
    {
        $new[($headers[$i])] = $values[$i];
    }
    return $new;
}

function processPaypal($line)
{
    static $headers;
    $to_skip = ['Name','Email','Payer ID','Report Date','Available Balance'];
    foreach($to_skip as $key)
    {
        if((strpos($line[0],$key) !== false))
        {
            return;
        }    
    }
    if(!$headers && $line[0] === 'Date')
    {
        $headers = $line;
        return;
    }
    $row = joinHeadersAndValue($headers, $line);
    var_dump($row);
        
    
}

function getIifTemplate()
{
    $template = 'TRNS	"<$date$>"	"Paypal"	"<$entity$>"	"<$Express Checkout Payment Received$>"	<$amount$>	"<$No Frills Magento Layout - No Frills Magento Layout: Single User License$>"	
SPL	"6/29/2015"	"Sales-Software"	"<$entity$>"	-<$amount_full$>
SPL	"6/29/2015"	"Bank Fee"	Fee	<$amount_fee$>
ENDTRNS';

}

/**
* One Line Description
*
* @command csv_to_iif
* @argument path_to_file CSV File
*/
function pestle_cli($argv)
{
    extract($argv);
    $handle = fopen($path_to_file, 'r');
    $process_function = false;
    while($line = fgetcsv($handle))
    {
        if(!$process_function)
        {
            $process_function = getProcessFunctionFromFirstLine($line);
        }        
        call_user_func($process_function, $line);
    }
    output("Hello Sailor");
}
