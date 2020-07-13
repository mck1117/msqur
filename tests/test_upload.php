<?php

require_once "common.php";

class TestUpload extends PHPUnit\Framework\TestCase
{
    public function testTuneUpload()
    {
    	global $msqur, $rusefi, $test;
		$test = new CommonTestHelper();
		$test->startTest();

		// add tune file
		$uploadedFile = "testcases/mazda_B6ZE.msq";
		$_FILES = [
            'upload-file' => [
                'name' => $uploadedFile,
                'type' => 'image/png',
                'size' => 5093,
                'tmp_name' => $uploadedFile,
                'error' => 0
            ]
        ];

        // add ini file
        $test->addFile("ini/rusefi/2020/07/06/mre_f4/2108843221.ini", "testcases/mazda_B6ZE.ini");

        // simulate login
        $_POST = array("rusefi_token"=>"1");
        $rusefi->userid = 1;

		// call the main code
		include("../src/upload.php");

		// check if tables are filled with the data
		$this->assertCount(1, $test->getTableFields("msqur_metadata", "id"));
		$this->assertCount(1, $test->getTableFields("msqur_files", "id"));
		$this->assertCount(1, $test->getTableFields("msqur_engines", "id"));

		// get the tune info
		$tune = $msqur->browse(array(), 0, "msq");
		$this->assertCount(1, $tune);
		$tune_id = $tune[0]["mid"];
		$this->assertEquals(1, $tune_id);
		// todo: check all tune fields

		// get the engine info from the tune
		$engine = $rusefi->getEngineFromTune($tune_id);
		// the engine has 7 fields
		$this->assertCount(7, $engine);
		// todo: check the engine fields

		//$html = $msqur->view($tune_id, "ts", array());
    }
}
?>