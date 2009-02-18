<?php
error_reporting(E_ALL);
require('nde.php');

$database = new NDEDatabase('main');
$songs = $database->songs();

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

Memory peak = ', number_format(memory_get_peak_usage() / 1024 / 1024, 2), 'MB memory';
?>