<?php

require_once "common.php";

class TestMlg extends PHPUnit\Framework\TestCase
{
    public function testMlgParser() {
		$data = file_get_contents("testcases/2020-06-30_19.21.53_mck1117_large.mlg");
		$mlgParser = new MlgParser();
		$mlgParser->initStats();
		// parse
		$ret = $mlgParser->parseBinary($data, true);
        $this->assertEquals("warn", $ret["status"]);

		$stats = $mlgParser->getStats(strlen($data));
		$this->assertEquals("34 min 8 secs", $stats["duration"]);
		// todo: check other log stats
	}
}
?>