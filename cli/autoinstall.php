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
 * Automatic install script for the archivingmod_quiz plugin
 *
 * @package    archivingmod_quiz
 * @copyright  2025 Niels Gandraß <niels@gandrass.de>
 *             2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Must be kept at old define syntax because Moodle CodeSniffer profile does not detect new const syntax properly.
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once("{$CFG->libdir}/clilib.php");

use archivingmod_quiz\local\autoinstall;

// XXX-> CLI options parsing.

$defaultworkerurl = 'http://localhost:8080';
$defaultwsname = autoinstall::DEFAULT_WSNAME;
$defaultroleshortname = autoinstall::DEFAULT_ROLESHORTNAME;
$defaultusername = autoinstall::DEFAULT_USERNAME;

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'workerurl' => $defaultworkerurl,
        'wsname' => $defaultwsname,
        'rolename' => $defaultroleshortname,
        'username' => $defaultusername,
        'force' => false,
    ],
    [
        'h' => 'help',
        'f' => 'force',
    ]
);

$usage = <<<EOT
Automatically configures Moodle for use with the archivingmod_quiz plugin.

ATTENTION: This CLI script ...
- Enables web services and REST protocol
- Creates a quiz archiver service role and a corresponding user
- Creates a new web service with all required webservice functions
- Authorises the user to use the webservice.

Usage:
    $ php autoinstall.php
    $ php autoinstall.php --username="my-custom-archive-user"
    $ php autoinstall.php [--help|-h]

Options:
    --help, -h          Show this help message
    --force, -f         Force the autoinstall, regardless of the current state of the system
    --workerurl=<value> Sets the URL of the worker (default: {$defaultworkerurl})
    --wsname=<value>    Sets a custom name for the web service (default: {$defaultwsname})
    --rolename=<value>  Sets a custom name for the web service role (default: {$defaultroleshortname})
    --username=<value>  Sets a custom username for the web service user (default: {$defaultusername})
EOT;

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

// XXX-> Begin of autoinstall routine.

// Set admin user.
$USER = get_admin();

cli_writeln("Starting automatic installation of archivingmod_quiz plugin...");
cli_separator();

[$success, $log] = autoinstall::execute(
    $options['workerurl'],
    $options['wsname'],
    $options['rolename'],
    $options['username'],
    $options['force']
);

cli_write($log . "\r\n");

if ($success) {
    cli_separator();
    cli_writeln("Automatic installation of archivingmod_quiz plugin finished successfully.");
    exit(0);
} else {
    cli_writeln("Aborted.");
    cli_separator();
    cli_writeln("FAILED: Automatic installation of archivingmod_quiz plugin failed.");
    exit(1);
}
