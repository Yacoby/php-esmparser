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


class ESMTest extends PHPUnit_Framework_TestCase {
    protected $esm;
    protected function setUp() {
        $this->esm = new ESM();
    }
    
    public function testGetRecords() {
        $esm = $this->esm;
        $esm->addRecord(new Record('TES3'));
        $esm->addRecord(new Record('STAT'));

        $r = $esm->getRecords();
        $this->assertEquals(2, count($r));
        $this->assertFalse($r[0] == $r[1]);
    }

    public function testGetRecordCount() {
        $esm = $this->esm;
        $esm->addRecord(new Record('TES3'));
        $this->assertEquals(1, $esm->getRecordCount());

        $esm->addRecord(new Record('STAT'));
        $this->assertEquals(2, $esm->getRecordCount());
    }

    public function testGetRecordsByType() {
        $esm = $this->esm;
        $esm->addRecord(new Record('STAT'));
        $esm->addRecord(new Record('TES3'));
        $esm->addRecord(new Record('STAT'));

        $r = $esm->getRecordsByType('STAT');
        foreach ($r as $v){
            $this->assertTrue($v->getType() == 'STAT');
        }
        $this->assertEquals(2, count($r));
    }

}
?>
