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
 * Plugin administration pages are defined here
 *
 * @package     archivingmod_quiz
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use archivingmod_quiz\attempt_report;
use archivingmod_quiz\task;
use local_archiving\local\admin\setting\admin_setting_configcheckbox_alwaystrue;
use local_archiving\local\admin\setting\admin_setting_filename_pattern;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


global $DB;

if ($hassiteconfig) {
    $settings = new admin_settingpage('archivingmod_quiz_settings', new lang_string('pluginname', 'archivingmod_quiz'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Descriptive text.
        $settings->add(new admin_setting_heading('archivingmod_quiz/header_docs',
            null,
            get_string('setting_header_docs_desc', 'archivingmod_quiz')
        ));

        /*
        // Autoinstall.
        if (autoinstall::plugin_is_unconfigured()) {
            // @codingStandardsIgnoreStart
            $autoinstallurl = new moodle_url('/mod/quiz/report/archiver/adminui/autoinstall.php');
            $autoinstalldesc = "<a href='{$autoinstallurl}' class='btn btn-primary'>".get_string('autoinstall_start_now', 'archivingmod_quiz')."</a>";
            $autoinstalldesc .= "<br><br><p>".get_string('autoinstall_explanation', 'archivingmod_quiz')."</p>";
            // @codingStandardsIgnoreEnd
        } else {
            $autoinstalldesc = get_string('autoinstall_already_configured', 'archivingmod_quiz');
        }
        $settings->add(new admin_setting_description('archivingmod_quiz/autoinstall',
            get_string('setting_autoconfigure', 'archivingmod_quiz'),
            $autoinstalldesc
        ));
        */

        // Worker service.
        $settings->add(new admin_setting_heading('archivingmod_quiz/header_archive_worker',
            get_string('setting_header_archive_worker', 'archivingmod_quiz'),
            get_string('setting_header_archive_worker_desc', 'archivingmod_quiz')
        ));

        // Worker service: URL.
        $settings->add(new admin_setting_configtext('archivingmod_quiz/worker_url',
            get_string('setting_worker_url', 'archivingmod_quiz'),
            get_string('setting_worker_url_desc', 'archivingmod_quiz'),
            '',
            PARAM_TEXT
        ));

        // Worker service: Webservice.
        $settings->add(new admin_setting_configselect('archivingmod_quiz/webservice_id',
            get_string('webservice', 'webservice'),
            get_string('setting_webservice_desc', 'archivingmod_quiz'),
            null,
            [-1 => ''] + $DB->get_records_menu('external_services', null, 'name ASC', 'id, name')
        ));

        // Worker service: Webservice user.
        $settings->add(new admin_setting_configtext('archivingmod_quiz/webservice_userid',
            get_string('setting_webservice_userid', 'archivingmod_quiz'),
            get_string('setting_webservice_userid_desc', 'archivingmod_quiz'),
            '',
            PARAM_INT
        ));

        // Worker service: Custom Moodle base URL.
        $settings->add(new admin_setting_configtext('archivingmod_quiz/internal_wwwroot',
            get_string('setting_internal_wwwroot', 'archivingmod_quiz'),
            get_string('setting_internal_wwwroot_desc', 'archivingmod_quiz'),
            '',
            PARAM_TEXT
        ));

        // Job Presets.
        $settings->add(new admin_setting_heading('archivingmod_quiz/header_job_presets',
            get_string('setting_header_job_presets', 'local_archiving'),
            get_string('setting_header_job_presets_desc', 'local_archiving'),
        ));

        // Job preset: Export Attempts.
        $settings->add(new admin_setting_configcheckbox_alwaystrue('archivingmod_quiz/job_preset_export_attempts',
            get_string('task_export_attempts', 'archivingmod_quiz'),
            get_string('task_export_attempts_help', 'archivingmod_quiz'),
            '1',
        ));

        // Job preset: Attempt report sections.
        foreach (attempt_report::SECTIONS as $section) {
            $set = new admin_setting_configcheckbox('archivingmod_quiz/job_preset_report_section_'.$section,
                get_string('task_report_section_'.$section, 'archivingmod_quiz'),
                get_string('task_report_section_'.$section.'_help', 'archivingmod_quiz'),
                '1',
            );
            $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);

            foreach (attempt_report::SECTION_DEPENDENCIES[$section] as $dependency) {
                $set->add_dependent_on('archivingmod_quiz/job_preset_report_section_'.$dependency);
            }

            $settings->add($set);
        }

        // Job preset: Export paper format.
        $set = new admin_setting_configselect('archivingmod_quiz/job_preset_paper_format',
            get_string('task_paper_format', 'archivingmod_quiz'),
            get_string('task_paper_format_help', 'archivingmod_quiz'),
            'A4',
            array_combine(attempt_report::PAPER_FORMATS, attempt_report::PAPER_FORMATS),
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        // Job preset: Attempt folder name pattern.
        $set = new admin_setting_filename_pattern('archivingmod_quiz/job_preset_attempt_foldername_pattern',
            get_string('task_attempt_foldername_pattern', 'archivingmod_quiz'),
            get_string('task_attempt_foldername_pattern_help', 'archivingmod_quiz', [
                'variables' => array_reduce(
                    attempt_report::ATTEMPT_FOLDERNAME_PATTERN_VARIABLES,
                    fn ($res, $varname) => $res."<li><code>\${".$varname."}</code>: ".
                        get_string('task_attempt_filename_pattern_variable_'.$varname, 'archivingmod_quiz').
                        "</li>"
                    , ""
                ),
                'forbiddenchars' => implode('', \local_archiving\storage::FOLDERNAME_FORBIDDEN_CHARACTERS),
            ]),
            '${username}/${attemptid}-${date}_${time}',
            attempt_report::ATTEMPT_FOLDERNAME_PATTERN_VARIABLES,
            \local_archiving\storage::FOLDERNAME_FORBIDDEN_CHARACTERS,
            PARAM_TEXT,
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        // Job preset: Attempt filename pattern.
        $set = new admin_setting_filename_pattern('archivingmod_quiz/job_preset_attempt_filename_pattern',
            get_string('task_attempt_filename_pattern', 'archivingmod_quiz'),
            get_string('task_attempt_filename_pattern_help', 'archivingmod_quiz', [
                'variables' => array_reduce(
                    attempt_report::ATTEMPT_FILENAME_PATTERN_VARIABLES,
                    fn ($res, $varname) => $res."<li><code>\${".$varname."}</code>: ".
                        get_string('task_attempt_filename_pattern_variable_'.$varname, 'archivingmod_quiz').
                        "</li>"
                    , ""
                ),
                'forbiddenchars' => implode('', \local_archiving\storage::FILENAME_FORBIDDEN_CHARACTERS),
            ]),
            'attempt-${attemptid}-${username}_${date}-${time}',
            attempt_report::ATTEMPT_FILENAME_PATTERN_VARIABLES,
            \local_archiving\storage::FILENAME_FORBIDDEN_CHARACTERS,
            PARAM_TEXT,
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        // Job preset: Image optimization.
        $set = new admin_setting_configcheckbox('archivingmod_quiz/job_preset_image_optimize',
            get_string('task_image_optimize', 'archivingmod_quiz'),
            get_string('task_image_optimize_help', 'archivingmod_quiz'),
            '0',
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        // Job preset: Image optimization: Max width.
        $set = new admin_setting_configtext('archivingmod_quiz/job_preset_image_optimize_width',
            get_string('task_image_optimize_width', 'archivingmod_quiz'),
            get_string('task_image_optimize_width_help', 'archivingmod_quiz'),
            '1280',
            PARAM_INT
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $set->add_dependent_on('archivingmod_quiz/job_preset_image_optimize');
        $settings->add($set);

        // Job preset: Image optimization: Max height.
        $set = new admin_setting_configtext('archivingmod_quiz/job_preset_image_optimize_height',
            get_string('task_image_optimize_height', 'archivingmod_quiz'),
            get_string('task_image_optimize_height_help', 'archivingmod_quiz'),
            '1280',
            PARAM_INT
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $set->add_dependent_on('archivingmod_quiz/job_preset_image_optimize');
        $settings->add($set);

        // Job preset: Image optimization: Quality.
        $set = new admin_setting_configtext('archivingmod_quiz/job_preset_image_optimize_quality',
            get_string('task_image_optimize_quality', 'archivingmod_quiz'),
            get_string('task_image_optimize_quality_help', 'archivingmod_quiz'),
            '85',
            PARAM_INT
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $set->add_dependent_on('archivingmod_quiz/job_preset_image_optimize');
        $settings->add($set);

        // Job preset: Keep HTML files.
        $set = new admin_setting_configcheckbox('archivingmod_quiz/job_preset_keep_html_files',
            get_string('task_keep_html_files', 'archivingmod_quiz'),
            get_string('task_keep_html_files_help', 'archivingmod_quiz'),
            '0',
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);
    }

    // Settingpage is added to tree automatically. No need to add it manually here.
}
