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
 * Show course's file on the current course page
 * 
 * @package     block
 * @subpackage  course_files
 * @author      Shuai Zhang
 * @copyright   2013 Shuai Zhang <shuaizhang@lts.ie>
 */


//  Display the report page
require_once('../../config.php');

global $DB, $OUTPUT, $PAGE;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$courseid));
$coursecontext = context_course::instance($courseid);

if (!$course){
    print_error('invalidcourse', 'block_course_files', $courseid);
}
require_login($course);
if (!has_capability('moodle/course:manage', $coursecontext)){
    echo 'Only teacher can access this page.';
    close();
}

$url = new moodle_url('/blocks/course_files/view_report.php',array('id'=>$courseid));

//Ensure only teacher can use this block
if(has_capability('moodle/course:manage', $coursecontext)){
    $table = new html_table();
    $table->head = array('Filename','Filesize','Mimetype','Author','License','Time Created');
    $tabledata = array();
    $result = $DB->get_records_sql(
       "SELECT filename, filesize, mimetype ,author, license, timecreated
          FROM {files}
         WHERE filesize >0
           AND {files}.contextid
            IN (   SELECT id
                     FROM {context}
                    WHERE path 
                     LIKE (   SELECT CONCAT('%/',id,'/%')
                                  AS contextquery
                                FROM {context}
                               WHERE instanceid = $courseid
                                 AND contextlevel = 50
                           )
                )",array());

    // Fetch the data and put them into table
    if($result){
        foreach ($result as $file) {
        	  $file->filesize = improve_filesize($file->filesize);
            $file->author = improve_author($file->author);
            $file->timecreated = improve_timecreated($file->timecreated);
            $file->license = improve_license($file->license);
            array_push($tabledata,$file);
        }
    } else {
        $tabledata = array(array('none','none','none','none','none','none'));
    }
    $table->data = $tabledata;
}



//$PAGE->set_pagelayout('course');
$PAGE->set_pagelayout('report');
$PAGE->set_url($url,array('id' => $courseid));
$PAGE->set_title($course->fullname);
$PAGE->set_heading('Files on '.$course->fullname);



echo $OUTPUT->header();
echo $OUTPUT->heading('Files in Course: '.$course->fullname, 1);
echo html_writer::table($table); 

echo $OUTPUT->footer();




// Make file size more readable
function improve_filesize($filesize) {
    $i = 0;
    while($filesize > 1024){
        $filesize = round(($filesize / 1024),1);
        $i += 1;
    }
    if ($i = 0){
        $filesize .= ' bytes';
    } else if($i = 1) {
        $filesize .= ' KB';
    } else if($i = 2) {
        $filesize .= ' MB';
    }
return $filesize;
}

// Make author more readable
function improve_author($author) {
    if ($author != null){
        return $author;
    } else {
        $author = 'None';
        return $author;
    }
}

// Make timecreated more readable
function improve_timecreated($timecreated) {
    return date('Y-m-d H:i:s',$timecreated); 
}

// Make license more readable
function improve_license($license) {
    if($license = 'allrightsreserved')
        $license = 'All rights reserved';
    return $license;
}