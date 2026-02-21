<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['test']) || !isset($_POST['action'])) {
  http_response_code(400);
  exit();
}

$action = $_POST['action'];

switch ($action) {
  case 'answer':
    $question = intval($_POST['question']);
    $option = intval($_POST['option']);
    $_SESSION['test']['answers'][$question] = $option;
    break;
        
  case 'flag':
    $question = intval($_POST['question']);
    $_SESSION['test']['flagged'][$question] = !$_SESSION['test']['flagged'][$question];
    break;
        
  case 'navigate':
    $direction = intval($_POST['direction']);
    $new_index = $_SESSION['test']['current'] + $direction;
    if ($new_index >= 0 && $new_index < count($_SESSION['test']['questions'])) {
      $_SESSION['test']['current'] = $new_index;
    }
    break;
        
  case 'goTo':
    $index = intval($_POST['index']);
    if ($index >= 0 && $index < count($_SESSION['test']['questions'])) {
      $_SESSION['test']['current'] = $index;
    }
    break;
    
  case 'clear_test':
    unset($_SESSION['test']);
    break;
}

echo json_encode(['success' => true]);