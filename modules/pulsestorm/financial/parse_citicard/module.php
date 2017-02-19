<?php
namespace Pulsestorm\Financial\Parse_Citicard;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
function apply_column_headers($data)
{
    static $headers = false;
    $headers = !$headers ? array_keys(array_flip($data)) : $headers;		
    
    for($i=0;$i<count($headers);$i++)
    {
        $data[$headers[$i]] = $data[$i];
    }
    
    foreach($data as $key=>$value)
    {
        if(is_numeric($key))
        {
            unset($data[$key]);
        }
    }
    return $data;
}

function file_get_contents_csv($filename,$has_headers=true)
{
    $all 		= array();
    $file 		= fopen($filename,'r');
    if($has_headers)
    {
        apply_column_headers(fgetcsv($file));
    }
    while($data = fgetcsv($file))
    {
        if($has_headers)
        {
            $all[] 	= apply_column_headers($data);
        }
        else
        {
            $all[] 	= $data;
        }
        
    }
    fclose($file);	
    
    return $all;
}

function parseDescription($string)
{
    return [
        'description'=>trim($string)
    ];
//     preg_match('%\d\d\d-\d\d\d-\d\d\d\d%', $string, $matches);
//     $phone = array_pop($matches);
//     
//     $state = 
}
	
/**
* BETA: Parses Citicard's CSV files into yaml
*
* @command parse_citicard
* @argument file File to Parse?
* @argument count Starting Count?
*/
function pestle_cli($argv)
{
    $file   = $argv['file'];
    $count  = $argv['count'];
    $items  = file_get_contents_csv($file);
    foreach($items as $item)
    {
        $parts = parseDescription($item['Description']);
        $description = $parts['description'];
        if($description === 'ELECTRONIC PAYMENT-THANK YOU')
        {
            continue;
        }
        // 120-Paid On 03/10/2016:028.28 Do it Best Hardware
        output($count,'-Paid On ', $item['Date'],':',$item['Debit'], 
            ' ', $description);
        $count++;
    }
}
