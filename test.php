<?php
error_reporting(E_ALL);

$start = microtime(true);

require('nde.php');

$database = new NDEDatabase('main');
$songs = $database->records();

echo count($songs) . ' songs in database<br />
<ul>';
foreach ($songs as $song)
{
	echo '
	<li>', $song->artist, ' - ', $song->title, '</li>';
}
echo '
</ul>
<br />

Memory peak = ', number_format(memory_get_peak_usage() / 1024 / 1024, 2), 'MB memory<br />
Took ', number_format(microtime(true) - $start, 2), ' seconds.';

/*
Memory peak = 13.37MB memory
Took 9.02 seconds.
*/
?>