<?php
/**
 * NDE (Nullsoft Database Engine) in PHP 
 * By Daniel15 - http://www.d15.biz/
 * $Id$
 */

require('nde.php');
 
/**
 * A media database is a special kind of database. Well, not really, but we 
 * treat it specially.
 */
class NDEMediaDatabase extends NDEDatabase
{
	protected $artists;
	
	protected function _process_record($record)
	{
		// For a song, we gotta have artist and title as a minimum.
		if (!isset($record->artist))
			$record->artist = null;
		if (!isset($record->title))
			$record->title = null;
			
		// Do the normal processing.
		$index = parent::_process_record($record);
		// Do we not have this artist already?
		if (!isset($this->artists[$record->artist]))
			$this->artists[$record->artist] = new NDEMediaArtist($record->artist);

		// Also add it to our artists array
		$this->artists[$record->artist]->songs[] = &$this->records[$index];
	}
	
	/**
	 * Get all the artists
	 */
	public function artists()
	{
		return $this->artists;
	}
	
	/**
	 * Get a single artist
	 */
	public function artist($artist)
	{
		return isset($this->artists[$artist]) ? $this->artists[$artist] : false;
	}
}


class NDEMediaArtist
{
	public $songs;
	
	function songs()
	{
		return $this->songs;
	}
}
?>