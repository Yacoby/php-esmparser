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

class ESMSerializerTest extends PHPUnit_Framework_TestCase {

    private static function cmp($f1, $f2) {
        $retVal = -2;
        system("cmp $f1 $f2 -s", $retVal);
        return $retVal;
    }

    public function testMWEDIT_Blank() {
        $esm = new ESM();

        $record = new Record('TES3');

        $sub = new SubRecord('HEDR');
        $sub->packSingleValue('f', 1.0, 0, 4); //ver. MWEDIT uses 1.0
        $sub->packSingleValue('f', 0, 4, 4);
        $sub->packSingleValue('a', '', 8, 32);
        $sub->packSingleValue('a', '', 40, 256);
        $sub->packSingleValue('l', 0, 256+40, 4);

        $record->addSubRecord($sub);
        $esm->addRecord($record);

        ESMSerializer::save($esm, 'tmp/MWEdit_Blank_Test.esp');

        $cmp = self::cmp('tmp/MWEdit_Blank_Test.esp', 'files/MWEdit_Blank.esp');
        $this->assertEquals(0,$cmp);
    }

    public function testBasicWrite() {
        $esm = new ESM();
        ESMSerializer::load($esm, 'files/Better Bodies.esp', array());
        $header = $esm->getRecordByType('TES3', 0);

        //create new
        $esm = new ESM();
        $esm->addRecord($header);
        ESMSerializer::save($esm, 'tmp/Test.esp');

        //check can read without exception
        $esm = new ESM();
        ESMSerializer::load($esm,  'tmp/Test.esp', array());
    }

    public function testReadBB() {
        $esm = new ESM();
        ESMSerializer::load($esm, 'files/Better Bodies.esp', array('BODY'));

        $records = $esm->getRecordsByType('BODY');

        $parts = array();
        foreach ( $records as $rec ) {
            $name = $rec->getSubRecord('NAME');
            $parts[] = $name->getData();
        }

        $this->assertTrue(in_array("B_N_Nord_F_Groin\0", $parts));
    }

    public function testWrite1() {
        $esm = new ESM();

        $record = new Record('TES3');

        $sub = new SubRecord('HEDR', '');
        $sub->packSingleValue('f', 1.3, 0, 4); //ver
        $sub->packSingleValue('f', 0, 4, 4); //unknown. ESM or ESP flag?
        $sub->packSingleValue('a', '', 8, 32); //author
        $sub->packSingleValue('a', '', 40, 256); //description
        $sub->packSingleValue('l', 1, 256+40, 4); //number of records (doesn;t need to be right)

        $record->addSubRecord($sub);

        $this->assertEquals(300, strlen($sub->getData()) );

        $esm->addRecord($record);


        $record = new Record('STAT');
        $record->addSubRecord(
            new SubRecord('NAME', "MyNewStatic\0")
        );
        $record->addSubRecord(
            new SubRecord('MODL', "AnInvalidPath\0")
        );
        $esm->addRecord($record);


        ESMSerializer::save($esm, 'tmp/Testx.esp');
    }

    public function testHeaderOnly() {
        $esm = new ESM();
        ESMSerializer::load($esm, 'files/Better Bodies.esp', array());

        $header = $esm->getRecordByType('TES3', 0);

        $this->assertTrue($header != null);
        $this->assertEquals($header->getType(), 'TES3');

        $hdr = $header->getSubRecord('HEDR');
        $this->assertTrue($hdr != null);

        $floatVer = $hdr->unpackSingleValue('f', 0, 4);
        $this->assertEquals(1.3, round($floatVer,1) );
    }

    public function testError1() {
        $this->setExpectedException('ESMException');
        $esm = new ESM();
        ESMSerializer::load($esm, 'files/Doesnt_Exist.esp', array());
    }

    public function testError2() {
        $this->setExpectedException('ESMException');
        $esm = new ESM();
        ESMSerializer::load($esm, 'files/', array());
    }

}
?>
