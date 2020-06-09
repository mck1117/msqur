<?php
/*
.MLG file parser.
(c) andreika, 2020.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. 
*/

define("IDX_TIME", 0);
define("IDX_RPM", 1);
define("IDX_CLT", 2);
define("IDX_TPS", 3);
define("IDX_PPS", 4);
define("IDX_AFR", 5);
define("IDX_TARGET_AFR", 6);
define("IDX_VE", 7);
define("IDX_SPEED", 8);
define("IDX_TRIGGER_ERR", 9);
define("IDX_ETB_DUTY", 10);	// needed to detect PPS vs TPS priority :(

define("STATE_UNKNOWN", 0);
define("STATE_STOPPED", 1);
define("STATE_CRANKING", 2);
define("STATE_IDLE", 3);
define("STATE_RUNNING", 4);

define("CRANKING_MIN_TIME", 0.1);	// 100 ms
define("IDLE_RPM", 700);
define("IDLE_MIN_TIME", 2);	// 2 seconds
define("COLD_START_TEMP", 40);	// 40 deg. celsius
define("THROTTLE_IDLE_THRESHOLD", 2);	// 2%

define("AFR_MIN", 9);
define("AFR_MAX", 19);
define("AFR_UNKNOWN", 999999);

class LogStats {
	public $duration;

	public $dataSize;
	public $numRecords;
	public $dataStartOffset;
	public $dataRecordSize;

	public $startingNum;
	public $startingNumCold;
	public $startingNumSuccess;
	public $startingFastestTime;
	public $startingNumTriggerErrors;

	public $idlingDuration;
	public $idlingAverageRpmSum;
	public $idlingAverageAfrDeltaSqSum;
	public $idlingNumTriggerErrors;

	public $runningDuration;
	public $runningMaxRpm;
	public $runningMaxThrottle;
	public $runningMaxVe;
	public $runningMaxSpeed;
	public $runningAverageAfrDeltaSqSum;

	public $engineState;
	public $timeLastStateChange;
	public $trigErrorsLastStateChange;

	public $idlingRpmCnt;
	public $idlingAfrCnt;
	public $runningAfrCnt;
	public $isThrottlePressed;
	public $isAfrDetected;
	public $isEtbPresent;

	public $prevTime;
	public $timeDeltaAverageCnt;
};

class MlgParser {
	// file format description:
	private	$mlgMagic = "MLVLG\0";
	
	private	$mlgHeaderFmt = "a6Magic/nVersion/NUnixTimestamp/nTextOffset/nRes1/nDataOffset/nRecordSize/nNumFields";
	private $mlgHeaderSize = 0x16;
	
	private	$mlgFieldFmt = "cType/a34Name/a11Units/GScale/GShift/cPrecision";
	private $mlgFieldSize = 0x37;
	
	//private	$mlgDataTypes = array(0=>"C"/*U08*/, 1=>"c"/*S08*/, 2=>"n"/*U16*/, 3=>"s"/*S16*/, 4=>"N"/*U32*/, 5=>"l"/*S32*/, 6=>""/*?*/, 7=>"G"/*F32*/);
	private	$mlgDataTypes = array(0=>"C"/*U08*/, 1=>"c"/*S08*/, 2=>"S"/*U16*/, 3=>"s"/*S16*/, 4=>"L"/*U32*/, 5=>"l"/*S32*/, 6=>""/*?*/, 7=>"f"/*F32*/);
	private	$mlgDataTypeSizes = array(0=>1, 1=>1, 2=>2, 3=>2, 4=>4, 5=>4, 6=>0/*?*/, 7=>4);
	
	// todo: each record has a weird U32 "counter-ish" field and also the attitional byte at the end...
	private $mlgDataExtraField = "Ni";
	private $mlgDataExtraField2 = "Cx";
	private $mlgDataSizeExtra = 0x5;

	private $stats;
	private $reqFields = NULL;

	function detectFormat($data) {
		$dataSize = strlen($data);
		if ($dataSize < $this->mlgHeaderSize)
			return "";
		$header = @unpack($this->mlgHeaderFmt, $data);
		if ($header["Magic"] == $this->mlgMagic)
			return "LogBinary";
		if ($dataSize > 100)
			$data = substr($data, 0, 100);
		if (preg_match("/#*\"(.*?)rusEFI(.*?)\"/", $data))
			return "LogText";
		return "";
	}
	
