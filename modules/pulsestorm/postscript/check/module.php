<?php
namespace Pulsestorm\Postscript\Testbed;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function generateText($text, $widthInInches, $heightInInches)
{
    $trueLeft   = 17;
    $trueTop    = 770;
    
    $lessX      = 19;   //unsure why these are needed, but they are?
    $moreY      = 20;   //unsuer why these are needed, but they are?
    
    $left   = ($trueLeft + round($widthInInches * 72)) - $lessX;
    $top    = ($trueTop - round($heightInInches * 72)) + $moreY;
    //$string = '500 707 moveto
    $string = $left . ' ' . $top . ' moveto
('.$text.') show';
    return $string;
}

function formatAmount($amount)
{
    $amount = preg_replace('%[^0-9.]%','',$amount);
    return $amount;
}

function getWordsFromAmount($amount)
{
    $parts = explode('.', $amount);
    $nw = new \Numbers_Words;
    $ret = ucwords($nw->toWords($parts[0],"en_US")) . ' and ' . $parts[1] . '/100';
    return $ret; 
}

function getCheckPostScript($date, $amount, $to, $accountFrom, $addressOne, $addressTwo)
{
    $amount = formatAmount($amount);
    $words = getWordsFromAmount($amount);
    $texts = [
        /* fold 1 */    
        [$date,    (6 + (15/16) + (5/72)), (0 + (7/8) + (5/72))],        
        [$amount,    (6 + (15/16) + (3/72)), (1 + (7/16) + (2/72))],        
        [$to,  (1 + (3/16)), (1 + (7/16))],                        
        [$words,  
            (0 + (8/16) + (2/72)), (1 + (12/16) + (2/72))],                        
        [$to,  (0 + (16/16) + (2/72)), (2 + (1/16) + (2/72))],                        
        [$addressOne,  (0 + (16/16) + (2/72)), (2 + (4/16) + (2/72))],         
        [$addressTwo,  (0 + (16/16) + (2/72)), (2 + (7/16) + (0/72))],                 
        
        /* fold 2 */    
        [$to,  (0 + (13/16) + (3/72)), (3 + (15/16))],                 
        [$date,  (6 + (0/16) + (3/72)), (3 + (15/16))],                         
        [$amount,  (7 + (4/16) + (4/72)), (4 + (1/16) + (3/72))],                         
        [$accountFrom,  (0 + (8/16) + (2/72)), (6 + (12/16) + (2/72))],                         
        [$amount,  (7 + (4/16) + (4/72)), (6 + (12/16) + (2/72))],       
                                                  
        /* fold 3 */            
        [$to,  (0 + (13/16) + (3/72)), (6 + (17/16) + (27/72))],                 
        [$date,  (6 + (0/16) + (3/72)), (6 + (17/16) + (27/72))],                         
        [$amount,  (7 + (4/16) + (4/72)), (7 + (3/16) + (30/72))],                         
        [$accountFrom,  (0 + (8/16) + (2/72)), (9 + (14/16) + (29/72))],                         
        [$amount,  (7 + (4/16) + (4/72)), (9 + (14/16) + (29/72))],                       
    ];

    $postScripts = [];
    foreach($texts as $text)
    {
        $postScripts[] = generateText($text[0], $text[1], $text[2]);
    }
    
    $string = '%!PS' . "\n";
    $string .= ( '<< /PageSize [612 792] >> setpagedevice
/Helvetica              % name the desired font
11 selectfont           % choose the size in points and establish 
                        % the font as the current one

');                        
    $string .= ( implode("\n", $postScripts) . "\n");
    $string .= ( 'showpage                % print all on the page');  
    return $string;
}

/**
* One Line Description
*
* @command postscript:check
* @argument check_date Date on Check? [11/25/17]
* @argument amount Amount? [$4,000.00]
* @argument to Check to? [Alan Storm]
* @argument from_account From Account? [Bank_Name]
* @argument address1 Address to Line One [123 Main Street]
* @argument address2 Address to Line Two [Anytown, OR 97202]
* @argument output Path To PS File? [STDOUT]
*/
function pestle_cli($argv)
{
    extract($argv);
    $postscript = getCheckPostScript($check_date, $amount, $to, $from_account,
        $address1, $address2);
        
    if($output === 'STDOUT')
    {
        output($postscript);        
    }        
    file_put_contents($output, $postscript);
}
