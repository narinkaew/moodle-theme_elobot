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
 * User state course store.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace block_xp\local\xp;
defined('MOODLE_INTERNAL') || die();

//use context_helper;
//use moodle_database;
//use stdClass;
//use user_picture;
//use block_xp\local\logger\collection_logger_with_group_reset;
//use block_xp\local\logger\reason_collection_logger;
//use block_xp\local\reason\reason;

/**
 * User state course store.
 *
 * This is a repository of XP of each user. It also stores the level of
 * each user in the 'lvl' column, that only for ordering purposes. When
 * you change the levels_info, you must update the stored levels.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_user_state_store {

    /** User preference prefix for notify. */
    const USERPREF_NOTIFY = 'block_xp_notify_level_up_';

    /** The table name. */
    const TABLE_LOG = 'block_xp_log';

    /** Event name. */
    const EVENT_NAME = 'completed_level_';

	/** Level xp. */
    const BASE_POINT = 100;

    /** User preference prefix for course module. */
    const USERPREF_COURSE_MODULE = 'block_xp_elobot_course_module_';

    /** User preference prefix for email. */
    const USERPREF_EMAIL = 'block_xp_elobot_email_';

    /** The table name. */
    const TABLE_USER_PREFERENCES = 'user_preferences';

	/** @var moodle_database The database. */
    protected $db;
    /** @var int The course ID. */
    protected $courseid;
    /** @var int The course module ID. */
    protected $coursemoduleid;
    /** @var string The DB table. */
    protected $table = 'block_xp';
    /** @var string The user preference prefix for course module */
    //protected $course_module_key;


    /**
     * Constructor.
     *
     * @param moodle_database $db The DB.
     * @param int $courseid The course ID.
     */
    public function __construct(moodle_database $db, $courseid, $coursemoduleid) {
        $this->db = $db;
        $this->courseid = $courseid;
        $this->coursemoduleid = $coursemoduleid;
        //$this->$course_module_key = self::USERPREF_COURSE_MODULE . $coursemoduleid;

        $this->key = self::USERPREF_NOTIFY . $courseid;
    }

    /**
     * Add a certain amount of experience points.
     *
     * @param int $userid The receiver.
     * @param int $amount The amount.
     */
    public function increase($userid) {
        if ($record = $this->exists($userid)) {

        	// if($nextlevel <= $record->lvl)
        	// {
        	// 	print_error(get_string('samelevel', 'theme_elobot'));
        	// }

            $currentlevel = $record->lvl;
            $nextlevel = $currentlevel + 1;
            $amount = $currentlevel * self::BASE_POINT;

        	/** Update point & level */
            $sql = "UPDATE {{$this->table}}
                       SET xp = :xp,
                       	   lvl = :lvl
                     WHERE courseid = :courseid
                       AND userid = :userid";
            $params = [
                'xp' => $amount,
                'lvl' => $nextlevel,
                'courseid' => $this->courseid,
                'userid' => $userid
            ];
            $this->db->execute($sql, $params);
            //echo "<br>update completed";

            // // Non-atomic level update. We best guess what the XP should be, and go from there.
            // $newxp = $record->xp + $amount;
            // $newlevel = $this->levelsinfo->get_level_from_xp($newxp)->get_level();
            // if ($record->lvl != $newlevel) {
            //     $this->db->set_field($this->table, 'lvl', $newlevel, ['courseid' => $this->courseid, 'userid' => $id]);
            // }
        } else {
            $currentlevel = 1;
            $nextlevel = $currentlevel + 1;
            $amount = $currentlevel * self::BASE_POINT;

        	/** Insert first xp */
        	$insert_result = $this->insert($userid, $amount, $nextlevel);
            //echo "<br>insert completed=".$insert_result;
        }

        /** Logging coursemodule is passed to user preferences */
        $this->insert_pref_course_module($userid, $currentlevel);

		/** Notify a user */
		$this->notify($userid);
		//echo "<br>notify";

		/** Log a thing */
		$eventname = self::EVENT_NAME . $currentlevel;
		$this->log($userid, $eventname, self::BASE_POINT);
		//echo "<br>log";

		$backtocourseurl = new moodle_url('/course/view.php', array('id' => $this->courseid));
		redirect($backtocourseurl) ;
    }

    /**
     * Return whether the entry exists.
     *
     * @param int $id The receiver.
     * @return stdClass|false
     */
    public function exists($userid) {
        $params = [];
        $params['userid'] = $userid;
        $params['courseid'] = $this->courseid;
        return $this->db->get_record($this->table, $params);
    }

    /**
     * Insert the entry in the database.
     *
     * @param int $userid The receiver.
     * @param int $amount The amount.
     */
    protected function insert($userid, $amount, $nextlevel) {
        $record = new stdClass();
        $record->courseid = $this->courseid;
        $record->userid = $userid;
        $record->xp = $amount;
        $record->lvl = $nextlevel;
        //$record->lvl = $this->levelsinfo->get_level_from_xp($amount)->get_level();
        $this->db->insert_record($this->table, $record);
    }

    /**
     * Notify a user.
     *
     * @param int $userid The user ID.
     * @return void
     */
    protected function notify($userid) {
        set_user_preference($this->key, 1, $userid);
    }

    /**
     * Insert the user preference in the database.
     *
     * @param int $userid The user ID.
     * @return void
     */
    public function insert_pref_course_module($userid, $currentlevel) {
    	$course_module_key = self::USERPREF_COURSE_MODULE . $this->coursemoduleid;
        set_user_preference($course_module_key, $currentlevel, $userid);
    }

    /**
     * Return whether the user preference exists.
     *
     * @param int $id The receiver.
     * @return stdClass|false
     */
    public function exists_pref_course_module($userid) {
    	$course_module_key = self::USERPREF_COURSE_MODULE . $this->coursemoduleid;
        //return get_user_preferences($course_module_key, 0, $userid);

        $params = [];
        $params['userid'] = $userid;
        $params['name'] = $course_module_key;
        return $this->db->get_record(self::TABLE_USER_PREFERENCES, $params);
    }

    /**
     * Insert the user preference in the database.
     *
     * @param int $userid The user ID.
     * @return void
     */
    public function insert_pref_email($userid) {
        $email_key = self::USERPREF_EMAIL . $this->coursemoduleid;
        set_user_preference($email_key, true, $userid);
    }

    /**
     * Return whether the user preference exists.
     *
     * @param int $id The receiver.
     * @return stdClass|false
     */
    public function exists_pref_email($userid) {
        $email_key = self::USERPREF_EMAIL . $this->coursemoduleid;

        $params = [];
        $params['userid'] = $userid;
        $params['name'] = $email_key;
        return $this->db->get_record(self::TABLE_USER_PREFERENCES, $params);
    }

    /**
     * Log a thing.
     *
     * @param int $userid The target.
     * @param int $points The points.
     * @param string $eventname A eventname.
     * @param DateTime|null $time When that happened.
     * @return void
     */
    protected function log($userid, $eventname, $points, DateTime $time = null) {
        $time = $time ? $time : new DateTime();
        $record = new stdClass();
        $record->courseid = $this->courseid;
        $record->userid = $userid;
        $record->eventname = $eventname;
        $record->xp = $points;
        $record->time = $time->getTimestamp();
        try {
            $this->db->insert_record(static::TABLE_LOG, $record);
        } catch (dml_exception $e) {
            // Ignore, but please the linter.
            $pleaselinter = true;
        }
    }

    /**
     * User current level
     *
     * @param int $userid The target.
     * @return int current level.
     */
    public function get_current_level($userid) {
        $currentlevel = 1;
        if ($record = $this->exists($userid)) {
            $currentlevel = $record->lvl;
        }

        return $currentlevel;
    }
}