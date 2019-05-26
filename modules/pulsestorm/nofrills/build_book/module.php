<?php
namespace Pulsestorm\Nofrills\Build_Book;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

function buildSourceFilesReport($lines)
{
    $report  = [];
    $code    = [];
    $chapter = false;
    $parsingCode = false;
    foreach($lines as $line)
    {
        if($line[0] == '#' && $line[1] != '#')
        {
            $chapter = trim($line); 
            $report[$chapter] = [];
        }        
        if(!$chapter){ continue; }
        
        if(preg_match('%^(    )|(\t)%', $line)){
            $parsingCode = true;
            $code[] = $line;            
        }
        
        if($parsingCode && !preg_match('%^(    )|(\t)%', $line)) {
            $report[$chapter][] = implode('', $code);
            $parsingCode = false;
            $code = [];
        }        
    }
    return $report;    
}

function buildSourceFiles($lines)
{
    $pathCode =  'output/code';
    @mkdir($pathCode);
    $report = buildSourceFilesReport($lines);
    $i=0;
    foreach($report as $chapter=>$samples)
    {
        $i++;
        $path = $pathCode . '/chapter-' . $i;
        if($chapter == '# Appendix')
        {
            $path = $pathCode . '/appdendix';
        }
        @mkdir($path);
        $j=0;
        foreach($samples as $sample)
        {
            $j++;
            $pathCodeFile = $path . '/' . $j . '.txt';            
            file_put_contents($pathCodeFile, $sample);
            output("Creating $pathCodeFile");
        }                
    }    
}

/**
* BETA: Command for building No Frills Magento 2 Layout
*
* @command pulsestorm:build-book
* @options --sample-book should I build a sample book instead of a full book?
*/
function pestle_cli($argv, $options)
{
    if(isset($options['sample-book']))
    {
        define('SRC','src.sample');
    }
    else
    {
        define('SRC','src');
    }
    
    
    echo `mkdir -p output`;
    $files = glob(SRC . '/*.md');
    if(count($files) === 0)
    {
        output("No " . SRC . "/, bailing");
        exit;
    }
    
    $chapters = [   
        SRC . '/chapter-0-introduction.md',
        SRC . '/chapter-1-blocks-template-php.md',
        SRC . '/chapter-2-layout-xml.md',
        SRC . '/chapter-3-layouthandles.md',
        SRC . '/chapter-4-page-layout.md',
        SRC . '/chapter-5-themes.md',
        SRC . '/chapter-6-advanced-xml-loading.md',
        SRC . '/chapter-7-frontend-css.md',
        SRC . '/chapter-8-frontend-javascript.md',
        SRC . '/chapter-9-frontend-advanced-topics.md',
        SRC . '/chapter-10-knockout-scopes.md'
    ];
    $appendices = [
        SRC . '/appendix-areas.md',
        SRC . '/appendix-autoload.md',
        SRC . '/appendix-cache.md',        
        SRC . '/appendix-cli.md',
        SRC . '/appendix-components.md',
        SRC . '/appendix-curl.md',
        SRC . '/appendix-di.md',
        SRC . '/appendix-frontend-build.md',
        SRC . '/appendix-install-module.md',
        SRC . '/appendix-interfaces.md',
        SRC . '/appendix-magento-modes.md',
        SRC . '/appendix-unix-find.md',
        SRC . '/appendix-view-source.md',                                         
    ];
    
    $raw = [];
    foreach($chapters as $file)
    {
        if(!in_array($file, $files))
        {
            output($file . ' does not exist in src/, bailing');
            exit;
        }
        
        $raw[] = file_get_contents($file);
    }
    
    $chapterAppendix = ["# Appendix\n\n"];
    foreach($appendices as $appendix)
    {
        if(!in_array($file, $files))
        {
            output($file . ' does not exist in src/, bailing');
            exit;
        }    
        $chapterAppendix[] = file_get_contents($appendix);
    }
    $raw[] = implode("\n\n", $chapterAppendix);
    $raw = implode("\n\n", $raw);
    
    
    $raw = preg_replace('/(^#[^#])/sim', ('\pagebreak' . "\n" . '$1'), $raw);
    file_put_contents('/tmp/working.md', $raw);
    
    $lines   = file('/tmp/working.md');    
    buildSourceFiles($lines);
    
    echo `pandoc /tmp/working.md --toc -s -o output/No-Frills-Magento-2-Layout.tex`;
    // echo `pandoc /tmp/working.md -V documentclass=book -V classoption=oneside --template nofrills --toc -s --listings -o output/No-Frills-Magento-2-Layout.tex`;    
    // echo `pandoc /tmp/working.md -o new.pdf --from markdown --template eisvogel --listings --toc -s -o output/No-Frills-Magento-2-Layout.tex`;            
    //     echo `pandoc output/No-Frills-Magento-2-Layout.tex -V documentclass=book -V classoption=oneside --template nofrills --toc -s --listings --latex-engine=xelatex -o output/No-Frills-Magento-2-Layout.pdf `;
    echo `pandoc output/No-Frills-Magento-2-Layout.tex -V documentclass=book -V classoption=oneside --template nofrills --toc -s --listings --pdf-engine=xelatex -o output/No-Frills-Magento-2-Layout.pdf `;
    echo `pandoc /tmp/working.md --toc -s -o output/No-Frills-Magento-2-Layout.html `;
    echo `pandoc /tmp/working.md --toc -s -o output/No-Frills-Magento-2-Layout.epub`;
    echo `pandoc /tmp/working.md --toc -s -o output/No-Frills-Magento-2-Layout.epub3`;                
    
    //echo `tar -cvf output/Pulsestorm_Nofrillslayout.tar -C /Users/alanstorm/Sites/magento-2-1-0.dev/project-community-edition app/code/Pulsestorm/Nofrillslayout`;
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
