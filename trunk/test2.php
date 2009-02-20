<?php
error_reporting(E_ALL);

$start = microtime(true);

require('nde_media.php');

$database = new NDEMediaDatabase('main');
$songs = $database->records();
echo 'You have ', count($songs), ' songs.<br />';

$cascada_songs = $database->artist('Cascada')->songs();
echo 'You have ', count($cascada_songs), ' songs by Cascada.<br />';

echo '
Memory peak = ', number_format(memory_get_peak_usage() / 1024 / 1024, 2), 'MB memory<br />
Took ', number_format(microtime(true) - $start, 2), ' seconds.';
?>