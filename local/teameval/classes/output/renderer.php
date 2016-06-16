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

        $context = $block->teameval->get_context();
        $c = new stdClass; // template context

        if (has_capability('local/teameval:changesettings', $context)) {
            $settingsform = new forms\settings_form();
            $settingsform->set_data($block->settings);
            
            $c->settings = $this->render_from_template('local_teameval/settings', ['form' => $settingsform->render()]);
        }

        if (has_capability('local/teameval:createquestionnaire', $context)) {
            $PAGE->requires->js_call_amd('local_teameval/addquestion', 'initialise', [$block->cm->id, $block->settings->self, $block->questiontypes]);

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

            // Results and Mark Release are only available to teamevals attached to modules
            if (isset($block->cm)) {

                $c->results = $this->render_from_template('local_teameval/results', ['types' => $types, 'report' => $report, 'cmid' => $block->cm->id]);

                $c->release = $this->render_from_template('local_teameval/release', $block->release->export_for_template($this));

            }
        }

        $noncompletion = null;

        if ($block->teameval->can_submit($USER->id)) {
            $PAGE->requires->js_call_amd('local_teameval/submitquestion', 'initialise', [$block->cm->id]);

        } else if (has_capability('local/teameval:submitquestionnaire', $context, null, false)) {
            // if we have this capability but can't submit then we need to communicate noncompletion
            $completion = $block->teameval->user_completion($USER->id);
            if ($completion < 1) {
                $n = count($block->questions) - round($completion * count($block->questions));
                $penalty = round($block->teameval->non_completion_penalty($USER->id) * 100, 2);
                $noncompletion = ['n' => $n, 'penalty' => $penalty];
            }
        }

        if (isset($block->feedback)) {
            $c->feedback = $this->render($block->feedback);
        }

        if (\local_teameval\is_developer()) {
            $PAGE->requires->js_call_amd('local_teameval/developer', 'initialise');
        }

        $questions = [];
        foreach($block->questions as $q) {
            $locked = !$block->teameval->can_submit_response($q->plugininfo->name, $q->questionid, $USER->id);
            $submissionview = $q->question->submission_view($USER->id, $locked);
            $editingview = $q->question->editing_view($USER->id);
            $questions[] = [
                "content" => $this->render_from_template($q->submissiontemplate, $submissionview + ["_cmid" => $block->cm->id]),
                "type" => $q->plugininfo->name,
                "questionid" => $q->questionid,
                "submissioncontext" => json_encode($submissionview),
                "editingcontext" => json_encode($editingview)
                ];
        }

        $deadline = null;
        if (isset($block->settings->deadline)) {
            $deadline = userdate($block->settings->deadline);
        }
        
        $c->questionnaire = $this->render_from_template('local_teameval/questionnaire_submission', ["questions" => $questions, "deadline" => $deadline, "noncompletion" => $noncompletion]);

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