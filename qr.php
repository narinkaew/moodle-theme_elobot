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
// require_once($CFG->libdir.'/gradelib.php');
// require_once($CFG->dirroot.'/mod/quiz/locallib.php');
// require_once($CFG->libdir . '/completionlib.php');
// require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->dirroot . '/theme/elobot/qr/qr_code.class.php');
//require_once($CFG->dirroot . '/theme/elobot/qr/renderer.php');
require_once($CFG->dirroot . '/theme/elobot/classes/course_user_state_store.php');

global $CFG, $PAGE, $DB;


$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$q = optional_param('q',  0, PARAM_INT);  // Quiz ID.
$uid = optional_param('uid',  0, PARAM_INT);  // User ID.
//$lv = required_param('lv', PARAM_INT); // Completed level.

// echo '<br>id='.$id;
// echo '<br>uid='.$uid;

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

// echo '<br>course='.$cm->course;
// echo '<br>level='.$lv;
//echo '<br>quiz='.$q;
//echo '<br>quiz_course='.$quiz->course;

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

// // Create an object to manage all the other (non-roles) access rules.
// $timenow = time();
// $quizobj = quiz::create($cm->instance, $USER->id);
// $accessmanager = new quiz_access_manager($quizobj, $timenow,
//         has_capability('mod/quiz:ignoretimelimits', $context, null, false));
// $quiz = $quizobj->get_quiz();

// // Trigger course_module_viewed event and completion.
// quiz_view($quiz, $course, $cm, $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/quiz/theme/elobot/qr.php', array('id' => $id, 'uid' => $uid)); //, 'lv' => $lv

// Include css.
//$PAGE->requires->css('/theme/elobot/css/styles.css');

// $title = $course->shortname;
// $heading = $course->fullname;
// $PAGE->set_title($title);
// $PAGE->set_heading($heading);

// echo $OUTPUT->header();

if (isguestuser()) {
    // Guests can't do a quiz, so offer them a choice of logging in or going back.
} else if (!isguestuser() && !($canattempt || $canpreview || $viewobj->canreviewmine)) {
    // If they are not enrolled in this course in a good enough role, tell them to enrol.
} else {
    /** Declare course_user_state_store object */
    $course_user_state_store = new course_user_state_store($DB, $cm->course, $cm->id);
    if ( $userpref = $course_user_state_store->exists_pref_course_module($uid) )
    {
        $completedlevel = $userpref->value;
        $nextlevel = $completedlevel + 1;
    }
    else {
        /** Find current level & next level */
        $currentlevel = $course_user_state_store->get_current_level($USER->id);
        $nextlevel = $currentlevel + 1;
    }


    /** Display avatar for next level */
    $avatar_next_level = $CFG->wwwroot.'/theme/elobot/pix/' . $nextlevel . '.png';
    // echo html_writer::tag(
    //         'div',
    //         html_writer::empty_tag('img', ['src' => $avatar_next_level,
    //             'alt' => 'Level up to ' . $nextlevel])
    //     );
    // echo html_writer::empty_tag('br');

    $tableHml = '';
    $tableHml .= '<div>';
    $tableHml .= '  <table style="width:100%; height:100%;">';
    $tableHml .= '      <tr style="valign:center; height:50%;">';
    $tableHml .= '          <td align="center">';
    $tableHml .= '              <img src="' . $avatar_next_level . '" style="display: block; height: 80%;" />';
    $tableHml .= '          </td>';
    $tableHml .= '      </tr>';

    /** Display QR code for next level */
    $qr_code = new qr_code();
    //echo $qr_code->generate_qrcode($id, $uid); //, $currentlevel

    $tableHml .= '      <tr style="valign:center; height:50%;">';
    $tableHml .= '          <td align="center">';
    $tableHml .= $qr_code->generate_qrcode($id, $uid);;
    $tableHml .= '          </td>';
    $tableHml .= '      </tr>';
    $tableHml .= '  </table>';
    $tableHml .= '</div>';

    echo $tableHml;


    // echo html_writer::link(new moodle_url('/mod/quiz/view.php', array('id' => $id)), 'Back to quiz');
    //echo '<button id="printbutton" onclick="window.print();">' . get_string('printpage', 'local_qrlinks') . '</button>';
    //echo $this->single_button($viewobj->backtocourseurl, get_string('backtocourse', 'quiz'), 'get', array('class' => 'continuebutton'));
}

// echo $OUTPUT->footer();
