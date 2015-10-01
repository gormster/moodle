<?php

namespace local_teameval\output;

use local_teameval\team_evaluation;
use core_plugin_manager;
use renderable;

class team_evaluation_block implements renderable {

    public $cm;
    
    public $subplugins;

    public $teameval;

    /**
     * @param int $cmid This is the cmid of the activity module this teameval belongs to
     */
    public function __construct($cmid) {
        $this->cm = get_coursemodule_from_id(null, $cmid);
        $this->teameval = new team_evaluation($cmid);
        $this->subplugins = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");
        
    }

}

?>