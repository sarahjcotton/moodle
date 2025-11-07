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
 * Adhoc task to upload grades via CSV.
 *
 * @package     gradeimport_csv
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-eu.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeimport_csv\task;

defined('MOODLE_INTERNAL') || die();

use csv_import_reader;
use gradeimport_csv_load_data;
use core\task\manager;

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->dirroot . '/grade/import/lib.php');

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
        $task->set_component('gradeimport_csv');
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
        // Data has already been submitted so we can use the $iid to retrieve it.
        $csvimport = new csv_import_reader($data->iid, 'grade');
        $header = $csvimport->get_columns();

        $this->progress->update(2, 3, "Preparing grade data for import");
        $gradeimport = new gradeimport_csv_load_data();
        $status = $gradeimport->prepare_import_grade_data(
            $header,
            $data->formdata,
            $csvimport,
            $data->courseid,
            $data->separatemode,
            $data->currentgroup,
            $data->verbosescales,
        );

        // At this stage if things are all ok, we commit the changes from temp table.
        if ($status) {
            $this->progress->update(3, 3, "Committing data to the gradebook");
            grade_import_commit($data->courseid, $data->importcode, true, false);
        } else {
            $errors = $gradeimport->get_gradebookerrors();
            $errors[] = get_string('importfailed', 'grades');
            mtrace(var_export($errors, true));
        }
        $this->log_start("Import complete");
    }

    /**
     * Load an existing task from the database.
     *
     * @param int $id The task ID.
     * @return self
     */
    public static function load(int $id): self {
        global $DB;
        $customdata = $DB->get_field('task_adhoc', 'customdata', ['id' => $id], strictness: MUST_EXIST);
        $task = new import_grades();
        $task->set_id($id);
        $task->set_custom_data(json_decode($customdata));
        $task->set_component('core_course');
        return $task;
    }

    /**
     * Get the cache-accelerated task ID for the given course.
     *
     * @param int $courseid
     * @return ?int The task ID if a reset is pending, or null if no reset is pending.
     */
    public static function get_taskid_for_course(int $courseid): ?int {
        $tasks = manager::get_adhoc_tasks('\\' . self::class);
        $taskid = null;
        foreach ($tasks as $task) {
            if ($task->get_custom_data()->courseid == $courseid) {
                $taskid = $task->get_id();
                break;
            }
        }
        return $taskid;
    }
}
