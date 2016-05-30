<?php

class block_teameval_templates extends block_base {
    
    public function init() {
        $this->title = get_string('teamevaltemplates', 'block_teameval_templates');
    }

    public function get_content() {
    	if ($this->page->user_is_editing()) {
		    
		    if ($this->content !== null) {
		      return $this->content;
		    }
	    
		    $this->content         = new stdClass;

		    $this->content->text   = $this->page->context->get_context_name();
		    $this->content->text .= print_r($this->page->context->get_parent_context_ids(),true);

		    $this->content->footer = 'Footer here...';

		    return $this->content;
		}
	 
	    $empty = new stdClass;
	    return $empty;

	}

}

?>