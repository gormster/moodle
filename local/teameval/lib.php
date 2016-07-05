<?php

use local_teameval\team_evaluation;

function local_teameval_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($filearea == 'template') {
        require_login($course);

        require_capability('local/teameval:viewtemplate', $context);

        if (count($args) != 2) {
            return false;
        }

        list($id, $filename) = $args;

        if (!team_evaluation::exists($id)) {
            return false;
        }

        $teameval = new team_evaluation($id);

        // create the file if it doesn't already exist
        $file = $teameval->export_questionnaire();
        if (!$file) {
            return false;
        }
     
        send_stored_file($file, null, 0, $forcedownload);

    }

    return false;

}


?>
