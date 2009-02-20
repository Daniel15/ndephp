<?php
/**
 * NDE (Nullsoft Database Engine) in PHP 
 * By Daniel15 - http://www.d15.biz/
 * $Id$
 *
 * References:
 * - http://gutenberg.free.fr/fichiers/SDK%20Winamp/nde_specs_v1.txt
 * - http://translate.google.com/translate?hl=en&sl=de&u=http://itavenue.de/java/mlremote-oder-die-winamp-media-library
 */
 
// All the data type stuff is in a separate file
require('data_types.php');

/**
 * NDE Index file class
 */
class NDEIndex
{
	// This is the very first thing in the file. It's used to verify that the
	// file actually is a NDE Index.
	const SIGNATURE = 'NDEINDEX';
	private $record_count;
	private $file;
	
	/**
	 * When we create this class, we'd better load the file, and check how long
	 * it is.
	 */
	public function __construct($file)
	{
		$this->file = fopen($file, 'rb');
		// TODO: Check signature is actually valid
		$temp = fread($this->file, strlen(self::SIGNATURE));
		NDEDatabase::debug_msg('Index signature: ' . $temp . '<br />');
		// Number of records in the file
		$this->record_count = array_pop(unpack('I', fread($this->file, 4)));
		NDEDatabase::debug_msg('Number of records: ' . $this->record_count . '<br />');
		// Bytes that don't seem to do anything
		$temp = fread($this->file, 4);	
	}
	
	public function record_count()
	{
		return $this->record_count;
	}
	
	/**
	 * Get the next index from the index file.
	 */
	public function get()
	{
		$data = unpack('I2', fread($this->file, 8));
		return array(
			'offset' => $data[1],
			'index' => $data[2]
		);
	}
}

/**
 * NDE Data file class
 */
class NDEData
{
	// This is the very first thing in the file. It's used to verify that the
	// file actually is a NDE Table.
	const SIGNATURE = 'NDETABLE';
	private $file;
	// The order the columns are in. This is defined by the "Column" field,
	// which is the first one in the file.
	private $columns;
	
	/**
	 * Just load the file and check the signature
	 */
	public function __construct($file)
	{
		$this->file = fopen($file, 'rb');
		// TODO: Actually check the signature.
		$temp = fread($this->file, strlen(self::SIGNATURE));
		NDEDatabase::debug_msg('Data signature: ' . $temp . '<br />');
	}
	
	/**
	 * Get a record from the file. One record consists of many fields, in a
	 * linked list. Firstly, this gets the first field (identified by the offset
	 * passed to this function) and reads that. Then, it checks if it has a next
	 * field to go to (the field will contain this data). If so, it goes to that
	 * field, and reads it. This continues until we have no more fields in the
	 * record. After that, we check what type of field it is.
	 * 
	 * The two "other" types are "column" and "index". I don't know what the 
	 * index type actually does, but the "column" type tells us all the
	 * information stored about songs. The very first record in the file is a 
	 * "column" record, and the second record is a "index" record. The rest of
	 * the file is all information about songs.
	 */ 
	function get_record($offset, $index)
	{
		$record = new NDEFileRecord();
		
		NDEDatabase::debug_msg('<strong>Record:</strong><br />');

		// While we have fields to get
		do
		{
			// Go to this offset
			fseek($this->file, $offset);
			// Read some stuff
			$data = fread($this->file, 14);
			// Find out the length we need to read from the file
			$size = array_pop(unpack('i', substr($data, 2, 4)));
			// Add this data
			$data .= fread($this->file, $size);
			// The actual field itself
			$field = new NDEField($data);
			$record->fields[] = $field;
		
			// Do we have another one in this series? Better grab the offset
			$offset = $field->next;
		}
		while ($offset != 0);
		
		// Is this the "column" field?
		if ($record->fields[0]->type == NDEField::FIELD_COLUMN)
		{
			// We need to fill our $columns variable!
			foreach ($record->fields as $field)
				$this->columns[$field->id] = $field->data->name;
				
			return false;
		}
		// otherwise, it could be that weird index one.
		elseif ($record->fields[0]->type == NDEField::FIELD_INDEX)
		{
			// TODO: Find out what this field actually is.
			return false;
		}
		
		// Otherwise, it's a song!
		// We need to store all the data stuffs
		$song = new NDERecord();
		
		foreach ($record->fields as $field)
		{
			$variable = $this->columns[$field->id];
			$song->$variable = $field->data->data;
		}
		
		return $song;
	}
}

/**
 * NDE Database
 * 
 * A database is basically an index file, and a data file. The index file tells 
 * us where to go to get data, and the data file tells us where the data 
 * actually is. This class coordinates the two files.
 */
class NDEDatabase
{
	// Files
	private $index;
	private $data;
	
	// Set this to true to get too much debugging info (go on, try it). :P
	const debug = false;

	protected $records;
	
	/**
	 * Creates a new instance of the NDEDatabase class.
	 *
	 * When an instance of this class is created, we need to:
	 * 1. Load the index file (basefile + '.idx').
	 * 2. Load the data file (basefile + '.dat').
	 * 3. Loop through all the indices, and load the corresponding data.
	 */
	public function __construct($basefile)
	{
		$this->index = new NDEIndex($basefile . '.idx');
		$this->data = new NDEData($basefile . '.dat');
		
		// Need to read in all the records
		for ($i = 0; $i < $this->index->record_count(); $i++)
		{
			// Get the next index from the index file
			$index_data = $this->index->get();
			NDEDatabase::debug_msg('<strong>Read ' . $i . ':</strong> offset = ' . $index_data['offset'] . ', index = ' . $index_data['index'] . '... <br />');
			// Now, get the data associated with this index.
			$data = $this->data->get_record($index_data['offset'], $index_data['index']);
			// Was it a record? If so, process it.
			if ($data instanceOf NDERecord)
				$this->_process_record($data);
		}
	}
	
	/**
	 * Add a record to our array. Defined as a function so that other classes
	 * can extend it.
	 */
	protected function _process_record($record)
	{
		$this->records[] = $record;
		// Return the index of the new record.
		return count($this->records) - 1;
	}
	
	/**
	 * Getter method for $songs. Returns the songs in the database.
	 */
	public function records()
	{
		return $this->records;
	}
	
	/**
	 * Debugging function, used to show debug messages only if we're in debug
	 * mode.
	 */
	static function debug_msg($string)
	{
		if (self::debug)
			echo $string;
	}
}
?>