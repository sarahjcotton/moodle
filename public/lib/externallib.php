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
 * Support for external API
 *
 * @package    core_webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::generate_token', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_generate_token() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::generate_token', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_create_service_token() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::delete_service_descriptions', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_delete_descriptions() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::validate_format', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_validate_format() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::format_string', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_format_string() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::format_text', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_format_text() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::generate_token_for_current_user', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_generate_token_for_current_user() {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}

/**
 * @deprecated since Moodle 4.4 - MDL-76583
 */
#[\core\attribute\deprecated('\core_external\util::log_token_request', since: '4.4', mdl: 'MDL-76583', final: true)]
function external_log_token_request(): void {
    \core\deprecation::emit_deprecation(__FUNCTION__);
}