	// Parse binary data
	function parseBinary($data) {
		$dataSize = strlen($data);

		$warn = "";

		if ($dataSize < $this->mlgHeaderSize)
			return array("text"=>"The log file is too small or empty!", "status"=>"deny");

		$header = @unpack($this->mlgHeaderFmt, $data);
		
		//print_r($header);
		
		if ($header["Magic"] != $this->mlgMagic)
			return array("text"=>"Wrong log file header! MLG-format not recognized!", "status"=>"deny");
		if ($header["NumFields"] < 1)
			return array("text"=>"Corrupted log file header!", "status"=>"deny");
		$fields = array();
		$fdata = substr($data, $this->mlgHeaderSize);
		
		$dataFmts = array($this->mlgDataExtraField);
		
		$dataRecordSize = 0;
		$recList = array();
		for ($i = 0; $i < $header["NumFields"]; $i++) {
			$field = unpack($this->mlgFieldFmt, $fdata);

			if (!isset($field["Type"]) || !isset($field["Name"])) {
				return array("text"=>"Wrong data fields or unknown format!", "status"=>"deny");
			}
			if ($field["Type"] < 0 || $field["Type"] > 7 || $field["Type"] == 6) {
				return array("text"=>"Unknown data field type for '".$field["Name"]."'!", "status"=>"deny");
			}
			$field["ShortName"] = $this->getLogFieldName($field["Name"]);
			if (array_key_exists($field["ShortName"], $dataFmts)) {
				return array("text"=>"The field name '".$field["Name"]."' exists more than once!", "status"=>"deny");
			}
			
			$dName = "d".$i;	// we need it to be short for performance reasons! // = $field["ShortName"];
			$dataFmts[$field["ShortName"]] = $this->mlgDataTypes[$field["Type"]] . $dName;
			$field["dName"] = $dName;
			$dataRecordSize += $this->mlgDataTypeSizes[$field["Type"]];

			// add all fields if no requirements
			if ($this->reqFields === NULL)
				$recList[] = array($field["dName"], $field["Scale"]);
			
			$fdata = substr($fdata, $this->mlgFieldSize);
			$fields[] = $field;
		}


		$dataFmts[] = $this->mlgDataExtraField2;

		if ($dataRecordSize != $header["RecordSize"]) {
			return array("text"=>"Record size mismatch (".$dataRecordSize." != ".$header["RecordSize"].")! Wrong data fields?", "status"=>"deny");
		}
		
		//print_r($fields);

		// init fields filtering
		if ($this->reqFields !== NULL) {
			$reqNotFound = array();
			foreach ($this->reqFields as $rf) {
				$f = array_search($rf, array_column($fields, "ShortName"));
				if ($f === FALSE)
					$reqNotFound[] = "'".$rf."'";
				else
					$recList[] = array($fields[$f]["dName"], $fields[$f]["Scale"]);
				
			}
			if (count($reqNotFound) > 0) {
				return array("text"=>"Some of the required fields are not found in the log (" . implode(",", $reqNotFound) . ")!", "status"=>"deny");
			}
		}
		
		// big-endian -> little-endian conversion
		$dataFmts = array_reverse($dataFmts);
		// construct data field
		$dataFmt = implode("/", $dataFmts);
		
		if ($header["DataOffset"] > $dataSize) {
			return array("text"=>"Corrupted file data!", "status"=>"deny");
		}

		$dataTotalRecordSize = $dataRecordSize + $this->mlgDataSizeExtra;

		// estimate the number of records
		$numRecords = floatVal($dataSize - $header["DataOffset"]) / floatVal($dataTotalRecordSize);
		if ($numRecords < 1) {
			return array("text"=>"The log file has no records and is empty!", "status"=>"deny");
		}
		if ($numRecords != intVal($numRecords)) {
			$warn = "The log file is truncated! The last ".intVal($numRecords)."-th record is incomplete!";
		}
		$numRecords = intVal($numRecords);

		// start record processing...
		$timeStart = microtime(true);

		$curOffset = $header["DataOffset"];
		$rdata = substr($data, $curOffset);
		$records = str_split($rdata, $dataTotalRecordSize);

		$this->preProcess($dataSize, $numRecords, $curOffset, $dataTotalRecordSize);

		$rec = array();
		$idx = 0;
		for ($i = 0; $i < $numRecords; $i++, $idx++) {
			// big-endian -> little-endian conversion (unfortunately unpack() is not enough because of signed ints...)
			$r = strrev($records[$idx]);
			$d = @unpack($dataFmt, $r);

			$type = ($d["i"] & 0xff);
			if ($type != 0) {	// block type
				if ($type == 1) {
					// unfortunately this block type has non-matching size, so we need to shift the data manually
					$curOffset += $idx * $dataTotalRecordSize + 54;
					$idx = -1;
					$rdata = substr($data, $curOffset);
					$records = str_split($rdata, $dataTotalRecordSize);
					// todo: the ECU was reset? do something?
					continue;
				}
				return array("text"=>"Unsupported file format version!", "status"=>"deny");
			}

			// apply scale
			foreach ($recList as $j=>$rl) {
				$rec[$j] = $d[$rl[0]] * $rl[1];
			}

			$this->processRecord($rec);

			//break;
		}

		// we need to finish the current state somehow
		if ($rec[IDX_RPM] != 0) {
			$rec[IDX_RPM] = 0;
			$this->changeState($rec);
		}
		$timeElapsed = microtime(true) - $timeStart;

		if ($warn) {
			return array("text"=> $warn, "status"=>"warn");
		}
		return array("text"=>"Your log seems OK!", "status"=>"ok");
	}

