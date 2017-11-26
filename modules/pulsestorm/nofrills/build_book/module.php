<?php
namespace Pulsestorm\Nofrills\Build_Book;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* BETA: Command for building No Frills Magento 2 Layout
*
* @command pulsestorm:build-book
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
        'src/toc.md',    
        'src/chapter-0-introduction.md',
        'src/chapter-1-blocks-template-php.md',
        'src/chapter-2-layout-xml.md',
        'src/chapter-3-layouthandles.md',
        'src/chapter-4-page-layout.md',
        'src/chapter-5-themes.md',
        'src/chapter-6-advanced-xml-loading.md',
        'src/chapter-7-frontend-overview.md',
        'src/chapter-8-adding-frontend-files-to-module.md',
        'src/chapter-9-serving-frontend-file.md',
        'src/chapter-10-adding-frontend-layout-xml.md',
        'src/chapter-11-knockout-scopes.md',
        'src/appendix-areas.md',
        'src/appendix-autoload.md',
        'src/appendix-cache.md',        
        'src/appendix-cli.md',
        'src/appendix-components.md',
        'src/appendix-curl.md',
        'src/appendix-di.md',
        'src/appendix-frontend-build.md',
        'src/appendix-interfaces.md',
        'src/appendix-magento-modes.md',
        'src/appendix-unix-find.md',
        'src/appendix-view-source.md',
        'src/appendix-cache.md',                                           
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
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.html `;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub3`;                
    
    echo `tar -cvf output/Pulsestorm_Nofrillslayout.tar -C /Users/alanstorm/Sites/magento-2-1-0.dev/project-community-edition app/code/Pulsestorm/Nofrillslayout`;
    $date = date('Y-m-d-H-i-s',time());
    $zip = $date . '.zip';
    echo `zip -r $zip output`;
    
    $result     = `wc -w /tmp/working.md`;
    $parts      = preg_split('%\s{1,1000}%', trim($result));
    $word_count = array_pop($parts);
    
    $word_count = preg_split('%\s{1,100}%', trim($result));
        
    $word_count = array_shift($word_count);
    
    $cmd = "echo \"$date $word_count\" >> wordcount.txt";
    `$cmd`;
    readfile('wordcount.txt');
    // echo `cat wordcount.txt`;
}
