<?php
/**
 * Background Excel Parser Script
 * Run via CLI: php parse_excel_bg.php <file_path>
 * 
 * Parses Excel file and saves each sheet as individual JSON file.
 * Creates .status file to signal completion to the web app.
 */

set_time_limit(0);
ini_set('memory_limit', '2048M');

$file_path = $argv[1] ?? null;
if (!$file_path || !file_exists($file_path)) {
	$status_path = ($file_path ?: '/tmp/unknown') . '.status';
	file_put_contents($status_path, json_encode(['status' => 'error', 'error' => 'File not found: ' . $file_path]));
	exit(1);
}

$status_file = $file_path . '.status';

// Write initial status
file_put_contents($status_file, json_encode([
	'status' => 'parsing',
	'started_at' => time(),
	'message' => 'Memulai parsing file Excel...'
]));

// Use absolute path for third_party (passed as argv[2] or auto-detect)
$app_path = $argv[2] ?? dirname(__DIR__) . DIRECTORY_SEPARATOR;

try {
	$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

	if ($file_ext === 'xlsx') {
		require_once $app_path . 'third_party/SimpleXLSX.php';
		$xls = \Shuchkin\SimpleXLSX::parse($file_path);
		if (!$xls) {
			throw new Exception('Failed to parse XLSX: ' . \Shuchkin\SimpleXLSX::parseError());
		}
	} else {
		require_once $app_path . 'third_party/SimpleXLS.php';
		$xls = \Shuchkin\SimpleXLS::parse($file_path);
		if (!$xls) {
			throw new Exception('Failed to parse XLS: ' . \Shuchkin\SimpleXLS::parseError());
		}
	}

	file_put_contents($status_file, json_encode([
		'status' => 'extracting',
		'message' => 'File berhasil diparsing, mengekstrak sheet...'
	]));

	$sheets = $xls->sheetNames();

	// Save sheet names
	file_put_contents($file_path . '.sheets.json', json_encode($sheets));

	// Extract each sheet as individual JSON + preview files
	$jan_name_map = [];
	foreach ($sheets as $index => $name) {
		file_put_contents($status_file, json_encode([
			'status' => 'extracting',
			'message' => 'Sheet ' . ($index + 1) . '/' . count($sheets) . ': ' . $name
		]));

		$rows = $xls->rows($index);
		file_put_contents($file_path . '.sheet.' . strtoupper($name) . '.json', json_encode($rows));

		// Save lightweight preview (first 50 data rows + headers)
		$preview_rows = array_slice($rows, 0, 53); // 3 header rows + 50 data rows
		file_put_contents($file_path . '.sheet.' . strtoupper($name) . '.preview.json', json_encode($preview_rows));

		// Build JAN name map for cross-sheet name lookup
		if (strtoupper($name) === 'JAN') {
			for ($r = 3; $r < count($rows); $r++) {
				$jan_no_tab = intval($rows[$r][2] ?? 0);
				$jan_nama = trim($rows[$r][1] ?? '');
				$jan_alamat = trim($rows[$r][3] ?? '-');
				if ($jan_no_tab > 0 && !empty($jan_nama)) {
					$jan_name_map[$jan_no_tab] = [
						'nama' => $jan_nama,
						'alamat' => $jan_alamat
					];
				}
			}
		}
	}

	// Save JAN name map separately (tiny file, used by preview)
	file_put_contents($file_path . '.jan_names.json', json_encode($jan_name_map));

	// Done
	file_put_contents($status_file, json_encode([
		'status' => 'done',
		'sheets' => $sheets,
		'message' => 'Selesai!'
	]));

} catch (Exception $e) {
	file_put_contents($status_file, json_encode([
		'status' => 'error',
		'error' => $e->getMessage()
	]));
	exit(1);
}
