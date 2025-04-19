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

    /** @var string[] Valid variables for attempt report filename patterns */
    public const ATTEMPT_FILENAME_PATTERN_VARIABLES = [
        'courseid',
        'coursename',
        'courseshortname',
        'cmid',
        'groupids',
        'groupidnumbers',
        'groupnames',
        'quizid',
        'quizname',
        'attemptid',
        'username',
        'firstname',
        'lastname',
        'idnumber',
        'timestart',
        'timefinish',
        'date',
        'time',
        'timestamp',
    ];

    /** @var string[] Valid variables for attempt folder name patterns */
    public const ATTEMPT_FOLDERNAME_PATTERN_VARIABLES = self::ATTEMPT_FILENAME_PATTERN_VARIABLES;

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

}
