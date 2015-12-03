<?php

namespace teamevalreport_scores\output;

use core_user;
use stdClass;
use user_picture;

class scores_report implements \renderable, \templatable {

    public $scores;

    public function __construct($scores) {
        $display_scores = [];
        foreach($scores as $userid => $score) {
            $user = core_user::get_user($userid, user_picture::fields());
            $c = new stdClass;
            $c->user = $user;
            $c->score = $score;
            $display_scores[] = $c;
        }
        $this->scores = $display_scores;
    }

    public function export_for_template(\renderer_base $output) {
        $ctx = [];
        foreach($this->scores as $score) {
            $userpic = $output->render(new user_picture($score->user));
            $fullname = fullname($score->user);
            $score = round($score->score, 2);
            $ctx[] = ['userpic' => $userpic, 'fullname' => $fullname, 'score' => $score];
        }
        return ['scores' => $ctx];
    }

}