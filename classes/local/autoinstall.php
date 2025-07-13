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

namespace archivingmod_quiz\local;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


// @codeCoverageIgnoreStart
require_once("{$CFG->dirroot}/user/lib.php");
require_once("{$CFG->dirroot}/webservice/lib.php");
require_once("{$CFG->dirroot}/lib/adminlib.php");
// @codeCoverageIgnoreEnd

use coding_exception;
use context_system;
use dml_exception;
use webservice;

/**
 * Autoinstall routines for the archivingmod_quiz plugin
 *
 * @package   archivingmod_quiz
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 *            2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoinstall {
    // TODO (MDL-0): Switch to pre-defined web service via db/services.php and
    // thereby remove creation of web service and function assignment from autoinstall.

    /** @var string Default name for the webservice to create */
    const DEFAULT_WSNAME = 'archivingmod_quiz_webservice';

    /** @var string Default username for the service account to create */
    const DEFAULT_USERNAME = 'archivingmod_quiz_serviceaccount';

    /** @var string Default shortname for the role to create */
    const DEFAULT_ROLESHORTNAME = 'archivingmod_quiz';

    /** @var string[] List of capabilities to assign to the created role */
    const WS_ROLECAPS = [
        'mod/quiz:reviewmyattempts',
        'mod/quiz:view',
        'mod/quiz:viewreports',
        'moodle/backup:anonymise',
        'moodle/backup:backupactivity',
        'moodle/backup:backupcourse',
        'moodle/backup:backupsection',
        'moodle/backup:backuptargetimport',
        'moodle/backup:configure',
        'moodle/backup:downloadfile',
        'moodle/backup:userinfo',
        'moodle/course:ignoreavailabilityrestrictions',
        'moodle/course:view',
        'moodle/course:viewhiddenactivities',
        'moodle/course:viewhiddencourses',
        'moodle/course:viewhiddensections',
        'moodle/user:ignoreuserquota',
        'webservice/rest:use',
    ];

    /** @var string[] List of functions to add to the created webservice */
    const WS_FUNCTIONS = [
        'archivingmod_quiz_generate_attempt_report',
        'archivingmod_quiz_get_attempts_metadata',
        'archivingmod_quiz_process_uploaded_artifact',
        'archivingmod_quiz_update_task_status',
    ];

    /**
     * Determines if the archivingmod_quiz plugin was configured previously.
     *
     * @return bool True if the plugin is unconfigured, false otherwise
     * @throws dml_exception If the plugin configuration cannot be retrieved
     */
    public static function plugin_is_unconfigured(): bool {
        return intval(get_config('archivingmod_quiz', 'webservice_id')) <= 0
            && intval(get_config('archivingmod_quiz', 'webservice_userid')) <= 0;
    }

    /**
     * Performs an automatic installation of the archivingmod_quiz plugin.
     *
     * This function:
     *   - Enables web services and REST protocol
     *   - Creates a quiz archiver service role and a corresponding user
     *   - Creates a new web service with all required webservice functions
     *   - Authorises the user to use the webservice.
     *
     * @param string $workerurl The URL of the quiz archive worker service
     * @param string $wsname The name for the web service to create
     * @param string $rolename The shortname for the role to create
     * @param string $username The username for the service account to create
     * @param bool $force If true, the installation is forced regardless of the
     *                    current state of the system
     * @return array An array with two elements: a boolean indicating success
     *               and a string with a log of the performed actions
     */
    public static function execute(
        string $workerurl,
        string $wsname = self::DEFAULT_WSNAME,
        string $rolename = self::DEFAULT_ROLESHORTNAME,
        string $username = self::DEFAULT_USERNAME,
        bool $force = false
    ): array {
        global $CFG;

        // Prepare return values.
        $success = false;

        try {
            // Init log array.
            $log = [];

            // Ensure current user is an admin.
            if (!is_siteadmin()) {
                $log[] = "Error: You need to be a site administrator to run this script.";
                throw new \RuntimeException();
            }

            // Check if the plugin is already configured.
            if (!self::plugin_is_unconfigured()) {
                if ($force) {
                    $log[] = "Warning: The archivingmod_quiz plugin is already configured. Forcing reconfiguration nonetheless ...";
                } else {
                    $log[] = "Error: The archivingmod_quiz plugin is already configured. Use --force to bypass this check.";
                    throw new \RuntimeException();
                }
            }

            // Apply default values for all plugin settings.
            $adminroot = admin_get_root();
            $adminsearch = $adminroot->search('archivingmod_quiz');
            if (!$adminsearch || !$adminsearch['archivingmod_quiz']->page) {
                $log[] = "Error: Could not find admin settings definitions for archivingmod_quiz plugin.";
                throw new \RuntimeException();
            }
            $adminpage = $adminsearch['archivingmod_quiz']->page;
            $appliedsettings = admin_apply_default_settings($adminpage);
            if (count($appliedsettings) < 1) {
                $log[] = "Error: Could not apply default settings for archivingmod_quiz plugin.";
                throw new \RuntimeException();
            } else {
                $log[] = "  -> Default plugin settings applied.";
            }

            // Check worker URL.
            if (empty($workerurl)) {
                $log[] = "Error: The given worker URL is invalid.";
                throw new \RuntimeException();
            }

            // Get system context.
            try {
                $systemcontext = context_system::instance();
            } catch (dml_exception $e) {
                $log[] = "Error: Cannot get system context: ".$e->getMessage();
                throw new \RuntimeException();
            }

            // Create a web service user.
            try {
                $webserviceuserid = user_create_user([
                    'auth' => 'manual',
                    'username' => $username,
                    'password' => bin2hex(random_bytes(28))."#1A",
                    'firstname' => 'Quiz Archive Worker',
                    'lastname' => 'Service Account',
                    'email' => 'noreply@localhost',
                    'confirmed' => 1,
                    'deleted' => 0,
                    'policyagreed' => 1,
                    'mnethostid' => $CFG->mnet_localhost_id,
                ]);
                $webserviceuser = \core_user::get_user($webserviceuserid);
                $log[] = "  -> Web service user '{$webserviceuser->username}' with ID {$webserviceuser->id} created.";
            } catch (dml_exception $e) {
                $log[] = "Error: Cloud not create webservice user: ".$e->getMessage();
                throw new \RuntimeException();
            } catch (\Exception $e) {  // Random\RandomException is only thrown with PHP >= 8.2, generic \Exception otherwise.
                $log[] = "Error: Could not create webservice user: ".$e->getMessage();
                throw new \RuntimeException();
            }

            // Create a web service role.
            try {
                $wsroleid = create_role(
                    'Quiz Archive Worker Service Account',
                    $rolename,
                    'A role that bundles all access rights required for the archivingmod_quiz plugin to work.'
                );
                set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM]);

                $log[] = "  -> Role '{$rolename}' created.";
            } catch (coding_exception $e) {
                $log[] = "Error: Cannot create role {$rolename}: {$e->getMessage()}";
                throw new \RuntimeException();
            }

            foreach (self::WS_ROLECAPS as $cap) {
                try {
                    assign_capability($cap, CAP_ALLOW, $wsroleid, $systemcontext->id, true);
                    $log[] = "    -> Capability {$cap} assigned to role '{$rolename}'.";
                } catch (coding_exception $e) {
                    $log[] = "Error: Cannot assign capability {$cap}: {$e->getMessage()}";
                    throw new \RuntimeException();
                }
            }

            // Give the user the role.
            try {
                role_assign($wsroleid, $webserviceuser->id, $systemcontext->id);
                $log[] = "  -> Role '{$rolename}' assigned to user '{$webserviceuser->username}'.";
            } catch (coding_exception $e) {
                $log[] = "Error: Cannot assign role to webservice user: ".$e->getMessage();
                throw new \RuntimeException();
            }

            // Enable web services and REST protocol.
            try {
                set_config('enablewebservices', true);
                $log[] = "  -> Web services enabled.";

                $enabledprotocols = get_config('core', 'webserviceprotocols');
                if (stripos($enabledprotocols, 'rest') === false) {
                    set_config('webserviceprotocols', $enabledprotocols . ',rest');
                }
                $log[] = "  -> REST webservice protocol enabled.";
            } catch (dml_exception $e) {
                $log[] = "Error: Cannot get config setting webserviceprotocols: ".$e->getMessage();
                throw new \RuntimeException();
            }

            // Enable the webservice.
            $webservicemanager = new webservice();
            $serviceid = $webservicemanager->add_external_service((object)[
                'name' => $wsname,
                'shortname' => $wsname,
                'enabled' => 1,
                'requiredcapability' => '',
                'restrictedusers' => true,
                'downloadfiles' => true,
                'uploadfiles' => true,
            ]);

            if (!$serviceid) {
                $log[] = "Error: Service {$wsname} could not be created.";
                throw new \RuntimeException();
            } else {
                $log[] = "  -> Web service '{$wsname}' created with ID {$serviceid}.";
            }

            // Add functions to the service.
            foreach (self::WS_FUNCTIONS as $f) {
                $webservicemanager->add_external_function_to_service($f, $serviceid);
                $log[] = "    -> Function {$f} added to service '{$wsname}'.";
            }

            // Authorise the user to use the service.
            $webservicemanager->add_ws_authorised_user((object) [
                'externalserviceid' => $serviceid,
                'userid' => $webserviceuser->id,
            ]);

            $service = $webservicemanager->get_external_service_by_id($serviceid);
            $webservicemanager->update_external_service($service);
            $log[] = "  -> User '{$webserviceuser->username}' authorised to use service '{$wsname}'.";

            // Configure archivingmod_quiz plugin settings.
            try {
                $log[] = "  -> Configuring the archivingmod_quiz plugin...";

                set_config('webservice_id', $serviceid, 'archivingmod_quiz');
                $log[] = "    -> Web service set to '{$wsname}'.";

                set_config('webservice_userid', $webserviceuser->id, 'archivingmod_quiz');
                $log[] = "    -> Web service user set to '{$webserviceuser->username}'.";

                set_config('worker_url', $workerurl, 'archivingmod_quiz');
                $log[] = "    -> Worker URL set to '{$workerurl}'.";
            } catch (\Exception $e) {
                $log[] = "Error: Failed to set config settings for archivingmod_quiz plugin: ".$e->getMessage();
                throw new \RuntimeException();
            }

            $success = true;
        } catch (\RuntimeException $e) {
            $success = false;
        } catch (\Exception $e) {
            $success = false;
            $log[] = "Error: An unexpected error occurred: ".$e->getMessage();
        } finally {
            return [$success, implode("\r\n", $log)];
        }
    }

}
