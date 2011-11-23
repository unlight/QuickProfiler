<?php if (!defined('APPLICATION')) exit();

$PluginInfo['QuickProfiler'] = array(
	'Name' => 'Quick Profiler',
	'Description' => 'Adapts PHP Quick Profiler to work with Garden. PHP Quick Profiler is a helper class that outputs debugging related information to the screen when the page has finished executing.',
	'Version' => '1.24',
	'Date' => '30 Oct 2011',
	'Updated' => 'Autumn 2011',
	'Author' => 'Infamous',
	'AuthorUrl' => 'http://www.dm-deck17.com',
	'RegisterPermissions' => array('Plugins.QuickProfiler.View')
);

$Tmp = Gdn::FactoryOverwrite(True);
Gdn::FactoryInstall(Gdn::AliasDatabase, 'QuickProfilerDatabase', dirname(__FILE__).'/class.quickprofilerdatabase.php', Gdn::FactorySingleton, array('Database'));
Gdn::FactoryOverwrite($Tmp);
unset($Tmp);

class QuickProfilerPlugin extends Gdn_Plugin {
	
	private $bConsoleRendered;
	private $bAfterBodyFired;
	
	public static function AllowView() {
		return (Debug() || CheckPermission('Plugins.QuickProfiler.View'));
	}
	
	/**
	* Dummy handler. Needed to initialize plugin by PluginManager.
	* Then __destruct can be executed.
	* 
	* @param mixed $Sender.
	* @return NULL.
	*/
	public function Base_All_Handler($Sender) {
	}
	
	public function Base_AfterBody_Handler($Sender) {
		$this->bAfterBodyFired = True;
/*		if (Debug() && !$this->bConsoleRendered) {
			Log::CheckPoint('Profiler collected all data in: %s');
			Gdn::PluginManager()->FireEvent('CollectQuickProfilerData');
			Log::CheckPoint('Profiler collected all data in: %s');
			$this->DrawConsole();
		}*/
	}
	
	public function Base_CollectQuickProfilerData_Handler($Sender) {
		if (!self::AllowView()) return;
		Log::Speed('Page loaded in: %s', Log::GetLoadTime());
		Log::Tab('Speed', Log::GetLoadTime(), 'Load Time', 'Blue');
		// Get database query time
		$Queries = Gdn::Database()->Queries();
		$Times = array_map(create_function('$Query', 'return $Query["Time"];'), $Queries);
		$DatabaseQueryTime = count($Queries) .'q' . ' in ' .Log::GetReadableTime(array_sum($Times));
		Log::Tab('Query', $DatabaseQueryTime, 'Database', 'Purple');
		// Log queries
		$Connection = Gdn::Database()->Connection();
		foreach ($Queries as $Query) {
			$PdoStatement = $Connection->Query('explain ' . $Query['Sql']);
			$Meta = array();
			if ($PdoStatement) {
				list($explain) = $PdoStatement->FetchAll(PDO::FETCH_OBJ);
				if ($explain->possible_keys == $explain->key) $explain->key = '';
				$Meta = array(
					'Possible keys' => $explain->possible_keys,
					'Key used' => $explain->key,
					'Ref' => $explain->ref,
					'Type' => $explain->type,
					'Rows' => $explain->rows,
					'Time' => Log::GetReadableTime($Query['Time']),
					'Extra' => $explain->Extra
				);
			}
			$Name = $Query['Sql'];
			$PreName = $Query['MethodArgs'] . ';';
			Log::Query($Name, $Meta, $PreName);
		}
		// Log files
		$IncludedFiles = get_included_files();
		Log::Tab('Files', sprintf('%d Files', count($IncludedFiles)), 'Included', 'IndianRed');
		$GetFileSize = create_function('$File', 'return filesize($File);');
		$TotalSize = array_sum(array_map($GetFileSize, $IncludedFiles));
		Log::Console('Files', sprintf('Included %d files', count($IncludedFiles)), Log::GetReadableSize($TotalSize));
		// Log memory
		Log::Tab('Memory', Log::GetReadableSize(memory_get_peak_usage()), 'Memory Used', 'Orange', True);
		$MemoryLimit = ini_get('memory_limit');
		Log::Memory('Total Available', $MemoryLimit, True);
		// Log cache
		if (Gdn::Cache()->ActiveEnabled()) {
			Log::Console('Data', 'Cache Revision: ' . Gdn::Cache()->GetRevision());
			Log::Console('Data', 'Permissions Revision: ' . Gdn::UserModel()->GetPermissionsIncrement());
		}
		// Log Controller's Data
		$Controller = Gdn::Controller();
		if ($Controller && isset($Controller->Data) && is_array($Controller->Data)) {
			//Log::Tab('Data', count($Controller->Data), 'Data', '', True);
			Log::Console('Data Code', $Controller->Data);
		}
	}

	protected function DrawConsole() {
		if ($this->bConsoleRendered) return;
		$this->bConsoleRendered = True;
		$Controller = Gdn::Controller();
		if ($Controller) {
			$bDrawConsole = GetIncomingValue('DeliveryType', DELIVERY_TYPE_ALL) == DELIVERY_TYPE_ALL
				&& $Controller->DeliveryMethod() == DELIVERY_METHOD_XHTML
				&& $Controller->DeliveryType() == DELIVERY_TYPE_ALL 
				&& $Controller->SyndicationMethod == SYNDICATION_NONE;
			if ($bDrawConsole) {
				$bRendered = True;
				include dirname(__FILE__) . '/views/console.php';
			}
		}
	}
	
	public function __destruct() {
		if (!self::AllowView()) return;
		if ($this->bAfterBodyFired && !$this->bConsoleRendered) {
			Log::CheckPoint('Profiler collected all data in: %s');
			Gdn::PluginManager()->FireEvent('CollectQuickProfilerData');
			Log::CheckPoint('Profiler collected all data in: %s');
			$this->DrawConsole();
		}
	}
	
	public function Setup() {
		$BootstrapBeforeFile = PATH_CONF . '/bootstrap.before.php';
		if (!file_exists($BootstrapBeforeFile)) file_put_contents($BootstrapBeforeFile, '<?php');
		
		$ClassLog = "PATH_ROOT . '/plugins/QuickProfiler/class.log.php'";
		
		$Contents = file_get_contents($BootstrapBeforeFile);
		foreach (array($Contents) as $Line) {
			if (strpos($Line, $ClassLog) !== FALSE) return; // Already added
		}
		$Contents = rtrim($Contents);
		if (substr($Contents, -2) == '?>') $Contents = substr($Contents, 0, -2);
		
		$NewLines[] = "\n";
		$NewLines[] = '// These lines were added by QuickProfiler plugin.';
		$NewLines[] = "if (file_exists($ClassLog)) require_once $ClassLog;";
		$NewLines = implode("\n", $NewLines);
		
		file_put_contents($BootstrapBeforeFile, $Contents . $NewLines);
	}
	
}


