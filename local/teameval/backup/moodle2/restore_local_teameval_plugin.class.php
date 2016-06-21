<?php

class restore_local_teameval_plugin extends restore_local_plugin {

	// I have to do all this bullshit
	// Because prepare_pathelements is private
	// for no bloody reason
	public function define_plugin_structure($connectionpoint) {
		if (!$connectionpoint instanceof restore_path_element) {
            throw new restore_step_exception('restore_path_element_required', $connectionpoint);
        }

        $paths = array();
        $this->connectionpoint = $connectionpoint;
        $methodname = 'define_' . basename($this->connectionpoint->get_path()) . '_plugin_structure';

        if (method_exists($this, $methodname)) {
            if ($bluginpaths = $this->$methodname()) {
                foreach ($bluginpaths as $path) {
                	if (is_null($path->get_processing_object())) {
	                    $path->set_processing_object($this);
	                }
                    $paths[] = $path;
                }
            }
        }
        return $paths;
	}

	protected function define_module_plugin_structure() {
        $teameval = new restore_path_element('teameval', $this->get_pathfor('/teameval'));

        // $questions = new restore_path_element('questions', $this->get_pathfor('/teameval/questions'), true);

        $question = new restore_path_element('question', $this->get_pathfor('/teameval/questions/question'));

        $question_subplugins = $this->add_subplugin_structure('teamevalquestion', $question, 'local', 'teameval');

        $paths = array_merge($question_subplugins, [$teameval, $question]);

        $this->step->log(print_r(array_map(function($i) { return $i->get_path(); }, $paths),true), backup::LOG_DEBUG);

        return $paths;
	}

	public function process_teameval($settings) {
		global $DB;

		// it fucking boggles my mind that this is necessary
		// but sometimes this function is called with an object
		// and sometimes with an array
		$settings = (object)$settings;

		$cmid = $this->task->get_moduleid();
		$this->step->log("module ID: $cmid", backup::LOG_INFO);
		
		$settings->cmid = $cmid;

		$newid = $DB->insert_record('teameval', $settings);

		$this->set_mapping('teameval', 0, $newid);
	}

	public function process_question($question) {
		global $DB;
		$question = (object)$question;
		$question->teamevalid = $this->get_new_parentid('teameval');
		$DB->insert_record('teameval_questions', $question);
	}

	protected function post_process_question() {
		global $DB;
		//fix question ids
		$teamevalid = $this->get_new_parentid('teameval');
		$questions = $DB->get_records('teameval_questions', ['teamevalid' => $teamevalid]);
		foreach($questions as $question) {
			$oldid = $question->questionid;
			$question->questionid = $this->get_mappingid($question->qtype.'_questionid', $question->questionid);
			if ($question->questionid) {
				$this->step->log("mapped question $oldid on to question {$question->questionid}", backup::LOG_DEBUG);
				$DB->update_record('teameval_questions', $question);
			} else {
				$this->step->log("deleted question $oldid", backup::LOG_DEBUG);
				$DB->delete_records('teameval_questions', ['id' => $question->id]);
			}
		}
	}





	protected function after_execute_module() {
		$this->post_process_question();
	}




	// This is copied from Moodle 3. Remove it when possible.

	protected function add_subplugin_structure($subplugintype, $element, $plugintype, $pluginname) {
        global $CFG;
        // This global declaration is required, because where we do require_once($backupfile);
        // That file may in turn try to do require_once($CFG->dirroot ...).
        // That worked in the past, we should keep it working.
        
        // Check the requested plugintype is a valid one.
        if (!array_key_exists($plugintype, core_component::get_plugin_types())) {
            throw new restore_step_exception('incorrect_plugin_type', $plugintype);
        }
        // Check the requested pluginname, for the specified plugintype, is a valid one.
        if (!array_key_exists($pluginname, core_component::get_plugin_list($plugintype))) {
            throw new restore_step_exception('incorrect_plugin_name', array($plugintype, $pluginname));
        }
        // Check the requested subplugintype is a valid one.
        $subpluginsfile = core_component::get_component_directory($plugintype . '_' . $pluginname) . '/db/subplugins.php';
        if (!file_exists($subpluginsfile)) {
            throw new restore_step_exception('plugin_missing_subplugins_php_file', array($plugintype, $pluginname));
        }
        include($subpluginsfile);
        if (!array_key_exists($subplugintype, $subplugins)) {
             throw new restore_step_exception('incorrect_subplugin_type', $subplugintype);
        }
        // Every subplugin optionally can have a common/parent subplugin
        // class for shared stuff.
        $parentclass = 'restore_' . $plugintype . '_' . $pluginname . '_' . $subplugintype . '_subplugin';
        $parentfile = core_component::get_component_directory($plugintype . '_' . $pluginname) .
            '/backup/moodle2/' . $parentclass . '.class.php';
        if (file_exists($parentfile)) {
            require_once($parentfile);
        }
        // Get all the restore path elements, looking across all the subplugin dirs.
        $subpluginsdirs = core_component::get_plugin_list($subplugintype);

        $paths = [];
        foreach ($subpluginsdirs as $name => $subpluginsdir) {
            $classname = 'restore_' . $subplugintype . '_' . $name . '_subplugin';
            $restorefile = $subpluginsdir . '/backup/moodle2/' . $classname . '.class.php';
            if (file_exists($restorefile)) {
                require_once($restorefile);
                $restoresubplugin = new $classname($subplugintype, $name, $this->step);
                // Add subplugin paths to the step.
                $paths = array_merge($paths, $restoresubplugin->define_subplugin_structure($element));
            }
        }
        return $paths;
    }

}