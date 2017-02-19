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
    if(strpos($row['Type'],'Transfer to Bank') !== false)
    {
        return null;
    }
    $iif      = getIifTemplate();
    $iif      = str_replace('<$date$>',         $row['Date'],       $iif);
    $iif      = str_replace('<$entity$>',       $row['Name'],       $iif);
    $iif      = str_replace('<$amount$>',       trim($row['Net']),        $iif);
    
    $product_title = $row['Item Title'];
    if(!$product_title)
    {
        $product_title = $row['Subject'];
    }
    
    //dupe "no title" behavior
    if($product_title)
    {
        $iif      = str_replace('<$product_name$>', $product_title, $iif);
    }
    else
    {
        $iif      = str_replace('"<$product_name$>"' . "\t", '', $iif);
    }
    
    $iif      = str_replace('<$amount_full$>',  number_format(($row['Gross'] * -1),2), $iif);    
    $iif      = str_replace('<$amount_fee$>',   number_format(($row['Fee'] * -1),2), $iif);        

    if((int) $row['Fee'] === 0)
    {
        $parts = preg_split('%[\r\n]{1,2}%', $iif);
        $parts = array_filter($parts, function($item){
            return strpos($item, '"Bank Fee"') === false;
        });
        
        $iif = implode("\n",$parts);
        if(strpos($iif, '"Bank Fee"') === false)
        {
            $iif = str_replace('Express Checkout Payment Received', 'Payment Received', $iif);
        }                 
    }
    return $iif;    
}

function getIifTemplate()
{
    $template = 'TRNS	"<$date$>"	"Paypal"	"<$entity$>"	"Express Checkout Payment Received"	<$amount$>	"<$product_name$>"	
SPL	"<$date$>"	"Sales-Software"	"<$entity$>"	<$amount_full$>
SPL	"<$date$>"	"Bank Fee"	Fee	<$amount_fee$>
ENDTRNS';
    return $template;
}

/**
* BETA: Converts a CSV file to .iif
*
* @command csv_to_iif
* @argument path_to_file CSV File
*/
function pestle_cli($argv)
{
    extract($argv);
    $handle = fopen($path_to_file, 'r');
    $process_function = false;
    $iifs = [];
    while($line = fgetcsv($handle))
    {
        if(!$process_function)
        {
            $process_function = getProcessFunctionFromFirstLine($line);
        }        
        $iifs[] = call_user_func($process_function, $line);
    }
    $iifs = array_filter($iifs);
    $iifs = array_reverse($iifs);
    output('!TRNS	DATE	ACCNT	NAME	CLASS	AMOUNT	MEMO
!SPL	DATE	ACCNT	NAME	AMOUNT	MEMO
!ENDTRNS');
    output(implode("\n", $iifs));
}