	function parseText($data) {
		$dataSize = strlen($data);
		// todo:
		return array("text"=>"The text log format is currently not supported!", "status"=>"deny");
	}
	
	function getLogFieldName($n) {
		return strtolower(preg_replace("/[^A-Za-z0-9]+/", "_", trim($n)));
	}

	///////////////////////////////////////////

	private $reqFieldsForStats = array(IDX_TIME=>"time", IDX_RPM=>"rpm", IDX_CLT=>"clt", IDX_TPS=>"tps", IDX_PPS=>"throttle_pedal_position", 
		IDX_AFR=>"air_fuel_ratio", IDX_TARGET_AFR=>"fuel_target_afr", IDX_VE=>"fuel_ve", IDX_SPEED=>"vehicle_speed", IDX_TRIGGER_ERR=>"trg_err",
		IDX_ETB_DUTY=>"etb_duty");


	function preProcess($dataSize, $numRecords, $dataStartOffset, $dataRecordSize) {
		$this->state->dataSize = $dataSize;
		$this->state->numRecords = $numRecords;
		$this->state->dataStartOffset = $dataStartOffset;
		$this->state->dataRecordSize = $dataRecordSize;
		//!!!!!!!!!!!
		//echo "Number of records = " . $numRecords . "\r\nTime elapsed: " . $timeElapsed." secs\r\n";
		//echo "Printing the last record:\r\n";
		//print_r(array_combine($this->reqFields === NULL ? array_column($fields, "ShortName") : $this->reqFields, $rec));
	}

