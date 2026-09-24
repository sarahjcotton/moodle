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

namespace core_courseformat\local;

/**
 * Class course linear navigation settings.
 *
 * @package    core_courseformat
 * @copyright  2025 Laurent David <laurent.david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linearnavigationsettings {
    /** @var string Setting name for enabling linear navigation */
    public const SETTING_ENABLE_LINEAR_NAV = 'enablelinearnav';

    /** @var int Default value for enabling linear navigation */
    private const SETTING_ENABLE_LINEAR_NAV_DEFAULT = 1;

    /**
     * Get the linear navigation value configured site-wide for a format.
     *
     * Formats that do not define the setting fall back to enabled.
     *
     * @param string $formatname The course format name
     * @return int
     */
    private static function get_default_linear_navigation_value(string $formatname): int {
        $formatconfig = get_config('format_' . $formatname);
        if (is_object($formatconfig) && property_exists($formatconfig, self::SETTING_ENABLE_LINEAR_NAV)) {
            return (int) $formatconfig->{self::SETTING_ENABLE_LINEAR_NAV};
        }

        return self::SETTING_ENABLE_LINEAR_NAV_DEFAULT;
    }

    /**
     * Check if the navigation footer should be shown on the page.
     *
     * @param \moodle_page $page
     * @return bool if the navigation footer should be shown
     */
    public static function show_navigation_footer(\moodle_page $page): bool {
        if ($page->cm === null) {
            // Not on an activity page, do not add the sticky footer.
            return false;
        }
        if ($page->has_sticky_footer()) {
            // If there is already a sticky footer, do not add another one.
            return false;
        }
        if (!$page->should_show_navigation_footer()) {
            // If the page should not show the navigation footer, do not add the sticky footer.
            return false;
        }

        if (!self::is_linear_navigation_enabled($page->course)) {
            // Only add the navigation footer when linear navigation is enabled.
            return false;
        }

        if ($page->cm->is_stealth() && !has_capability('moodle/course:viewhiddenactivities', $page->cm->context)) {
            // Stealth activities should not show the navigation footer if the user lacks hidden activity permissions.
            return false;
        }

        return true;
    }

    /**
     * Check if linear navigation is enabled for the course.
     *
     * This only checks the course format and the site-level setting for that format, regardless of any
     * page-level state. It is useful for activities that need to adapt their output (for example,
     * hiding navigation controls of their own) when linear navigation is enabled.
     *
     * @param int|\stdClass $course The course record or course ID.
     * @return bool true if linear navigation is enabled for the course.
     */
    public static function is_linear_navigation_enabled(int|\stdClass $course): bool {
        $format = \course_get_format($course);
        if (!$format->uses_linear_navigation()) {
            // The course format does not support linear navigation.
            return false;
        }
        return (bool) self::get_default_linear_navigation_value($format->get_format());
    }
}
