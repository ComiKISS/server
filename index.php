<?php
function comikiss_output($response, $error = 0) {
	header('Content-Type: application/json');
	echo json_encode(['response' => $response, 'error' => $error]);
	exit;
}

if(empty($_POST)) {
	comikiss_output([], 'Method must be POST.');
}

if(!isset($_POST['action'])) {
	comikiss_output([], 'Missing action parameter.');
}

if(!in_array($_POST['action'], ['list', 'comic', 'volume', 'chapter', 'people', 'source', 'backup', 'restore'], true)) {
	comikiss_output([], 'Invalid action parameter.');
}

$action = $_POST['action'];

include 'database.php';

if(isset($_POST['pass']) && $_POST['pass'] !== $database['password']) {
	comikiss_output([], 'Incorrect password.');
}

if(!isset($_POST['pass']) && ($_POST['action'] == 'backup' || $_POST['action'] == 'restore')) {
	comikiss_output([], 'You\'re not allowed to do that.');
}

if($action == 'restore' && !isset($_POST['json'])) {
	comikiss_output([], 'Missing json parameter.');
}

if($action == 'restore') {
	$json = json_decode($_POST['json'], true);
    if(json_last_error() != JSON_ERROR_NONE || !isset($json['password']) || !is_string($json['password'])) {
    	comikiss_output([], 'Invalid JSON.');
    }
	file_put_contents('database.php', '<?php
$database = ' . var_export($json, true) . ';');
	comikiss_output([]);
}

if($action == 'backup') {
	comikiss_output($database);
}

if($action == 'list') {
	$list = [];
	foreach($database['comic'] as $comic_id => $comic) {
		if(!$comic['private'] || isset($_POST['pass'])) {
			$list[] = [
				'id' => $comic_id,
				'titles' => $comic['titles'],
				'private' => $comic['private']
			];
		}
	}
	comikiss_output($list);
}

if(!isset($_POST['id'])) {
	comikiss_output([], 'Missing id parameter.');
}

if(!ctype_digit($_POST['id'])) {
	comikiss_output([], 'id parameter must be numeric.');
}

$id = (int)$_POST['id'];

if(!isset($database[$action][$id])) {
	comikiss_output([], 'Invalid id parameter.');
}

if($database[$action][$id]['private'] === true && !isset($_POST['pass'])) {
	comikiss_output([], 'This entry is private.');
}

comikiss_output($database[$action][$id]);
