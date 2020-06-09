<?php

include "mlg.format.php";

/*
 * @brief Defines the actions taken at the user level.
 * 
 * upload
 * browse
 * view
 * etc.
 */
class Rusefi
{
	private $msqur = null;
	public $username;
	public $userid;
	public $isAdminUser;
	
	public $forum_url = "https://rusefi.com/forum";
	public $forum_login_url, $forum_user_profile_url;

	private $reqLogFields = array("time", "rpm", "clt", "tps", "air_fuel_ratio", "engine_load", "vehicle_speed", "throttle_pedal_position");

	function __construct($msqur)
	{
		global $_COOKIE, $_POST;
		$this->msqur = $msqur;

		$this->userid = -1;
		$this->isAdminUser = false;
		
		// first, get userid from phpbb SESSION_ID
		if (isset($_COOKIE['phpbb3_1lnf6_sid'])) {
			$sid = $_COOKIE['phpbb3_1lnf6_sid'];
			$this->userid = $this->getUserIdFromSid($sid);
			// Anonymous user?
			if ($this->userid <= 1)
				$this->userid = -1;
		}
		
		// if failed, try to get userid from the stored 'token' cookie
		if ($this->userid < 0 && isset($_COOKIE['rusefi_token'])) {
			$rusefi_token = $_COOKIE['rusefi_token'];
			$this->userid = $this->getUserIdFromToken($rusefi_token);
		}

		// if failed, try to get userid from the passed POST parameter
		if ($this->userid < 0 && isset($_POST['rusefi_token'])) {
			$rusefi_token = $_POST['rusefi_token'];
			$this->userid = $this->getUserIdFromToken($rusefi_token);
		}

		$this->username = ($this->userid != -1) ? $this->getUserNameFromId($this->userid) : "";
		
		//!!!!!!!!!!!
		//$this->userid = 2;
		//$this->username = "AndreyB";
	
		$this->isAdminUser = in_array($this->userid, RUSEFI_ADMIN_USER_IDS);
		$this->forum_login_url = $this->forum_url . "/ucp.php?mode=login";
		$this->forum_user_profile_url = $this->forum_url . "/memberlist.php?mode=viewprofile&u=" . $this->userid;

		$this->userTunes = $this->getUserTunes($this->userid);
	}

	private function getUserIdFromToken($token)
	{
		if (!$this->msqur->db->connect()) return -1;
		try
		{
			$st = $this->msqur->db->db->prepare("SELECT user_id FROM phpbb_rusefi_tokens WHERE token = :rusefi_token LIMIT 1");
			DB::tryBind($st, ":rusefi_token", $token);
			$st->execute();
			if ($st->rowCount() > 0)
			{
				$result = $st->fetch(PDO::FETCH_ASSOC);
				$user_id = $result['user_id'];
				$st->closeCursor();
				return $user_id;
			}
			else
			{
				if (DEBUG) debug("No result for $token!");
				$st->closeCursor();
			}
		}
		catch (PDOException $e)
		{
			$this->msqur->db->dbError($e);
		}
		return -1;
	}

	private function getUserIdFromSid($sid)
	{
		if (!$this->msqur->db->connect()) return -1;
		try
		{
			$st = $this->msqur->db->db->prepare("SELECT session_user_id FROM phpbb_sessions WHERE session_id = :sid LIMIT 1");
			DB::tryBind($st, ":sid", $sid);
			$st->execute();
			if ($st->rowCount() > 0)
			{
				$result = $st->fetch(PDO::FETCH_ASSOC);
				$user_id = $result['session_user_id'];
				$st->closeCursor();
				return $user_id;
			}
			else
			{
				if (DEBUG) debug("No result for $sid!");
				$st->closeCursor();
			}
		}
		catch (PDOException $e)
		{
			$this->msqur->db->dbError($e);
		}
		return -1;
	}