	function processRecord($d) {
		// first, try to detect the throttle (todo: we need more clear outputchannel behavior for ETB/noETB)
		if ($d[IDX_ETB_DUTY] != 0)
			$this->state->isEtbPresent = 1;
		$throttle = ($this->state->isEtbPresent) ? $d[IDX_PPS] : $d[IDX_TPS];
		$this->state->isThrottlePressed = $throttle > THROTTLE_IDLE_THRESHOLD;

		// process the state changes
		if (($this->state->engineState == STATE_UNKNOWN || $this->state->engineState == STATE_STOPPED) && $d[IDX_RPM] > 0) {
			$this->changeState($d);
		}
		else if ($this->state->engineState == STATE_CRANKING && $d[IDX_RPM] >= IDLE_RPM) {
			$this->changeState($d);
		}
		else if (($this->state->engineState == STATE_IDLE || $this->state->engineState == STATE_RUNNING) && $d[IDX_RPM] < IDLE_RPM) {
			$this->changeState($d);
		}
		else if (($this->state->engineState == STATE_CRANKING || $this->state->engineState == STATE_IDLE) && $this->state->isThrottlePressed) {
			$this->changeState($d);
		}
		else if ($this->state->engineState == STATE_RUNNING && !$this->state->isThrottlePressed) {
			$this->changeState($d);
		}

		// get time increment
		$dTime = $d[IDX_TIME] - $this->state->prevTime;
		if ($dTime > 0) {
			$this->state->duration += $dTime;
			$this->state->timeDeltaAverageCnt++;
		}
		$this->state->prevTime = $d[IDX_TIME];

		// get AFR deviation
		if ($d[IDX_AFR] >= AFR_MIN && $d[IDX_AFR] <= AFR_MAX) {
			$this->state->isAfrDetected = 1;
			$deltaAfrSq = ($d[IDX_AFR] - $d[IDX_TARGET_AFR]);
			$deltaAfrSq *= $deltaAfrSq;	// squared
		} else {
			$deltaAfrSq = AFR_UNKNOWN;
		}
		
		// now we know the current state, let's gather the stats
		if ($this->state->engineState == STATE_IDLE) {
			$this->state->idlingAverageRpmSum += $d[IDX_RPM];
			$this->state->idlingRpmCnt++;
			if ($deltaAfrSq != AFR_UNKNOWN) {
				$this->state->idlingAverageAfrDeltaSqSum += $deltaAfrSq;
				$this->state->idlingAfrCnt++;
			}
		}
		else if ($this->state->engineState == STATE_RUNNING) {
			if ($deltaAfrSq != AFR_UNKNOWN) {
				$this->state->runningAverageAfrDeltaSqSum += $deltaAfrSq;
				$this->state->runningAfrCnt++;
			}
			// update 'max' values
			if ($d[IDX_RPM] > $this->state->runningMaxRpm)
				$this->state->runningMaxRpm = $d[IDX_RPM];
			if ($throttle > $this->state->runningMaxThrottle)
				$this->state->runningMaxThrottle = $throttle;
			if ($d[IDX_VE] > $this->state->runningMaxVe)
				$this->state->runningMaxVe = $d[IDX_VE];
			if ($d[IDX_SPEED] > $this->state->runningMaxSpeed)
				$this->state->runningMaxSpeed = $d[IDX_SPEED];
		}
	}

	function changeState($d) {
		// detect the new state
		if ($d[IDX_RPM] >= IDLE_RPM || ($d[IDX_RPM] > 0 && ($this->state->engineState == STATE_RUNNING ||  $this->state->engineState == STATE_IDLE)))
			$newState = ($this->state->isThrottlePressed) ? STATE_RUNNING : STATE_IDLE;
		else
			$newState = ($d[IDX_RPM] > 0) ? STATE_CRANKING : STATE_STOPPED;

		//echo '-------state=' . $newState . " (old=".$this->state->engineState.")\r\n";
		//print_r($d);
		//print_r($this->state);

		if ($newState == $this->state->engineState)
			return;
		$newTime = $d[IDX_TIME];

		if ($this->state->engineState == STATE_UNKNOWN) {
			$this->state->timeLastStateChange = $newTime;
			$this->state->engineState = $newState;
			return;
		}

		if ($this->state->engineState == STATE_CRANKING) {
			$crankingTime = floatval($newTime - $this->state->timeLastStateChange);
			if ($crankingTime >= CRANKING_MIN_TIME) {
				$this->state->startingNum++;
				// successfull cranking
				if ($newState == STATE_IDLE || $newState == STATE_RUNNING) {
					$this->state->startingNumSuccess++;
					if ($d[IDX_CLT] < COLD_START_TEMP)
						$this->state->startingNumCold++;
				
					// we got a new cranking record!
					if ($crankingTime < $this->state->startingFastestTime)
						$this->state->startingFastestTime = $crankingTime;
				}
			}
			// we accumulate all trigger errors starting from the state change to 'cranking'
			$this->state->startingNumTriggerErrors += ($d[IDX_TRIGGER_ERR] - $this->state->trigErrorsLastStateChange);
		}
		else if ($this->state->engineState == STATE_IDLE) {
			$this->state->idlingDuration += $newTime - $this->state->timeLastStateChange;
			$this->state->idlingNumTriggerErrors += ($d[IDX_TRIGGER_ERR] - $this->state->trigErrorsLastStateChange);
		}
		else if ($this->state->engineState == STATE_RUNNING) {
			$this->state->runningDuration += $newTime - $this->state->timeLastStateChange;
		}

		$this->state->timeLastStateChange = $newTime;
		$this->state->trigErrorsLastStateChange = $d[IDX_TRIGGER_ERR];
		$this->state->engineState = $newState;
	}

