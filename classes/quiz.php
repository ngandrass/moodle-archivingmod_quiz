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
 * This file defines the quiz class
 *
 * @package   archivingmod_quiz
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Quiz management class
 */
class quiz {

    /** @var \stdClass Course object this instance is associated with */
    protected \stdClass $course;

    /** @var \cm_info Course module info object this instance is associated with */
    protected \cm_info $cm;

    /** @var object Moodle admin settings object */
    protected object $config;

    /**
     * Creates a new attempt report
     *
     * @param int $courseid ID of the course the cm lives in
     * @param int $cmid ID of the course module the quiz lives in
     * @param int $quizid ID of the quiz this attempt report belongs to
     * @throws \dml_exception If the course or cm cannot be found
     * @throws \moodle_exception If the given arguments are invalid
     */
    public function __construct(
        protected int $courseid,
        protected int $cmid,
        protected int $quizid
    ) {
        // Validate arguments.
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
        if ($course->id != $courseid) {
            throw new \moodle_exception('invalidcourseid', 'local_archiving');
        }
        if ($cm->instance != $quizid) {
            throw new \moodle_exception('invalidcmidorquizid', 'local_archiving');
        }

        $this->course = $course;
        $this->cm = $cm;
        $this->config = get_config('quiz_archiver');
    }

    /**
     * Get all attempts for all users inside this quiz, excluding previews
     *
     * @return array Array of all attempt IDs together with the userid that were
     * made inside this quiz. Indexed by attemptid.
     *
     * @throws \dml_exception
     */
    public function get_attempts(): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT id AS attemptid, userid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid",
            [
                "quizid" => $this->quizid,
            ]
        );
    }

    /**
     * Gets the metadata of all attempts made inside this quiz, excluding previews.
     *
     * @param array|null $filterattemptids If given, only attempts with the given
     * IDs will be returned.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_attempts_metadata(?array $filterattemptids = null): array {
        global $DB;

        // Handle attempt ID filter.
        if ($filterattemptids) {
            $filterwhereclause = "AND qa.id IN (" . implode(', ', array_map(fn($v): string => intval($v), $filterattemptids)) . ")";
        }

        // Get all requested attempts.
        return $DB->get_records_sql(
            "SELECT qa.id AS attemptid, qa.userid, qa.attempt, qa.state, qa.timestart, qa.timefinish, " .
            "       u.username, u.firstname, u.lastname, u.idnumber " .
            "FROM {quiz_attempts} qa LEFT JOIN {user} u ON qa.userid = u.id " .
            "WHERE qa.preview = 0 AND qa.quiz = :quizid " . ($filterwhereclause ?? ''),
            [
                "quizid" => $this->quizid,
            ]
        );
    }

    /**
     * Checks if an attempt with the given ID exists inside this quiz and it's
     * not a preview
     *
     * @param int $attemptid ID of the attempt to check for existence
     * @return bool True if an attempt with the given ID exists inside this quiz
     * @throws \dml_exception
     */
    public function attempt_exists(int $attemptid): bool {
        global $DB;

        return $DB->record_exists('quiz_attempts', [
            'id' => $attemptid,
            'quiz' => $this->quizid,
            'preview' => 0,
        ]);
    }

    /**
     * Returns a list of all files that were attached to questions inside the
     * given attempt
     *
     * @param int $attemptid ID of the attempt to get the files from
     * @return array List of all files that are attached to the questions
     *               inside the given attempt
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_attempt_attachments(int $attemptid): array {
        $files = [];
        $attemptobj = quiz_create_attempt_handling_errors($attemptid);
        $ctx = \context_module::instance($attemptobj->get_cmid());

        // Get all files from all questions inside this attempt.
        foreach ($attemptobj->get_slots() as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            $qafiles = $qa->get_last_qt_files('attachments', $ctx->id);

            foreach ($qafiles as $qafile) {
                $files[] = [
                    'usageid' => $qa->get_usage_id(),
                    'slot' => $slot,
                    'file' => $qafile,
                ];
            }
        }

        return $files;
    }

}
