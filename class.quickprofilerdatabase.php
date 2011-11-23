<?php if (!defined('APPLICATION')) die();

class QuickProfilerDatabase extends Gdn_Database {
	
	protected $Queries;
	
	public function Queries() {
		return $this->Queries;
	}
	
	public function QueryTimes() {
		return array();
	}

	protected function GetInfo(&$Query, $Trace) {
		$Class = GetValue('class', $Trace);
		$Query['Method'] = $Class . GetValue('type', $Trace, '') . $Trace['function'];
		$Query['Arguments'] = $Trace['args'];
		//$Query['File'] = substr($Trace['file'], strlen(PATH_ROOT) + 1);
	}
	
	public function Query($Sql, $InputParameters = NULL, $Options = array()) {
		$SQL =& $this->_SQL;
		$TimeStart = Now();
		$Query = array('Sql' => $SQL->ApplyParameters($Sql, $InputParameters), 'Parameters' => $InputParameters);
		
		$Result = parent::Query($Sql, $InputParameters, $Options);
		if (StringBeginsWith($Sql, 'set names')) return $Result;

		$Backtrace = debug_backtrace();
		$SqlDriverIndex = -1;
		for ($Index = count($Backtrace) - 1; $Index >= 0; --$Index) {
			$Trace = $Backtrace[$Index];
			$Class = GetValue('class', $Trace);
			if ($SqlDriverIndex < 0 && $Class == 'Gdn_SQLDriver') $SqlDriverIndex = $Index;
			if ($Class && StringEndsWith($Class, 'Model', True)) {
				$this->GetInfo($Query, $Trace);
				break;
			}
		}
		
		if (empty($Query['Method'])) $this->GetInfo($Query, $Trace);
		
		$Query['MethodArgs'] = $Query['Method'] . '(' . Log::FormatArgs($Query['Arguments']) . ')'; 
		
		$Query['Time'] = Now() - $TimeStart;
		$this->Queries[] = $Query;
		
		return $Result;
	}
	

}