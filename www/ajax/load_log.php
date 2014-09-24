<?php

$name = $_GET['name'];
$length = $_GET['length'];

$filename = "../" . $name . "_errors.txt";
$fp = fopen( $filename, "r" ) or die("Couldn't open $filename");
$text = array();
while (!feof($fp)) {
    $line = fgets($fp, 10000000);
    $text[] = $line;
}

// Remove empty lines
$text = array_values(array_filter($text, "trim"));

$html = '';
foreach ($text as $i => $row) {
    if ($i < $length) {
        continue;
}
    $id = "row_$i";
    if ($i % 2 == 0) {
        $html .= "<li id=$id class='even_list' style='width=100%;padding:5px;'
            onclick='highlight($id); update_details($id);'>$row</li>";
    } else {
        $html .= "<li id=$id class='odd_list' style='width=100%;padding:5px;'
            onclick='highlight($id); update_details($id)'>$row</li>";
    }
}

echo $html;

?>
