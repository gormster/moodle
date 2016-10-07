<?php

namespace teamevalquestion_comment\output;

use teamevalquestion_comment\question;
use local_teameval\team_evaluation;
use renderable;
use templatable;
use stdClass;
use renderer_base;

class editing_view implements renderable, templatable {

    function __construct(question $question, $locked = false) {
        $this->question = $question;
        $this->locked = $locked;
    }

    function export_for_template(renderer_base $output) {

        return [
            'id' => $this->question->id, 
            'title' => $this->question->title, 
            'description' => $this->question->description, 
            'anonymous' => $this->question->anonymous, 
            'optional' => $this->question->optional, 
            'locked' => $this->locked
        ];

    }

}