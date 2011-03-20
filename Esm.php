<?php
/*
Copyright (c) 2009 Jacob Essex

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/
class ESMException extends Exception {}

/**
 * While PHP reads strings fine, floats, longs etc need to be
 * unpacked. Hence you should use the get/setType functions
 */
class SubRecord {
    private $_type, $_data;

    public function __construct($type, $data = '') {
        $this->_type = $type;
        $this->_data = $data;
    }

    public function getType() {
        return $this->_type;
    }

    /**
     * Gets the raw data held by this sub record
     *
     * @return string
     */
    public function getData() {
        return $this->_data;
    }
    /**
     * Sets the raw data
     *
     * @param string $d
     */
    public function setData($d) {
        $this->_data = $d;
    }

    /**
     * Gets a string, striping off the ending null(s).
     */
    public function getString() {
        return trim($this->_data);
    }
    /**
     * Sets a string, ensuing that the string is null terminated
     * @param string $str
     */
    public function setString($str) {
    //requires PHP version > 5.1
        $cmp = substr_compare($str, "\0", -1, 1) === 0;
        if ( !$cmp )
            $str .= "\0";
        $this->_data = $str;
    }


    public function setLong($long) {
        assert(is_numeric($long));
        $this->_data = pack('l', $long);
    }

    public function getLong() {
        $data = unpack('l', $this->_data);
        return $data[1];
    }

    public function setFloat($float) {
        assert(is_numeric($float));
        $this->_data = pack('f', $float);
    }

    public function getFloat() {
        $data = unpack('f', $this->_data);
        return $data[1];
    }

    /**
     * Uses unpack() to unpack a value from a block of data.
     *
     * @param string $format
     * @param int $from
     * @param int $length
     */
    public function unpackSingleValue($format, $from = 0, $length = 0) {
        $string = substr($this->_data, $from, $length);
        if ( $length == 0) {
            $string = substr($this->_data, $from);
        }
        $p = unpack($format,$string);
        return $p[1];
    }

    /**
     * Packs a single value into the data
     * @param string $format Same format as pack()
     * @param <type> $value The value to pack
     * @param int $from The start of the data to pack()
     * @param int $length The expected length of the data
     */
    public function packSingleValue($format, $value, $from, $length) {
        if ( is_string($value) ) {
            if ( strlen($value) > $length ) {
                throw new ESMException('Value too large');
            }
            $value = str_pad($value, $length, "\0");
            assert(strlen($value) == $length);
        }

        //ensure data is correct size
        $this->_data = str_pad($this->_data, $from, "\0");

        //split the data into two segments, before and after
        $splitData = array('','');
        $splitData[0] = substr($this->_data, 0, $from);
        if ( strlen($this->_data) > $from + $length ) {
            $splitData[1] = substr($this->_data,$from+$length);
        }

        $this->_data = $splitData[0] . pack($format, $value) . $splitData[1];
    }


}

class Record {
    private $_type;
    private $_subRecords = array();

    private $_flags = 0;
    private $_headerUnk = 0;

    public function __construct($type) {
        $this->_type = $type;
    }

    /**
     *
     * @return long
     */
    public function getFlags() {
        return $this->_flags;
    }
    /**
     *
     * @param long $f
     */
    public function setFlags($f) {
        assert(is_numeric($f));
        $this->_flags = $f;
    }

    /**
     * Don't use function. Name not stable
     */
    public function _setHeaderUnk($hdr1) {
        $this->_headerUnk = $hdr1;
    }
    /**
     * Don't use function. Name not stable
     */
    public function _getHeaderUnk() {
        return $this->_headerUnk;
    }

    public function getType() {
        return $this->_type;
    }

    /**
     * Adds a subrecord to the record
     *
     * @param SubRecord $sr
     * @return SubRecord
     */
    public function addSubRecord(SubRecord $sr) {
        $this->_subRecords[] = $sr;
        return $sr;
    }

    /**
     * Gets a sub record by type and index or returns null if it doesn't exist
     * @param string $type
     * @return SubRecord
     */
    public function getSubRecord($type, $index = 0) {
        $count = 0;
        foreach ( $this->_subRecords as $sr ) {
            if ( $sr->getType() == $type ) {
                if ( $count == $index) {
                    return $sr;
                }
                $count = $count + 1;
            }
        }
        return null;
    }
    /**
     * @return array SubRecord
     */
    public function getSubRecords() {
        return $this->_subRecords;
    }
}


class ESM {

/**
 * Holds records in an array using the type as a key
 *
 * @var array
 */
    private $_records = array();

    /**
     * Slow, only used for writing. It creates a new array of records
     *
     * @return array Record
     */
    public function getRecords() {
        $a = array();
        foreach ( $this->_records as $key => $val ) {
            $a = array_merge($a, $val);
        }
        return $a;
    }

    /**
     *
     * @return int
     */
    public function getRecordCount() {
        $count = 0;
        foreach ( $this->_records as $reca ) {
            $count += count($reca);
        }
        return $count;
    }

