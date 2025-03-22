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
 * Quiz activity archiving driver
 *
 * @package     archivingmod_quiz
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz;

use local_archiving\driver\archivingmod_base;

// @codingStandardsIgnoreFile
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Quiz activity archiving driver
 */
class archivingmod extends archivingmod_base {

    /**
     * @inheritDoc archivingmod_base::get_name()
     */
    public static function get_name(): string {
        return get_string('pluginname', 'archivingmod_quiz');
    }

    /**
     * @inheritDoc archivingmod_base::get_supported_activities()
     */
    public static function get_supported_activities(): array {
        return ['quiz'];
    }

    /**
     * @inheritDoc archivingmod_base::get_task_settings_form()
     * @throws \dml_exception
     */
    public function get_task_settings_form(string $handler, \cm_info $cminfo): \local_archiving\form\job_create_form {
        return new form\job_create_form($handler, $cminfo);
    }

}
