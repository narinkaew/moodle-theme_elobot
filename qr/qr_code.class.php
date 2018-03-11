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
 * QR code library
 *
 * @package    theme_elobot
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
//define('K_TCPDF_CALLS_IN_HTML', true);

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
//require_once($CFG->dirroot.'/lib/pdflib.php');

require_login();
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

/**
 * Business logic of QR code.
 *
 * @copyright  2018 Narin Kaewchutima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qr_code {

    /**
     * Serialize QR Code into HTML tag
     *
     * @param string $href
     */
    public function generate_qrcode($id, $uid) { //, $lv
        $public_name = get_string('qrheader', 'theme_elobot');
        $public_description = get_string('qrdescription', 'theme_elobot');

        $headtext  = html_writer::start_tag('h2');
        $headtext .= $public_name;
        $headtext .= html_writer::end_tag('h2');
        $headdiv = html_writer::div($headtext, 'qrheader');

        $desctext = $public_description;
        $descdiv = html_writer::div($desctext, 'qrdescription');

        $qrurl = new moodle_url("/theme/elobot/qr_img.php", array('id' => $id, 'uid' => $uid) ); //, 'lv' => $lv
        $alt = "";

        $out = '';
        //$out  .= $headdiv;
        $out .= html_writer::img($qrurl, $alt, array('height' => '80%'));
        $out .= html_writer::empty_tag('br');
        //$out .= $descdiv;

        return $out;
    }
}