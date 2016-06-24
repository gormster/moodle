<?php

use local_teameval\team_evaluation;

class block_teameval_templates extends block_list {
    
    public function init() {
        $this->title = get_string('teamevaltemplates', 'block_teameval_templates');
    }

    function applicable_formats() {
	  return array(
	    'all' => true, 
	    'mod' => false,
	    'my' => false
	  );
	}

    public function get_content() {
    	if ($this->page->user_is_editing()) {

    		global $OUTPUT;
		    
		    if ($this->content !== null) {
		      return $this->content;
		    }
	    
		    $this->content = new stdClass;

		    $this->content->items = [];
		    $this->content->icons = [];

		    $all_teamevals = team_evaluation::templates_for_context($this->page->context->id);

		    foreach($all_teamevals as $teameval) {
		    	$url = new moodle_url('/blocks/teameval_templates/template.php', array('id' => $teameval->id));
		    	$this->content->items[] = html_writer::link($url, $teameval->get_settings()->title);
		    	$this->content->icons[] = $OUTPUT->pix_icon('icon', '', 'local_teameval');
		    }

			$url = new moodle_url('/blocks/teameval_templates/template.php', array('contextid' => $this->page->context->id));
			$this->content->footer = html_writer::link($url, $OUTPUT->pix_icon('t/add', '') . get_string('newtemplate', 'block_teameval_templates'));

		    return $this->content;
		}
	 
	    $empty = new stdClass;
	    return $empty;

	}

}

?>