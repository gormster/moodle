<?php

namespace teamevalquestion_split100\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use stdClass;
use renderer_base;

use teamevalquestion_split100\question;
use teamevalquestion_split100\response;
use local_teameval\team_evaluation;

class submission_view implements renderable, templatable {

    protected $title;

    protected $description;

    protected $users;

    protected $demo = false;

    protected $self;

    public function __construct(question $question, team_evaluation $teameval) {
        global $USER, $DB;

        $this->self = $teameval->get_settings()->self;

        $this->title = $question->title;
        $this->description = $question->description;
        
        if (team_evaluation::check_capability($teameval, ['local/teameval:submitquestionnaire'], ['doanything' => false])) {
            $teammates = $teameval->teammates($USER->id);

            $this->users = [];
            $response = new response($teameval, $question, $USER->id);
            foreach ($teammates as $id => $user) {
                $u = new stdClass;
                $u->user = $user;
                $u->pct = $response->opinion_of($id) ?: 100.0 / count($teammates);
                $this->users[] = $u;
            }
        } else {
            $this->users = [];
            $this->demo = true;
        }
    }

    public function export_for_template(renderer_base $output) {
        $users = [];
        if ($this->demo) {
            $pcts = [20, 10, 15, 55];
            for ($i=0; $i < 4; $i++) { 
                $user = new stdClass;
                $user->id = -$i;
                $user->name = $this->self ? ($i == 0 ? "Yourself" : "Example User $i") : "Example User ".($i + 1);
                $user->pct = $pcts[$i];
                $users[] = $user;
            }
        } else {
            foreach ($this->users as $user) {
                $user->name = fullname($user->user);
                $user->pic = $output->user_picture($user->user);
                $user->id = $user->user->id;
                unset($user->user);
                $users[] = $user;
            }
        }

        $totalwidth = 0;
        foreach ($users as $user) {
            $user->width = question::real_to_display($user->pct, count($users));
            $user->left = $totalwidth;
            $user->rounded = round($user->pct);
            $totalwidth += $user->width;
        }

        $first = reset($users);
        $first->first = true;
        if ($this->self) {
            $first->self = true;
        }

        $c = new stdClass;

        $c->title = $this->title;
        $c->description = $this->description;
        $c->users = $users;

        return $c;
    }

}