<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code to be executed after the plugin's database scheme has been installed is
 * defined here
 *
 * @package     archivingmod_quiz
 * @category    upgrade
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Custom code to be run on installing the plugin
 */
function xmldb_archivingmod_quiz_install() {
    // Print hint for autoinstall feature.
    $autoinstallurl = new moodle_url('/local/archiving/driver/mod/quiz/autoinstall.php');

    echo '<div class="alert alert-info" role="alert">';
    echo '<p>'.get_string('autoinstall_explanation', 'archivingmod_quiz')."</p>";
    echo '<p><a href='.$autoinstallurl.' class="btn btn-primary text-white">'.
        get_string('autoinstall_start_now', 'archivingmod_quiz').
        '</a></p>';
    echo '<p>'.get_string('manual_configuration_continue', 'archivingmod_quiz').'</p>';
    echo '</div>';

    return true;
}
