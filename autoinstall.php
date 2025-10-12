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
 * Handler for autoinstall feature from the admin UI of the archivingmod_quiz plugin.
 *
 * @package   archivingmod_quiz
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use archivingmod_quiz\form\autoinstall_form;
use archivingmod_quiz\local\autoinstall;

require_once(__DIR__ . '/../../../../../config.php');
require_once("{$CFG->libdir}/moodlelib.php");


// Disable error reporting to prevent warning of potential redefinition of constants.
$olderrorreporting = error_reporting();
error_reporting(0);

/** @var bool Disables output buffering */
const NO_OUTPUT_BUFFERING = true;

error_reporting($olderrorreporting);

// Ensure user has permissions.
require_login();
$ctx = context_system::instance();
require_capability('moodle/site:config', $ctx);

// Setup page.
$PAGE->set_context($ctx);
$PAGE->set_url('/local/archiving/driver/mod/quiz/autoinstall.php');
$title = get_string('autoinstall_plugin', 'archivingmod_quiz');
$PAGE->set_title($title);

$returnlink = html_writer::link(
    new moodle_url('/admin/settings.php', ['section' => 'archivingmod_quiz']),
    get_string('back')
);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Content.
if (autoinstall::plugin_is_unconfigured()) {
    $form = new autoinstall_form();

    if ($form->is_cancelled()) {
        // Cancelled.
        echo '<p>' . get_string('autoinstall_cancelled', 'archivingmod_quiz') . '</p>';
        echo '<p>' . $returnlink . '</p>';
    } else if ($data = $form->get_data()) {
        // Perform autoinstall.
        [$success, $log] = autoinstall::execute(
            $data->workerurl,
            $data->wsname,
            $data->rolename,
            $data->username
        );

        // Show result.
        echo '<p>' . get_string('autoinstall_started', 'archivingmod_quiz') . '</p>';
        echo '<p>' . get_string('logs') . '</p>';
        echo "<pre>{$log}</pre><br/>";

        if ($success) {
            echo '<p>' . get_string('autoinstall_success', 'archivingmod_quiz') . '</p>';
        } else {
            echo '<p>' . get_string('autoinstall_failure', 'archivingmod_quiz') . '</p>';
        }

        echo '<p>' . $returnlink . '</p>';
    } else {
        echo '<p>' . get_string('autoinstall_explanation', 'archivingmod_quiz') . '</p>';
        echo '<p>' . get_string('autoinstall_explanation_details', 'archivingmod_quiz') . '</p>';
        $form->display();
    }
} else {
    echo '<p>' . get_string('autoinstall_already_configured_long', 'archivingmod_quiz') . '</p>';
    echo '<p>' . $returnlink . '</p>';
}

// End page.
echo $OUTPUT->footer();
