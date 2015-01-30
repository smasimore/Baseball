<?php
include_once 'Page.php';

// TODO(smas): Refactor this into UI elements and css classes.
class LogPage extends Page {

    private $name;

    public function __construct($logged_in, $user) {
        parent::__construct($logged_in, true);
        $this->name = $user;
        $this->setHeader(' ');
        $this->display();
    }

    public function display() {
        if (!$this->name) {
           $logs = new UOList(
                array(
                    "<a href='log.php?name=sarah'>Sarah's Log</a>",
                    "<a href='log.php?name=dan'>Dan's Log</a>"
                )
            );
            echo $logs->getHTML();
            return;
        }

        $html = "<div style='height:300px;width:100%;border:3px solid #000000;
            font:16px/26px Georgia, Garamond, Serif;overflow:auto;'>";

        $filename = __DIR__ . '/../' . $this->name . '_errors.txt';
        $fp = fopen( $filename, "r+" ) or die("Couldn't open $filename");
        // Empties file
        ftruncate($fp, 0);

        $text = array();
        while (!feof($fp)) {
            $line = fgets($fp);
            $text[] = $line;
        }

        // Remove empty lines
        $text = array_values(array_filter($text, "trim"));
        if (count($text) > LOG_LINES) {
            $text = array_splice($text, count($text) - LOG_LINES);
        }

        $html .= "<ul id='error_list' type='none' style='padding: 0;'>";

        $text = array();
        foreach ($text as $i => $row) {
            $id = "row_$i";
            if ($i % 2 == 0) {
                $html .=
                    "<li
                        id=$id
                        class='even_list'
                        style='width=100%;padding:5px;'
                        onclick='highlight($id); update_details($id);'>
                        $row
                    </li>";
            } else {
                $html .=
                    "<li
                        id=$id
                        class='odd_list'
                        style='width=100%;padding:5px;'
                        onclick='highlight($id); update_details($id)'>
                        $row
                    </li>";
            }
        }
        $html .= "</ul></div>";

        $html_details =
            "<div
                id='error_details'
                style='margin-top:5px;height:500px;width:100%;border:3px
                dashed #ccc;padding-left:5px;margin-right:5px;
                font:16px/26px Georgia, Garamond, Serif;overflow:auto;
                background:white; display: none;'
            />";

        $html .= $html_details;

        echo $html;
    }
}
?>
