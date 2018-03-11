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
 * Defines the renderer for the block xp module.
 *
 * @package   theme_elobot
 * @copyright 2018 Narin Kaewchutima
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_xp\local\xp\level;
use block_xp\output\xp_widget;

use block_xp\local\block;
use block_xp\local\config\course_world_config;

require_once($CFG->dirroot . '/blocks/xp/renderer.php');

/**
 * Block XP renderer class.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_elobot_block_xp_renderer extends block_xp_renderer {

    /** Default number of levels. */
    const DEFAULT_COUNT = 7;
    /** Default base for XP algo. */
    const DEFAULT_BASE = 100;
    /** Default coef for XP algo. */
    const DEFAULT_COEF = 1;

    /** @var course_world World cache. */
    protected $worlds;

    /** @var course_filter_manager The filter manager. */
    protected $filtermanager;

    /**
     * Print a level's badge.
     *
     * @param level $level The level.
     * @return string
     */
    protected function level_badge_with_options(level $level, array $options = []) {
    	global $CFG;

        $levelnum = $level->get_level();
        $classes = 'block_xp-level level-' . $levelnum;
        $label = get_string('levelx', 'block_xp', $levelnum);

        if (!empty($options['small'])) {
            $classes .= ' small';
        }

        $html = '';
        $levelimg = '/theme/elobot/pix/'.$levelnum.'.png';
        $badgeurl = $CFG->wwwroot.$levelimg;
        $badgepath = $CFG->dirroot.$levelimg;
        if ( file_exists($badgepath) ) {
            $html .= html_writer::tag(
                'div',
                html_writer::empty_tag ('img', ['src' => $badgeurl, 'alt' => $label, 'style' => 'width:100%']),
                ['class' => $classes . ' level-badge']
            );
        } else {
            $html .= html_writer::tag('div', $levelnum, ['class' => $classes, 'aria-label' => $label]);
        }

        return $html;
    }

    /**
     * Render XP widget.
     *
     * @param renderable $widget The widget.
     * @return string
     */
    public function render_xp_widget(xp_widget $widget) {

        /** Original renderer */
        $o = '';

        foreach ($widget->managernotices as $notice) {
            $o .= $this->notification_without_close($notice, 'warning');
        }

        // Badge.
        $o .= $this->level_badge($widget->state->get_level());

        // Total XP.
        $xp = $widget->state->get_xp();
        $o .= html_writer::tag('div', $this->xp($xp), ['class' => 'xp-total']);

        // Progress bar.
        $o .= $this->progress_bar($widget->state);

        // Intro.
        if (!empty($widget->intro)) {
            $o .= html_writer::div($this->render($widget->intro), 'introduction');
        }

        // Recent rewards.
        if (!empty($widget->recentactivity) || $widget->forcerecentactivity) {
            $o .= $this->recent_activity($widget->recentactivity, $widget->recentactivityurl);
        }

        // Navigation.
        if (!empty($widget->actions)) {
            $o .= $this->xp_widget_navigation($widget->actions);
        }
        /** End - Original renderer */

        $this->set_default_course_rules($this->page->course->id);
        $this->set_default_course_levels($this->page->course->id);

        return $o;
    }

	/**
	 * Set the default rules.
	 *
	 * @param int $courseid The course ID.
	 */
	private function set_default_course_rules($courseid) {
	    /**
	     * Set 'block_xp_config -> defaultfilters' to '0' 
	     */
	    $this->world = \block_xp\di::get('course_world_factory')->get_world($courseid);
	    $config = $this->world->get_config();
        $state = $config->get('defaultfilters');
        if ($state == course_world_config::DEFAULT_FILTERS_NOOP) {
            // Early bail.
            return $this->filtermanager;

        } else if ($state == course_world_config::DEFAULT_FILTERS_MISSING || $state == course_world_config::DEFAULT_FILTERS_STATIC) {
            // The default filters were not applied yet.
            $config->set('defaultfilters', course_world_config::DEFAULT_FILTERS_NOOP);

        }

	    /**
	     * Set 'block_xp_filters -> default rules
	     */
	    $existing_filters = $this->get_user_filters($courseid);
	    if ($existing_filters == null OR count($existing_filters) == 0)
	    {
	    	$this->import_filters($this->get_default_rules(), $courseid);
		}
	}

	/**
	 * Set the default levels.
	 *
	 * @param int $courseid The course ID.
	 */
	private function set_default_course_levels($courseid) {
	    /**
	     * Set 'block_xp_config -> default 'levelsdata' as json (6 levels)
	     */
	    $this->world = \block_xp\di::get('course_world_factory')->get_world($courseid);
	    $config = $this->world->get_config();
        $levelsdata = $config->get('levelsdata');
        if (empty($levelsdata)) {
        	$levelsinfo = $this->get_default_levels();
            $config->set('levelsdata', json_encode($levelsinfo->jsonSerialize()));
        }
	}

    /**
     * Get the filters defined by the user.
     *
     * @return array Of filter data from the DB, though properties is already json_decoded.
     */
    private function get_user_filters($courseid) {
    	global $DB;

        $results = $DB->get_recordset('block_xp_filters', array('courseid' => $courseid),
            'sortorder ASC, id ASC');
        $filters = array();
        foreach ($results as $key => $filter) {
            $filters[$filter->id] = \block_xp_filter::load_from_data($filter);
        }
        $results->close();
        return $filters;
    }

	/**
	 * Import filters by appending them.
	 *
	 * @param array $filters An array of filters.
	 * @return void
	 */
	private function import_filters(array $filters, $courseid) {
		global $DB;

	    $sortorder = (int) $DB->get_field('block_xp_filters', 'COALESCE(MAX(sortorder), -1) + 1',
	        ['courseid' => $courseid]);

	    foreach ($filters as $filter) {
	        $record = $filter->export();
	        $record->courseid = $courseid;
	        $record->sortorder = $sortorder++;
	        $newfilter = \block_xp_filter::load_from_data($record);
	        $newfilter->save();
	    }

	    $this->world->get_filter_manager()->invalidate_filters_cache();
	}

	/**
	 * Default rules.
	 *
	 * @return List of rules
	 */
	private function get_default_rules() {

        /* No default rules */
        return array();
	}

	/**
	 * Default levels.
	 *
	 * @return List of levels
	 */
	private function get_default_levels() {
        $newdata = [
            'usealgo' => false,
            'base' => self::DEFAULT_BASE,
            'coef' => self::DEFAULT_COEF,
           'xp' => [
                '1' => 0
            ],
            'desc' => [
                '1' => ''
            ]
        ];

        //$leveldesc = ['lv2', 'lv3', 'lv4', 'lv5', 'lv6', 'lv7'];

        for ($i = 2; $i <= self::DEFAULT_COUNT; $i++) {
        	if ($i == 2) {
        		$newdata['xp'][$i] = self::DEFAULT_BASE;
        	} else {
				$newdata['xp'][$i] = self::DEFAULT_BASE + round($newdata['xp'][$i - 1] * self::DEFAULT_COEF);
        	}

        	/** Set default description for each level */
        	$newdata['desc'][$i] = ''; //$leveldesc[$i - 2];
        }

        return new \block_xp\local\xp\algo_levels_info($newdata);
	}
}