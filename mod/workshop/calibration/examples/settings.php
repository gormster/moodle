<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The configuration variables for "Best" grading evaluation
 *
 * The values defined here are used as defaults for all module instances.
 *
 * @package    workshopeval
 * @subpackage best
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $options = array();
    for ($i = 9; $i >= 1; $i--) {
        $options[$i] = new lang_string('comparisonlevel' . $i, 'workshopcalibration_examples');
    }
    
    $settings->add(new admin_setting_configselect('workshopcalibration_examples/accuracy', get_string('comparison', 'workshopcalibration_examples'),
                        new lang_string('configcomparison', 'workshopcalibration_examples'), 5, $options));
    $settings->add(new admin_setting_configselect('workshopcalibration_examples/consistence', get_string('consistency', 'workshopcalibration_examples'),
                        new lang_string('configconsistency', 'workshopcalibration_examples'), 5, $options));
}