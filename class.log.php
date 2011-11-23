<?php if (!defined('APPLICATION')) exit();

Log::StartTime();

final class Log {

	private static $StartTime;
	private static $Logs = array();
	private static $Tabs = array();
	protected static $AvailableColors = array(
		'Blue', 
		'LightBlue', 
		'LightGreen', 
		'LightCoral', 
		'Purple', 
		'Orange', 
		'IndianRed'
	);
	
/*	public static function __callStatic($Method, $Arguments) {
	}*/
	
	public static function GetTabs() {
		return self::$Tabs;
	}
	
	public static function FormatArgs($Args) {
		$Arguments = array();
		foreach ($Args as $Key => $Arg) {
			if (is_numeric($Arg)) $Arg = strval($Arg);
			elseif (is_object($Arg)) $Arg = '{'.get_class($Arg).'}';
			elseif (is_array($Arg)) {
				$Arg = '[' . self::FormatArgs($Arg) . ']';
			} elseif (!is_numeric($Key) && preg_match('/(=|<>|>|<|>=|<=|null)$/i', $Key, $Match)) {
				$Arg = $Key . ' ' . $Arg;
			} else $Arg = var_export($Arg, True);
			$Arguments[] = $Arg;
		}
		$Arguments = implode(', ', $Arguments);
		return $Arguments;
	}
	
	public static function Tab($Name, $Value = Null, $Description = '', $Color = '', $AddToConsole = False) {
		if (!is_array($Name)) $Name = compact('Name', 'Value', 'Color', 'Description');
		$TabName = $Name['Name'];
		$Name['Color'] = self::GetColor($Name['Color']);
		$Tab =& self::$Tabs[$TabName];
		if (empty($Tab)) $Tab = array();
		$Tab = array_merge($Tab, array_filter($Name));
		if ($AddToConsole) self::Console($TabName, $Description, $Value);
	}
	
	public static function StartTime() {
		if (self::$StartTime) return;
		self::$StartTime = microtime(True);
	}
	
	public static function GetLoadTime() {
		return self::GetReadableTime(self::GetTime());
	}
	
	public static function GetColor($Name = '', $bRemoveFromAvailable = True) {
		if (!$Name) {
			$Index = array_rand(self::$AvailableColors);
			if ($bRemoveFromAvailable) $bRemoveFromAvailable = False;
		} else {
			$Index = array_search($Name, self::$AvailableColors);
			if ($Index === False) throw new Exception("Color '$Name' not found in list or non available.");
		}
		$Result = self::$AvailableColors[$Index];
		if ($bRemoveFromAvailable) unset(self::$AvailableColors[$Index]);
		return $Result;
	}
	
	public static function GetLogs() {
		$Result = self::$Logs;
		$GetLogNameFunction = create_function('$A', 'return GetValue(0, explode(" ", $A));');
		$LogNames = array_map($GetLogNameFunction, array_keys($Result));
		$LogNames = array_unique($LogNames);
		foreach ($LogNames as $Type) {
			if (array_key_exists($Type, self::$Tabs)) continue;
			if ($Type == 'Console') $Color = 'Green';
			else $Color = self::GetColor();
			self::$Tabs[$Type] = array(
				'Name' => $Type,
				'Color' => $Color,
				'Value' => count(self::$Logs[$Type]),
				'Description' => $Type
			);
		}
		//d(self::$Tabs);
		return $Result;
	}
	
	public static function GetTime() {
		if (!self::$StartTime) return False;
		return (microtime(True) - self::$StartTime);
	}
	
	public static function Dump() {
		$Arguments = func_get_args();
		foreach ($Arguments as $Arg) {
			$VarDump = self::VarDump($Arg);
			$Item = new StdClass();
			$Item->Name = $VarDump;
			$Item->Type = 'Dump Code';
			$Item->Value = False;
			self::$Logs['Console'][] = $Item;
			self::$Logs['Dump'][] = $Item;
		}
	}
	
	/**
	* Undocumented 
	* Fixed function d() from UsefulFunctions plugin.
	* 
	* @param mixed $Var.
	* @return string $Result.
	*/
	public static function VarDump($Var) {
		ob_start();
		var_dump($Var);
		$String = ob_get_contents();
		ob_end_clean();
		$String = preg_replace("/\=\>\n +/s", '=> ', $String);
		$String = htmlspecialchars($String, ENT_NOQUOTES, 'utf-8', False);
		return $String;
	}
	
