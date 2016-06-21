<?php

class backup_local_teameval_plugin extends backup_local_plugin {

	protected function define_module_plugin_structure() {

		$this->step->log("We're inside backup_local_plugin", backup::LOG_INFO);

		$plugin = $this->get_plugin_element();

		$pluginwrapper = new backup_nested_element($this->get_recommended_name());

		$plugin->add_child($pluginwrapper);

		$this->step->log("recommended name: " . $this->get_recommended_name(), backup::LOG_INFO);

		$settings = new backup_nested_element('teameval', ['id'],
			[
				'title',
				'enabled',
				'public',
				'autorelease',
				'self',
				'fraction',
				'noncompletionpenalty',
				'deadline'
			]);

		$pluginwrapper->add_child($settings);

		$settings->set_source_table('teameval', array('cmid' => backup::VAR_MODID));

		$questions = new backup_nested_element('questions');

		$question = new backup_nested_element('question', ['qtype', 'questionid'], ['qtype', 'questionid', 'ordinal']);

		$questions->add_child($question);

		$settings->add_child($questions);

		$question->set_source_table('teameval_questions', ['teamevalid' => backup::VAR_PARENTID], 'ordinal ASC');

		$this->add_subplugin_structure('teamevalquestion', $question, true, 'local', 'teameval');

		return $plugin;

	}







	// This is imported from a future version of Moodle. Remove it once we upgrade.

	protected function add_subplugin_structure($subplugintype, $element, $multiple, $plugintype = null, $pluginname = null) {
        global $CFG;
        
        // Check the requested plugintype is a valid one.
        if (!array_key_exists($plugintype, core_component::get_plugin_types())) {
             throw new backup_step_exception('incorrect_plugin_type', $plugintype);
        }
        // Check the requested pluginname, for the specified plugintype, is a valid one.
        if (!array_key_exists($pluginname, core_component::get_plugin_list($plugintype))) {
             throw new backup_step_exception('incorrect_plugin_name', array($plugintype, $pluginname));
        }
        // Check the requested subplugintype is a valid one.
        $subpluginsfile = core_component::get_component_directory($plugintype . '_' . $pluginname) . '/db/subplugins.php';
        if (!file_exists($subpluginsfile)) {
             throw new backup_step_exception('plugin_missing_subplugins_php_file', array($plugintype, $pluginname));
        }
        include($subpluginsfile);
        if (!array_key_exists($subplugintype, $subplugins)) {
             throw new backup_step_exception('incorrect_subplugin_type', $subplugintype);
        }
        // Arrived here, subplugin is correct, let's create the optigroup.
        $optigroupname = $subplugintype . '_' . $element->get_name() . '_subplugin';
        $optigroup = new backup_optigroup($optigroupname, null, $multiple);
        $element->add_child($optigroup); // Add optigroup to stay connected since beginning.
        // Every subplugin optionally can have a common/parent subplugin
        // class for shared stuff.
        $parentclass = 'backup_' . $plugintype . '_' . $pluginname . '_' . $subplugintype . '_subplugin';
        $parentfile = core_component::get_component_directory($plugintype . '_' . $pluginname) .
            '/backup/moodle2/' . $parentclass . '.class.php';
        if (file_exists($parentfile)) {
            require_once($parentfile);
        }
        // Get all the optigroup_elements, looking over all the subplugin dirs.
        $subpluginsdirs = core_component::get_plugin_list($subplugintype);
        foreach ($subpluginsdirs as $name => $subpluginsdir) {
            $classname = 'backup_' . $subplugintype . '_' . $name . '_subplugin';
            $backupfile = $subpluginsdir . '/backup/moodle2/' . $classname . '.class.php';
            if (file_exists($backupfile)) {
                require_once($backupfile);
                $backupsubplugin = new $classname($subplugintype, $name, $optigroup, $this->step);
                // Add subplugin returned structure to optigroup.
                $backupsubplugin->define_subplugin_structure($element->get_name());
            }
        }
    }

}