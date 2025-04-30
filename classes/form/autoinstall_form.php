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
 * Defines the form for running the automatic configuration of this plugin
 *
 * @package    archivingmod_quiz
 * @copyright  2025 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz\form;


use archivingmod_quiz\local\autoinstall;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore


/**
 * Form to trigger automatic installation of the archivingmod_quiz plugin
 */
class autoinstall_form extends \moodleform {

    /**
     * Form definiton.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'header', get_string('settings', 'plugin'));

        // Add configuration options.
        $mform->addElement('text', 'workerurl', get_string('setting_worker_url', 'archivingmod_quiz'), ['size' => 50]);
        $mform->addElement('static', 'workerurl_help', '', get_string('setting_worker_url_desc', 'archivingmod_quiz'));
        $mform->setType('workerurl', PARAM_TEXT);
        $mform->addRule('workerurl', null, 'required', null, 'client');

        $mform->addElement('text', 'wsname', get_string('autoinstall_wsname', 'archivingmod_quiz'), ['size' => 50]);
        $mform->addElement('static', 'wsname_help', '', get_string('autoinstall_wsname_help', 'archivingmod_quiz'));
        $mform->setDefault('wsname', autoinstall::DEFAULT_WSNAME);
        $mform->setType('wsname', PARAM_TEXT);
        $mform->addRule('wsname', null, 'required', null, 'client');

        $mform->addElement('text', 'rolename', get_string('autoinstall_rolename', 'archivingmod_quiz'), ['size' => 50]);
        $mform->addElement('static', 'rolename_help', '', get_string('autoinstall_rolename_help', 'archivingmod_quiz'));
        $mform->setDefault('rolename', autoinstall::DEFAULT_ROLESHORTNAME);
        $mform->setType('rolename', PARAM_TEXT);
        $mform->addRule('rolename', null, 'required', null, 'client');

        $mform->addElement('text', 'username', get_string('autoinstall_username', 'archivingmod_quiz'), ['size' => 50]);
        $mform->addElement('static', 'username_help', '', get_string('autoinstall_username_help', 'archivingmod_quiz'));
        $mform->setDefault('username', autoinstall::DEFAULT_USERNAME);
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', null, 'required', null, 'client');

        // Action buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }

}
