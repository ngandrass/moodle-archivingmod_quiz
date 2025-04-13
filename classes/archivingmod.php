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

use local_archiving\archive_job;
use local_archiving\driver\mod\task;
use local_archiving\driver\mod\task_status;
use local_archiving\exception\yield_exception;

// @codingStandardsIgnoreFile
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Quiz activity archiving driver
 */
class archivingmod extends \local_archiving\driver\mod\archivingmod {

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

    public static function get_name(): string {
        return get_string('pluginname', 'archivingmod_quiz');
    }

    public static function get_modname(): string {
        return 'quiz';
    }

    public static function get_supported_activities(): array {
        return ['quiz'];
    }

    public function can_be_archived(): bool {
        global $DB;

        // FIXME: Always mark as archivable for debug purposes.
        return true;

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

    public function get_job_create_form(string $handler, \cm_info $cminfo): \local_archiving\form\job_create_form {
        return new form\job_create_form($handler, $cminfo);
    }

    public function execute_task(task $task): void {
        $status = $task->get_status();

        try {
            if ($status == task_status::STATUS_UNINITIALIZED) {
                $status = task_status::STATUS_CREATED;
            }

            if ($status == task_status::STATUS_CREATED) {
                $status = task_status::STATUS_AWAITING_PROCESSING;
                throw new yield_exception();
            }

            if ($status == task_status::STATUS_AWAITING_PROCESSING) {
                $status = task_status::STATUS_RUNNING;
                $task->set_progress(0);
                throw new yield_exception();
            }

            if ($status == task_status::STATUS_RUNNING) {
                if ($task->get_progress() < 50) {
                    $task->set_progress(50);
                    throw new yield_exception();
                } else if ($task->get_progress() < 100) {
                    $task->set_progress(100);
                    throw new yield_exception();
                } else {
                    $status = task_status::STATUS_FINALIZING;
                }
            }

            if ($status == task_status::STATUS_FINALIZING) {
                $status = task_status::STATUS_FINISHED;
            }
        } finally {
            $task->set_status($status);
        }
    }

}
