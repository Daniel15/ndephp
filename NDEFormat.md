
# Introduction #

This page describes the **Nullsoft Database Engine** format.

Tables are saved using two different files. _filename.dat_ contains the actual data, and _filename.idx_ contains an index used to access this data.

# Index format #

The index file has a header as follows:

| Offset | Data Type | Size | Field |
|--------|-----------|------|-------|
| 0 | String | 8 | "NDEINDEX" |
| 8 | Integer | 4 | Number of records |
| 12 | Integer | 4 | Unknown (always seems to be 0xFF 0x00 0x00 0x00) |

The rest of the file is the index itself. Each record consists of two integers:

| Offset | Data Type | Size | Field |
|-----------|--------------|---------|----------|
| 0 | Integer | 4 | Offset of this record in the data file |
| 4 | Integer | 4 | Index of this record |

# Data format #
The data file simply begins with "NDETABLE", and the rest of the file is data. Before we continue, we need to know some terminology. One **data file** has many **records** (a record is much like a row in a database). A **record** has many **fields** (a field is much like a column in a database).

## Records ##
A record basically consists of a **linked list** of fields. This means that a record starts with one field, which contains the offset to the next field. To read the whole record, you keep following these offsets, until you get one that is equal to 0 (once you reach this, you've read the whole record).

There are a few "special" types of records in the index file:
  * The first record is a list of the columns that make up each record
  * The second record is something having to do with indices (I don't know what this actually does)

## Fields ##
Fields have a header like the following:

| Offset | Data Type | Size | Field |
|--------|-----------|------|-------|
| 0 | Unsigned char | 1 | Column ID |
| 1 | Unsigned char | 1 | Field type |
| 2 | Integer | 4 | Size of field data |
| 6 | Integer | 4 | Offset to next field in this record |
| 10 | Integer | 4 | Offset to previous field ?? (I don't use this) |

Most of these fields should be self-explanatory. The next "size" bytes after this header is the actual field data (eg. if "size" is 20, then the next 20 bytes is the actual data). If the field is a "column" type, the Column ID tells you what ID this column is (first column is 1, second column is 2, etc). Otherwise, the Column ID tells you what column this data is in.

### Field types ###
Fields may be any of the following types (types marked with a `*` are not implemented in NDEPHP yet):

| ID | Type name |
|:-------|:--------------|
| 0 | [Column](#Column) |
| 1`*` | Index |
| 2`*` | Redirector |
| 3 | [String](#String) |
| 4 | [Integer](#Integer) |
| 5`*` | Boolean |
| 6`*` | Binary |
| 7`*` | GUID |
| 8`*` | Unknown |
| 9`*` | Float |
| 10 | [Date and time](#Integer) |
| 11 | [Length](#Integer) |

The field type are explained in more detail below. Offsets are relative to the **start** of the field.
<a id="Column" />
#### Column ####

| Offset | Data Type | Size | Field |
|--------|-----------|------|-------|
| 0 | Unsigned char | 1 | Column field type (see list above) |
| 1 | Unsigned char | 1 | Index unique values ?? (unknown what this does) |
| 2 | Unsigned char | 1 | Size of column name |
| 3 | String | Size (offset 2, above) | Name of the column |

<a id="String" />
#### String ####
| Offset | Data Type | Size | Field |
|--------|-----------|------|-------|
| 0 | Unsigned short | 2 | Size of the string |
| 2 | String | Size | String value |

<a id="Integer" />
#### Integer ####
The integer format is also used for the  Date and time, and Length types. A length is simply an integer that represents the length of something, and a Date and time is simply a UNIX timestamp.

| Offset | Data Type | Size | Field |
|--------|-----------|------|-------|
| 0 | Integer | 4 | Value |


# How to read the files #
Below is some psuedocode showing how to read these files:
```
Open index file
Open data file
Read header of index file
Read header of data file
For each record in the index file
  Start with a blank record
  Set offset variable to offset specified in index file
  Loop
    Get the data from the data file at the specified offset
    Add this data to the record
    Set offset variable to "next field" in the field data
  While offset is not 0
  // "Record" now contains the record you just read in.

  If record is a "column" type
    Store the column names in a columns variable
  Else
    Store the data based on column names in columns variable
  End if
End For each

// You've now read the whole file and have all the data
```

# References #
The following references were used for obtaining this data:
  * [Nullsoft Database Engine Format Specifications v1.0](http://gutenberg.free.fr/fichiers/SDK%20Winamp/nde_specs_v1.txt) (some things have changed slightly since this was published)
  * [mlRemote or: The Winamp Media Library](http://translate.google.com/translate?hl=en&sl=de&u=http://itavenue.de/java/mlremote-oder-die-winamp-media-library) (converted from German)
