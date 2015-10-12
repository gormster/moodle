<?php
    
namespace local_teameval\output;

use plugin_renderer_base;
use local_teameval\output\team_evaluation_block;
use context_module;

class renderer extends plugin_renderer_base {

    public function render_team_evaluation_block(team_evaluation_block $block) {
        
        global $PAGE, $USER;

        $context = context_module::instance($block->cm->id);

        if (has_capability('local/teameval:changesettings', $context)) {
            $PAGE->requires->js_call_amd('local_teameval/settings', 'initialise', [$block->cm->id, $block->teameval->get_settings()]);
        }

        if (has_capability('local/teameval:createquestionnaire', $context)) {
            $PAGE->requires->js_call_amd('local_teameval/addquestion', 'initialise', [$block->cm->id, $block->questiontypes]);
        }

        $questions = [];
        foreach($block->questions as $q) {
            $submissionview = $q->question->submission_view($USER->id);
            $editingview = $q->question->editing_view($USER->id);
            $questions[] = [
                "content" => $this->render_from_template($q->submissiontemplate, $submissionview),
                "type" => $q->plugininfo->name,
                "questionid" => $q->questionid,
                "submissioncontext" => json_encode($submissionview),
                "editingcontext" => json_encode($editingview)
                ];
        }
        
        return $this->render_from_template('local_teameval/questionnaire_submission', ["questions" => $questions]);
        
    }

}

?>