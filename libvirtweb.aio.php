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
session_start([
	'name' => 'libvirt-session',
	'cookie_httponly' => '1',
	'use_strict_mode' => '1'
]);

// Session security
if (session_status() === PHP_SESSION_ACTIVE) {
	// Regenerate session id to avoid session fixation attacks
	session_regenerate_id(false);
}

// Prepare session data
if (!isset($_SESSION['notifications'])) {
	$_SESSION['notifications'] = new stdClass;
	$_SESSION['notifications']->info = [];
	$_SESSION['notifications']->error = [];
}

// Call the 'PPM' Image reader class
// Class code is taken from: https://www.webdecker.de/artikel/items/php-ppm-image-file-reader.html
require_once __DIR__ . '/libs/PpmImageReader.php';

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
function secure_password($pass) {
	if ($pass) {
		return base64_encode(str_rot13($pass));
	}
	return false;
}
function restore_password($pass) {
	if ($pass) {
		return base64_decode(str_rot13($pass));
	}
	return false;
}

// Wrapper to the 'PPM' Image reader class
// Class code is taken from: https://www.webdecker.de/artikel/items/php-ppm-image-file-reader.html
function ppm_to_png($image, $quality = 100, $output = null) {
	$ppm = new PpmImageReader();

	// quality to compression level
	// see php doc and zlib manual
	if ($quality === 100) {
		$compression = 0;
	}
	else {
		$compression = intval(($quality/100*10));
	}

	if ($ppm->canRead($image) === true) {
		// Get ppm image data as array
		// [type, image resource, width, height, dpi x, dpi y]
		$ppm_data = $ppm->read($image);

		if (!is_null($output)) {
			imagepng($ppm_data[1], $output, $compression);
		}
		else {
			return $ppm_data;
		}
	}
	return false;
}
function ppm_to_jpg($image, $quality = 100, $output = null) {
	$ppm = new PpmImageReader();
	if ($ppm->canRead($image) === true) {
		// Get ppm image data as array
		// [type, image resource, width, height, dpi x, dpi y]
		$ppm_data = $ppm->read($image);

		if (!is_null($output)) {
			imagejpeg($ppm_data[1], $output, $quality);
		}
		else {
			return $ppm_data;
		}
	}
	return false;
}

