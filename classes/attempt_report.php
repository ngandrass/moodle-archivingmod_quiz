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
 * This file defines the attempt_report class
 *
 * @package   archivingmod_quiz
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Quiz attempt report renderer
 */
class attempt_report {

    /** @var string[] Valid variables for attempt report filename patterns */
    public const ATTEMPT_FILENAME_PATTERN_VARIABLES = [
        'courseid',
        'coursename',
        'courseshortname',
        'cmid',
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

    /** @var array Sections that can be included in the report */
    public const SECTIONS = [
        "header",
        "quiz_feedback",
        "question",
        "question_feedback",
        "general_feedback",
        "rightanswer",
        "history",
        "attachments",
    ];

    /** @var array Dependencies of report sections */
    public const SECTION_DEPENDENCIES = [
        "header" => [],
        "question" => [],
        "quiz_feedback" => ["header"],
        "question_feedback" => ["question"],
        "general_feedback" => ["question"],
        "rightanswer" => ["question"],
        "history" => ["question"],
        "attachments" => ["question"],
    ];

    /** @var string[] Available paper formats for attempt PDFs */
    public const PAPER_FORMATS = [
        'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6',
        'Letter', 'Legal', 'Tabloid', 'Ledger',
    ];

}
