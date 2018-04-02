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
 * QR link incoming logic page.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

$id = required_param('id', PARAM_INT);
$uid = optional_param('uid', 0, PARAM_INT);

/** User ID for Android and some application */
$ampuid = optional_param('amp;uid',  0, PARAM_INT);

/** Hot fix for some qr scan application issue */
if($uid == 0) {
    $uid = $ampuid;
}

$url = new moodle_url('/theme/elobot/update_level.php', array('id' => $id, 'uid' => $uid)); //, 'lv' => $lv

redirect($url);