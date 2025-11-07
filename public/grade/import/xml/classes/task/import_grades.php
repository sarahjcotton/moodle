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

use core\task\manager;

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
     * @param int $userid
     * @return import_grades
     */
    public static function create(int $courseid, int $userid): self {
        $task = new self();
        $task->set_component('gradeimport_xml');
        $task->set_userid($userid);
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
            $this->log_start("Import complete");
        } else {
            $this->log_start($error);
            // Send a notification to the user.
            self::send_notification($this->get_userid(), $data->courseid, $error);
            $this->progress->error(get_string('importtaskfailed', 'grades'));
            $this->progress->update(3, 3, get_string('importtaskfailed', 'grades'));
        }
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
        $task = new \gradeimport_xml\task\import_grades();
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

    /**
     * Send error notification for sync job failures.
     *
     * @param int $userid User to send the notification to.
     * @param int $courseid Course the notification is associated with.
     * @param string $messagebody The message.
     */
    public static function send_notification(int $userid, int $courseid, string $messagebody): void {
        $course = get_course($courseid);
        $message = new \core\message\message();
        $message->component = 'gradeimport_xml';
        $message->name = 'gradeimportxml';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $userid;
        $message->subject = get_string('importtaskfailed:notificationheading', 'grades', $course->fullname);
        $message->fullmessage = $messagebody;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $messagebody;
        $message->smallmessage = $messagebody;
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        $message->contexturlname = 'Course ' . $course->fullname;

        // Extra content for specific processor.
        $content = [
            '*' => [
                'footer' => '<p>Link to course: ' . $message->contexturl . '</p>',
            ],
        ];
        $message->set_additional_content('email', $content);
        message_send($message);
    }
}
