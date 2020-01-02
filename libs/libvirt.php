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

class libVirt {
	// Taken from my CMS project: CMS-Base
	public function get_loading_time() {
		return number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 4);
	}

	public function secure_password($pass) {
		if ($pass) {
			return base64_encode(str_rot13($pass));
		}
		return false;
	}

	public function restore_password($pass) {
		if ($pass) {
			return base64_decode(str_rot13($pass));
		}
		return false;
	}

	// Wrapper to the 'PPM' Image reader class
	// Class code is taken from: https://www.webdecker.de/artikel/items/php-ppm-image-file-reader.html
	private function ppm_to_png($image, $quality = 100, $output = null) {
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
	private function ppm_to_jpg($image, $quality = 100, $output = null) {
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
	public function virsh_shell_exec($command) {
		$base_command = 'virsh';
		$run_command = escapeshellcmd($base_command . ' ' . $command);
		$redirector = '2>&1';
		return trim(shell_exec($run_command . ' ' . $redirector));
	}
	public function virsh_exec($command, &$output, &$return_code) {
		$base_command = 'virsh';
		$run_command = escapeshellcmd($base_command . ' ' . $command);
		$redirector = '2>&1';
		exec($run_command . ' ' . $redirector, $output, $return_code);
	}
	public function virsh_exec_notify($command, &$output, &$return_code) {
		$base_command = 'virsh';
		$run_command = escapeshellcmd($base_command . ' ' . $command);
		$redirector = '2>&1';
		exec($run_command . ' ' . $redirector, $output, $return_code);
		foreach ($output as $line) {
			if (!empty($line)) {
				if (strpos($line, 'error:') !== false) {
					$this->add_notification($line, true);
				}
				else {
					$this->add_notification($line);
				}
			}
		}
	}
	public function exec_notify($command, &$output, &$return_code) {
		$run_command = escapeshellcmd($command);
		$redirector = '2>&1';
		exec($run_command . ' ' . $redirector, $output, $return_code);
		foreach ($output as $line) {
			if (!empty($line)) {
				if (strpos($line, 'error:') !== false) {
					$this->add_notification($line, true);
				}
				else {
					$this->add_notification($line);
				}
			}
		}
	}
	public function notify($message, $error = false) {
		if (!empty($message)) {
			if ($error !== false) {
				$this->add_notification($message, true);
			}
			else {
				$this->add_notification($message);
			}
		}
	}
	public function virsh_passthru($command, &$return_code = null) {
		$base_command = 'virsh';
		$run_command = escapeshellcmd($base_command . ' ' . $command);
		$redirector = '2>&1';
		passthru($run_command . ' ' . $redirector, $return_code);
	}
	public function virsh_connect($uri = 'qemu:///system', $read_only = false) {
		$output = null;
		$return_code = null;
		$this->virsh_exec('connect --name ' . $uri . ($read_only === true ? ' --readonly' : ''), $output, $return_code);
		if ($return_code !== 0) {
			$_SESSION['connected'] = false;
			$this->add_notification('Could not connect to the hypervisor.', true);
		}
		else {
			$_SESSION['connected'] = true;
			$this->add_notification('Connected to the hypervisor.');
		}
		$this->add_notification('Connection URI: ' . $uri);
		return $_SESSION['connected'];
	}

	// Notifications
	public function add_notification($data, $error = false) {
		if ($error === false) {
			$add_status = array_push($_SESSION['notifications']->info, $data);
		}
		else {
			$add_status = array_push($_SESSION['notifications']->error, $data);
		}
		return $add_status;
	}
	public function create_notification(&$data, $timeout = 4000) {
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
	public function create_error_notification(&$data, $timeout = 4000) {
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

	// Content extractor / parser
	private function extract_data($line, $delim) {
		$line = trim($line);
		$data = explode($delim, $line);
		$data = array_filter($data);
		return $data;
	}
	public function parse_vm_stats($vm_name, $as_json = false, $pretty_print = false) {
		$lines_to_skip = 1;
		$cmd_output = '';
		$cmd_return = '';
		$extracted_data = [];
		$data_header = [];
	
		// Get vm raw data
		$this->virsh_exec('domstats --raw ' . $vm_name, $cmd_output, $cmd_return);
	
		if ($cmd_return === 0) {
			// Internal counters
			$index = 0;
			$processed_lines = 0;
		
			// Create data header
			foreach ($cmd_output as $line) {
				$index++;
				if ($index > $lines_to_skip && !empty($line)) {
					$processed_lines++;
					$parsed_line_array = $this->extract_data($line, '.');
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
					$parsed_line_headers = $this->extract_data($line, '.');
					$parsed_line_array = $this->extract_data($line, '=');
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

	// Statistics
	public function get_vm_cpu_stats($vm_name, $as_json = false, $pretty_print = false) {
		// Collect data from raw vm stats
		if ($parsed_data = $this->parse_vm_stats($vm_name)) {
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
	public function get_vm_disk_stats($vm_name, $as_json = false, $pretty_print = false) {
		// Collect data from raw vm stats
		if ($parsed_data = $this->parse_vm_stats($vm_name)) {
			$disk_stats = [];
		
			// Clean up mem data
			foreach ($parsed_data['block'] as $block) {
				$block_entry_name = explode('.', $block['name']);
				if (count($block_entry_name) === 2) {
					$block_data = ['name' => $block_entry_name[1], 'value' => (is_numeric($block['value']) ? (int)$block['value'] : $block['value'])];
				}
				else {
					$block_data = ['name' => $block['name'], 'value' => (is_numeric($block['value']) ? (int)$block['value'] : $block['value'])];
				}
				array_push($disk_stats, $block_data);
			}
	
			// Output as array or json
			if ($as_json === true) {
				if ($pretty_print === true) {
					$json = json_encode($disk_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				}
				else {
					$json = json_encode($disk_stats, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
				}
				return $json;
			}
			else {
				return $disk_stats;
			}
		}
		return false;
	}
	public function get_vm_mem_stats($vm_name, $as_json = false, $pretty_print = false) {
		// Collect data from raw vm stats
		if ($parsed_data = $this->parse_vm_stats($vm_name)) {
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
	public function get_vm_net_stats($vm_name, $as_json = false, $pretty_print = false) {
		// Collect data from raw vm stats
		if ($parsed_data = $this->parse_vm_stats($vm_name)) {
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

	// VM Infos
	public function get_vm_networks() {
		$output = null; $return_code = null;
		$lines_to_skip = 2;
		$index = 0;
		$extracted_data = [];
		$vm_networks = [];
	
		$this->virsh_exec('net-list', $output, $return_code);
	
		foreach ($output as $line) {
			$index++;
			if ($index > $lines_to_skip && !empty($line)) {
				$extracted_data = $this->extract_data($line, ' ');
				array_push($vm_networks, $extracted_data[0]);
			}
		}
	
		return (count($vm_networks) > 0 ? $vm_networks : false);
	}
	public function get_vm_pools() {
		$output = null; $return_code = null;
		$lines_to_skip = 2;
		$index = 0;
		$extracted_data = [];
		$vm_pools = [];
	
		$this->virsh_exec('pool-list', $output, $return_code);
	
		foreach ($output as $line) {
			$index++;
			if ($index > $lines_to_skip && !empty($line)) {
				$extracted_data = $this->extract_data($line, ' ');
				array_push($vm_pools, $extracted_data[0]);
			}
		}
	
		return (count($vm_pools) > 0 ? $vm_pools : false);
	}
	public function get_vm_uid($vm_name) {
		if (!$vm_name) {
			return false;
		}
		return $this->virsh_shell_exec('domuuid ' . $vm_name);
	}
	public function get_vm_state($vm_name) {
		if (!$vm_name) {
			return false;
		}
		return $this->virsh_shell_exec('domstate ' . $vm_name);
	}

	// Screenshots
	public function create_vm_screenshots($vm_name) {
		$input_image  = '/tmp/' . $vm_name . '_screen.ppm';
		$output_image = '/tmp/' . $vm_name . '_screen.png';
	
		// Create the screenshot from 'virsh'
		passthru(escapeshellcmd('virsh screenshot ' . $vm_name . ' --file ' . $input_image . ' --screen 0') . ' 2>&1 >/dev/null');
	
		// Convert the created screenshot to PNG
		if (is_readable($input_image)) {
			// In case of issues, I might move on the 'netpbm' package
			// and the 'pnmtopng' command to convert the PPM images to PNG
	
			// Convert the PPM image to PNG using pure PHP implementation
			$this->ppm_to_png($input_image, 80, $output_image);
	
			// Output the converted image as Data-URI (base64)
			if (is_readable($output_image)) {
				echo '<img class="materialboxed" src="data:image/png;base64,' . base64_encode(file_get_contents($output_image)) . '" width="80" alt="' . $vm_name . ' screenshot">';
			}
		}
		else {
			echo 'Could not read screenshot.';
		}
	}

	// Tables
	public function create_table_active_vms_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
		$lines_to_skip = 2;
		$index = 0;
		$col_index = 0;
		$created_lines = 0;
		$extracted_data = [];
		$col_id = 1;
		$col_name = 2;
		$col_state = 3;
	
		foreach ($lines as $line) {
			$index++;
	
			if ($index > $lines_to_skip && !empty($line)) {
				$created_lines++;
	
				echo '<tr>' . PHP_EOL;
	
				$extracted_data = $this->extract_data($line, $delim);
	
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
						$this->create_vm_screenshots($vm_name);
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
	public function create_table_generic_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
		$lines_to_skip = 2;
		$index = 0;
		$created_lines = 0;
		$extracted_data = [];

		foreach ($lines as $line) {
			$index++;
			if ($index > $lines_to_skip && !empty($line)) {
				$created_lines++;
				echo '<tr>' . PHP_EOL;
				$extracted_data = $this->extract_data($line, $delim);
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
	public function create_table_active_ips_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
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
	
				$extracted_data = $this->extract_data($line, $delim);
	
				foreach ($extracted_data as $data) {
					$col_index++;
					$cleaned_data = trim($data);
	
					// Create mac cell
					if ($col_index === $col_mac_address) {
						echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
					}
	
					// Create ip cell
					elseif ($col_index === $col_ip_address) {
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
								echo '<td>' . $vm_hostname . '</td>' . PHP_EOL;
								echo '<td>' . $vm_duid . '</td>' . PHP_EOL;
							}
							else {
								echo '<td>' . $vm_hostname . '</td>' . PHP_EOL;
							}
						}
						else {
							echo '<td>' . $cleaned_data . '</td>' . PHP_EOL;
						}
					}
	
					// Create other cells
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
	public function create_table_vms_rows($lines, $delim = ' ', $cols = 0, $cls = '') {
		$lines_to_skip = 2;
		$index = 0;
		$col_index = 0;
		$created_lines = 0;
		$extracted_data = [];
		$col_id = 1;
		$col_name = 2;
		$col_state = 3;
	
		foreach ($lines as $line) {
			$index++;
	
			if ($index > $lines_to_skip && !empty($line)) {
				$created_lines++;
	
				echo '<tr>' . PHP_EOL;
	
				$extracted_data = $this->extract_data($line, $delim);
	
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
						echo '<a href="?module=' . $_SESSION['module'] . '&action=snap&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Create snapshot"><i class="material-icons">save</i></a>' . PHP_EOL;
						echo '<a href="?module=' . $_SESSION['module'] . '&action=edit&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Edit"><i class="material-icons">edit</i></a>' . PHP_EOL;
						echo '<a href="?module=' . $_SESSION['module'] . '&action=delete&name=' . $vm_name . '" class="tooltipped" data-position="bottom" data-tooltip="Delete"><i class="material-icons">delete</i></a>' . PHP_EOL;
						echo '<a href="#more-action-modal-vm_' . $this->get_vm_uid($vm_name) . '" class="modal-trigger tooltipped" data-position="bottom" data-tooltip="More actions"><i class="material-icons">more_vert</i></a>' . PHP_EOL;

						// Created dedicated VM modal
						echo '<div id="more-action-modal-vm_' . $this->get_vm_uid($vm_name) . '" class="modal bottom-sheet">' . PHP_EOL;
						echo '<div class="modal-content">' . PHP_EOL;
						echo '<h4>' . $vm_name . ' - advanced</h4>' . PHP_EOL;
						echo '<div class="row">' . PHP_EOL;
						echo '<div class="col s6">' . PHP_EOL;
						echo '<ul>' . PHP_EOL;
						echo '<li><a href="?module=' . $_SESSION['module'] . '&action=clone&name=' . $vm_name . '" class="tooltipped" data-position="right" data-tooltip="Clone the VM"><i class="material-icons">save</i> Clone</a></li>' . PHP_EOL;
						echo '<li><a href="?module=' . $_SESSION['module'] . '&action=prep&name=' . $vm_name . '" class="tooltipped" data-position="right" data-tooltip="Clean the cloned guest"><i class="material-icons">refresh</i> SysPrep</a></li>' . PHP_EOL;
						echo '</ul>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						echo '<div class="col s6">' . PHP_EOL;
						echo '<p>GRAPH</p>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						echo '<div class="modal-footer">' . PHP_EOL;
						echo '<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						echo '</div>' . PHP_EOL;

						// Closing table cell
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
	public function vm_is_active($vm_name) {
		$is_active = false;
		$vm_state = $this->get_vm_state($vm_name);
		if ($vm_state === 'running' || $vm_state === 'paused') {
			$is_active = true;
		}
		return $is_active;
	}

	public function vm_is_paused($vm_name) {
		$is_paused = false;
		$vm_state = $this->get_vm_state($vm_name);
		if ($vm_state === 'paused') {
			$is_paused = true;
		}
		return $is_paused;
	}

	// Ajax data
	public function ajax_response($data) {
		$response = new stdClass;
		$response->html  = '';
		$response->html .= $data;
		$response->success = (!is_null($data) ? true : false);
		return $response;
	}
	public function send_json($data, $formatted = false) {
		header('Content-Type: application/json');
		if ($formatted === true) {
			echo json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
		}
		else {
			echo json_encode($data);
		}
	}
}