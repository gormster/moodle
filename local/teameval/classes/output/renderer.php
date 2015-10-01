<?php
    
namespace local_teameval\output;

use plugin_renderer_base;
use local_teameval\output\team_evaluation_block;
use context_module;

class renderer extends plugin_renderer_base {

    public function render_team_evaluation_block(team_evaluation_block $block) {
        
        global $PAGE, $USER;

        $context = context_module::instance($block->cm->id);

        $templates = [];
        foreach($block->subplugins as $subplugin) {
            $t = "{$subplugin->type}_{$subplugin->name}/question_submission";
            $cls = $subplugin->get_question_class();
            $c = new $cls($block->cm->id);
            $templates[] = [ "test" => $this->render_from_template($t, $c->submission_view($USER->id))];
        }

        if (has_capability('local/teameval:changesettings', $context)) {
            $PAGE->requires->js_call_amd('local_teameval/settings', 'initialise', [$block->cm->id, $block->teameval->get_settings()]);
        }
        
        return $this->render_from_template('local_teameval/questionnaire_submission', ["subtemplates" => $templates]);
        
    }

}

?>