    /**
     * Returns all records with that type
     *
     * @param string $type
     * @return array Record
     */
    public function getRecordsByType($type) {
        assert(is_string($type) && strlen($type)==4);
        if ( array_key_exists($type, $this->_records) ) {
            return $this->_records[$type];
        }
        return array();
    }

    /**
     *
     * @param type $type
     * @param int $index
     * @return Record
     */
    public function getRecordByType($type, $index) {
        assert(is_string($type) && strlen($type)==4);
        assert(is_numeric($index));

        $r = $this->getRecordsByType($type);

        if ( count($r) <= ($index) ) {
            return null;
        }

        return $r[$index];
    }

    public function addRecord(Record $r) {
        $this->_records[$r->getType()][] = $r;
    }

}



/**
 * Class for handling loading or saving of esms
 */
class ESMSerializer {
/**
 * Loads a esm/p/s form the given location
 *
 * The types arg should contain all the records to load
 *
 * @param ESM $esm
 * @param string $fileName
 * @param array $types
 */
    public static function load(ESM $esm, $fileName, array $types) {
        if ( !in_array('TES3', $types) ) {
            $types[] = 'TES3';
        }

        self::check(
            file_exists($fileName),
            "The expected file $fileName doesn't exist"
        );
        self::check(
            is_file($fileName),
            "The expected file $fileName is not a file"
        );

        $fileSize = filesize($fileName);

        $fp = fopen($fileName, 'rb');


        $header = self::readRecord($fp, $types);
        $esm->addRecord($header);

        self::check(
            $header != null,
            'Error when reading header. Failed?'
        );
        self::check(
            $header->getType() == 'TES3',
            'Incorect file type'
        );

        //record count isn't always accurate
        while ( ftell($fp) < $fileSize ) {
            $record = self::readRecord($fp, $types);

            if ( $record != null ) {
                $esm->addRecord($record);
            }

        }

        fclose($fp);
    }

    /**
     * @return Record
     */
    private static function readRecord($fp, $types) {
        $type = fread($fp, 4);

        $data = unpack('l3', fread($fp, 12));
        $length = $data[1];

        self::check(
            is_numeric($length),
            'Offset is not a number'
        );


        if ( !in_array($type, $types) ) {
            fseek($fp, $length, SEEK_CUR);
            return null;
        }

        //read record data in a block
        $recordData = fread($fp, $length);

        //parse
        $record = new Record($type);
        $record->setFlags($data[2]);
        $record->_setHeaderUnk($data[3]);

        do {
            $recordData = self::readSubRecord($fp, $record, $recordData);
        }while ( strlen($recordData) );

        return $record;
    }

    /**
     * @return string
     */
    private static function readSubRecord($fp, Record $r, $data) {
        $type = substr($data, 0, 4);
        $length = unpack('l', substr($data, 4, 4));
        $length = $length[1];

        self::check(
            is_numeric($length),
            'Offset is not a number'
        );

        $r->addSubRecord(
            new SubRecord($type,substr($data, 8, $length))
        );
        //return the unread segment of the string
        return substr($data, 8 + $length);
    }

    public static function save(ESM $esm, $filename) {
        $header = $esm->getRecordByType('TES3',0);

        self::check(
            $header != null,
            'The header wasn\'t found (or too many headers)'
        );

        //set correct number of records

        $header->getSubRecord('HEDR',0)->packSingleValue(
            'l',
            $esm->getRecordCount(),
            256+40,
            4
        );



        $fp = fopen($filename, 'w+b');

        //write this first
        self::writeRecord($fp, $header);

        $records = $esm->getRecords();
        foreach ( $records as $record ) {
            if ( $record->getType() == 'TES3' ) //don't write header again
                continue;

            self::writeRecord($fp, $record);
        }
        fclose($fp);
    }

    private static function writeRecord($fp, Record $record) {
        fwrite($fp, $record->getType(), 4); //name

        $sizePos = ftell($fp); //pointer to start of size bytes
        $data = pack(
            'l3',
            0,//dummy size
            $record->_getHeaderUnk(),
            $record->getFlags()
        );
        fwrite($fp, $data, 12);

        $pos = ftell($fp);

        foreach ($record->getSubRecords() as $subRecord ) {
            self::writeSubRecord($fp, $subRecord);
        }
        $len = ftell($fp) - $pos;

        //rewrite length
        fseek($fp, $sizePos);
        fwrite($fp, pack('l',$len),4);

        fseek($fp,0,SEEK_END);
    }

    private static function writeSubRecord($fp, SubRecord $subRecord) {
        fwrite($fp, $subRecord->getType(), 4);

        $length = pack('l', strlen($subRecord->getData()));

        fwrite($fp, $length, 4);
        fwrite($fp, $subRecord->getData(), strlen($subRecord->getData()));
    }

    /**
     * Simplifes checking values
     *
     * @param bool $v
     * @param string $m
     */
    private static function check($v, $m) {
        if ( !$v ) {
            throw new ESMException($m);
        }
    }

}

?>
