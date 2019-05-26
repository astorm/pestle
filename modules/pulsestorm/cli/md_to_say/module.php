<?php
namespace Pulsestorm\Cli\Md_To_Say;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
use Michelf\Markdown;

function swapExtension($filename, $from, $to)
{
    return preg_replace('%\.'.$from.'$%','.' . $to, $filename);
}

/**
* Converts a markdown files to an aiff
* @command pulsestorm:md-to-say
*/
function pestle_cli($argv)
{
    $file = inputOrIndex("Path to Markdown File?", null, $argv, 0);

    $contents = file_get_contents($file);
    $html     = Markdown::defaultTransform($contents);
    $html     = preg_replace(
        '%<pre><code>.+?</code></pre>%six',
        '<p>[CODE SNIPPED].</p>',
        $html
    );
    $html = str_replace('</p>','</p><br>',$html);

    $tmp = tempnam('/tmp', 'md_to_say') . '.html';
    file_put_contents($tmp, $html);

    $cmd = 'textutil -convert txt ' . $tmp;
    `$cmd`;

    $tmp_txt    = swapExtension($tmp, 'html','txt');
    $tmp_aiff   = swapExtension($tmp, 'html','aiff');

    $cmd = "say -f $tmp_txt -o $tmp_aiff";
    output($cmd);
    // `$cmd`;
    // $tmp_txt = preg_replace('%\.html$%','.txt', $tmp);

    output($tmp_aiff);
    output("Done");
}
