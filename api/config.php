<?php
define('SITE_NAME', 'Venus CBT');
define('BASE_PATH', __DIR__ . '/..'); // Go up one level from api/ to root
define('PUBLIC_PATH', BASE_PATH . '/public');

// For Vercel, use /tmp for writable storage
if (isset($_SERVER['VERCEL'])) {
    define('IS_VERCEL', true);
    define('STORAGE_PATH', '/tmp/');
} else {
    define('IS_VERCEL', false);
    define('STORAGE_PATH', PUBLIC_PATH . '/storage/');
}

define('COURSES_FILE', STORAGE_PATH . 'courses.json');
define('STUDY_NOTES_FILE', STORAGE_PATH . 'study_notes.json');

// Copy storage files to /tmp on Vercel
if (IS_VERCEL) {
    $storageFiles = [
        'courses.json',
        'study_notes.json',
        'mathematics.json',
        'physics.json',
        'statistics.json',
        'computer.json',
        'literacy.json'
    ];
    
    foreach ($storageFiles as $file) {
        $source = PUBLIC_PATH . '/storage/' . $file;
        $dest = STORAGE_PATH . $file;
        if (file_exists($source) && !file_exists($dest)) {
            copy($source, $dest);
        }
    }
}

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
        'MTS101' => STORAGE_PATH . 'mathematics.json',
        'PHY101' => STORAGE_PATH . 'physics.json',
        'STA111' => STORAGE_PATH . 'statistics.json',
        'CSC101' => STORAGE_PATH . 'computer.json',
        'GNS103' => STORAGE_PATH . 'literacy.json'
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

function getStudyNotes() {
    if (!file_exists(STUDY_NOTES_FILE)) return [];
    $json = file_get_contents(STUDY_NOTES_FILE);
    return json_decode($json, true) ?: [];
}

// For Vercel, ensure proper URL generation
function base_url($path = '') {
    if (IS_VERCEL) {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . '/' . ltrim($path, '/');
    }
    return $path;
}
?>