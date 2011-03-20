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


class SubRecordTest extends PHPUnit_Framework_TestCase {
    protected $subRecord;
    protected function setUp() {
        $this->subRecord = new SubRecord('NAME');
    }

    public function testGetType() {
        $this->assertEquals('NAME', $this->subRecord->getType());
    }

    public function testString() {
    //test nulls are sorted
        $this->subRecord->setString('Morrowind');
        $this->assertEquals("Morrowind\0", $this->subRecord->getData());
        $this->assertEquals("Morrowind", $this->subRecord->getString());
    }

    public function testLong() {
        $sub = $this->subRecord;
        $sub->setLong(256);
        $this->assertEquals(256, $sub->getLong());
    }

    public function testFloat() {
        $sub = $this->subRecord;
        $sub->setFloat(25.6);
        $this->assertEquals(
            25.6,
            round($sub->getFloat(), 4)
        );
    }
    
    public function testPackValues() {
        $sub = $this->subRecord;

        //test string val longer than expected
        $sub->packSingleValue('a*', '123', 0, 6);
        $this->assertEquals("123\0\0\0", $sub->getData());

        //test pack overwriting
        $sub->packSingleValue('l', 256, 0, 4);
        $long = $sub->unpackSingleValue('l', 0);
        $this->assertEquals(256, $long);

        //double check by cutting it down and then getting long
        $sub->setData(
            substr($sub->getData(), 0,4)
        );
        $this->assertEquals(256, $sub->getLong());

    }
}
?>