// Wrapper to 'virsh'
function virsh_shell_exec($command) {
	$base_command = 'virsh';
	$run_command = escapeshellcmd($base_command . ' ' . $command);
	$redirector = '2>&1';
	return trim(shell_exec($run_command . ' ' . $redirector));
}
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
				add_notification($line, true);
			}
			else {
				add_notification($line);
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
function connect($uri = 'qemu:///system') {
	$output = null;
	$return_code = null;
	virsh_exec('connect --name ' . $uri, $output, $return_code);
	if ($return_code !== 0) {
		$_SESSION['connected'] = false;
		add_notification('Could not connect to the hypervisor.', true);
	}
	else {
		$_SESSION['connected'] = true;
		add_notification('Connected to the hypervisor.');
		add_notification('Connection URI: ' . $uri);
	}
	return $_SESSION['connected'];
}

// Notifications
function add_notification($data, $error = false) {
	if ($error === false) {
		$add_status = array_push($_SESSION['notifications']->info, $data);
	}
	else {
		$add_status = array_push($_SESSION['notifications']->error, $data);
	}
	return $add_status;
}
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
	$_SESSION['module'] = htmlentities(strip_tags(filter_var($_GET['module'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
	$_SESSION['action'] = htmlentities(strip_tags(filter_var($_GET['action'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
	$_SESSION['vm_name'] = htmlentities(strip_tags(filter_var($_GET['name'], FILTER_SANITIZE_STRING)));
}

// Content creator
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
	$lines_to_skip = 2;
	$index = 0;
	$col_index = 0;
	$created_lines = 0;
	$extracted_data = [];
	$col_mac_address = 2;
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

				// Create mac cell
				if ($col_index === $col_mac_address) {
					// Assign vm hostname
					if ($cleaned_data !== '-' || !empty($cleaned_data)) {
						echo '<td><a href="?module=vni&mac=' . $cleaned_data . '">' . $cleaned_data . '</a></td>' . PHP_EOL;
					}
					else {
						echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
					}
					// echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
				}

				// Create ip cell
				elseif ($col_index === $col_ip_address) {
					/* // Assign vm hostname
					if ($cleaned_data !== '-' || !empty($cleaned_data)) {
						echo '<td><a href="?module=vni">' . $cleaned_data . '</a></td>' . PHP_EOL;
					}
					else {
						echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
					} */
					echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
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
						echo '<a href="?module=' . $_SESSION['module'] . '&action=start&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Start"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
					}
					else {
						echo '<a href="?module=' . $_SESSION['module'] . '&action=stop&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Stop"><i class="material-icons">stop</i></a>' . PHP_EOL;
						if ($cleaned_data === 'paused') {
							echo '<a href="?module=' . $_SESSION['module'] . '&action=resume&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Resume"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
						}
						else {
							echo '<a href="?module=' . $_SESSION['module'] . '&action=suspend&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Suspend"><i class="material-icons">pause</i></a>' . PHP_EOL;
						}
						echo '<a href="?module=' . $_SESSION['module'] . '&action=reboot&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Reboot"><i class="material-icons">replay</i></a>' . PHP_EOL;
					}
					echo '</td>' . PHP_EOL;

					// Screenshots
					echo '<td>' . PHP_EOL;
					// echo 'screenshot here' . PHP_EOL;
					create_vm_screenshots($vm_name);
					echo '</td>' . PHP_EOL;

					// Other actions
					echo '<td>' . PHP_EOL;
					echo '<a href="?module=' . $_SESSION['module'] . '&action=view&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="View"><i class="material-icons">personal_video</i></a>' . PHP_EOL;
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
	if (count($extracted_data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}
function create_table_vms_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
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
						echo '<a href="?module=' . $_SESSION['module'] . '&action=start&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Start"><i class="material-icons">play_arrow</i></a>' . PHP_EOL;
					}
					else {
						echo '<a href="?module=' . $_SESSION['module'] . '&action=stop&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Stop"><i class="material-icons">stop</i></a>' . PHP_EOL;
						echo '<a href="?module=' . $_SESSION['module'] . '" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons">refresh</i></a>' . PHP_EOL;
					}
					echo '</td>' . PHP_EOL;
					echo '<td>' . PHP_EOL;
					echo '<a href="?module=' . $_SESSION['module'] . '&action=save&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Create snapshot"><i class="material-icons">save</i></a>' . PHP_EOL;
					echo '<a href="?module=' . $_SESSION['module'] . '&action=edit&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Edit"><i class="material-icons">edit</i></a>' . PHP_EOL;
					echo '<a href="?module=' . $_SESSION['module'] . '&action=del&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Delete"><i class="material-icons">delete</i></a>' . PHP_EOL;
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
	if (count($extracted_data) === 0) {
		echo '<tr><td' . ($cols > 0 ? ' colspan="' . $cols . '"' : '') . ($cls !== '' ? ' class="' . $cls . '"' : '') . '>No data</td></td>' . PHP_EOL;
	}
}

// VM Control
function vm_is_active($vm_name) {
	$is_active = false;
	$vm_state = virsh_shell_exec('domstate ' . $vm_name);
	if ($vm_state === 'running' || $vm_state === 'paused') {
		$is_active = true;
	}
	return $is_active;
}
function create_vm_screenshots($vm_name) {
	$input_image  = '/tmp/' . $vm_name . '_screen.ppm';
	$output_image = '/tmp/' . $vm_name . '_screen.png';

	// Create the screenshot from 'virsh'
	passthru(escapeshellcmd('virsh screenshot ' . $vm_name . ' --file ' . $input_image . ' --screen 0') . ' 2>&1 >/dev/null');

	// Convert the created screenshot to PNG
	if (is_readable($input_image)) {
		// Old code that used ImageMagick 'convert' command to convert PPM images to PNG
		// This code is slower than the new PHP implementation
		/* passthru(escapeshellcmd('convert -quality 80 /tmp/' . $vm_name . '_screen.ppm /tmp/' . $vm_name . '_screen.png') . ' 2>&1 >/dev/null');
		if (is_readable('/tmp/' . $vm_name . '_screen.png')) {
			echo '<img class="materialboxed" src="data:image/png;base64,' . base64_encode(file_get_contents('/tmp/' . $vm_name . '_screen.png')) . '" width="80" alt="' . $vm_name . ' screenshot">';
		} */

		// In case of issues, I might move on the 'netpbm' package
		// and the 'pnmtopng' command to convert the PPM images to PNG

		// Convert the PPM image to PNG using pure PHP implementation
		ppm_to_png($input_image, 80, $output_image);

		// Output the converted image as Data-URI (base64)
		if (is_readable($output_image)) {
			echo '<img class="materialboxed" src="data:image/png;base64,' . base64_encode(file_get_contents($output_image)) . '" width="80" alt="' . $vm_name . ' screenshot">';
		}
	}
	else {
		echo 'Could not read screenshot.';
	}
}
function parse_vm_stats($vm_name, $as_json = false, $pretty_print = false) {
	$lines_to_skip = 1;
	$cmd_output = '';
	$cmd_return = '';
	$extracted_data = [];
	$data_header = [];

	// Get vm raw data
	virsh_exec('domstats --raw ' . $vm_name, $cmd_output, $cmd_return);

	if ($cmd_return === 0) {
		// Internal counters
		$index = 0;
		$processed_lines = 0;

		// Create data header
		foreach ($cmd_output as $line) {
			$index++;
			if ($index > $lines_to_skip && !empty($line)) {
				$processed_lines++;
				$parsed_line_array = extract_data($line, '.');
				array_push($data_header, $parsed_line_array[0]);
			}
		}

		// Clean up data header
		$data_header = array_unique($data_header);

		// Merge headers with main array
		array_merge($extracted_data, $data_header);

		// Reset internal counters
		$index = 0;
		$processed_lines = 0;

		// Parse raw vm data
		foreach ($cmd_output as $line) {
			$index++;
			if ($index > $lines_to_skip && !empty($line)) {
				$processed_lines++;
				$parsed_line_headers = extract_data($line, '.');
				$parsed_line_array = extract_data($line, '=');
				if (count($parsed_line_array) === 2) {
					$parsed_assoc_array = ['name' => $parsed_line_array[0], 'value' => $parsed_line_array[1]];
					$extracted_data[$parsed_line_headers[0]][] = $parsed_assoc_array;
				}
			}
		}

		// Output as array or json
		if ($as_json === true) {
			if ($pretty_print === true) {
				$json = json_encode($extracted_data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
			else {
				$json = json_encode($extracted_data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
			}
			return $json;
		}
		else {
			return $extracted_data;
		}
	}
	return false;
}
function get_vm_cpu_stats($vm_name, $as_json = false, $pretty_print = false) {
	// Collect data from raw vm stats
	if ($parsed_data = parse_vm_stats($vm_name)) {
		$cpu_stats = [];

		// Clean up vpu data
		foreach ($parsed_data['vcpu'] as $vcpu) {
			$vcpu_entry_name = explode('.', $vcpu['name']);
			if (count($vcpu_entry_name) === 2) {
				$vcpu_data = ['name' => $vcpu_entry_name[1], 'value' => (is_numeric($vcpu['value']) ? (int)$vcpu['value'] : $vcpu['value'])];
			}
			else {
				$vcpu_data = ['name' => $vcpu['name'], 'value' => (is_numeric($vcpu['value']) ? (int)$vcpu['value'] : $vcpu['value'])];
			}
			array_push($cpu_stats, $vcpu_data);
		}

		// Output as array or json
		if ($as_json === true) {
			if ($pretty_print === true) {
				$json = json_encode($cpu_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
			else {
				$json = json_encode($cpu_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
			}
			return $json;
		}
		else {
			return $cpu_stats;
		}
	}
	return false;
}
function get_vm_mem_stats($vm_name, $as_json = false, $pretty_print = false) {
	// Collect data from raw vm stats
	if ($parsed_data = parse_vm_stats($vm_name)) {
		$mem_stats = [];

		// Clean up mem data
		foreach ($parsed_data['balloon'] as $balloon) {
			$balloon_entry_name = explode('.', $balloon['name']);
			if (count($balloon_entry_name) === 2) {
				$balloon_data = ['name' => $balloon_entry_name[1], 'value' => (is_numeric($balloon['value']) ? (int)$balloon['value'] : $balloon['value'])];
			}
			else {
				$balloon_data = ['name' => $balloon['name'], 'value' => (is_numeric($balloon['value']) ? (int)$balloon['value'] : $balloon['value'])];
			}
			array_push($mem_stats, $balloon_data);
		}

		// Output as array or json
		if ($as_json === true) {
			if ($pretty_print === true) {
				$json = json_encode($mem_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
			else {
				$json = json_encode($mem_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
			}
			return $json;
		}
		else {
			return $mem_stats;
		}
	}
	return false;
}
function get_vm_net_stats($vm_name, $as_json = false, $pretty_print = false) {
	// Collect data from raw vm stats
	if ($parsed_data = parse_vm_stats($vm_name)) {
		$net_stats = [];

		// Clean up mem data
		foreach ($parsed_data['net'] as $net) {
			$net_entry_name = explode('.', $net['name']);
			if (count($net_entry_name) === 2) {
				$net_data = ['name' => $net_entry_name[1], 'value' => (is_numeric($net['value']) ? (int)$net['value'] : $net['value'])];
			}
			else {
				$net_data = ['name' => $net['name'], 'value' => (is_numeric($net['value']) ? (int)$net['value'] : $net['value'])];
			}
			array_push($net_stats, $net_data);
		}

		// Output as array or json
		if ($as_json === true) {
			if ($pretty_print === true) {
				$json = json_encode($net_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
			else {
				$json = json_encode($net_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
			}
			return $json;
		}
		else {
			return $net_stats;
		}
	}
	return false;
}
function get_vm_networks() {
	$output = null; $return_code = null;
	$lines_to_skip = 2;
	$index = 0;
	$extracted_data = [];
	$vm_networks = [];

	virsh_exec('net-list', $output, $return_code);

	foreach ($output as $line) {
		$index++;
		if ($index > $lines_to_skip && !empty($line)) {
			$extracted_data = extract_data($line, ' ');
			array_push($vm_networks, $extracted_data[0]);
		}
	}

	return (count($vm_networks) > 0 ? $vm_networks : false);
}

// Interface config
if (!isset($_SESSION['vm_networks']) ||
	(isset($_SESSION['vm_networks']) && !is_array($_SESSION['vm_networks']))) {
		$_SESSION['vm_networks'] = get_vm_networks();
}
if (isset($_SESSION) && is_array($_SESSION)) {
	echo '<!-- ' . PHP_EOL;
	// var_dump($_SESSION);
	print_r($_SESSION);
	echo ' -->' . PHP_EOL;
}

// Displayed title
$project_title = 'libVirt Web';
if (isset($_SESSION['module']) && !empty($_SESSION['module'])) {
	switch ($_SESSION['module']) {
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
if (isset($_SESSION['action']) && !empty($_SESSION['action'])) {
	$output_action = '';
	switch ($_SESSION['action']) {
		case 'reboot':
			$action_cmd = 'reboot ' . $_SESSION['vm_name'];
			virsh_exec_notify($action_cmd, $output_action, $ret_action);
			break;

		case 'resume':
			$action_cmd = 'resume ' . $_SESSION['vm_name'];
			virsh_exec_notify($action_cmd, $output_action, $ret_action);
			break;

		case 'save':
			$action_cmd  = 'snapshot-create-as --domain ' . $_SESSION['vm_name'];
			$action_cmd .= ' --name "' . (vm_is_active($_SESSION['vm_name']) ? 'live' : 'offline') . '-snapshot-' . date("dmYHis") . '"';
			$action_cmd .= ' --description "' . (vm_is_active($_SESSION['vm_name']) ? 'Live' : 'Offline') . ' snapshot taken on ' . date("d/m/Y H:i:s") . '"';
			virsh_exec_notify($action_cmd, $output_action, $ret_action);

			// Create a 'disk-only' snapshot in case of error
			if ($ret_action !== 0) {
				$action_cmd  = 'snapshot-create-as --domain ' . $_SESSION['vm_name'];
				$action_cmd .= ' --name "' . (vm_is_active($_SESSION['vm_name']) ? 'live' : 'offline') . '-disk-only-snapshot-' . date("dmYHis") . '"';
				$action_cmd .= ' --description "' . (vm_is_active($_SESSION['vm_name']) ? 'Live' : 'Offline') . ' disk-only snapshot taken on ' . date("d/m/Y H:i:s") . '"';
				$action_cmd .= ' --quiesce';
				$action_cmd .= ' --disk-only';
				virsh_exec_notify($action_cmd, $output_action, $ret_action);
			}
			break;

		case 'del':
			$action_cmd  = 'undefine --domain ' . $_SESSION['vm_name'];
			/* $action_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
			$action_cmd .= ' --snapshots-metadata --nvram'; */

			// Disabled for now, it requires much more debug to be in place right now...
			// virsh_exec_notify($action_cmd, $output_action, $ret_action);

			// Retry to delete using another way in case of error
			/* if ($ret_action !== 0) {
				$action_cmd  = 'undefine --domain ' . $_SESSION['vm_name'];
				$action_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
				$action_cmd .= ' --snapshots-metadata --nvram';
				virsh_exec_notify($action_cmd, $output_action, $ret_action);
			} */
			break;

		case 'start':
			$action_cmd = 'start ' . $_SESSION['vm_name'];
			virsh_exec_notify($action_cmd, $output_action, $ret_action);
			break;

		case 'stop':
			$action_cmd = 'shutdown ' . $_SESSION['vm_name'];
			virsh_exec_notify($action_cmd, $output_action, $ret_action);
			break;

		case 'suspend':
			$action_cmd = 'suspend ' . $_SESSION['vm_name'];
			virsh_exec_notify($action_cmd, $output_action, $ret_action);
			break;

		case 'view':
			$action_cmd = 'virt-viewer -v -w ' . escapeshellarg($_SESSION['vm_name']) . ' &';
			exec($action_cmd, $output_action, $ret_action);
			break;

		default:
			# code...
			break;
	}
}

// File upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_FILES['upload_file'])) {
		echo print_r($_FILES['upload_file'], true);
		if (move_uploaded_file($_FILES['upload_file']['tmp_name'], sys_get_temp_dir() . '/' . $_FILES['upload_file']['name'])){
			echo $_FILES['upload_file']['name']. " OK";
		}
		else {
			echo $_FILES['upload_file']['name']. " KO";
		}
	}
	else {
		echo print_r($_FILES['upload_file'], true);
		echo 'No files uploaded...' . PHP_EOL;
	}
	exit;
}

// Ajax data
function send_json($data, $formatted = false) {
	header('Content-Type: application/json');
	if ($formatted === true) {
		echo json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
	}
	else {
		echo json_encode($data);
	}
}
if (isset($_REQUEST['module']) && $_REQUEST['module'] === 'ajx') {
	if (isset($_REQUEST['data']) && !empty($_REQUEST['data'])) {
		$ajax_data = htmlentities(strip_tags(filter_var($_REQUEST['data'], FILTER_SANITIZE_STRING)));

		switch ($ajax_data) {
			case 'cpu':
				$cmd_output = virsh_shell_exec('nodecpustats');
				break;

			case 'mem':
				$cmd_output = virsh_shell_exec('nodememstats');
				break;

			case 'node':
				$cmd_output = virsh_shell_exec('nodeinfo');
				break;

			case 'vhostcpu':
				if (vm_is_active($_SESSION['vm_name'])) {
					$cmd_output = virsh_shell_exec('cpu-stats ' . $_SESSION['vm_name']);
				}
				else {
					$cmd_output = 'VM is not running.';
				}
				break;

			case 'vcpu':
				if (vm_is_active($_SESSION['vm_name'])) {
					$cmd_output = print_r(get_vm_cpu_stats($_SESSION['vm_name']), true);
				}
				else {
					// $cmd_output = 'VM is not running.';
					$cmd_output  = 'VM is not running.' . PHP_EOL;
					$cmd_output .= print_r(get_vm_cpu_stats($_SESSION['vm_name']), true);
				}
				break;

			case 'vmem':
				if (vm_is_active($_SESSION['vm_name'])) {
					// $cmd_output = virsh_shell_exec('dommemstat ' . $_SESSION['vm_name']);
					$cmd_output = print_r(get_vm_mem_stats($_SESSION['vm_name']), true);
				}
				else {
					// $cmd_output = 'VM is not running.';
					$cmd_output  = 'VM is not running.' . PHP_EOL;
					$cmd_output .= print_r(get_vm_mem_stats($_SESSION['vm_name']), true);
				}
				break;

			case 'vnet':
				if (vm_is_active($_SESSION['vm_name'])) {
					$cmd_output = print_r(get_vm_net_stats($_SESSION['vm_name']), true);
				}
				else {
					$cmd_output = 'VM is not running.';
				}
				break;

			case 'vhost':
				$cmd_output = virsh_shell_exec('domstats --raw ' . $_SESSION['vm_name']);
				break;
		}

		// Prepare ajax response
		$ajax_response = new stdClass;
		$ajax_response->html  = '';
		$ajax_response->html .= $cmd_output;
		$ajax_response->success = (!is_null($cmd_output) ? true : false);

		// Send ajax response as JSON
		send_json($ajax_response, true);

		// Stop processing
		exit;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<!-- Import Google Icon Font -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<!-- Import materialize.css -->
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css" media="screen,projection"/>

	<!-- Let browser know website is optimized for mobile -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<title><?php echo $page_title; ?></title>

	<!-- Custom style -->
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
	.dropdown-content li>a, .dropdown-content li>span {
		color: #039be5;
	}
	#sidenav-overlay {
		z-index: 996;
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
						<li><a href="#modal-help" class="tooltipped modal-trigger" data-position="bottom" data-html="true" data-tooltip="Display &lt;strong&gt;virsh&lt;/strong&gt; commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li><a href="#!" class="dropdown-button" data-activates="settings-dropdown" data-hover="false" data-alignment="right" data-belowOrigin="true"><i class="material-icons left">settings</i>Settings<i class="material-icons right">arrow_drop_down</i></a></li>
					</ul>
					<ul id="settings-dropdown" class="dropdown-content">
						<li><a href="#!" class="display-expand"><i class="material-icons left">swap_horiz</i>Expand display</a></li>
						<li><a href="#!"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
						<li class="divider"></li>
						<li><a href="#!">Other</a></li>
					</ul>
					<ul class="side-nav" id="mobile-demo">
						<li><a href="?module=dsh" class="tooltipped" data-position="bottom" data-tooltip="Show dashboard"><i class="material-icons left">dashboard</i>Dashboard</a></li>
						<li><a href="./" class="tooltipped" data-position="bottom" data-tooltip="Show modules"><i class="material-icons left">apps</i>Modules</a></li>
						<li><a href="?module=hlp" title="Display 'virsh' commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li class="no-padding">
							<ul class="collapsible collapsible-accordion">
								<li>
									<a class="collapsible-header"><i class="material-icons left" style="margin-left: 16px;">settings</i>Settings<i class="material-icons right">arrow_drop_down</i></a>
									<div class="collapsible-body">
										<ul>
											<li><a href="#!" class="display-expand"><i class="material-icons left">swap_horiz</i>Expand display</a></li>
											<li><a href="#!"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
											<li><a href="#!">Other</a></li>
										</ul>
									</div>
								</li>
							</ul>
						</li>
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
						switch ($_SESSION['module']) {
							// TODO: Should be rewritten to handle all networks
							case 'dsh':
								$output_vms = ''; $output_ips = '';
								exec('virsh list', $output_vms, $ret_vms);
								exec('virsh net-dhcp-leases default', $output_ips, $ret_ips);
								?>

					<div class="row">
						<div class="col s6">
							<h3>CPU</h3>
							<pre id="cpu-stats"><?php
							virsh_passthru('nodecpustats');
							?></pre>
						</div>
						<div class="col s6">
							<h3>Memory</h3>
							<pre id="mem-stats"><?php
							virsh_passthru('nodememstats');
							?></pre>
						</div>
						<div class="col s12">
							<blockquote>The data displayed above will be converted into realtime graph soon.</blockquote>
						</div>
						<div class="col s6">
							<h3>Node</h3>
							<pre id="node-info"><?php
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
							<h3>Running VMs</h3>
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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_vms);
							var_dump($ret_vms);
							?></pre> -->
							<?php if (isset($_SESSION['action']) && !empty($_SESSION['action'])): ?>
							<h6>Raw data</h6>
							<pre><?php
							if (isset($output_action)) {
								print_r($output_action);
							}
							if (isset($ret_action)) {
								var_dump($ret_action);
							}
							?></pre>
							<?php endif; ?>
						</div>
						<div class="col s12">
							<h3>Active IPs</h3>
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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Finish this part
							case 'hyp':
								?>

					<div class="row">
						<div class="col s12">
							<h1>Hypervisor</h1>
							<div class="row">
								<div class="col s6 m4 l3">
									<div class="card-panel hoverable">
										<p class="flow-text center-align">
											<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=view'; ?>">
												<i class="material-icons">developer_board</i>
												<br><span class="truncate">System Info</span>
											</a>
										</p>
									</div>
								</div>
								<div class="col s6 m4 l3">
									<div class="card-panel hoverable">
										<p class="flow-text center-align">
											<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=vm'; ?>">
												<i class="material-icons">computer</i>
												<br><span class="truncate">Create VM</span>
											</a>
										</p>
									</div>
								</div>
								<div class="col s6 m4 l3">
									<div class="card-panel hoverable">
										<p class="flow-text center-align">
											<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=net'; ?>">
												<i class="material-icons">router</i>
												<br><span class="truncate">Create Network</span>
											</a>
										</p>
									</div>
								</div>
								<div class="col s6 m4 l3">
									<div class="card-panel hoverable">
										<p class="flow-text center-align">
											<a href="#modal-upload" class="modal-trigger">
												<i class="material-icons">storage</i>
												<br><span class="truncate">Upload Images</span>
											</a>
										</p>
									</div>
								</div>
							</div>
							<!-- <div class="row">
								<div class="col s12">
									<h5>Parsed data</h5>
									<pre style="height: 300px;"><?php
									print_r(xml2json(virsh_shell_exec('sysinfo'), false, true));
									?></pre>
									<h6>Raw data</h6>
									<pre style="height: 300px;"><?php
									echo htmlentities(virsh_shell_exec('sysinfo'));
									?></pre>
								</div>
							</div> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Finish this part
							case 'vmi':
								?>

					<div class="row">
						<div class="col s12">
							<h1>VM Details</h1>
							<div class="row">
								<div class="col s7">
									<h3>Summary</h3>
									<pre><?php
									virsh_passthru('dominfo ' . $_SESSION['vm_name']);
									?></pre>
								</div>
								<div class="col s5">
									<h3>Hypervisor</h3>
									<pre id="vhostcpu-stats" style="height: 300px;"><?php
									if (vm_is_active($_SESSION['vm_name'])) {
										virsh_passthru('cpu-stats ' . $_SESSION['vm_name']);
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
									}
									?></pre>
								</div>
							</div>
							<h3>Statistics</h3>
							<div class="row">
								<div class="col s4">
									<h5>CPU</h5>
									<pre id="vcpu-stats" style="height: 300px;"><?php
									if (vm_is_active($_SESSION['vm_name'])) {
										print_r(get_vm_cpu_stats($_SESSION['vm_name']));
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
										print_r(get_vm_cpu_stats($_SESSION['vm_name']));
									}
									?></pre>
								</div>
								<div class="col s4">
									<h5>Memory</h5>
									<pre id="vmem-stats" style="height: 300px;"><?php
									if (vm_is_active($_SESSION['vm_name'])) {
										// virsh_passthru('dommemstat ' . $_SESSION['vm_name']);
										print_r(get_vm_mem_stats($_SESSION['vm_name']));
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
										print_r(get_vm_mem_stats($_SESSION['vm_name']));
									}
									?></pre>
								</div>
								<div class="col s4">
									<h5>Network</h5>
									<pre id="vnet-stats" style="height: 300px;"><?php
									if (vm_is_active($_SESSION['vm_name'])) {
										print_r(get_vm_net_stats($_SESSION['vm_name']));
									}
									else {
										echo 'VM is not running.' . PHP_EOL;
									}
									?></pre>
								</div>
								<div class="col s12">
									<h5>Global</h5>
									<pre id="vhost-stats" style="height: 300px;"><?php
									virsh_passthru('domstats --raw ' . $_SESSION['vm_name']);
									?></pre>
									<pre style="height: 300px;"><?php
									print_r(parse_vm_stats($_SESSION['vm_name']));
									?></pre>
								</div>
							</div>
							<h3>
								Virtual CPUs
								<i class="material-icons tooltipped light-blue-text text-darken-1" style="cursor: pointer;" data-position="right" data-tooltip="View CPU Stats" onclick="$('#modal-cpu-stats').modal('open');">info_outline</i>
							</h3>
							<pre><?php
							virsh_passthru('vcpucount ' . $_SESSION['vm_name']);
							?></pre>
							<!-- <a href="#modal-cpu-stats" class="modal-trigger"><i class="material-icons left">info_outline</i>View CPU Stats</a> -->
							<div id="modal-cpu-stats" class="modal modal-fixed-footer">
								<div class="modal-content">
									<h4>CPU Stats</h4>
									<pre><?php
									virsh_passthru('vcpuinfo ' . $_SESSION['vm_name']);
									?></pre>
								</div>
								<div class="modal-footer">
									<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
								</div>
							</div>
							<h3>Network Interfaces</h3>
							<pre><?php
							virsh_passthru('domiflist ' . $_SESSION['vm_name']);
							?></pre>
							<h3>Network Addresses</h3>
							<pre><?php
							if ($_SESSION['vm_name'] === 'ceph-admin') {
								virsh_passthru('domifaddr ' . $_SESSION['vm_name'] . ' --interface vnet0');
							}
							else {
								echo 'Tested on "ceph-admin" only...' . PHP_EOL;
							}
							?></pre>
							<h3>Attached devices</h3>
							<pre><?php
							virsh_passthru('domblklist ' . $_SESSION['vm_name']);
							?></pre>
							<h3>Snapshots</h3>
							<pre><?php
							virsh_passthru('snapshot-list ' . $_SESSION['vm_name']);
							?></pre>
							<h3>Running Jobs</h3>
							<pre><?php
							if (vm_is_active($_SESSION['vm_name'])) {
								virsh_passthru('domjobinfo ' . $_SESSION['vm_name']);
							}
							else {
								echo 'VM is not running.' . PHP_EOL;
							}
							?></pre>
							<!-- <h1>domtime</h1>
							<pre><?php

							// passthru('virsh domtime ' . escapeshellarg($_SESSION['vm_name']) . ' 2>&1');
							// Working: false
							// Outputs: error: argument unsupported: QEMU guest agent is not configured

							?></pre> -->
							<!-- <h1>domfsinfo</h1>
							<pre><?php

							// passthru('virsh domfsinfo ' . escapeshellarg($_SESSION['vm_name']) . ' 2>&1');

							// Working: false
							// Outputs: error: Unable to get filesystem information
							// Outputs: error: argument unsupported: QEMU guest agent is not configured

							?></pre> -->
							<!-- <h1>domhostname</h1>
							<pre><?php

							// passthru('virsh domhostname ' . escapeshellarg($_SESSION['vm_name']) . ' 2>&1');

							// Working: false
							// Outputs: error: failed to get hostname
							// Outputs: error: this function is not supported by the connection driver: virDomainGetHostname

							?></pre> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Should be rewritten to handle all networks
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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_vms);
							var_dump($ret_vms);
							?></pre> -->
							<?php if (isset($_SESSION['action'], $output_action, $ret_action) && !empty($output_action)): ?>
							<h6>Raw data</h6>
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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Should be rewritten to handle all networks
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
							<!-- <h6>Raw data</h6>
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
							<!-- <h6>Raw data</h6>
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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_vnfs);
							var_dump($ret_vnfs);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Should be rewritten to handle all networks
							case 'vni':
								$output_ips = '';
								exec('virsh net-dhcp-leases default', $output_ips, $ret_ips);
								?>

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
							<!-- <h6>Raw data</h6>
							<pre><?php
							print_r($output_ips);
							var_dump($ret_ips);
							?></pre> -->
						</div>
					</div>

								<?php
								break;

							// TODO: Should be rewritten to handle all networks
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
							<!-- <h6>Raw data</h6>
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
							<!-- <h6>Raw data</h6>
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
							<!-- <h6>Raw data</h6>
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
				<div class="col l3 offset-l3 s12">
					<h5 class="white-text">Links</h5>
					<ul>
						<li><a class="grey-text text-lighten-3" href="https://github.com/Jiab77/libvirt-web" rel="nofollow noopener noreferrer" target="_blank">Project</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="footer-copyright">
			<div class="container">
				<?php echo '&copy; ' . date("Y") . ' &ndash; <a href="https://github.com/jiab77" rel="nofollow noopener noreferrer" target="_blank">Jiab77</a>'; ?>
				<!-- <a class="grey-text text-lighten-4 right" href="https://gist.github.com/jiab77" rel="nofollow noopener noreferrer" target="_blank">My gists</a> -->
			</div>
		</div>
	</footer>

	<!-- Modals -->
	<div id="modal-help" class="modal modal-fixed-footer">
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
	<div id="modal-upload" class="modal modal-fixed-footer">
		<div class="modal-content grey-text text-darken-3">
			<h4>Upload Files</h4>
			<div class="row">
				<div class="col s12">
					<!-- <form action="<?php echo htmlentities(strip_tags((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])); ?>"> -->
					<form id="uploadForm">
						<div class="file-field input-field">
							<div class="btn">
								<span>File</span>
								<input type="file" id="uploadFiles" onchange="updateSize();" multiple>
							</div>
							<div class="file-path-wrapper">
								<input class="file-path validate" type="text" placeholder="Upload one or more files">
							</div>
						</div>
						<div id="uploadProgress" class="progress" style="display: none;">
							<div id="progressValue" class="determinate" style="width: 0%"></div>
						</div>
						<p id="uploadSize" style="display: none;">
							Total: <span id="fileNum"></span><br>
							Size: <span id="fileSize"></span><br>
							Status: <span id="uploadStatus"></span>
						</p>
					</form>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
		</div>
	</div>

	<!-- Import jQuery before materialize.js -->
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>

	<!-- App JS -->
	<script type="text/javascript" src="libvirt.js"></script>
	<script type="text/javascript" src="libvirt.ui.js"></script>

	<!-- Connection -->
	<?php
	if (!isset($_SESSION['connected']) || (isset($_SESSION['connected']) && $_SESSION['connected'] !== true)) {
		// var_dump(connect(), $_SESSION);
		connect();
	}
	?>

	<!-- Notifications -->
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
