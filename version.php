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
 * Version information.
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2018031104;                // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = 2018031104;                // Match release exactly to version.
$plugin->requires  = 2016052300;                // Requires Moodle version 3.1
$plugin->maturity  = MATURITY_STABLE;

$plugin->component = 'theme_elobot';       // Full name of the plugin.
$plugin->dependencies = array(
    'theme_bootstrapbase'  => 2016051900,
    'theme_clean'  => 2016051900,
);