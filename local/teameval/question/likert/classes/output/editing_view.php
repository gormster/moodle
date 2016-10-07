<?php

namespace teamevalquestion_likert\output;

use teamevalquestion_likert\question;
use renderable;
use templatable;
use stdClass;
use renderer_base;

class editing_view implements renderable, templatable {

    function __construct(question $question, $locked) {
        $this->question = $question;
        $this->locked = $locked;
    }

    function export_for_template(renderer_base $output) {
        $context = ["id" => $this->question->id, "title" => $this->question->title, "description" => $this->question->description, "minval" => $this->question->minval, "maxval" => $this->question->maxval];

        $meanings = [];
        for ($i=$this->question->minval; $i <= $this->question->maxval; $i++) { 
            $o = ["value" => $i];
            if (isset($this->question->meanings->$i)) {
                $o["meaning"] = $this->question->meanings->$i;
            }
            $meanings[] = $o;
        }

        $context['meanings'] = $meanings;

        if ($this->locked) {
            $context['locked'] = true;
        }

        return $context;
    }

}