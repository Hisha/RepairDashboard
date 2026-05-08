<?php
require_once __DIR__ . '/bootstrap.php';

$allowed = ['all', 'north', 'south'];
$value = $_POST['north_south_filter'] ?? 'all';

$_SESSION['north_south_filter'] = in_array($value, $allowed, true)
? $value
: 'all';

$redirect = $_POST['redirect'] ?? 'index.php';

header('Location: ' . $redirect);
exit;