	public function getUserNameFromId($user_id)
	{
		if (!$this->msqur->db->connect()) return FALSE;
		try
		{
			$st = $this->msqur->db->db->prepare("SELECT username FROM phpbb_users WHERE user_id = :user_id LIMIT 1");
			DB::tryBind($st, ":user_id", $user_id);
			$st->execute();
			if ($st->rowCount() > 0)
			{
				$result = $st->fetch(PDO::FETCH_ASSOC);
				$username = $result['username'];
				$st->closeCursor();
				return $username;
			}
			else
			{
				if (DEBUG) debug("No result for $user_id");
				$st->closeCursor();
			}
		}
		catch (PDOException $e)
		{
			$this->msqur->db->dbError($e);
		}
		return FALSE;
	}

	public function preprocessTune()
	{
		$more = " <a target='_blank' href='https://github.com/rusefi/rusefi/wiki/HOWTO_upload_tune'>More...</a>";

		if ($this->userid < 0)
			return array("text"=>"Cannot check your tune. Please authorize!", "status"=>"deny");
			
		$einfo = parseQueryString("einfo");
		if (!isset($einfo["name"]) || !isset($einfo["make"]) || !isset($einfo["code"]))
			return array("text"=>"Wrong data!", "status"=>"deny");
		if ($einfo["name"][1] == "" || $einfo["make"][1] == "" || $einfo["code"][1] == "")
			return array("text"=>"Vehicle Name, Engine Make and Code may not be empty! Please check your Tune settings!" . $more, "status"=>"deny");

		$optparams = array("displacement", "compression", "induction");

		// ok, the user data seems valid, let's check his existing engines...

		$engines = $this->msqur->db->getUserVehicles($this->userid);
		$engines = ($engines === FALSE) ? array() : $engines;
		
		$ret = array("text"=>"", "status"=>"warn");
		
		if (count($engines) < 1)
			$ret["text"] = "You are going to upload your first rusEFI Tune! Please check carefully all the info above!". $more;
		else
		{
			$isNewName = true;
			$isNewMakeCode = true;
			$isNewEngineParams = true;
			// check if the user already has that make or code
			foreach ($engines as $e)
			{
				if ($this->areValuesEqual($e["name"], $einfo["name"][1]))
				{
					$isNewName = false;
					if ($this->areValuesEqual($e["make"], $einfo["make"][1]) && $this->areValuesEqual($e["code"], $einfo["code"][1]))
					{
						$isNewMakeCode = false;
						$isNewEngineParams = false;
						foreach ($optparams as $p)
						{
							if (!$this->areValuesEqual($e[$p], $einfo[$p][1]))
							{
								$whatIsDifferent[] = $p;
								$isNewEngineParams = true;
							}
						}
					}
				}
			}

			if ($isNewName)
			{
				$ret["text"] = "This tune is for a new Vehicle! Are you sure you want to add it? Please check carefully the info above!". $more;
			}
			else if ($isNewMakeCode)
			{
				$ret["text"] = "The Engine Make or Code is different in this tune from the stored for your vehicle! Are you sure you want to update them? Please check carefully the info above!". $more;
			}
			else if ($isNewEngineParams)
			{
				$ret["text"] = "The new '".implode(",", $whatIsDifferent)."' info detected for your stored vehicle! Are you sure you want to update the vehicle data?". $more;
			}
		}
		
		// check if the user filled in all the data
		$notAllParams = false;
		foreach ($optparams as $p)
		{
			if ($einfo[$p][1] == "")
				$notAllParams = true;
		}

		if ($notAllParams)
		{
			$ret["text"] .= "<div class=warn2><em>Warning! Your vehicle info is incomplete!</em></div>";
		}

		// no warnings?
		if ($ret["text"] == "")
		{
			$ret["text"] = "Your tune seems OK!";
			$ret["status"] = "ok";
		}
		
		return $ret;
	}

	private function parseLogData($data, $type, $fullSize)
	{
		global $logValues;

		$mlgParser = new MlgParser();
		if ($type == -1)
			$type = $mlgParser->detectFormat($data);
		$mlgParser->initStats();
		if ($type == "LogBinary")
			$ret = $mlgParser->parseBinary($data, $this->reqLogFields);
		else if ($type == "LogText")
			$ret = $mlgParser->parseText($data, $this->reqLogFields);
		else
			$ret = array("text"=>"Unknown log type!", "status"=>"deny");

		// fill info
		if ($ret["status"] != "deny") {
			$logValues = $mlgParser->getStats($fullSize);
			$ret["logValues"] = $logValues;
			$ret["logFields"] = "<small>".$this->fillLogFields()."</small>";
		} else {
			$ret["logValues"] = array();
			$ret["logFields"] = "";
		}

		return $ret;
	}


