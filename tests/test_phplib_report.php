<?php

include_once 'phplib/report.php';


class PhplibReportTests extends PHPUnit_Framework_TestCase {

    public function testrenderTopNPrettyLine() {
        $res = renderTopNPrettyLine([1,4,3,2,5,9,7,8,6]);
        $expected = '<strong>0</strong> (9 times), <strong>1</strong> (8 times), <strong>2</strong> (7 times), <strong>3</strong> (6 times)';
        $this->assertEquals($expected, $res);
    }
}
