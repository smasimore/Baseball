<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

function get_html($url) {
    $ch = curl_init();
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; //browsers keep this blank.  
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows;U;Windows NT 5.0;en-US;rv:1.4) Gecko/20030624 Netscape/7.1 (ax)');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE); 
    $result = curl_exec ($ch);
    curl_close ($ch);
    return($result);
}


function findOverlap($string_1, $string_2) {
    $string_1_length = strlen($string_1);
    $string_2_length = strlen($string_2);
    $return          = '';
    if ($string_1_length === 0 || $string_2_length === 0)
    {
        return $return;
    }
    $longest_common_subsequence = array();
    $longest_common_subsequence = array_fill(0, $string_1_length, array_fill(0, $string_2_length, 0));
    $largest_size = 0;
 
    for ($i = 0; $i < $string_1_length; $i++)
    {
        for ($j = 0; $j < $string_2_length; $j++)
        {
            if ($string_1[$i] === $string_2[$j])
            {
                if ($i === 0 || $j === 0)
                {
                    $longest_common_subsequence[$i][$j] = 1;
                }
                else
                {
                    $longest_common_subsequence[$i][$j] = $longest_common_subsequence[$i - 1][$j - 1] + 1;
                }
 
                if ($longest_common_subsequence[$i][$j] > $largest_size)
                {
                    $largest_size = $longest_common_subsequence[$i][$j];
                    $return       = '';
                }
 
                if ($longest_common_subsequence[$i][$j] === $largest_size)
                {
                    $return = substr($string_1, $i - $largest_size + 1, $largest_size);
                }
            }
        }
    }
    return $return;
}

function addExample() {
    global $post_intersect, $pre_intersect, $source_code, $full_site;
    echo "Enter a something that isn't highlighted that should be: ";
    $handle = fopen("php://stdin","r");
    $example_4 = strtolower(trim(fgets($handle)));
    $location_4 = strpos($source_code, $example_4);
    $isolate_4 = substr($source_code, ($location_4 - 20), 40);
    $pre_4 = split_string($isolate_4, $example_4, BEFORE, EXCL);
    $post_4 = split_string($isolate_4, $example_4, AFTER, EXCL);
    $post_intersect = findOverlap($post_intersect, $post_4);
    $pre_intersect = findOverlap($pre_intersect, $pre_4);
    $post_intersect_format = str_replace('"', '\"', $post_intersect);
    $pre_intersect_format = str_replace('"', '\"', $pre_intersect);
    $result = "parse_array_clean($"."source_code, '$pre_intersect_format', '$post_intersect_format');"."\n";
    echo "Try this new parse"."\n";
    echo $result."\n";
    $test_results =  parse_array_clean($source_code, $pre_intersect, $post_intersect);
    foreach ($test_results as $stat) {
        $raw_stat = $pre_intersect.$stat.$post_intersect;
        $highlight_stat = "<mark>$stat</mark>";
        $final_stat = $pre_intersect.$highlight_stat.$post_intersect;
        $full_site = str_replace($raw_stat, $final_stat, $full_site);
    }
    $csv_name = '/Library/Webserver/Documents/scraper.php';
    export_csv($csv_name, array(array($full_site)));
    echo "Check out www.sabertoothventures.com/scraper.php to view what will be scraped"."\n"."\n";
}

echo "\n"."What website are you trying to scrape? ";
$handle = fopen("php://stdin","r");
$target = strtolower(trim(fgets($handle)));
$source_code = scrape($target);
// Save a version of the full html for possible rendering later
$full_site = $source_code;

echo "Enter a word directly above the data you want to scrape (reccommended) or press ENTER to continue: ";
$handle = fopen("php://stdin","r");
$split_keyword = strtolower(trim(fgets($handle)));
if ($split_keyword) {
    $source_code = split_string($source_code, $split_keyword, AFTER, EXCL);
}
echo "Now enter 3 examples of what you want to scrape..."."\n";
echo "Example 1: ";
$handle = fopen("php://stdin","r");
$example_1 = strtolower(trim(fgets($handle)));
echo "Example 2: ";
$handle = fopen("php://stdin","r");
$example_2 = strtolower(trim(fgets($handle)));
echo "Example 3: ";
$handle = fopen("php://stdin","r");
$example_3 = strtolower(trim(fgets($handle)));

$location_1 = strpos($source_code, $example_1);
$location_2 = strpos($source_code, $example_2);
$location_3 = strpos($source_code, $example_3);
$isolate_1 = substr($source_code, ($location_1 - 20), 40);
$isolate_2 = substr($source_code, ($location_2 - 20), 40);
$isolate_3 = substr($source_code, ($location_3 - 20), 40);
$pre_1 = split_string($isolate_1, $example_1, BEFORE, EXCL);
$post_1 = split_string($isolate_1, $example_1, AFTER, EXCL);
$pre_2 = split_string($isolate_2, $example_2, BEFORE, EXCL);
$post_2 = split_string($isolate_2, $example_2, AFTER, EXCL);
$pre_3 = split_string($isolate_3, $example_3, BEFORE, EXCL);
$post_3 = split_string($isolate_3, $example_3, AFTER, EXCL);
$post_intersect = findOverlap($post_1, findOverlap($post_2, $post_3));
$pre_intersect = findOverlap($pre_1, findOverlap($pre_2, $pre_3));
$post_intersect_format = str_replace('"', '\"', $post_intersect);
$pre_intersect_format = str_replace('"', '\"', $pre_intersect);
$result = "parse_array_clean($"."source_code, '$pre_intersect_format', '$post_intersect_format');"."\n";

if (!$result) {
    exit("Sorry, you'll likely have to do this manually for now");
} else {
    echo "Use the function below to parse the data you are looking for!"."\n";
    echo $result."\n";
    $test_results =  parse_array_clean($source_code, $pre_intersect, $post_intersect);
    foreach ($test_results as $stat) {
        $raw_stat = $pre_intersect.$stat.$post_intersect;
        $highlight_stat = "<mark>$stat</mark>";
        $final_stat = $pre_intersect.$highlight_stat.$post_intersect;
        $full_site = str_replace($raw_stat, $final_stat, $full_site);
    }
    $csv_name = '/Library/Webserver/Documents/scraper.php';
    export_csv($csv_name, array(array($full_site)));
    echo "Check out www.sabertoothventures.com/scraper.php to view what will be scraped"."\n"."\n";
}

while (true) {
    echo "Did you get everything you wanted? (y/n) ";
    $handle = fopen("php://stdin","r");
    $confirm = strtolower(trim(fgets($handle)));
    if ($confirm == 'y') {
        exit('Awesome, enjoy!');
    } else {
        addExample();
    } 
}

?>
