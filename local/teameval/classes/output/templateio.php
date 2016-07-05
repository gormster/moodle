<?php

namespace local_teameval\output;

use renderable;
use templatable;
use stdClass;
use renderer_base;
use moodle_url;
use file_picker;

use local_teameval\team_evaluation;

class templateio implements renderable, templatable {
	
	protected $download;

	protected $teamevalid;

	protected $showaddbutton;

	protected $filepickeroptions;

	public function __construct(team_evaluation $teameval) {

		$this->download = moodle_url::make_pluginfile_url(
			$teameval->get_context()->id, 
			'local_teameval', 
			'template', 
			$teameval->id, 
			'/', $teameval->template_file_name());

		$this->teamevalid = $teameval->id;

		$this->showaddbutton = ($teameval->questionnaire_locked() === false);

		if ($this->showaddbutton) {
			$options = new stdClass;
	        $options->accepted_types = '*.mbz';
	        $options->context = $teameval->get_context();
	        $options->buttonname = 'choose';
	        $options->itemid = file_get_unused_draft_itemid();
	        $this->filepickeroptions = $options;
		}

	}

	public function export_for_template(renderer_base $output) {

		$c = new stdClass;

		$c->download = $this->download;
		$c->teamevalid = $this->teamevalid;
		$c->showaddbutton = $this->showaddbutton;

		if ($this->showaddbutton) {
	        $filepicker = new file_picker($this->filepickeroptions);
	        $c->filepicker = $output->render($filepicker);
	        $c->filepickerid = $filepicker->options->client_id;
	        $c->filepickeritemid = $filepicker->options->itemid;
	        $this->filepickeroptions = $filepicker->options;
	    }

		return $c;

	}

	public function get_filepicker_options() {
		if (empty($this->filepickeroptions)) {
			return null;
		}
		return $this->filepickeroptions;
	}

}