	function initStats() {
		$this->state = new LogStats();
		$class_vars = get_class_vars("LogStats");
		foreach ($class_vars as $cv=>$cvv) {
			$this->state->$cv = 0;
		}
		$this->reqFields = $this->reqFieldsForStats;
		$this->state->startingFastestTime = INF;
		$this->state->prevTime = INF;
	}

	function printTime($t) {
		if ($t == INF)
		 	return "Unknown";
		if ($t < 10.0) {
			return "". round($t, 2) . " secs";
		}
		if ($t < 60.0)
			return "". round($t, 1) . " secs";
		$t = round($t);
		$h = intval($t / 3600);
		$m = intval(($t % 3600) / 60);
		$s = intval(($t % 3600) % 60);
		$str = array();
		if ($h > 0)
			$str[] = $h . " h";
		if ($m > 0)
			$str[] = $m . " min";
		if ($s == 1)
			$str[] = $s . " sec";
		else if ($s > 0)
			$str[] = $s . " secs";
		return implode(" ", $str);
	}

	function printPercent($num, $denom) {
		if ($denom == 0)
			return "0%";
		$p = 100.0 * $num / $denom;
		if ($p < 10.0)
			return round($p, 1) . "%";
		return round($p) . "%";
	}

	function printRound($v) {
		if ($v == INF)
			return "Unknown";
		return round($v);
	}

	function divide($num, $denom) {
		if ($denom == 0)
			return INF;
		return floatval($num) / $denom;
	}

	function getStats($fullSize) {

		$idlingAverageRpm = $this->divide($this->state->idlingAverageRpmSum, $this->state->idlingRpmCnt);
		$idlingAverageAfrDeltaSq = $this->divide($this->state->idlingAverageAfrDeltaSqSum, $this->state->idlingAfrCnt);
		$runningAverageAfrDeltaSq = $this->divide($this->state->runningAverageAfrDeltaSqSum, $this->state->runningAfrCnt);

		$avgTimePerRecord = $this->state->duration / $this->state->timeDeltaAverageCnt;
		$estimatedNumRecords = floatVal($fullSize - $this->state->dataStartOffset) / $this->state->dataRecordSize;
		$estimatedTotalDuration = $estimatedNumRecords * $avgTimePerRecord;
/*
		//!!!!!!!!!!!
		print_r($this->state);
		echo "idlingAverageRpm=$idlingAverageRpm\r\n";
		echo "idlingAverageAfrDeltaSq=$idlingAverageAfrDeltaSq\r\n";
		echo "runningAverageAfrDeltaSq=$runningAverageAfrDeltaSq\r\n";
		echo "avgTimePerRecord=$avgTimePerRecord\r\n";
		echo "estimatedTotalDuration=$estimatedTotalDuration\r\n";
*/
		$logValues = array(
			"duration" => $this->printTime($this->state->duration),
			"dataRate" => $this->printRound($this->divide(1, $avgTimePerRecord)),
    		"startingNum" => $this->state->startingNum,
    		"startingNumCold" => $this->state->startingNumCold,
    		"startingSuccessRate" => $this->printPercent($this->state->startingNumSuccess, $this->state->startingNum),
    		"startingFastestTime" => $this->printTime($this->state->startingFastestTime),
    		"startingNumTriggerErrors" => $this->state->startingNumTriggerErrors,
    		"idlingDuration" => $this->printTime($this->state->idlingDuration),
    		"idlingAverageRpm" => $this->printRound($idlingAverageRpm),
    		"idlingAfrAccuracy" => $this->printPercent(1, 1.0 + $idlingAverageAfrDeltaSq),
    		"idlingNumTriggerErrors" => $this->state->idlingNumTriggerErrors,
    		"runningDuration" => $this->printTime($this->state->runningDuration),
    		"runningMaxRpm" => $this->state->runningMaxRpm,
    		"runningMaxThrottle" => $this->printPercent($this->state->runningMaxThrottle, 100.0),
    		"runningMaxVe" => $this->state->runningMaxVe,
    		"runningMaxSpeed" => $this->state->runningMaxSpeed,
    		"runningAfrAccuracy" => $this->printPercent(1, 1.0 + $runningAverageAfrDeltaSq),
    	);

		// we need it only for *very* long partially loaded files
		if ($fullSize > $this->state->dataSize)
			$logValues["estimatedDuration"] = $estimatedTotalDuration;

		return $logValues;
	}
};
