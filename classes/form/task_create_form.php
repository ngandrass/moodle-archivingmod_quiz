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
 * Defines the task creation form
 *
 * @package    archivingmod_quiz
 * @copyright  2025 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingmod_quiz\form;

use archivingmod_quiz\attempt_report;
use archivingmod_quiz\task;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore


/**
 * Form to initiate a new quiz archive job
 */
class task_create_form extends \moodleform {

    /** @var \cm_info Info object for the targeted course module */
    protected \cm_info $cm_info;

    /**
     * Creates a new form instance
     *
     * @param \cm_info $cm_info Info object for the targeted course module
     * @throws \moodle_exception If invalid cm_info was given
     */
    public function __construct(\cm_info $cm_info) {
        // Store and validate cm_info
        $this->cm_info = $cm_info;
        if ($this->cm_info->modname != 'quiz') {
            throw new \moodle_exception(
                "Invalid cm object passed to task_create_form. Expected 'quiz', got '{$this->cm_info->modname}'
            ");
        }

        parent::__construct();
    }

    /**
     * Form definiton
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        global $CFG;

        $config = get_config('archivingmod_quiz');
        $mform = $this->_form;

        // Title and description.
        $mform->addElement('html', '<h1>'.get_string('create_quiz_archive', 'archivingmod_quiz').'</h1>');
        $mform->addElement('html', '<p>'.get_string('archive_quiz_form_desc', 'archivingmod_quiz').'</p>');

        // Internal information of mod_quiz.
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options.
        $mform->addElement('header', 'header_settings', get_string('settings'));

        // Options: Test.
        $mform->addElement('static', 'quiz_name', get_string('modulename', 'mod_quiz'), $this->cm_info->name);

        // Options: Attempts.
        $mform->addElement(
            'advcheckbox',
            'export_attempts',
            get_string('attempts', 'mod_quiz'),
            get_string('export_attempts', 'archivingmod_quiz'),
            ['disabled' => 'disabled'],
            ['1', '1']
        );
        $mform->addHelpButton('export_attempts', 'export_attempts', 'archivingmod_quiz');
        $mform->setDefault('export_attempts', true);

        foreach (attempt_report::SECTIONS as $section) {
            $mform->addElement(
                'advcheckbox',
                'export_report_section_'.$section, '&nbsp;',
                get_string('export_report_section_'.$section, 'archivingmod_quiz'),
                $config->{'job_preset_export_report_section_'.$section.'_locked'} ? 'disabled' : null
            );
            $mform->addHelpButton('export_report_section_'.$section, 'export_report_section_'.$section, 'archivingmod_quiz');
            $mform->setDefault('export_report_section_'.$section, $config->{'job_preset_export_report_section_'.$section});

            if (!$config->{'job_preset_export_report_section_'.$section.'_locked'}) {
                foreach (attempt_report::SECTION_DEPENDENCIES[$section] as $dependency) {
                    $mform->disabledIf('export_report_section_'.$section, 'export_report_section_'.$dependency, 'notchecked');
                }
            }
        }

        // Advanced options.
        $mform->addElement('header', 'header_advanced_settings', get_string('advancedsettings'));
        $mform->setExpanded('header_advanced_settings', false);

        // Advanced options: Paper format.
        $mform->addElement(
            'select',
            'export_attempts_paper_format',
            get_string('export_attempts_paper_format', 'archivingmod_quiz'),
            array_combine(attempt_report::PAPER_FORMATS, attempt_report::PAPER_FORMATS),
            $config->job_preset_export_attempts_paper_format_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_attempts_paper_format', 'export_attempts_paper_format', 'archivingmod_quiz');
        $mform->setDefault('export_attempts_paper_format', $config->job_preset_export_attempts_paper_format);

        // Advanced options: Attempts filename pattern.
        $mform->addElement(
            'text',
            'export_attempts_filename_pattern',
            get_string('export_attempts_filename_pattern', 'archivingmod_quiz'),
            $config->job_preset_export_attempts_filename_pattern_locked ? 'disabled' : null
        );
        if ($CFG->branch > 402) {
            $mform->addHelpButton(
                'export_attempts_filename_pattern',
                'export_attempts_filename_pattern',
                'archivingmod_quiz',
                '',
                false,
                [
                    'variables' => array_reduce(
                        task::ATTEMPT_FILENAME_PATTERN_VARIABLES,
                        fn($res, $varname) => $res."<li>".
                                "<code>\${".$varname."}</code>: ".
                                get_string('export_attempts_filename_pattern_variable_'.$varname, 'archivingmod_quiz').
                            "</li>",
                        ""
                    ),
                    'forbiddenchars' => implode('', \local_archiving\storage::FILENAME_FORBIDDEN_CHARACTERS),
                ]
            );
        } else {
            // TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
            $mform->addHelpButton('export_attempts_filename_pattern', 'export_attempts_filename_pattern_moodle42', 'archivingmod_quiz');
        }
        $mform->setType('export_attempts_filename_pattern', PARAM_TEXT);
        $mform->setDefault('export_attempts_filename_pattern', $config->job_preset_export_attempts_filename_pattern);
        $mform->addRule('export_attempts_filename_pattern', null, 'maxlength', 255, 'client');

        // Advanced options: Image optimization.
        $mform->addElement(
            'advcheckbox',
            'export_attempts_image_optimize',
            get_string('export_attempts_image_optimize', 'archivingmod_quiz'),
            get_string('enable'),
            $config->job_preset_export_attempts_image_optimize_locked ? 'disabled' : null,
            ['0', '1']
        );
        $mform->addHelpButton('export_attempts_image_optimize', 'export_attempts_image_optimize', 'archivingmod_quiz');
        $mform->setDefault('export_attempts_image_optimize', $config->job_preset_export_attempts_image_optimize);

        // Image max width/height fields.
        $mformgroup = [];
        $mformgroupfieldseperator = 'x';
        if ($config->job_preset_export_attempts_image_optimize_width_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_width_static',
                '',
                $config->job_preset_export_attempts_image_optimize_width
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_width',
                $config->job_preset_export_attempts_image_optimize_width
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_width',
                get_string('export_attempts_image_optimize_width', 'archivingmod_quiz'),
                ['size' => 4]
            );
            $mform->setDefault('export_attempts_image_optimize_width', $config->job_preset_export_attempts_image_optimize_width);
        }
        $mform->setType('export_attempts_image_optimize_width', PARAM_INT);

        if ($config->job_preset_export_attempts_image_optimize_height_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_height_static',
                '',
                $config->job_preset_export_attempts_image_optimize_height
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_height',
                $config->job_preset_export_attempts_image_optimize_height
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_height',
                get_string('export_attempts_image_optimize_height', 'archivingmod_quiz'),
                ['size' => 4]
            );
            $mform->setDefault('export_attempts_image_optimize_height', $config->job_preset_export_attempts_image_optimize_height);
            $mformgroupfieldseperator .= '&nbsp;';
        }
        $mform->setType('export_attempts_image_optimize_height', PARAM_INT);

        $mformgroup[] = $mform->createElement('static', 'export_attempts_image_optimize_px', '', 'px');

        $mform->addGroup(
            $mformgroup,
            'export_attempts_image_optimize_group',
            get_string('export_attempts_image_optimize_group', 'archivingmod_quiz'),
            [$mformgroupfieldseperator, ''],
            false
        );
        $mform->addHelpButton('export_attempts_image_optimize_group', 'export_attempts_image_optimize_group', 'archivingmod_quiz');
        $mform->hideIf('export_attempts_image_optimize_group', 'export_attempts_image_optimize', 'notchecked');

        // Image quality field.
        $mformgroup = [];
        if ($config->job_preset_export_attempts_image_optimize_quality_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_quality_static',
                '',
                $config->job_preset_export_attempts_image_optimize_quality
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_quality',
                $config->job_preset_export_attempts_image_optimize_quality
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_quality',
                get_string('export_attempts_image_optimize_quality', 'archivingmod_quiz'),
                ['size' => 2]
            );
            $mform->setDefault(
                'export_attempts_image_optimize_quality',
                $config->job_preset_export_attempts_image_optimize_quality
            );
        }
        $mform->setType('export_attempts_image_optimize_quality', PARAM_INT);

        $mformgroup[] = $mform->createElement('static', 'export_attempts_image_optimize_quality_percent', '', '%');
        $mform->addGroup(
            $mformgroup,
            'export_attempts_image_optimize_quality_group',
            get_string('export_attempts_image_optimize_quality', 'archivingmod_quiz'),
            '',
            false
        );
        $mform->addHelpButton(
            'export_attempts_image_optimize_quality_group',
            'export_attempts_image_optimize_quality',
            'archivingmod_quiz'
        );
        $mform->hideIf('export_attempts_image_optimize_quality_group', 'export_attempts_image_optimize', 'notchecked');

        // Advanced options: Keep HTML files.
        $mform->addElement(
            'advcheckbox',
            'export_attempts_keep_html_files',
            get_string('export_attempts_keep_html_files', 'archivingmod_quiz'),
            get_string('export_attempts_keep_html_files_desc', 'archivingmod_quiz'),
            $config->job_preset_export_attempts_keep_html_files_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_attempts_keep_html_files', 'export_attempts_keep_html_files', 'archivingmod_quiz');
        $mform->setDefault('export_attempts_keep_html_files', $config->job_preset_export_attempts_keep_html_files);

        // Submit.
        $mform->closeHeaderBefore('submitbutton');
        $mform->addElement('submit', 'submitbutton', get_string('archive_quiz', 'archivingmod_quiz'));
    }

    /**
     * Server-side form data validation
     *
     * @param mixed $data Submitted form data
     * @param mixed $files Uploaed files
     * @return array Associative array with error messages for invalid fields
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!\local_archiving\storage::is_valid_filename_pattern(
            $data['export_attempts_filename_pattern'],
            task::ATTEMPT_FILENAME_PATTERN_VARIABLES
        )) {
            $errors['export_attempts_filename_pattern'] = get_string('error_invalid_filename_pattern', 'local_archiving');
        }

        return $errors;
    }

    /**
     * Returns the data submitted by the user but forces all locked fields to
     * their preset values
     *
     * @return \stdClass Cleared, submitted form data
     * @throws \dml_exception
     */
    public function get_data(): \stdClass {
        $data = parent::get_data();
        $config = get_config('archivingmod_quiz');

        // Force locked fields to their preset values.
        foreach ($config as $key => $value) {
            if (strpos($key, 'job_preset_') === 0 && strrpos($key, '_locked') === strlen($key) - 7) {
                if ($value) {
                    $data->{substr($key, 11, -7)} = $config->{substr($key, 0, -7)};
                }
            }
        }

        return $data;
    }

}
