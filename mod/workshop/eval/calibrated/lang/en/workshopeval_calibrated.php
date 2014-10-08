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
 * Strings for component 'workshopeval_best', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    workshopeval
 * @subpackage best
 * @copyright  2009 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['adjustgrades'] = 'Adjust Submission Grades';
$string['adjustgrades_help'] = <<<MDOWN
If this option is enabled, grades from reviewers with higher calibration scores carry more weight than those with lower calibration scores. For example, consider a submission reivewed by Alice and Bob. Alice gives the submission a 20, while Bob gives it a 10. **Without** this option enabled, the submission would get a 15. However, Alice's calibration score was a perfect 100%, while Bob only scored 25%. Alice is a _more competent_ reviewer. If **Adjust Submission Grades** is enabled, the submission gets 18, closer to Alice's score.

The equation used to calculate this is a **weighted mean**, where the weighting is the individual's calibration score. In the example above, the calculation is ( 20 &times; 100% + 10 &times; 25% ) / ( 100% + 25% ).
MDOWN;
$string['pluginname'] = 'Calibrated against example assessments';
$string['settings'] = 'Grading evaluation settings';

$string['nocompetentreviewers'] = 'According to your settings, the following submissions have no competent reviewers and have not been given a mark:';
