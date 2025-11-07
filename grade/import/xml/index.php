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

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/import/xml/lib.php');
require_once($CFG->dirroot . '/grade/import/xml/grade_import_form.php');

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url(new moodle_url('/grade/import/xml/index.php', array('id'=>$id)));
$PAGE->set_pagelayout('admin');

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);
require_capability('moodle/grade:import', $context);
require_capability('gradeimport/xml:view', $context);

// print header
$strgrades = get_string('grades', 'grades');
$actionstr = get_string('pluginname', 'gradeimport_xml');

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeimport/xml:publish', $context);
}

$mform = new grade_import_form(null, array('acceptedtypes' => array('.xml')));

if ($data = $mform->get_data()) {
    if ($filepath = $mform->save_temp_file('userfile')) {
        // Create adhoc task.
        $task = \gradeimport_xml\task\import_grades::create($COURSE->id);
        $data = [
            'courseid' => $COURSE->id,
            'filepath' => $filepath,
            'importfeedback' => $data->feedback,
        ];
        $task->set_custom_data($data);
        $taskid = \core\task\manager::queue_adhoc_task($task, true);
        if ($taskid) {
            $task->set_id($taskid);
            $task->initialise_stored_progress();
        }
    } else if (empty($data->key)) {
        redirect('import.php?id='.$id.'&amp;feedback='.(int)($data->feedback).'&url='.urlencode($data->url));

    } else {
        if ($data->key == 1) {
            $data->key = create_user_key('grade/import', $USER->id, $course->id, $data->iprestriction, $data->validuntil);
        }

        print_grade_page_head($COURSE->id, 'import', 'xml',
                              get_string('importxml', 'grades'), false, false, true, 'importxml', 'gradeimport_xml');

        echo '<div class="gradeexportlink">';
        $link = $CFG->wwwroot.'/grade/import/xml/fetch.php?id='.$id.'&amp;feedback='.(int)($data->feedback).'&amp;url='.urlencode($data->url).'&amp;key='.$data->key;
        echo get_string('import', 'grades').': <a href="'.$link.'">'.$link.'</a>';
        echo '</div>';
        echo $OUTPUT->footer();
        die;
    }
}

$actionbar = new \core_grades\output\import_action_bar($context, null, 'xml');
print_grade_page_head($COURSE->id, 'import', 'xml', get_string('importxml', 'grades'),
    false, false, true, 'importxml', 'gradeimport_xml', null, $actionbar);
if ($taskid) {
    echo $OUTPUT->notification(get_string('importgradestask', 'grades'), \core\output\notification::NOTIFY_SUCCESS);
    echo $OUTPUT->continue_button(new moodle_url('/grade/report/grader/index.php', ['id' => $course->id]));
} else {
    $mform->display();
}

echo $OUTPUT->footer();
