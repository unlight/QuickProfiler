<?php if (!defined('APPLICATION')) exit();

$Logs = Log::GetLogs();
$Tabs = Log::GetTabs();
$ActivePanel = GetValue('QuickProfilerPanel', $_COOKIE, 'Database');
$Visible = GetValue('QuickProfilerVisible', $_COOKIE);

echo '<style type="text/css">', file_get_contents(dirname(__FILE__) . '/style.css'), '</style>';
echo '<script type="text/javascript">', file_get_contents(dirname(__FILE__) . '/functions.js'), '</script>';

?>

<div id="QuickProfiler"<?php echo ($Visible) ? '' : ' class="None"';?>>
<div class="Tabs">
<?php foreach ($Tabs as $Tab) {
	$Color = GetValue('Color', $Tab);
	printf('<div class="Tab" id="%s">', $Tab['Name']);
	echo '<var';
	if ($Color) printf(' class="%s"', $Color);
	echo '>' . $Tab['Value'] . '</var>';
	$Description = GetValue('Description', $Tab);
	echo '<strong>', $Description, '</strong>';
	echo '</div>';
}
?>
</div>
<div class="Panels">
<?php foreach ($Logs as $Name => $LogData) {
	$Class = 'Panel ' . $Name;
	if ($Name != $ActivePanel) $Class .= ' None';
	printf('<div class="%s">', $Class);
	print('<div class="Logs">');
	foreach ($LogData as $Log) {
		printf('<div class="%s">', $Log->Type);
		if ($PreName = GetValue('PreName', $Log)) printf('<b>%s</b>', $PreName);
		printf('<pre>%s</pre>', $Log->Name);
		if ($Meta = GetValue('Meta', $Log)) {
			if (!array_key_exists(0, $Meta)) $Meta = array($Meta);
			foreach ($Meta as $MetaData) {
				$String = array();
				foreach ($MetaData as $Name => $Value) if ($Value) $String[] = "$Name: <b>$Value</b>";
				if ($String) printf('<pre class="Meta">%s</pre>', implode(' · ', $String));
			}
		}
		if ($Log->Value) printf('<em>%s</em>', $Log->Value);
		print('</div>');
	}
	print('</div>');
	print('</div>');
}
?>
</div>
</div>
