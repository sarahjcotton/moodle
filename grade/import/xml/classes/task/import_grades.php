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
 * Adhoc task to upload grades via XML.
 *
 * @package     gradeimport_xml
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-eu.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeimport_xml\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->dirroot . '/grade/import/lib.php');
require_once($CFG->dirroot . '/grade/import/xml/lib.php');

/**
 * Adhoc task class.
 */
class import_grades extends \core\task\adhoc_task {
    use \core\task\logging_trait;
    use \core\task\stored_progress_task_trait;

    /**
     * Create a new instance of the task.
     *
     * @param int $courseid
     * @return import_grades
     */
    public static function create(int $courseid): self {
        $task = new self();
        $task->set_component('gradeimport_xml');
        $task->set_custom_data((object)[
            'courseid' => $courseid,
        ]);
        return $task;
    }

    /**
     * Run the adhoc task and perform the import.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $this->start_stored_progress();

        $this->log_start("Importing grades for course ID {$data->courseid}");
        $this->progress->update(1, 3, "Importing grades for course ID {$data->courseid}");
        $course = get_course($data->courseid);
        $text = file_get_contents($data->filepath);

        $this->progress->update(2, 3, "Preparing grade data for import");
        $error = '';
        $importcode = import_xml_grades($text, $course, $error);
        if ($importcode) {
            $this->progress->update(3, 3, "Committing data to the gradebook");
            grade_import_commit($course->id, $importcode, $data->importfeedback, false);
            mtrace('Import complete');
        } else {
            mtrace('Error ' . $error);
        }
        $this->log_start("Import complete");
    }
}
