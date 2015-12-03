<?php

namespace teamevalreport_responses\output;

use core_user;
use stdClass;
use user_picture;

class responses_report implements \renderable, \templatable {

    public $scores;

    public function __construct() {

    }

    public function export_for_template(\renderer_base $output) {
        return [];
    }

}