	public function preprocessLog()
	{
		global $_FILES, $_POST;

		if ($this->userid < 0)
			return array("text"=>"Cannot check your log. Please authorize!", "status"=>"deny");

		//return array("text"=>print_r($_FILES["log"], true), "status"=>"deny");

		if (!isset($_FILES["log"])) {
			return array("text"=>"Cannot send the file! (The size limit is " . ini_get('post_max_size') . " compressed)", "status"=>"deny");
		}
		$logFile = $_FILES["log"];
		if ($logFile["error"] == 1) {
			return array("text"=>"The file is too big! The max size is ".ini_get('upload_max_filesize'), "status"=>"deny");
		}
		
		$zdata = isset($logFile['tmp_name']) ? @file_get_contents($logFile['tmp_name']) : FALSE;
		if ($zdata === FALSE) {
			return array("text"=>"Cannot read the compressed log data!", "status"=>"deny");
		}

		$data = @zlib_decode($zdata);
		if ($data === FALSE) {
			return array("text"=>"Cannot decompress the log data!", "status"=>"deny");
		}

		//file_put_contents("log", $data);

		$type = parseQueryString("type");
		$fullSize = parseQueryString("fullSize");
		$ret = $this->parseLogData($data, $type, $fullSize);

		return $ret;
	}

	public function getLogInfo($data) {
		$fullSize = strlen($data);
		$ret = $this->parseLogData($data, -1, $fullSize);
		// store the array as a JSON string
		return json_encode($ret["logValues"]);
	}

	public function fillLogFields() {
		global $logValues;
		ob_start();
		include "view/logfields.php";
		return ob_get_clean();
	}

	public function fillGeneralLogInfo() {
		global $logValues;
		global $rusefi;
		ob_start();
		include "view/loggeneral.php";
		return ob_get_clean();
	}

	public function processValue($v)
	{
		$v = trim($v);

		if (strcasecmp($v, "true") == 0)
			$v = "1";
		else if (strcasecmp($v, "false") == 0)
			$v = "0";
		else if (is_numeric($v))
			$v = round(floatval($v), 3);

		return $v;
	}

	public function areValuesEqual($v1, $v2)
	{
		$v1 = $this->processValue($v1);
		$v2 = $this->processValue($v2);
		return strcmp($v1, $v2) == 0;
	}

	public function isTuneAlreadyExists($files, $engineid)
	{
		foreach ($files as $file)
		{
			$id = $this->msqur->db->findMSQ($file, $engineid);
			if ($id > 0)
				return TRUE;
		}
		return FALSE;
	}

	public function getUserTunes($user_id) {
		$list = $this->msqur->db->getUserTunes($user_id);
		foreach ($list as &$l) {
			$l = $l["uploadDate"]
				. " " . implode(" ", array($l["make"], $l["code"], "\"" . $l["name"] . "\""))
				. " (" . $l["numCylinders"] . "cyl"
				. " " . $l["displacement"] . "L)";
		}
		return $list;
	}

	public function checkIfTuneIsValidForUser($user_id, $tune_id)
	{
		$tunes = $this->getUserTunes($user_id);
		return isset($tunes[$tune_id]);
	}

	public function unpackLogInfo(&$results)
	{
		foreach ($results as &$r)
		{
			$info = json_decode($r["info"]);
			if (is_object($info))
				$info = get_object_vars($info);
			$r = array_merge($r, $info);
		}
		return $results;
	}

	public function viewLog($id)
	{
		global $logValues;
		$res = $this->msqur->db->browseLog(array("l.id"=>$id));
		$this->unpackLogInfo($res);
		$logValues = $res[0];
		return "<div class=logViewPage>".$this->fillGeneralLogInfo().$this->fillLogFields()."</div>";
	}

	public function getLogForDownload($id)
	{
		return $this->msqur->db->getLog($id);
	}

}

$rusefi = new Rusefi($msqur);

?>