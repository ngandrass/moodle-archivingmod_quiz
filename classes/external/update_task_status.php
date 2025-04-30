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
 * This file defines the update_task_status webservice function
 *
 * @package   archivingmod_quiz
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz\external;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


use archivingmod_quiz\type\webservice_status;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;


/**
 * API endpoint to update the status of a quiz archiving task
 */
class update_task_status extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'uuid' => new external_value(
                PARAM_TEXT,
                'UUID assigned to this task by the worker service',
                VALUE_REQUIRED
            ),
            'taskid' => new external_value(
                PARAM_INT,
                'ID of the activity archiving task this request belongs to',
                VALUE_REQUIRED
            ),
            'status' => new external_value(
                PARAM_INT,
                'New status to set for the given activity archiving task',
                VALUE_REQUIRED
            ),
            'progress' => new external_value(
                PARAM_INT,
                'Number between 0 and 100 that indicates the current progress of the task',
                VALUE_OPTIONAL,
            ),
        ]);
    }

    /**
     * Returns description of return parameters
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(
                PARAM_TEXT,
                'Status of the executed wsfunction'
            ),
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $uuidraw UUID assigned to this task by the worker service
     * @param int $taskidraw ID of the activity archiving task this request belongs to
     * @param int $statusraw New status to set for the given activity archiving task,
     *                       based on archivingmod\type\activity_archiving_task_status
     * @param int|null $progressraw Number between 0 and 100 that indicates the current progress of the task
     * @return array Webservice response
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(
        string $uuidraw,
        int    $taskidraw,
        int    $statusraw,
        ?int   $progressraw = null
    ): array {
        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'uuid' => $uuidraw,
            'taskid' => $taskidraw,
            'status' => $statusraw,
            'progress' => $progressraw,
        ]);

        // TODO
        return ['status' => webservice_status::OK->name];

        /*
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);

            // Check capabilities.
            $context = \context_module::instance($job->get_cmid());
            require_capability('mod/quiz_archiver:use_webservice', $context);

            if ($job->is_complete()) {
                return [
                    'status' => 'E_JOB_ALREADY_COMPLETED',
                ];
            }

            if (!$job->has_write_access(optional_param('wstoken', null, PARAM_TEXT))) {
                return [
                    'status' => 'E_ACCESS_DENIED',
                ];
            }

            // Prepare statusextras.
            $statusextras = null;
            if ($params['statusextras']) {
                $statusextras = json_decode($params['statusextras'], true, 16, JSON_THROW_ON_ERROR);
            }

            // Update job status.
            $job->set_status(
                $params['status'],
                $statusextras
            );
        } catch (\dml_exception $e) {
            return [
                'status' => 'E_UPDATE_FAILED',
            ];
        } catch (\JsonException $e) {
            return [
                'status' => 'E_INVALID_STATUSEXTRAS_JSON',
            ];
        }

        // Report success.
        return [
            'status' => 'OK',
        ];
        */
    }

}
