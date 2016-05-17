<?php
    
namespace local_teameval\output;

use plugin_renderer_base;
use local_teameval\output\team_evaluation_block;
use context_module;
use local_teameval\forms;
use stdClass;

class renderer extends plugin_renderer_base {

    public function render_team_evaluation_block(team_evaluation_block $block) {
        
        global $PAGE, $USER;

        $context = context_module::instance($block->cm->id);
        $c = new stdClass; // template context

        if (has_capability('local/teameval:changesettings', $context)) {
            // $PAGE->requires->js_call_amd('local_teameval/settings', 'initialise', [$block->cm->id, $block->teameval->get_settings()]);
            $settingsform = new forms\settings_form();
            $settingsform->set_data($block->settings);
            
            $c->settings = $this->render_from_template('local_teameval/settings', ['form' => $settingsform->render()]);
        }

        if (has_capability('local/teameval:createquestionnaire', $context)) {
            $PAGE->requires->js_call_amd('local_teameval/addquestion', 'initialise', [$block->cm->id, $block->questiontypes]);

            $current_plugin = $block->teameval->get_report_plugin();
            $report_renderer = $PAGE->get_renderer("teamevalreport_{$current_plugin->name}");
            $report = $report_renderer->render($block->report);

            $types = [];
            foreach($block->reporttypes as $plugininfo) {
                $type = ['name' => $plugininfo->displayname, 'plugin' => $plugininfo->name];
                if ($plugininfo->name == $current_plugin->name) {
                    $type['selected'] = true;
                }
                $types[] = $type;
            }
            $c->results = $this->render_from_template('local_teameval/results', ['types' => $types, 'report' => $report, 'cmid' => $block->cm->id]);

            $c->release = $this->render_from_template('local_teameval/release', $block->release->export_for_template($this));
        }

        if (has_capability('local/teameval:submitquestionnaire', $context, null, false)) {
            $PAGE->requires->js_call_amd('local_teameval/submitquestion', 'initialise', [$block->cm->id]);

            $c->feedback = $this->render($block->feedback);
        }

        if (\local_teameval\is_developer()) {
            $PAGE->requires->js_call_amd('local_teameval/developer', 'initialise');
        }

        $questions = [];
        foreach($block->questions as $q) {
            $submissionview = $q->question->submission_view($USER->id);
            $editingview = $q->question->editing_view($USER->id);
            $questions[] = [
                "content" => $this->render_from_template($q->submissiontemplate, $submissionview + ["_cmid" => $block->cm->id]),
                "type" => $q->plugininfo->name,
                "questionid" => $q->questionid,
                "submissioncontext" => json_encode($submissionview),
                "editingcontext" => json_encode($editingview)
                ];
        }
        
        $c->questionnaire = $this->render_from_template('local_teameval/questionnaire_submission', ["questions" => $questions]);

        $c->hiderelease = $block->settings->autorelease;

        $PAGE->requires->js_call_amd('local_teameval/tabs', 'initialise');
        return $this->render_from_template('local_teameval/block', $c);
        
    }

    public function render_feedback(feedback $feedback) {
        $context = $feedback->export_for_template($this);
        return $this->render_from_template('local_teameval/feedback', $context);
    }

}

?>