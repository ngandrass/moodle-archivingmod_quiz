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

use local_archiving\activity_archiving_task;
use local_archiving\exception\yield_exception;
use local_archiving\type\activity_archiving_task_status;
use local_archiving\type\cm_state_fingerprint;
use local_archiving\type\task_content_metadata;

// @codingStandardsIgnoreFile
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Quiz activity archiving driver
 */
class archivingmod extends \local_archiving\driver\archivingmod {

    /** @var \stdClass Course the quiz lives in */
    protected \stdClass $course;

    /** @var \cm_info Info object of the associated course module */
    protected \cm_info $cm;

    /** @var int ID of the targeted quiz */
    protected int $quizid;

    public function __construct(\context_module $context) {
        parent::__construct($context);

        // Try to get course, cm info, and quiz.
        list($this->course, $this->cm) = get_course_and_cm_from_cmid($this->cmid, 'quiz');
        if (empty($this->cm)) {
            throw new \moodle_exception('invalid_cmid', 'archivingmod_quiz');
        }
        if ($this->course->id != $this->courseid) {
            throw new \moodle_exception('invalid_courseid', 'archivingmod_quiz');
        }
        $this->quizid = $this->cm->instance;
    }

    #[\Override]
    public static function is_ready(): bool {
        $config = get_config('archivingmod_quiz');

        if (
            intval($config->webservice_id ?? 0) <= 0 ||
            intval($config->webservice_userid ?? 0) <= 0 ||
            strlen($config->worker_url ?? '') < 1
        ) {
            return false;
        } else {
            return true;
        }
    }


    #[\Override]
    public static function get_supported_activities(): array {
        return ['quiz'];
    }

    #[\Override]
    public function can_be_archived(): bool {
        global $DB;

        // Check if quiz has questions.
        if (!$DB->record_exists('quiz_slots', ['quizid' => $this->quizid])) {
            return false;
        }

        // Check if quiz has attempts.
        if (!$DB->record_exists('quiz_attempts', ['quiz' => $this->quizid, 'preview' => 0])) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function get_job_create_form(string $handler, \cm_info $cminfo): \local_archiving\form\job_create_form {
        return new form\job_create_form($handler, $cminfo);
    }

    #[\Override]
    public function execute_task(activity_archiving_task $task): void {
        if ($task->get_status(usecached: true) == activity_archiving_task_status::UNINITIALIZED) {
            $task->set_status(activity_archiving_task_status::CREATED);
        }

        if ($task->get_status(usecached: true) == activity_archiving_task_status::CREATED) {
            // Prepare access to quiz and webservice.
            $quizmanager = quiz_manager::from_context($task->get_context());
            $attempts = $quizmanager->get_attempts();
            $wstoken = $task->create_webservice_token(
                webserviceid: get_config('archivingmod_quiz', 'webservice_id'),
                userid: get_config('archivingmod_quiz', 'webservice_userid'),
                lifetimesec: get_config('local_archiving', 'job_timeout_min') * MINSECS
            );

            // Calculate and persist metadata.
            $numattachments = 0;
            $task->get_logger()->trace('Counting number of attachments in all attempts ...');
            foreach ($attempts as $attempt) {
                $numattachments += count($quizmanager::get_attempt_attachments($attempt->attemptid));
            }
            $task->get_logger()->trace("Found {$numattachments} attachments in all attempts.");

            $job = $task->get_job();
            $job->set_metadata_entry('num_attempts', count($attempts));
            $job->set_metadata_entry('num_attachments', $numattachments);

            // Enqueue a new job at the worker.
            $worker = remote_archive_worker::instance();
            $workerjob = $worker->enqueue_archive_job(
                wstoken: $wstoken,
                task: $task,
                attemptids: array_keys($attempts)
            );
            $task->get_logger()->info("Enqueued new worker job with UUID {$workerjob->uuid}");

            // TODO: Error handling
            $task->set_status(activity_archiving_task_status::AWAITING_PROCESSING);
            throw new yield_exception();
        }

        if ($task->get_status(usecached: true) == activity_archiving_task_status::AWAITING_PROCESSING) {
            // TODO: Check for timeout. Probably on job level?

            // Task status is updated by the worker.
            throw new yield_exception();
        }

        if ($task->get_status(usecached: true) == activity_archiving_task_status::RUNNING) {
            // TODO: Check for timeout. Probably on job level?

            // Task status is updated by the worker.
            throw new yield_exception();
        }

        if ($task->get_status(usecached: true) == activity_archiving_task_status::FINALIZING) {
            // TODO: Check for timeout. Probably on job level?

            // Task is finalized by process_uploaded_artifact webservice function.
            throw new yield_exception();
        }
    }

    #[\Override]
    public function get_task_content_metadata(activity_archiving_task $task): array {
        $quizmanager = quiz_manager::from_context($task->get_context());

        $res = [];
        foreach ($quizmanager->get_attempts() as $attempt) {
            $res[] = new task_content_metadata(
                taskid: $task->get_id(),
                userid: $attempt->userid,
                reftable: 'quiz_attempts',
                refid: $attempt->attemptid,
                summary: null
            );
        }

        return $res;
    }

    /**
     * Returns a fingerprint for the current state of the referenced quiz.
     *
     * We use the latest modification time of the quiz itself and the latest
     * modification time of any attempt inside this quiz to calculate the
     * fingerprint.
     *
     * @return cm_state_fingerprint Fingerprint for the current state of the
     * referenced course module
     * @throws \JsonException On JSON encoding errors
     * @throws \coding_exception If the fingerprint calculation fails
     * @throws \dml_exception On database errors
     */
    #[\Override]
    public function fingerprint(): cm_state_fingerprint {
        global $DB;

        // Get the latest modification time of any attempt indide this quiz as well as the quiz itself.
        $quiztimemodified = $DB->get_field('quiz', 'timemodified', ['id' => $this->quizid], MUST_EXIST);

        $attempttimemodified = $DB->get_field_sql(
            "SELECT MAX(timemodified) FROM {quiz_attempts} WHERE quiz = :quizid",
            ['quizid' => $this->quizid],
            MUST_EXIST
        );

        // Calculate the fingerprint.
        return cm_state_fingerprint::generate([
            'quiztimemodified' => $quiztimemodified,
            'attempttimemodified' => $attempttimemodified,
        ]);
    }

}
