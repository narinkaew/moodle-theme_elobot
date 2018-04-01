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
 * Defines the renderer for the quiz module.
 *
 * @package   theme_elobot
 * @copyright 2018 Narin Kaewchutima
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/renderer.php');
require_once($CFG->dirroot . '/theme/elobot/lib.php');
require_once($CFG->dirroot . '/theme/elobot/qr/qr_code.class.php');
require_once($CFG->dirroot . '/theme/elobot/classes/course_user_state_store.php');

/**
 * The renderer for the quiz module.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_elobot_mod_quiz_renderer extends mod_quiz_renderer {

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param int $course The id of the course
     * @param array $quiz Array conting quiz data
     * @param int $cm Course Module ID
     * @param int $context The page context ID
     * @param array $infomessages information about this quiz
     * @param mod_quiz_view_object $viewobj
     * @param string $buttontext text for the start/continue attempt button, if
     *      it should be shown.
     * @param array $infomessages further information about why the student cannot
     *      attempt this quiz now, if appicable this quiz
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        global $CFG, $USER, $OUTPUT, $DB;

        /** Original renderer */
        $output = '';
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');

        /** Declare course_user_state_store object */
        $course_user_state_store = new course_user_state_store($DB, $cm->course, $cm->id);

        /** If already passed this quiz, don't display QR link. */
        // if ($course_user_state_store->exists_pref_course_module($USER->id)) {
        //     $output .= $this->box(get_string('levelpassed', 'theme_elobot'), 'quizinfo');
        //     return $output;
        // }

        /** If user is at max level, don't display QR link. It's completed. */
        $currentlevel = $course_user_state_store->get_current_level($USER->id);
        $maxlevel = find_max_level($course->id);
        if ($currentlevel == $maxlevel) {
            $output .= $this->box(get_string('levelcompleted', 'theme_elobot'), 'quizinfo');
            return $output;
        }

        /** Calculate scores, passed? */
        $mygrade = quiz_format_grade($quiz, $viewobj->mygrade);
        $quizgrade = quiz_format_grade($quiz, $quiz->grade);
        $percentgrade = ($mygrade / $quizgrade) * 100;

        /** If passed the quiz , display QR code link */
        if (check_for_passing_grade($course, $quiz, $cm) ) //|| is_siteadmin()
        {
            $targeturl = $CFG->wwwroot.'/theme/elobot/qr.php?id='.$cm->id.'&uid='.$USER->id;

            $output .= $OUTPUT->box_start('quizinfo');
            $output .= html_writer::link(   $targeturl,
                                            get_string('generateqr', 'theme_elobot'), 
                                            array(  'title' => 'Scan QR Code to next level', 
                                                    'target' => '_blank', 
                                            )
                                        );
             $output .= $OUTPUT->box_end();

            /** Email confirmation directly rather than using messaging so they will definitely get an email. */
            if (!$course_user_state_store->exists_pref_email($USER->id))
            {
                $supportuser = core_user::get_support_user();
                //echo "<br>Quiz=".$quiz->name;
                //echo "<br>QuizURL=".$quiz->url;
                // echo "<br>USER=".fullname($USER);
                // echo "<br>supportuser=".$supportuser->email;
                $emailupdatetitle = get_string('subject', 'theme_elobot', ['studentname' => fullname($USER), 'quizname' => $quiz->name]);
                //echo "<br>Subject=".$emailupdatetitle;

                $quizurl = $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id;
                $avatar_next_level = $CFG->wwwroot.'/theme/elobot/pix/' . ($currentlevel + 1) . '.png';
                $avatar_next_level_link = '<img src="' . $avatar_next_level . '" style="display: block; width: 300px;" />';
                $food_current_level = $CFG->wwwroot.'/theme/elobot/pix/feed/feed_' . $currentlevel . '.png';
                $food_current_level_link = '<img src="' . $food_current_level . '" style="display: block; width: 300px;" />';

                $emailupdatemessage = get_string('body', 'theme_elobot', ['studentname' => fullname($USER), 'quizname' => $quiz->name, 'quizurl' => $quizurl, 'coursename' => $course->fullname, 'nextavatar' => $avatar_next_level_link, 'qrcode' => (new qr_code())->generate_qrcode($cm->id, $USER->id), 'food' => $food_current_level_link ]);
                //echo "<br>Body=".$emailupdatemessage;

                if (!$mailresults = email_to_user($USER, $supportuser, $emailupdatetitle, $emailupdatemessage, $emailupdatemessage)) {
                    die("could not send email!");
                }

                /** Insert email flag to user_preference, send email only first time*/
                $course_user_state_store->insert_pref_email($USER->id);
            }
        }

        return $output;
    }
}