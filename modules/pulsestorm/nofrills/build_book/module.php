<?php
namespace Pulsestorm\Nofrills\Build_Book;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* One Line Description
*
* @command build_book
*/
function pestle_cli($argv)
{
    $files = glob('src/*.md');
    if(count($files) === 0)
    {
        output("No src/, bailing");
        exit;
    }
    
    $using = [
        'src/todo.md',
        'src/toc.md',
        'src/chapter0.md',
        'src/chapter1.md',
        'src/chapter2.md',            
        'src/chapter3.md',         
        'src/chapter4.md',         
        'src/chapter5.md',         
        'src/chapter6.md',         
        'src/chapter7.md',         
        'src/chapter8.md',         
        'src/chapter9.md',                                                         
    ];
    
    $raw = [];
    foreach($using as $file)
    {
        if(!in_array($file, $files))
        {
            output($file . ' does not exist in src/, bailing');
            exit;
        }
        
        $raw[] = file_get_contents($file);
    }
    
    $raw = implode("\n\n", $raw);
    
    
    file_put_contents('/tmp/working.md', $raw);
    
    echo `mkdir -p output`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.tex`;
    echo `pandoc output/in-progress-no-frills.tex -s -o output/in-progress-no-frills.pdf `;
    echo `pandoc /tmp/working.md-s -o output/in-progress-no-frills.html `;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub3`;                
    
    echo `tar -cvf output/Pulsestorm_Nofrillslayout.tar -C /Users/alanstorm/Sites/magento-2-1-0.dev/project-community-edition app/code/Pulsestorm/Nofrillslayout`;
    $date = date('Y-m-d-H-i-s',time());
    $zip = $date . '.zip';
    echo `zip -r $zip output`;
    
}
