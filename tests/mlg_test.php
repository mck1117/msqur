<?php

include "../src/mlg.format.php";

{
	//$data = file_get_contents("../logs/2020-06-03_19.27.34.mlg");
	//$data = file_get_contents("../logs/log.mlg");
	$data = file_get_contents("testcases/2020-06-30_19.21.53_mck1117_large.mlg");
	$mlgParser = new MlgParser();
	$mlgParser->initStats();
	$ret = $mlgParser->parseBinary($data, true);
	$stats = $mlgParser->getStats(strlen($data));
	print_r($ret);
	print_r($stats);
}
?>