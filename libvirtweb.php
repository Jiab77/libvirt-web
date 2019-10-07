<?php
/*
MIT License

Copyright (c) 2019 Jonathan Barda

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

// Init session
session_start();

// Prepare session data
if (!isset($_SESSION['notifications'])) {
	$_SESSION['notifications'] = new stdClass;
	$_SESSION['notifications']->info = [];
	$_SESSION['notifications']->error = [];
}

// Slightly modified version from: https://stackoverflow.com/a/20506281
function dom2xml($domNode) {
	foreach($domNode->childNodes as $node) {
		if ($node->hasChildNodes()) {
			dom2xml($node);
		}
		else {
			if ($domNode->hasAttributes() && strlen($domNode->nodeValue)) {
				$domNode->setAttribute("nodeValue", $node->textContent);
				$node->nodeValue = "";
			}
		}
	}
}

// Slightly modified version from: https://stackoverflow.com/a/20506281
function xml2json($xml, $as_array = false, $pretty_print = false) {
	// Create a new DOM document and load XML into it
	$dom = new DOMDocument();
	$dom->loadXML($xml);

	// Read and format the XML string
	dom2xml($dom);

	// Convert the XML string into an SimpleXMLElement object.
	$xmlObject = simplexml_load_string($dom->saveXML());

	// Encode the SimpleXMLElement object into a JSON string.
	if ($pretty_print === true) {
		$jsonString = str_replace(['@', '"\n"'], ['', '""'], json_encode($xmlObject, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}
	else {
		$jsonString = str_replace(['@', '"\n"'], ['', '""'], json_encode($xmlObject, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES));
	}

	// Convert it back into an associative array for the purposes of testing.
	$jsonArray = json_decode($jsonString, true);

	// Return an array or a string
	return ($as_array === true ? $jsonArray : $jsonString);
}

// Taken from my CMS project: CMS-Base
function get_loading_time() {
	return number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 4);
}

// Wrapper to 'virsh'
function virsh_exec($command, &$output, &$return_code) {
	$base_command = 'virsh';
	$run_command = escapeshellcmd($base_command . ' ' . $command);
	$redirector = '2>&1';
	exec($run_command . ' ' . $redirector, $output, $return_code);
}
function virsh_exec_notify($command, &$output, &$return_code) {
	$base_command = 'virsh';
	$run_command = escapeshellcmd($base_command . ' ' . $command);
	$redirector = '2>&1';
	exec($run_command . ' ' . $redirector, $output, $return_code);
	foreach ($output as $line) {
		if (!empty($line)) {
			if (strpos($line, 'error:') !== false) {
				array_push($_SESSION['notifications']->error, $line);
			}
			else {
				array_push($_SESSION['notifications']->info, $line);
			}
		}
	}
}
function virsh_passthru($command, &$return_code = null) {
	$base_command = 'virsh';
	$run_command = escapeshellcmd($base_command . ' ' . $command);
	$redirector = '2>&1';
	passthru($run_command . ' ' . $redirector, $return_code);
}

// Notifications
function create_notification(&$data, $timeout = 4000) {
	if (!is_array($data)) {
		echo '<script type="text/javascript">';
		echo 'Materialize.toast("' . $data . '", ' . $timeout . ', "rounded");';
		echo '</script>';
	}
	else {
		$key = 0;
		foreach ($data as $text) {
			$cleaned_text = trim($text);
			if (!empty($cleaned_text)) {
				echo '<script type="text/javascript">';
				echo 'Materialize.toast("' . $cleaned_text . '", ' . $timeout . ', "rounded");';
				echo '</script>';
			}
			unset($data[$key]);
			$key++;
		}
	}
}
function create_error_notification(&$data, $timeout = 4000) {
	if (!is_array($data)) {
		echo '<script type="text/javascript">';
		echo 'var toastContent = $(\'<span style="color: #f44336; font-weight: bold;">' . $data . '</span>\');';
		echo 'Materialize.toast(toastContent, ' . $timeout . ', "rounded");';
		echo '</script>';
	}
	else {
		$key = 0;
		foreach ($data as $text) {
			$cleaned_text = trim($text);
			if (!empty($cleaned_text)) {
				echo '<script type="text/javascript">';
				echo 'var toastContent = $(\'<span style="color: #f44336; font-weight: bold;">' . $cleaned_text . '</span>\');';
				echo 'Materialize.toast(toastContent, ' . $timeout . ', "rounded");';
				echo '</script>';
			}
			unset($data[$key]);
			$key++;
		}
	}
}

// Content extractor
function extract_data($line, $delim) {
	$line = trim($line);
	$data = explode($delim, $line);
	$data = array_filter($data);
	return $data;
}

// Content selector
if (isset($_GET['module']) && !empty($_GET['module'])) {
	$module = htmlentities(strip_tags(filter_var($_GET['module'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
	$action = htmlentities(strip_tags(filter_var($_GET['action'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
	$vm_name = htmlentities(strip_tags(filter_var($_GET['name'], FILTER_SANITIZE_STRING)));
}

// Content creator
function create_vm_screenshots($vm_name) {
	passthru(escapeshellcmd('virsh screenshot ' . $vm_name . ' --file /tmp/' . $vm_name . '_screen.ppm --screen 0') . ' 2>&1 >/dev/null');
	if (is_readable('/tmp/' . $vm_name . '_screen.ppm')) {
		passthru(escapeshellcmd('convert -quality 80 /tmp/' . $vm_name . '_screen.ppm /tmp/' . $vm_name . '_screen.png') . ' 2>&1 >/dev/null');
		if (is_readable('/tmp/' . $vm_name . '_screen.png')) {
			echo '<img class="materialboxed" src="data:image/png;base64,' . base64_encode(file_get_contents('/tmp/' . $vm_name . '_screen.png')) . '" width="80" alt="' . $vm_name . ' screenshot">';
		}
	}
	else {
		echo 'Could not create screenshot.';
	}
}
function create_table_generic_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
	$lines_to_skip = 2;
	$index = 0;
	$created_lines = 0;
	$extracted_data = [];
	foreach ($lines as $line) {
		$index++;
		if ($index > $lines_to_skip && !empty($line)) {
			$created_lines++;
			echo '<tr>' . PHP_EOL;
			$extracted_data = extract_data($line, $delim);
			foreach ($extracted_data as $data) {
				echo '<td>' . trim($data) . '</td>' . PHP_EOL;
			}
			echo '</tr>' . PHP_EOL;
		}
	}
	if (count($extracted_data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}
function create_table_active_ips_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
	global $module;

	$lines_to_skip = 2;
	$index = 0;
	$col_index = 0;
	$created_lines = 0;
	$extracted_data = [];
	$col_ip_address = 4;
	$col_hostname = 5;

	foreach ($lines as $line) {
		$index++;

		if ($index > $lines_to_skip && !empty($line)) {
			$created_lines++;

			echo '<tr>' . PHP_EOL;

			$extracted_data = extract_data($line, $delim);

			foreach ($extracted_data as $data) {
				$col_index++;
				$cleaned_data = trim($data);

				// Create ip cell
				if ($col_index === $col_ip_address) {
					// Assign vm hostname
					if ($cleaned_data !== '-' || !empty($cleaned_data)) {
						echo '<td><a href="?module=vni">' . $cleaned_data . '</a></td>' . PHP_EOL;
					}
					else {
						echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
					}
				}

				// Create hostname cell
				elseif ($col_index === $col_hostname) {
					// Assign vm hostname
					if ($cleaned_data !== '-') {
						$vm_data = explode(' ', $cleaned_data);
						$vm_hostname = $vm_data[0];
						$vm_duid = '';
						if (count($vm_data) > 1) {
							$vm_duid = $vm_data[1];
						}

						if (!empty($vm_duid)) {
							// echo '<td><a href="?module=vmi&hostname=' . $vm_hostname . '">' . $vm_hostname . '&nbsp;<i class="material-icons tooltipped" data-position="bottom" data-tooltip="' . $vm_hostname . ' | ' . $vm_duid . '">info_outline</i></a></td>' . PHP_EOL;
							// echo '<td><a href="?module=vmi&hostname=' . $vm_hostname . '">' . $vm_hostname . '</a></td>' . PHP_EOL;
							echo '<td>' . $vm_hostname . '</td>' . PHP_EOL;
							echo '<td>' . $vm_duid . '</td>' . PHP_EOL;
						}
						else {
							// echo '<td><a href="?module=vmi&hostname=' . $vm_hostname . '">' . $vm_hostname . '</a></td>' . PHP_EOL;
							echo '<td>' . $vm_hostname . '</td>' . PHP_EOL;
						}
					}
					else {
						echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
					}
				}

				// Create data cells
				else {
					echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
				}
			}

			echo '</tr>' . PHP_EOL;

			$col_index = 0; // reset col index
		}
	}
	if (count($extracted_data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}
function create_table_active_vms_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
	global $module;

	$lines_to_skip = 2;
	$index = 0;
	$col_index = 0;
	$created_lines = 0;
	$extracted_data = [];
	$col_id = 1;
	$col_name = 2;
	$col_state = 3;
	$vm_id = 0;

	foreach ($lines as $line) {
		$index++;

		if ($index > $lines_to_skip && !empty($line)) {
			$created_lines++;

			echo '<tr>' . PHP_EOL;

			$extracted_data = extract_data($line, $delim);

			foreach ($extracted_data as $data) {
				$col_index++;
				$cleaned_data = trim($data);

				// Assign vm id
				if ($col_index === $col_id) {
					if ($cleaned_data !== '-') {
						$vm_id = (int)$cleaned_data;
					}
				}

				// Assign vm name
				if ($col_index === $col_name) {
					$vm_name = $cleaned_data;
					echo '<td><a href="?module=vmi&name=' . $cleaned_data . '">' . $cleaned_data . '</a></td>' . PHP_EOL;
				}
				
				// Create actions links and screenshot cells
				elseif ($col_index === $col_state) {
					// Action links
					echo '<td>' . $cleaned_data . '&nbsp;' . PHP_EOL;
					if ($cleaned_data === 'shut off') {
						echo '<a href="?module=' . $module . '&action=start&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Start"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
					}
					else {
						echo '<a href="?module=' . $module . '&action=stop&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Stop"><i class="material-icons">stop</i></a>' . PHP_EOL;
						if ($cleaned_data === 'paused') {
							echo '<a href="?module=' . $module . '&action=resume&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Resume"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
						}
						else {
							echo '<a href="?module=' . $module . '&action=suspend&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Suspend"><i class="material-icons">pause</i></a>' . PHP_EOL;
						}
						echo '<a href="?module=' . $module . '&action=reboot&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Reboot"><i class="material-icons">replay</i></a>' . PHP_EOL;
					}
					echo '</td>' . PHP_EOL;

					// Screenshots
					echo '<td>' . PHP_EOL;
					// echo 'screenshot here' . PHP_EOL;
					create_vm_screenshots($vm_name);
					echo '</td>' . PHP_EOL;

					// Other actions
					echo '<td>' . PHP_EOL;
					echo '<a href="?module=' . $module . '&action=view&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="View"><i class="material-icons">personal_video</i></a>' . PHP_EOL;
					echo '</td>' . PHP_EOL;
				}

				// Create data cells
				else {
					echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
				}
			}

			echo '</tr>' . PHP_EOL;

			$col_index = 0; // reset col index
		}
	}
	if (count($data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}
function create_table_vms_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
	global $module;

	$lines_to_skip = 2;
	$index = 0;
	$col_index = 0;
	$created_lines = 0;
	$extracted_data = [];
	$col_id = 1;
	$col_name = 2;
	$col_state = 3;
	$vm_id = 0;

	foreach ($lines as $line) {
		$index++;

		if ($index > $lines_to_skip && !empty($line)) {
			$created_lines++;

			echo '<tr>' . PHP_EOL;

			$extracted_data = extract_data($line, $delim);

			foreach ($extracted_data as $data) {
				$col_index++;
				$cleaned_data = trim($data);

				// Assign vm name
				if ($col_index === $col_name) {
					$vm_name = $cleaned_data;
					echo '<td><a href="?module=vmi&name=' . $cleaned_data . '">' . $cleaned_data . '</a></td>' . PHP_EOL;
				}
				
				// Create action links
				elseif ($col_index === $col_state) {
					echo '<td>' . $cleaned_data . '&nbsp;' . PHP_EOL;
					if ($cleaned_data === 'shut off') {
						echo '<a href="?module=' . $module . '&action=start&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Start"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
					}
					else {
						echo '<a href="?module=' . $module . '&action=stop&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Stop"><i class="material-icons">stop</i></a>' . PHP_EOL;
						echo '<a href="?module=' . $module . '" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons">refresh</i></a>' . PHP_EOL;
					}
					echo '</td>' . PHP_EOL;
					echo '<td>' . PHP_EOL;
					echo '<a href="?module=' . $module . '&action=save&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Create snapshot"><i class="material-icons">save</i></a>' . PHP_EOL;
					echo '<a href="?module=' . $module . '&action=edit&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Edit"><i class="material-icons">edit</i></a>' . PHP_EOL;
					echo '<a href="?module=' . $module . '&action=del&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Delete"><i class="material-icons">delete</i></a>' . PHP_EOL;
					echo '</td>' . PHP_EOL;
				}

				// Create data cells
				else {
					echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
				}
			}

			echo '</tr>' . PHP_EOL;

			$col_index = 0; // reset col index
		}
	}
	if (count($data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}

// VM Control
function vm_is_active($vm_name) {
	$is_active = false;
	$vm_state = trim(shell_exec(escapeshellcmd('virsh domstate ' . $vm_name)));
	if ($vm_state === 'running' || $vm_state === 'paused') {
		$is_active = true;
	}
	return $is_active;
}

// Displayed title
$project_title = 'libVirt Web';
if (isset($module) && !empty($module)) {
	switch ($module) {
		case 'dsh': $page_title = 'Dashboard'; break;
		case 'hyp': $page_title = 'Hypervisor'; break;
		case 'vdi': $page_title = 'Volume Details'; break;
		case 'vmi': $page_title = 'VM Details'; break;
		case 'vms': $page_title = 'Virtual Machines'; break;
		case 'vni': $page_title = 'Network Details'; break;
		case 'vns': $page_title = 'Virtual Networks'; break;
		case 'vst': $page_title = 'Virtual Storage'; break;
		case 'hlp': $page_title = 'Help'; break;
		default: $page_title = ''; break;
	}

	$page_title .= (!empty($page_title) ? ' &ndash; ' . $project_title : '');
}
else {
	$page_title = $project_title;
}

// Actions
if (isset($action) && !empty($action)) {
	$output_action = '';
	switch ($action) {
		case 'reboot':
			virsh_exec_notify('reboot ' . $vm_name, $output_action, $ret_action);
			break;

		case 'resume':
			virsh_exec_notify('resume ' . $vm_name, $output_action, $ret_action);
			break;

		case 'start':
			virsh_exec_notify('start ' . $vm_name, $output_action, $ret_action);
			break;

		case 'stop':
			virsh_exec_notify('shutdown ' . $vm_name, $output_action, $ret_action);
			break;

		case 'suspend':
			virsh_exec_notify('suspend ' . $vm_name, $output_action, $ret_action);
			break;

		case 'view':
			exec('virt-viewer -v -w ' . escapeshellarg($vm_name) . ' &', $output_action, $ret_action);
			break;
		
		default:
			# code...
			break;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<!--Import Google Icon Font-->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<!--Import materialize.css-->
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css" media="screen,projection"/>

	<!--Let browser know website is optimized for mobile-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<title><?php echo $page_title; ?></title>

	<!--Custom style-->
	<style>
	body {
		display: flex;
		min-height: 100vh;
		flex-direction: column;
	}
	blockquote {
		border-left-color: #039be5;
	}
	main {
		flex: 1 0 auto;
	}
	i.material-icons {
		vertical-align: middle;
	}
	</style>
</head>

<body>
	<header>
		<div class="navbar-fixed">
			<nav class="grey darken-4 white-text">
				<div class="nav-wrapper">
					<a href="./" class="brand-logo"><i class="material-icons">developer_board</i>libVirt Web</a>
					<a href="#" data-activates="mobile-demo" class="button-collapse"><i class="material-icons">menu</i></a>
					<ul class="right hide-on-med-and-down">
						<li><a href="?module=dsh" class="tooltipped" data-position="bottom" data-tooltip="Show dashboard"><i class="material-icons left">dashboard</i>Dashboard</a></li>
						<li><a href="./" class="tooltipped" data-position="bottom" data-tooltip="Show modules"><i class="material-icons left">apps</i>Modules</a></li>
						<li><a href="#modal_help" class="tooltipped modal-trigger" data-position="bottom" data-html="true" data-tooltip="Display &lt;strong&gt;virsh&lt;/strong&gt; commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li><a href="#!" class="tooltipped display-expand" data-position="bottom" data-tooltip="Expand display"><i class="material-icons left">swap_horiz</i>Display</a></li>
					</ul>
					<ul class="side-nav" id="mobile-demo">
						<li><a href="?module=dsh" class="tooltipped" data-position="bottom" data-tooltip="Show dashboard"><i class="material-icons left">dashboard</i>Dashboard</a></li>
						<li><a href="./" class="tooltipped" data-position="bottom" data-tooltip="Show modules"><i class="material-icons left">apps</i>Modules</a></li>
						<li><a href="?module=hlp" title="Display 'virsh' commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li><a href="#!" class="display-expand" title="Expand display"><i class="material-icons left">swap_horiz</i>Display</a></li>
					</ul>
				</div>
			</nav>
		</div>
	</header>

	<main class="grey lighten-4 grey-text text-darken-3">
		<div id="variable-container" class="container">
			<div class="row">
				<div class="col s12">

					<?php if (!isset($_GET['module']) || (isset($_GET['module']) && empty($_GET['module']))): ?>

					<h1>Modules</h1>
					<div class="row">
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=dsh">
										<i class="material-icons">dashboard</i>
										<br><span class="truncate">Dashboard</span>
									</a>
								</p>
							</div>
						</div>
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=hyp">
										<i class="material-icons">cloud</i>
										<br><span class="truncate">Hypervisor</span>
									</a>
								</p>
							</div>
						</div>
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=vms">
										<i class="material-icons">computer</i>
										<br><span class="truncate">Virtual Machines</span>
									</a>
								</p>
							</div>
						</div>
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=vns">
										<i class="material-icons">router</i>
										<br><span class="truncate">Virtual Networks</span>
									</a>
								</p>
							</div>
						</div>
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=vst">
										<i class="material-icons">storage</i>
										<br><span class="truncate">Virtual Storage</span>
									</a>
								</p>
							</div>
						</div>
						<div class="col s6 m4 l3">
							<div class="card-panel hoverable">
								<p class="flow-text center-align">
									<a href="?module=hlp">
										<i class="material-icons">help_outline</i>
										<br><span class="truncate">Help</span>
									</a>
								</p>
							</div>
						</div>
					</div>

					<?php endif; ?>

					<?php
					if (isset($_GET['module']) && !empty($_GET['module'])) {
						$module = htmlentities(strip_tags(filter_var($_GET['module'], FILTER_SANITIZE_STRING)));

						switch ($module) {
							case 'dsh':
								$output_vms = ''; $output_ips = '';
								exec('virsh list', $output_vms, $ret_vms);
								exec('virsh net-dhcp-leases default', $output_ips, $ret_ips);
								?>

					<div class="row">
						<div class="col s6">
							<h3>CPU</h3>
							<pre><?php
							virsh_passthru('nodecpustats');
							?></pre>
						</div>
						<div class="col s6">
							<h3>Memory</h3>
							<pre><?php
							virsh_passthru('nodememstats');
							?></pre>
						</div>
						<div class="col s12">
							<blockquote>The data displayed above will be converted into realtime graph soon.</blockquote>
						</div>
						<div class="col s6">
							<h3>Node</h3>
							<pre><?php
							virsh_passthru('nodeinfo');
							?></pre>
						</div>
						<div class="col s6">
							<h3>Map</h3>
							<pre><?php
							virsh_passthru('nodecpumap');
							?></pre>
						</div>
					</div>
					<div class="row">
						<div class="col s12">
							<h3>Running VM's</h3>
							<table class="striped">
								<thead>
									<tr>
										<th>Id</th>
										<th>Name</th>
										<th>State</th>
										<th>Screenshot</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vms) && !empty($output_vms)) create_table_active_vms_rows($output_vms, '  ', 5, 'center-align'); ?>

								</tbody>
								<tfoot>
									<tr>
										<td colspan="3">Max instances: <?php virsh_passthru('maxvcpus'); ?></td>
									</tr>
								</tfoot>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vms);
							var_dump($ret_vms);
							?></pre> -->
							<?php if (isset($action) && !empty($action)): ?>
							<h5>Raw data</h5>
							<pre><?php
							print_r($output_action);
							var_dump($ret_action);
							?></pre>
							<?php endif; ?>
						</div>
						<div class="col s12">
							<h3>Active IP's</h3>
							<table class="striped">
								<thead>
									<tr>
										<th>Expiry Time</th>
										<th>MAC address</th>
										<th>Protocol</th>
										<th>IP address</th>
										<th>Hostname</th>
										<th>Client ID or DUID</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_ips) && !empty($output_ips)) create_table_active_ips_rows($output_ips, '  ', 6, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'hyp':
								?>

					<div class="row">
						<div class="col s12">
							<h1>Hypervisor</h1>
							<div class="row">
								<div class="col s6 m4 l3">
									<div class="card-panel hoverable">
										<p class="flow-text center-align">
											<a href="?module=dsh">
												<i class="material-icons">apps</i>
												<br><span class="truncate">Create VM</span>
											</a>
										</p>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col s12">
									<h5>Parsed data</h5>
									<pre style="height: 300px;"><?php
									print_r(xml2json(shell_exec('virsh sysinfo'), false, true));
									?></pre>
									<h5>Raw data</h5>
									<pre style="height: 300px;"><?php
									echo htmlentities(shell_exec('virsh sysinfo'));
									?></pre>
								</div>
							</div>
						</div>
					</div>

								<?php
								break;

							// TODO: Finish this part
							case 'vmi':
								$selected_vm = (isset($_GET['name']) && !empty($_GET['name']) ? $vm_name : '');
								?>

					<div class="row">
						<div class="col s12">
							<h1>VM Details</h1>
							<h3>Summary</h3>
							<pre><?php
							virsh_passthru('dominfo ' . $selected_vm);
							?></pre>
							<h3>Statistics</h3>
							<div class="row">
								<div class="col s4">
									<h5>Global</h5>
									<pre style="height: 300px;"><?php
									virsh_passthru('domstats --raw ' . $selected_vm);
									?></pre>
								</div>
								<div class="col s3">
									<h5>Memory</h5>
									<pre style="height: 300px;"><?php
									if (vm_is_active($selected_vm)) {
										virsh_passthru('dommemstat ' . $selected_vm);
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
									}
									?></pre>
								</div>
								<div class="col s5">
									<h5>CPU</h5>
									<pre style="height: 300px;"><?php
									if (vm_is_active($selected_vm)) {
										virsh_passthru('cpu-stats ' . $selected_vm);
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
									}
									?></pre>
								</div>
							</div>
							<h3>
								Virtual CPU's
								<i class="material-icons tooltipped light-blue-text text-darken-1" style="cursor: pointer;" data-position="right" data-tooltip="View CPU Stats" onclick="$('#modal-cpu-stats').modal('open');">info_outline</i>
							</h3>
							<pre><?php
							virsh_passthru('vcpucount ' . $selected_vm);
							?></pre>
							<!-- <a href="#modal-cpu-stats" class="modal-trigger"><i class="material-icons left">info_outline</i>View CPU Stats</a> -->
							<div id="modal-cpu-stats" class="modal modal-fixed-footer">
								<div class="modal-content">
									<h4>CPU Stats</h4>
									<pre><?php
									virsh_passthru('vcpuinfo ' . $selected_vm);
									?></pre>
								</div>
								<div class="modal-footer">
									<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
								</div>
							</div>
							<h3>Network Interfaces</h3>
							<pre><?php
							virsh_passthru('domiflist ' . $selected_vm);
							?></pre>
							<h3>Attached devices</h3>
							<pre><?php
							virsh_passthru('domblklist ' . $selected_vm);
							?></pre>
							<h3>Snapshots</h3>
							<pre><?php
							virsh_passthru('snapshot-list ' . $selected_vm);
							?></pre>
							<h3>Running Jobs</h3>
							<pre><?php
							if (vm_is_active($selected_vm)) {
								virsh_passthru('domjobinfo ' . $selected_vm);
							}
							else {
								echo 'VM is not running.' . PHP_EOL;
							}
							?></pre>
							<!-- <h1>domtime</h1>
							<pre><?php

							// passthru('virsh domtime ' . escapeshellarg($selected_vm) . ' 2>&1');
							// Working: false
							// Outputs: error: argument unsupported: QEMU guest agent is not configured

							?></pre> -->
							<!-- <h1>domfsinfo</h1>
							<pre><?php

							// passthru('virsh domfsinfo ' . escapeshellarg($selected_vm) . ' 2>&1');

							// Working: false
							// Outputs: error: Unable to get filesystem information
							// Outputs: error: argument unsupported: QEMU guest agent is not configured

							?></pre> -->
							<!-- <h1>domhostname</h1>
							<pre><?php

							// passthru('virsh domhostname ' . escapeshellarg($selected_vm) . ' 2>&1');

							// Working: false
							// Outputs: error: failed to get hostname
							// Outputs: error: this function is not supported by the connection driver: virDomainGetHostname
							
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'vms':
								$output_vms = ''; $output_ips = '';
								exec('virsh list --all', $output_vms, $ret_vms);
								exec('virsh net-dhcp-leases default', $output_ips, $ret_ips);
								?>

					<div class="row">
						<div class="col s12">
							<h1>Virtual Machines</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Id</th>
										<th>Name</th>
										<th>State</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vms) && !empty($output_vms)) create_table_vms_rows($output_vms, '  ', 4, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vms);
							var_dump($ret_vms);
							?></pre> -->
							<?php if (isset($action) && !empty($action)): ?>
							<h5>Raw data</h5>
							<pre><?php
							print_r($output_action);
							var_dump($ret_action);
							?></pre>
							<?php endif; ?>
						</div>
					</div>
					<div class="row">
						<div class="col s12">
							<h1>Virtual IP Addresses</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Expiry Time</th>
										<th>MAC address</th>
										<th>Protocol</th>
										<th>IP address</th>
										<th>Hostname</th>
										<th>Client ID or DUID</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_ips) && !empty($output_ips)) create_table_active_ips_rows($output_ips, '  ', 6, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'vns':
								$output_vns = ''; $output_ips = ''; $output_vnfs = '';
								exec('virsh net-list', $output_vns, $ret_vns);
								exec('virsh net-dhcp-leases default', $output_ips, $ret_ips);
								exec('virsh nwfilter-list', $output_vnfs, $ret_vnfs);
								?>

					<div class="row">
						<div class="col s12">
							<h1>Virtual Networks</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Name</th>
										<th>State</th>
										<th>Autostart</th>
										<th>Persistent</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vns) && !empty($output_vns)) create_table_generic_rows($output_vns, '  ', 4, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vns);
							var_dump($ret_vns);
							?></pre> -->
						</div>
					</div>
					<div class="row">
						<div class="col s12">
							<h1>Virtual IP Addresses</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Expiry Time</th>
										<th>MAC address</th>
										<th>Protocol</th>
										<th>IP address</th>
										<th>Hostname</th>
										<th>Client ID or DUID</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_ips) && !empty($output_ips)) create_table_active_ips_rows($output_ips, '  ', 6, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>
					<div class="row">
						<div class="col s12">
							<h1>Virtual Network Filters</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>UUID</th>
										<th>Name</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vnfs) && !empty($output_vnfs)) create_table_generic_rows($output_vnfs, '  ', 2, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vnfs);
							var_dump($ret_vnfs);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'vdi':
								$output_vsv = '';
								exec('virsh vol-list default', $output_vsv, $ret_vsv);
								?>

					<div class="row">
						<div class="col s12">
							<h1>Volume Details</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Name</th>
										<th>Path</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vsv) && !empty($output_vsv)) create_table_generic_rows($output_vsv, ' ', 2, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vsv);
							var_dump($ret_vsv);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'vst':
								$output_vsp = ''; $output_vsv = '';
								exec('virsh pool-list', $output_vsp, $ret_vsp);
								exec('virsh vol-list default', $output_vsv, $ret_vsv);
								?>

					<div class="row">
						<div class="col s12">
							<h1>Virtual Storage Pools</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Name</th>
										<th>State</th>
										<th>Autostart</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vsp) && !empty($output_vsp)) create_table_generic_rows($output_vsp, '  ', 3, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vsp);
							var_dump($ret_vsp);
							?></pre> -->
						</div>
					</div>
					<div class="row">
						<div class="col s12">
							<h1>Virtual Volumes</h1>
							<table class="striped">
								<thead>
									<tr>
										<th>Name</th>
										<th>Path</th>
									</tr>
								</thead>
								<tbody>

								<?php if (isset($output_vsv) && !empty($output_vsv)) create_table_generic_rows($output_vsv, ' ', 2, 'center-align'); ?>

								</tbody>
							</table>
							<!-- <h5>Raw data</h5>
							<pre><?php
							print_r($output_vsv);
							var_dump($ret_vsv);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							case 'hlp':
								?>

					<div class="row">
						<div class="col s12">
							<h1>Command list</h1>
							<pre><?php
							virsh_passthru('help');
							?></pre>
						</div>
					</div>

								<?php
								break;
							
							default:
								?>

					<div class="row">
						<div class="col s12">
							<h1>Invalid module</h1>
						</div>
					</div>

								<?php
								break;
						}
					}
					?>

					<?php if (isset($_GET['module'])): ?>

					<div class="row">
						<div class="col s12">
							<a href="javascript:history.back();"><i class="material-icons left">arrow_back</i> Back</a>
						</div>
					</div>

					<?php endif; ?>

				</div>
			</div>
		</div>
	</main>
	
	<footer class="page-footer grey darken-3">
		<div class="container">
			<div class="row">
				<div class="col l6 s12">
					<h5 class="white-text">libVirt Web</h5>
					<p class="grey-text text-lighten-4">A simple web interface based on <a href="https://libvirt.org/" rel="nofollow noopener noreferrer" target="_blank">libVirt</a>.</p>
					<small class="grey-text text-lighten-4"><?php echo 'Generated in ' . get_loading_time() . ' seconds'; ?></small>
				</div>
				<div class="col l4 offset-l2 s12">
					<h5 class="white-text">Links</h5>
					<ul>
						<li><a class="grey-text text-lighten-3" href="https://github.com/Jiab77/libvirt-web" rel="nofollow noopener noreferrer" target="_blank">Project</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="footer-copyright">
			<div class="container">
				<?php echo '&copy; ' . date("Y") . ' &ndash; <a href="github.com/jiab77" rel="nofollow noopener noreferrer" target="_blank">Jiab77</a>'; ?>
				<a class="grey-text text-lighten-4 right" href="gist.github.com/jiab77" rel="nofollow noopener noreferrer" target="_blank">My gists</a>
			</div>
		</div>
	</footer>

	<!--Modals-->
	<div id="modal_help" class="modal modal-fixed-footer">
		<div class="modal-content grey-text text-darken-3">
			<h4>Command list</h4>
			<pre><?php
			virsh_passthru('help');
			?></pre>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
		</div>
	</div>

	<!--Import jQuery before materialize.js-->
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>

	<!--Custom JS-->
	<script type="text/javascript">
	$(document).ready(function(){
		$('.button-collapse').sideNav();
		$('.tooltipped').tooltip({delay: 50});
		$('.materialboxed').materialbox();
		$('.modal').modal({
			dismissible: true, // Modal can be dismissed by clicking outside of the modal
			opacity: .5, // Opacity of modal background
			inDuration: 300, // Transition in duration
			outDuration: 200, // Transition out duration
			startingTop: '4%', // Starting top style attribute
			endingTop: '10%', // Ending top style attribute
			ready: function(modal, trigger) { // Callback for Modal open. Modal and trigger parameters available.
				console.group('Modal');
				console.info('Modal open.', modal, trigger);
				console.groupEnd();
			},
			complete: function(modal) { // Callback for Modal close
				console.group('Modal');
				console.info('Modal close.', modal);
				console.groupEnd();
			}
		});
		$('a[href="#!"]').on('click', function (event) {
			event.preventDefault();
		});
		$('.display-expand').on('click', function (event) {
			event.preventDefault();
			$('#variable-container').toggleClass('container');
		});
	})
	</script>

	<!--Notifications-->
	<?php
	if (isset($_SESSION['notifications']) && is_object($_SESSION['notifications'])) {
		if (count($_SESSION['notifications']->info) > 0) {
			create_notification($_SESSION['notifications']->info);
		}
		if (count($_SESSION['notifications']->error) > 0) {
			create_error_notification($_SESSION['notifications']->error);
		}
	}
	?>

</body>
</html>