	public static function Memory($Message, $Object = False, $ObjectIsValue = False) {
		$Item = new StdClass();
		if ($ObjectIsValue) $Item->Value = $Object;
		elseif (is_object($Object) || is_array($Object)) {
			$Item->Value = self::GetReadableSize(strlen(serialize($Object)));
			$Message = gettype($Object) . ': ' . $Message;
		} else $Item->Value = False;
		$Item->Name = $Message;
		$Item->Type = 'Memory';
		self::$Logs['Console'][] = $Item;
		self::$Logs['Memory'][] = $Item;
	}
	
	private static $CheckPoints = array();
	
	public static function CheckPoint($Message) {
		$Data =& self::$CheckPoints;
		if (isset($Data[$Message])) {
			$Message = call_user_func_array('self::Message', func_get_args());
			$Item = new StdClass();
			$Item->Value = self::GetReadableTime(Now() - $Data[$Message]);
			if (strpos($Message, '%s') !== False) $Message = sprintf($Message, $Item->Value);
			$Item->Name = $Message;
			$Item->Type = 'CheckPoint';
			self::$Logs['Console'][] = $Item;
			self::$Logs['Speed'][] = $Item;
		} else {
			$Data[$Message] = Now();
		}
	}
	
	public static function Message() {
		$Args = func_get_args();
		$Message =& $Args[0];
		$Count = substr_count($Message, '%');
		if ($Count != count($Args) - 1) $Message = str_replace('%', '%%', $Message);
		$Message = call_user_func_array('sprintf', $Args);
		return $Message;
	}
	
	/**
	* Set raw item $Item to console log colle.
	* 
	* @param object $Item.
	*/
	public static function Item($Item) {
		if (!is_object($Item)) $Item = (object) $Item;
		$Types = explode(' ', $Item->Type);
		if (in_array('Code', $Types)) if (!is_string($Item->Name)) $Item->Name = self::VarDump($Item->Name);
		self::$Logs['Console'][] = $Item;
		self::$Logs[$Types[0]][] = $Item;
	}
	
	public static function Console($Type, $Name, $Value = False) {
		$Item = new StdClass();
		$Types = explode(' ', $Type);
		if (in_array('Code', $Types)) {
			if (!is_string($Name)) $Name = self::VarDump($Name);
		}
		$Item->Name = $Name;
		$Item->Type = $Type;
		$Item->Value = $Value;
		self::$Logs['Console'][] = $Item;
		$LogName = $Types[0];
		self::$Logs[$LogName][] = $Item;
	}
	
	public static function Speed($Name = 'Point in time') {
		$Name = call_user_func_array('self::Message', func_get_args());
		$Item = new StdClass();
		$Item->Name = $Name;
		$Item->Type = 'Speed';
		$Item->Value = self::GetReadableTime(self::GetTime());
		self::$Logs['Console'][] = $Item;
		self::$Logs['Speed'][] = $Item;
	}
	
	public static function Query($Sql, $Meta = False, $Pre = False) {
		$Item = new StdClass();
		$Item->Name = $Sql;
		$Item->Type = 'Query';
		$Item->Value = False;
		$Item->Meta = $Meta;
		$Item->PreName = $Pre;
		self::$Logs['Console'][] = $Item;
		self::$Logs['Query'][] = $Item;
	}
	
	public static function GetReadableTime($Time) {
		$Format = 's';
		if ($Time < 0.001) {
			$Time = $Time * 1000;
			$Format = 'ms';
		}
		$Result = number_format($Time, 3, '.', '') . ' ' . $Format;
		return $Result;
	}
	
	public function GetReadableSize($Size, $Format = null) {
		// adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
		$Sizes = array('bytes', 'kB', 'MB', 'GB', 'TB');
		if ($Format === Null) $Format = '%01.2f %s';
		$LastSizeString = end($Sizes);
		foreach ($Sizes as $SizeString) {
			if ($Size < 1024) break;
			if ($SizeString != $LastSizeString) $Size /= 1024;
		}
		if ($SizeString == $Size[0]) $Format = '%01d %s';
		return sprintf($Format, $Size, $SizeString);
	}
}

