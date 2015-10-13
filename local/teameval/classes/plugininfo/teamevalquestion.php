<?php
    
namespace local_teameval\plugininfo;

defined('MOODLE_INTERNAL') || die();

class teamevalquestion extends \core\plugininfo\base {

    public function get_question_class() {
        return "\\teamevalquestion_{$this->name}\\question";
    }
    
    public function get_response_class() {
        return "\\teamevalquestion_{$this->name}\\response";
    }

    public function is_uninstall_allowed() {
    	return true;
    }
    
}

?>