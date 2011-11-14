<?php

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - 

 Title : Sample Landing page for PHP Quick Profiler Class
 Author : Created by Ryan Campbell
 URL : http://particletree.com/features/php-quick-profiler/

 Last Updated : April 22, 2009

 Description : This file contains the basic class shell needed
 to use PQP. In addition, the init() function calls for example
 usages of how PQP can aid debugging. See README file for help
 setting this example up.

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

require_once('classes/PhpQuickProfiler.php');
require_once('classes/MySqlDatabase.php');

class PQPExample {
	
	private $profiler;
	private $db = '';
	
	public function __construct() {
		$this->profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime(), '/plugins/QuickProfiler/~pqp/');
	}
	
	public function init() {
		$this->sampleConsoleData();
		$this->sampleDatabaseData();
		$this->sampleMemoryLeak();
		$this->sampleSpeedComparison();
	}
	
	/*-------------------------------------------
	     EXAMPLES OF THE 4 CONSOLE FUNCTIONS
	-------------------------------------------*/
	
	public function sampleConsoleData() {
		try {
			Console::log('Begin logging data');
			Console::logMemory($this, 'PQP Example Class : Line '.__LINE__);
			Console::logSpeed('Time taken to get to line '.__LINE__);
			Console::log(array('Name' => 'Ryan', 'Last' => 'Campbell', 'f' => Null, 'd' => ''));
			Console::logSpeed('Time taken to get to line '.__LINE__);
			Console::logMemory($this, 'PQP Example Class : Line '.__LINE__);
			Console::log('Ending log below with a sample error.');
			throw new Exception('Unable to write to log!');
		}
		catch(Exception $e) {
			Console::logError($e, 'Sample error logging.');
		}
	}
	
	/*-------------------------------------
	     DATABASE OBJECT TO LOG QUERIES
	--------------------------------------*/
	
	public function sampleDatabaseData() {
		$this->db = new MySqlDatabase(
			'localhost', 
			'root',
			'');
		$this->db->connect(true);
		$this->db->changeDatabase('Gdn');
		
		$sql = 'SELECT * FROM Feedback WHERE FeedbackID = 1';
		$rs = $this->db->query($sql);
		
		$sql = 'SELECT COUNT(*) FROM Feedback';
		$rs = $this->db->query($sql);
		
		$sql = 'SELECT COUNT(*) FROM Feedback WHERE FeedbackID != 1';
		$rs = $this->db->query($sql);
	}
	
	/*-----------------------------------
	     EXAMPLE MEMORY LEAK DETECTED
	------------------------------------*/
	
	public function sampleMemoryLeak() {
		$ret = '';
		$longString = 'This is a really long string that when appended with the . symbol 
					  will cause memory to be duplicated in order to create the new string.';
		for($i = 0; $i < 10; $i++) {
			$ret = $ret . $longString;
			Console::logMemory($ret, 'Watch memory leak -- iteration '.$i);
		}
	}
	
	/*-----------------------------------
	     POINT IN TIME SPEED MARKS
	------------------------------------*/
	
	public function sampleSpeedComparison() {
		Console::logSpeed('Time taken to get to line '.__LINE__);
		Console::logSpeed('Time taken to get to line '.__LINE__);
		Console::logSpeed('Time taken to get to line '.__LINE__);
		Console::logSpeed('Time taken to get to line '.__LINE__);
		Console::logSpeed('Time taken to get to line '.__LINE__);
		Console::logSpeed('Time taken to get to line '.__LINE__);
	}
	
	public function __destruct() {
		$this->profiler->display($this->db);
	}
	
}

$pqp = new PQPExample();
$pqp->init();
