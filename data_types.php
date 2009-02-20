<?php
/**
 * NDE (Nullsoft Database Engine) in PHP 
 * By Daniel15 - http://www.d15.biz/
 * $Id$
 */

/**
 * A record is basically some fields. As you can see here. :P
 */
class NDEFileRecord
{
	var $fields;
}

/**
 * A record, our internal representation. Nothing's here, look at
 * NDEDatabase::__construct
 */
class NDERecord
{

}

/**
 * A NDE Field
 * Format information (from Winamp SDK):
	==================================================================================================
	Offset                      Data Type      Size                  Field
	==================================================================================================
	0                           UCHAR          1                     Column ID
	1                           UCHAR          1                     Field Type
	2                           INT            4                     Size of field data
	6                           INT            4                     Next field position in table data pool
	10                          INT            4                     Previous field position in table data pool
	14                          FIELDDATA      SizeOfFieldData       Field data
	==================================================================================================
 */
class NDEField
{
	/**
	 * All the different field types
	 */
	const FIELD_UNDEFINED = 255;
	const FIELD_COLUMN = 0;
	const FIELD_INDEX = 1;
	const FIELD_REDIRECTOR = 2;
	const FIELD_STRING = 3;
	const FIELD_INTEGER = 4;
	const FIELD_BOOLEAN = 5;
	const FIELD_BINARY = 6;
	const FIELD_GUID = 7;
	const FIELD_FLOAT = 9;
	const FIELD_DATETIME = 10;
	const FIELD_LENGTH = 11;
	
	public $id;
	public $type;
	public $size;
	public $next;
	public $prev;
	public $raw;
	public $data;
	
	/**
	 * Creates a new NDEField.
	 * 
	 * When an instance of this class is created, we need to:
	 * 1. Get all the data (using the format information as shown above)
	 * 2. Set the "$data" variable based on the type of field this is.
	 */
	public function __construct($data)
	{
		// First two things are unsigned characters (UCHAR).
		$stuff = unpack('C2', substr($data, 0, 2));
		$this->id = $stuff[1];
		$this->type = $stuff[2];
		// Next three are integers.
		$stuff = unpack('i3', substr($data, 2, 12));
		$this->size = $stuff[1];
		$this->next = $stuff[2];
		$this->prev = $stuff[3];
		// And this is the rest of the data.
		$this->raw = substr($data, 14, $this->size);
		
		NDEDatabase::debug_msg('&rarr; <strong>Field:</strong> Column ID: ' . $this->id . ' . type: ' . $this->type . ', size: ' . $this->size . ', next: ' . $this->next . ', prev: ' . $this->prev . '<br />');
		
		// Actually get the data, depending on type.
		switch ($this->type)
		{
			case self::FIELD_COLUMN:
				$this->data = new NDEField_Column($this->raw);
				break;
				
			// I don't actually know what these are, so they're ignored for now.
			case self::FIELD_INDEX:
				break;
				
			case self::FIELD_STRING:
				$this->data = new NDEField_String($this->raw);
				break;
				
			case self::FIELD_INTEGER:
			case self::FIELD_LENGTH:
				$this->data = new NDEField_Integer($this->raw);
				break;
				
			case self::FIELD_DATETIME:
				$this->data = new NDEField_DateTime($this->raw);
				break;
				
			// Shouldn't really happen. Yes, I know I haven't implemented all
			// the different types, but the above ones are the only ones that
			// seem to be used in the media library.
			default:
				echo '<strong style="color: red">Unknown field type: ', $this->type, '</strong>';
				break;
		}
	}
	
	/**
	 * When we convert this to a string, we use the inner type.
	 * 
	 * Hopefully that's a good assumption.
	 */
	function __toString()
	{
		return $this->data->__toString();
	}
}

/**
 * All data types inherit from this class
 */
abstract class NDEField_Data
{

}

/**
 * NDE "Column" type
 * Format information:
	==================================================================================================
	Offset                      Data Type      Size                  Field
	==================================================================================================
	0                           UCHAR          1                     Column Field Type (ie, FIELD_INTEGER)
	1                           UCHAR          1                     Index unique values (0/1)
	2                           UCHAR          1                     Size of column name string
	3                           STRING         SizeOfColumnName      Public name of the column
	==================================================================================================
*/
class NDEField_Column extends NDEField_Data
{
	public $type;
	public $unique;
	public $size;
	public $name;
	
	public function __construct($data)
	{
		// Characters (UCHARs)
		$stuff = unpack('C3', $data);
		$this->type = $stuff[1];
		$this->unique = $stuff[2];
		$this->size = $stuff[3];
		// Name = rest of the data
		$this->name = substr($data, 3, $this->size);
		
		NDEDatabase::debug_msg('&mdash;&rarr; <strong>Column:</strong> Type: ' . $this->type . ', size: ' . $this->size . ', name: ' . $this->name . '<br />');
	}
	
	function __toString()
	{
		return $this->name;
	}
}

/**
 * NDE "String" type
 * Format information:
	==================================================================================================
	Offset                      Data Type      Size                  Field
	==================================================================================================
	0                           USHORT         2                     Size of string
	2                           STRING         SizeOfString          String
	==================================================================================================
*/
class NDEField_String extends NDEField_Data
{
	public $size;
	public $data;
	
	public function __construct($data)
	{
		// Unsigned short
		$this->size = array_pop(unpack('S', $data));
		// Convert from UTF-16 (what Winamp uses) to UTF-8 (what PHP uses)
		$this->data = iconv('UTF-16', 'UTF-8', substr($data, 2, $this->size));
		
		NDEDatabase::debug_msg('&mdash;&rarr; <strong>String:</strong> Size: ' . $this->size . ', data: ' . /*print_binary*/($this->data) . '<br />');
	}
	
	function __toString()
	{
		return $this->data;
	}
}

/**
 * NDE "Integer" type. I think this is the simplest one :)
 * Format information:
	==================================================================================================
	Offset                      Data Type      Size                  Field
	==================================================================================================
	0                           INT            4                     Integer value
	==================================================================================================
*/
class NDEField_Integer extends NDEField_Data
{
	public $data;
	
	public function __construct($data)
	{
		$this->data = array_pop(unpack('i', $data));
		
		NDEDatabase::debug_msg('&mdash;&rarr; <strong>Integer:</strong> data: ' . $this->data . '<br />');
	}
	
	public function __toString()
	{
		return $this->data;
	}
}

/**
 * NDE "DateTime" type. This is exact same as an integer, except it's treated
 * as a date/time format. The number is a UNIX timestamp.
 *
 * TODO: Add a field for formatted date?
*/
class NDEField_DateTime extends NDEField_Data
{
	public $data;
	
	public function __construct($data)
	{
		$this->data = array_pop(unpack('i', $data));
		
		NDEDatabase::debug_msg('&mdash;&rarr; <strong>DateTime:</strong> data: ' . $this->data . '<br />');
	}
	
	public function __toString()
	{
		return $this->data;
	}
}

/**
 * Print binary data as hex (eg. 0xDE 0xAD 0xFF 0x01)
 */
function print_binary($stuff)
{
	for ($i = 0; $i < strlen($stuff); $i++)
		echo '0x' . sprintf('%02X', ord($stuff[$i])) . ' ';
}
?>