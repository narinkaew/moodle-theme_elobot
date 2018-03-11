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
 * Theme eLobot lib for quiz.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Is pass the quiz?
 *
 * @param course Course.
 * @param quiz Quiz.
 * @param cm Coursemodule.
 * @return boolean
 */
function check_for_passing_grade($course, $quiz, $cm) {
    global $CFG, $USER;

    $is_passed = false;
    $userid = $USER->id;

    /** Check for passing grade. */
    require_once($CFG->libdir . '/gradelib.php');
    $item = grade_item::fetch(array('courseid' => $course->id, 
                                    'itemtype' => 'mod',
                                    'itemmodule' => 'quiz', 
                                    'iteminstance' => $cm->instance, 
                                    'outcomeid' => null));

    if ($item) {
        $grades = grade_grade::fetch_users_grades($item, array($userid), false);
        if (!empty($grades[$userid])) {
            $is_passed = $grades[$userid]->is_passed($item);
        }
    }

    return $is_passed;
}

/**
 * Get number of course's level.
 *
 * @param int $courseid.
 * @return int.
 */
function find_max_level($courseid) {
    $world = \block_xp\di::get('course_world_factory')->get_world($courseid);
    $config = $world->get_config();
    $data = json_decode($config->get('levelsdata'), true);

    if (!isset($data) || $data == '')
    {
        return 0;
    }

    $levelsxp = array_reduce(array_keys($data['xp']), function($carry, $key)  {
        $level = $key;
        $carry[$level] = $key;
        return $carry;
    }, []);

    return count($levelsxp);
}