<?php
namespace Pulsestorm\Magento2\Cli\Orphan_Content;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
function getUrl($url)
{
    output("Fetching $url");
    return `curl --silent $url`;
}

function output($string)
{
    echo $string,"\n";
}

function getUrlsFromHtml($html)
{
    $urls = array();
    $xml = new \DomDocument;
    @$xml->loadHtml($html);
    $xml = $xml->saveXml();
    $xml = str_replace('xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml"',
    'xmlns="http://www.w3.org/1999/xhtml"', $xml);    
    $xml = str_replace('xml:lang="en" lang="en" xml:lang="en"',
    'xml:lang="en" lang="en"',$xml);
    $xml = simplexml_load_string(trim($xml));    
    $xml->registerXpathNamespace('e','http://www.w3.org/1999/xhtml');
    $nodes = $xml->xpath('//e:a');
    foreach($nodes as $node)
    {
        $urls[] = (string) $node['href'];
    }
    return $urls;
}
function fetchAllUrls()
{
    $urls = array();
    $urls['archive']        = getUrlsFromHtml(getUrl('http://alanstorm.com/archives'));
    $urls['magento']        = getUrlsFromHtml(getUrl('http://alanstorm.com/category/magento'));
    $urls['magento-2']      = getUrlsFromHtml(getUrl('http://alanstorm.com/category/magento-2'));    
    $urls['oro']            = getUrlsFromHtml(getUrl('http://alanstorm.com/category/orocrm'));
    $urls['sugarcrm']       = getUrlsFromHtml(getUrl('http://alanstorm.com/category/sugarcrm'));
    $urls['drupal']         = getUrlsFromHtml(getUrl('http://alanstorm.com/category/drupal'));
    $urls['webos']          = getUrlsFromHtml(getUrl('http://alanstorm.com/category/webos'));
    $urls['python']         = getUrlsFromHtml(getUrl('http://alanstorm.com/category/python'));
    $urls['applescript']    = getUrlsFromHtml(getUrl('http://alanstorm.com/category/applescript'));
    $urls['modern_php']     = getUrlsFromHtml(getUrl('http://alanstorm.com/category/modern_php'));
    $urls['laravel']        = getUrlsFromHtml(getUrl('http://alanstorm.com/category/laravel'));
    return $urls;
}

function normalizeUrls($urls)
{
    foreach($urls as $type=>$array)
    {
        foreach($array as $key=>$url)
        {
            $url = rtrim($url,'/');
            $url = str_replace('http://alanstorm.com',      '', $url);
            $url = str_replace('http://www.alanstorm.com',  '', $url);
            $array[$key] = $url;
        }
        
        $urls[$type] = $array;
    }
    return $urls;
    
}

function removeIrrelevantDataFromUrls($urls)
{
    $to_remove = array('/atom','/archives','/project','/contact','/links','/projects',
    '/site/contact','/about'
    );
    
    //URLs I don't want to categorize now
    $to_remove = array_merge($to_remove, array(
        "/commerce-bug-2-5-graphviz",
        "/seo",
        "/bust",
        "/digg",
        "/iphone",
        "/iphone1",
        "/mt4beta",
        "/xsltphp",
        "/Centered",
        "/net_book",
        "/blackbook",
        "/ie8_redux",
        "/mitlaptop",
        "/aspell_osx",
        "/freshmaker",
        "/How_Odd___",
        "/php_market",
        "/why_safari",
        "/ascii_table",
        "/uri_cleanup",
        "/10_4_Upgrade",
        "/bbedit_ctags",
        "/desktoplinux",
        "/laptopchange",
        "/tt4/archives",
        "/why_it_sucks",
        "/recursive_fud",
        "/stackoverflow",
        "/xquery_random",
        "/cut_copy_paste",
        "/macrumors_ajax",
        "/recentprojects",
        "/content_courier",
        "/dot_mac_gallery",
        "/welcome_to_2001",
        "/mod_rewrite_tips",
        "/serversideimages",
        "/ipad_consequences",
        "/secrets_of_design",
        "/whitespace_begone",
        "/too_many_addresses",
        "/aligning_dot_labels",
        "/commerce_bug_paypal",
        "/event_apart_seattle",
        "/nerd_notes_2007_oct",
        "/nothing_to_see_here",
        "/sugarcrm_model_bean",
        "/url_regex_explained",
        "//feeds_working_again",
        "/domdocument_php_stop",
        "/firefox_native_never",
        "/javascript_plus_plus",
        "/macbook_battery_woes",
        "/objective_c_selector",
        "/printing_google_maps",
        "/simple_php_job_queue",
        "/what_what_i_thinking",
        "/parsing_html_with_php",
        "/thinkup_stackexchange",
        "/macworld_2008_thoughts",
        "/web_standards_2008_sep",
        "/Five_Firefox_Extensions",
        "/inbox_fear_and_loathing",
        "/magento_commercebug_1_5",
        "/preemptive_recall_apple",
        "/test_driven_development",
        "/ie_8_standards_whinefest",
        "/install_rhino_javascript",
        "/magento_commerce_bug_two",
        "/more_magento_july18_2011",
        "/jquery_object_literal_oop",
        "/magento_api_helper_manual",
        "/magento_debug_release_1_4",
        "/markdown_hosted_wordpress",
        "/OS_X_10_4_and_transmit_22",
        "/objective_c_selector_part_2",
        "/objective_c_selector_part_3",
        "/objective_c_selector_part_4",
        "/magento_quickies_july_4_2011",
        "/pulse_storm_github_migration",
        "/magento_quickies_june_25_2011",
        "/bbedit_command_line_new_window",
        "/googles_three_new_web_browsers",
        "/interfaces-and-abstract-classes",
        "/magento_quickies_august_22_2011",
        "/javascript_command_line_beautifier",
        "/magento_config_revisited_interlude",
        "/in_depth_magento_dispatch_interlude",
        "/contents_shifting_please_remain_seated",
        "/recently_magento_quickies_august_1_2011",
        "/the_two_futures_for_javascript_libraries",
        "/magento_commerce_bug_session_based_toggles",
        "/setting_up_a_zend_application_in_a_subdirectory",
        "/if_it_aint_broke_make_it_slower_so_we_can_keep_busy", 
        '/methods_objective_c_deeply_weird',
        '/station_identification_2014',
        '/magento_ultimate_module_creator_review',
        '/magento_ultimate_module_creator_review',
        '/an_open_letter_to_magentos_leaders',
        '/magento_2_book_review_theme_web_page_assets',
        '/patreon_for_magento_2_content'
    ));

    foreach($urls as $type=>$array)
    {
        $new = array();
        foreach($array as $key=>$url)
        {            
            if(strpos($url, '/category') === 0)
            {
                continue;
            }
            
            if(strpos($url, 'http') === 0)
            {
                continue;
            }
            
            if(!$url)
            {
                continue;
            }
            
            if(in_array($url, $to_remove))
            {
                continue;
            }
            
            if($url[0] == '#')
            {
                continue;
            }            
            $new[] = $url;
        }
        $urls[$type] = $new;
    }
    return $urls;
}

/**
* One Line Description
*
* @command orphan_content
*/
function pestle_cli($argv)
{

    $urls = fetchAllUrls();
    $urls = normalizeUrls($urls);
    $urls = removeIrrelevantDataFromUrls($urls);

    $urls_archive = $urls['archive'];
    unset($urls['archive']);
    
    $missing = $urls_archive;
    foreach($urls as $key=>$urls)
    {
        $missing = array_diff($missing, $urls);
    }

    output("The following array contains your orphan links: ");
    var_dump($missing);

}
