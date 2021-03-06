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
 * This page is the QR renderer page after passed the quiz.
 * Displays QR code to update sudent's point and reach to next level.
 *
 * @package   theme_elobot
 * @copyright 2018 Narin Kaewchutima
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/theme/elobot/qr/qr_code.class.php');
require_once($CFG->dirroot . '/theme/elobot/classes/course_user_state_store.php');

global $CFG, $PAGE, $DB;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$q = optional_param('q',  0, PARAM_INT);  // Quiz ID.
$uid = optional_param('uid',  0, PARAM_INT);  // User ID.

if ($id) {
    if (!$cm = get_coursemodule_from_id('quiz', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    if (!$quiz = $DB->get_record('quiz', array('id' => $q))) {
        print_error('invalidquizid', 'quiz');
    }
    if (!$course = $DB->get_record('course', array('id' => $quiz->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

// Check login and get context.
require_login($course, false, $cm);


$context = context_module::instance($cm->id);
require_capability('mod/quiz:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/quiz:attempt', $context);
$canreviewmine = has_capability('mod/quiz:reviewmyattempts', $context);
$canpreview = has_capability('mod/quiz:preview', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/quiz/theme/elobot/qr.php', array('id' => $id, 'uid' => $uid)); //, 'lv' => $lv

if (isguestuser()) {
    // Guests can't do a quiz, so offer them a choice of logging in or going back.
} else if (!isguestuser() && !($canattempt || $canpreview || $viewobj->canreviewmine)) {
    // If they are not enrolled in this course in a good enough role, tell them to enrol.
} else {
    /** Declare course_user_state_store object */
    $course_user_state_store = new course_user_state_store($DB, $cm->course, $cm->id);
    if ( $userpref = $course_user_state_store->exists_pref_course_module($uid) )
    {
        $currentlevel = $userpref->value;
        $nextlevel = $currentlevel + 1;
    }
    else {
        /** Find current level & next level */
        $currentlevel = $course_user_state_store->get_current_level($USER->id);
        $nextlevel = $currentlevel + 1;
    }

    /** Display avatar for next level */
    $avatar_next_level = $CFG->wwwroot.'/theme/elobot/pix/' . $nextlevel . '.png';

    $tableHml = '';
    $tableHml .= '<div>';
    $tableHml .= '  <table style="width:100%; height:100%;">';
    $tableHml .= '      <tr style="valign:center;">';
    $tableHml .= '          <td align="center">';
    $tableHml .= '              <img src="' . $avatar_next_level . '" style="display: block; width: 300px;" />';
    $tableHml .= '          </td>';
    $tableHml .= '      </tr>';

    /** Display QR code for next level */
    $qr_code = new qr_code();

    $tableHml .= '      <tr style="valign:center;">';
    $tableHml .= '          <td align="center">';
    $tableHml .= $qr_code->generate_qrcode($id, $uid);
    $tableHml .= '          </td>';
    $tableHml .= '      </tr>';

    /** Display food of completed level */
    $food_current_level = $CFG->wwwroot.'/theme/elobot/pix/feed/feed_' . $currentlevel . '.png';
    $tableHml .= '      <tr style="valign:center;">';
    $tableHml .= '          <td align="center">';
    $tableHml .= '              <img src="' . $food_current_level . '" style="display: block; width: 300px;" />';
    $tableHml .= '          </td>';
    $tableHml .= '      </tr>';  
    $tableHml .= '  </table>';
    $tableHml .= '</div>';

    echo $tableHml;
}