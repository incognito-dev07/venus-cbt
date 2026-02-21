<?php
define('SITE_NAME', 'Venus CBT');
define('COURSES_FILE', 'storage/courses.json');
define('STUDY_NOTES_FILE', 'storage/study_notes.json');

function getCourses() {
    if (!file_exists(COURSES_FILE)) return [];
    $json = file_get_contents(COURSES_FILE);
    return json_decode($json, true) ?: [];
}

function getCourse($course_id) {
    if (empty($course_id)) return null;
    $courses = getCourses();
    foreach ($courses as $course) {
        if ($course['id'] === $course_id) {
            return $course;
        }
    }
    return null;
}

function getQuestionFile($course_id) {
    $files = [
        'MTS101' => 'storage/mathematics.json',
        'PHY101' => 'storage/physics.json',
        'STA111' => 'storage/statistics.json',
        'CSC101' => 'storage/computer.json',
        'GNS103' => 'storage/literacy.json'
    ];
    return isset($files[$course_id]) ? $files[$course_id] : null;
}

function getQuestions($course_id) {
    $file = getQuestionFile($course_id);
    if (!$file || !file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return isset($data[$course_id]) ? $data[$course_id] : [];
}

// New function for study notes
function getStudyNotes() {
    if (!file_exists(STUDY_NOTES_FILE)) return [];
    $json = file_get_contents(STUDY_NOTES_FILE);
    return json_decode($json, true) ?: [];
}

function getStudyTopic($subject_id, $topic_id, $subtopic_id = null) {
    $notes = getStudyNotes();
    if (!isset($notes[$subject_id])) return null;
    if (!isset($notes[$subject_id]['topics'][$topic_id])) return null;
    
    if ($subtopic_id) {
        return $notes[$subject_id]['topics'][$topic_id]['subtopics'][$subtopic_id] ?? null;
    }
    
    return $notes[$subject_id]['topics'][$topic_id];
}
?>