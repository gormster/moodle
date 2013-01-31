<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__)))))."/config.php");
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__)))))."/lib/formslib.php");

class workshop_allocation_manual_upload_form extends moodleform {

	//todo: i18n

	function definition() {
        global $workshop;
        
		$mform = $this->_form;
		
		$helptext = <<<HTML
Use this form to <strong>upload</strong> allocations. The file format is CSV. The first field should be the <strong>participants's username</strong> and all other fields on the same row are the <strong>usernames of the reviewers</strong> of that participant. For example:

<table class="upload-form-example">
	<tr><td>aaron</td><td>beryl</td><td>carlos</td><td>dorothy</td></tr>
	<tr><td>beryl</td><td>aaron</td><td>dorothy</td></tr>
	<tr><td>dorothy</td><td>carlos</td><td>beryl</td><td>aaron</td></tr>
</table>


In this example, Aaron is reviewed by Beryl, Carlos and Dorothy; Beryl is reviewed by Aaron and Dorothy, and Dorothy is reviewed by Carlos, Beryl and Aaron.
HTML;
		
		$mform->addElement('static', 'helptext', '', $helptext);
		$mform->addElement('filepicker','file','CSV file',null,array('accepted_types' => '.csv'));
		$mform->addElement('hidden','cm',$workshop->cm->id);
		$mform->addElement('submit','submit','Submit');
		$mform->addElement('submit','clear','Clear All Allocations');
		$mform->addRule('clear','','callback','areYouSure','client');
	}
	
	function toHtml() {
		return $this->_form->toHtml();	
	}

	function exportValue($whatever) {
		return $this->_form->exportValue($whatever);
	}

}	


//team mode
class workshop_allocation_teammode_manual_upload_form extends moodleform {

	//todo: i18n

	function definition() {
        global $workshop;
        
		$mform = $this->_form;
		
		$helptext = <<<HTML
Use this form to <strong>upload</strong> allocations. The file format is CSV. The first field should be the group name (case-sensitive) and all other fields on the same row are the usernames of the reviewers of that participant. For example:

<table class="upload-form-example">
	<tr><td>Team A</td><td>beryl</td><td>carlos</td><td>dorothy</td></tr>
	<tr><td>Team B</td><td>aaron</td><td>dorothy</td></tr>
	<tr><td>Team C</td><td>carlos</td><td>beryl</td><td>aaron</td></tr>
</table>


In this example, Team A is reviewed by Beryl, Carlos and Dorothy; Team B is reviewed by Aaron and Dorothy, and Team C is reviewed by Carlos, Beryl and Aaron.
HTML;
		
		$mform->addElement('static', 'helptext', '', $helptext);
		$mform->addElement('filepicker','file','CSV file',null,array('accepted_types' => '.csv'));
		$mform->addElement('hidden','cm',$workshop->cm->id);
		$mform->addElement('submit','submit','Submit');
		$mform->addElement('submit','clear','Clear All Allocations');
		$mform->addRule('clear','','callback','areYouSure','client');
	}
	
	function toHtml() {
		return $this->_form->toHtml();	
	}

	function exportValue($whatever) {
		return $this->_form->exportValue($whatever);
	}

}