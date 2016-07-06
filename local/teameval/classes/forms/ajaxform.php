<?php

namespace local_teameval\forms;

require_once("$CFG->libdir/formslib.php");

use moodleform;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use HTML_QuickForm_group;
use HTML_QuickForm_static;

abstract class ajaxform extends moodleform {

    function external_parameters() {
        $mform = $this->_form;
        $params = $this->elements_as_params($mform->_elements);
        return new external_function_parameters([
            'form' => new external_single_structure($params)
        ]);
    }

    function returns() {
        $mform = $this->_form;
        $params = $this->elements_as_params($mform->_elements);
        return new external_single_structure($params);
    }

    protected function elements_as_params($els) {
        $params = [];
        foreach($els as $el) {
            $name = $el->getName();
            if ((strlen($name) > 0) && ($this->is_data_element($el))) {
                $params[$name] = $this->value_for_element($el);
            }
        }
        return $params;
    }

    protected function is_data_element($el)
    {
        // This function is incomplete. You can help by expanding it.
        if ($el instanceof HTML_QuickForm_static) {
            return false;
        }

        return true;
    }

    protected function value_for_element($element) {
        if ($element instanceof HTML_QuickForm_group) {
            $params = $this->elements_as_params($element->getElements());
            return new external_single_structure($params);
        } else {
            return new external_value(PARAM_RAW, 'AJAXFORM: ' . $element->getName());
        }
    }

    function process_data($json) {
        $this->_form->updateSubmission($json, []);
    }

}