<?php
    
namespace local_teameval\plugininfo;

defined('MOODLE_INTERNAL') || die();

class teamevalquestion extends \core\plugininfo\base {

    public function get_question_class() {
        include($this->full_path('lib.php'));
        return "\\local_teameval\\teamevalquestion_{$this->name}\\question";
    }
    
    public function get_response_class() {
        include($this->full_path('lib.php'));
        return "\\local_teameval\\teamevalquestion_{$this->name}\\response";
    }
    
}

?>