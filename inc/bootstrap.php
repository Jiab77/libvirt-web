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

// Call the 'PPM' Image reader class
// Class code is taken from: https://www.webdecker.de/artikel/items/php-ppm-image-file-reader.html
require_once 'libs/PpmImageReader.php';

// Include our own classes
require_once 'libs/libvirt.php';
require_once 'libs/libvirt.xml.php';
require_once 'libs/libvirt.sess.php';

// Init our own classes
$libVirt = new libVirt();
$libVirtXML = new libVirtXML();

// Interface config
if (!isset($_SESSION['vm_networks']) ||
	(isset($_SESSION['vm_networks']) && !is_array($_SESSION['vm_networks']))) {
		$_SESSION['vm_networks'] = $libVirt->get_vm_networks();
}
if (!isset($_SESSION['vm_pools']) ||
	(isset($_SESSION['vm_pools']) && !is_array($_SESSION['vm_pools']))) {
		$_SESSION['vm_pools'] = $libVirt->get_vm_pools();
}
if (isset($_SESSION) && is_array($_SESSION) &&
	isset($_SESSION['module']) && $_SESSION['module'] !== 'ajx') {
		/* echo '<!-- ' . PHP_EOL;
		echo 'Server session:' . PHP_EOL;
		print_r($_SESSION);
		echo ' -->' . PHP_EOL; */
}

// Content selector
if (isset($_GET['module']) && !empty($_GET['module'])) {
	$module = htmlentities(strip_tags(filter_var($_GET['module'], FILTER_SANITIZE_STRING)));
	$_SESSION['module'] = $module;
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
	$vm_action = htmlentities(strip_tags(filter_var($_GET['action'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['do']) && !empty($_GET['do'])) {
	$module_action = htmlentities(strip_tags(filter_var($_GET['do'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
	$selected_vm = htmlentities(strip_tags(filter_var($_GET['name'], FILTER_SANITIZE_STRING)));
	$_SESSION['selected_vm'] = $selected_vm;
}
if (isset($_GET['uri']) && !empty($_GET['uri'])) {
	$connect_uri = htmlentities(strip_tags(filter_var($_GET['uri'], FILTER_SANITIZE_STRING)));
	$_SESSION['connect_uri'] = $connect_uri;
}
if (isset($_GET['user']) && !empty($_GET['user'])) {
	$connect_user = htmlentities(strip_tags(filter_var($_GET['user'], FILTER_SANITIZE_STRING)));
	$_SESSION['connect_user'] = $connect_user;
}

// Displayed title
$hypervisor  = json_decode($libVirtXML->xml2json($libVirt->virsh_shell_exec('sysinfo'), false, false));
$host_title  = $hypervisor->system->entry[0]->attributes->{'nodeValue'};
$host_title .= ' ';
$host_title .= $hypervisor->system->entry[1]->attributes->{'nodeValue'};
$project_title = 'libVirt Web';
if (isset($module) && !empty($module)) {
	switch ($module) {
		case 'dsh': $module_title = 'Dashboard'; break;
		case 'hyp': $module_title = 'Hypervisor'; break;
		case 'vmi': $module_title = 'Virtual Machine &ndash; ' . $_SESSION['selected_vm']; break;
		case 'vms': $module_title = 'Virtual Machines'; break;
		case 'vni': $module_title = 'Virtual Network &ndash; ' . $_SESSION['selected_vm']; break;
		case 'vns': $module_title = 'Virtual Networks'; break;
		case 'vst': $module_title = 'Virtual Storage'; break;
		case 'hlp': $module_title = 'Help'; break;
		default: $module_title = ''; break;
	}

	$page_title  = $project_title;
	$page_title .= (!empty($host_title) ? ' &ndash; ' . $host_title : '');
	$page_title .= (!empty($module_title) ? ' &ndash; ' . $module_title : '');
}
else {
	$page_title  = $project_title;
	$page_title .= (!empty($host_title) ? ' &ndash; ' . $host_title : '');
}

// Actions (All Modules)
if (isset($vm_action) && !empty($vm_action)) {
	$virsh_output = '';
	switch ($vm_action) {
		case 'clone':
			if ($libVirt->vm_is_paused($_SESSION['selected_vm']) === true) {
				$exec_cmd  = 'virt-clone --original ' . $_SESSION['selected_vm'];
				$exec_cmd .= ' --name ' . $_SESSION['selected_vm'] . '-clone';
				// $exec_cmd .= ' --file ' . $_SESSION['selected_vm'] . '-clone-disk';
				$exec_cmd .= ' --auto-clone';
				$exec_cmd .= ' --debug';
				$libVirt->exec_cmd_notify($exec_cmd, $exec_output, $ret_action);
			}
			else {
				$libVirt->notify('Error: VM must be stopped or paused.', true);
			}
			break;

		case 'create':
			# code...
			break;

		case 'delete':
			/* $virsh_cmd  = 'undefine --domain ' . $_SESSION['selected_vm'];
			$virsh_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
			$virsh_cmd .= ' --snapshots-metadata --nvram'; */

			// Disabled for now, it requires much more debug to be in place right now...
			// $libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Retry to delete using another way in case of error
			/* if ($ret_action !== 0) {
				$virsh_cmd  = 'undefine --domain ' . $_SESSION['selected_vm'];
				$virsh_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
				$virsh_cmd .= ' --snapshots-metadata --nvram';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			} */
			break;

		case 'prep':
			if ($libVirt->vm_is_paused($_SESSION['selected_vm']) === true) {
				$excluded_operations = "$(virt-sysprep --list-operations | egrep -v 'fs-uuids|lvm-uuids|ssh-userdir' | awk '{ printf \"%s,\", $1}' | sed 's/,$//')";
				$exec_cmd  = 'virt-sysprep --domain ' . $_SESSION['selected_vm'] . '-clone';
				$exec_cmd .= ' --hostname ' . $_SESSION['selected_vm'] . '-clone';
				$exec_cmd .= ' --keep-user-accounts ' . $_SESSION['active_vm_user'];
				$exec_cmd .= ' --enable ' . $excluded_operations;
				// If debian based VM
				// $exec_cmd .= ' --firstboot-command "dpkg-reconfigure openssh-server"';
				$exec_cmd .= ' --verbose';
				// For more debugging (enable tracing of libguestfs calls)
				// $exec_cmd .= ' -x';
				$exec_cmd .= ' --dry-run';
				$libVirt->exec_cmd_notify($exec_cmd, $exec_output, $ret_action);
			}
			else {
				$libVirt->notify('Error: VM must be stopped or paused.', true);
			}
			break;

		case 'reboot':
			$virsh_cmd = 'reboot ' . $_SESSION['selected_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'resume':
			$virsh_cmd = 'resume ' . $_SESSION['selected_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'snap':
			$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['selected_vm'];
			$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'live' : 'offline') . '-snapshot-' . date("dmYHis") . '"';
			$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'Live' : 'Offline') . ' snapshot taken on ' . date("d/m/Y H:i:s") . '"';
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Create a 'disk-only' snapshot in case of error
			if ($ret_action !== 0) {
				$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['selected_vm'];
				$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'live' : 'offline') . '-disk-only-snapshot-' . date("dmYHis") . '"';
				$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'Live' : 'Offline') . ' disk-only snapshot taken on ' . date("d/m/Y H:i:s") . '"';
				// $virsh_cmd .= ' --quiesce'; // Only when qemu agent is installed
				$virsh_cmd .= ' --disk-only';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action2);
			}

			// Retry while stopping the VM first
			if ($ret_action2 !== 0) {
				$virsh_cmd  = 'shutdown --domain ' . $_SESSION['selected_vm'];
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

				$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['selected_vm'];
				$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'live' : 'offline') . '-snapshot-' . date("dmYHis") . '"';
				$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['selected_vm']) ? 'Live' : 'Offline') . ' snapshot taken on ' . date("d/m/Y H:i:s") . '"';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			}
			break;

		case 'start':
			$virsh_cmd = 'start ' . $_SESSION['selected_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'stop':
			$virsh_cmd = 'shutdown ' . $_SESSION['selected_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Force shutdown in case of error
			if ($ret_action !== 0 || $libVirt->vm_is_active($_SESSION['selected_vm']) === true) {
				$virsh_cmd = 'destroy ' . $_SESSION['selected_vm'];
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			}
			break;

		case 'suspend':
			$virsh_cmd = 'suspend ' . $_SESSION['selected_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'view':
			$libVirt->notify('Starting virt-viewer...');
			$exec_cmd = 'virt-viewer -v -w ' . escapeshellarg($_SESSION['selected_vm']) . ' &';
			$libVirt->exec_cmd_notify($exec_cmd, $exec_output, $ret_action);
			break;

		default:
			# code...
			break;
	}
}

// Actions (Per Modules)
if (isset($module_action) && !empty($module_action)) {
	$module_output = '';
	switch ($module_action) {
		case 'connect':
			if (isset($_SESSION['connect_uri']) && filter_var($_SESSION['connect_uri'], FILTER_VALIDATE_IP) === true &&
				isset($_SESSION['connect_user']) && !empty($_SESSION['connect_user'])) {
					$qemu_uri = 'qemu+ssh://' . $_SESSION['connect_user'] . '@' . $_SESSION['connect_uri'] . '/system';
			}
			elseif (isset($_SESSION['connect_uri']) &&
					$_SESSION['connect_uri'] === 'session' ||
					$_SESSION['connect_uri'] === 'system') {
						$qemu_uri = 'qemu://' . $_SESSION['connect_uri'];
			}
			else {
				$qemu_uri = null;
			}
			if (!is_null($qemu_uri)) {
				$libVirt->virsh_connect($qemu_uri);
			}
			break;

		case 'view':
			# code...
			break;

		default:
			# code...
			break;
	}
}

// File upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_FILES) && is_array($_FILES) && isset($_FILES['upload_file'])) {
		echo print_r($_FILES['upload_file'], true);
		if (move_uploaded_file($_FILES['upload_file']['tmp_name'], sys_get_temp_dir() . '/' . $_FILES['upload_file']['name'])){
			echo $_FILES['upload_file']['name']. " OK";
		}
		else {
			echo $_FILES['upload_file']['name']. " KO";
		}
	}
	else {
		echo 'No files uploaded...' . PHP_EOL;
	}
	exit;
}

// Ajax
if (isset($_REQUEST['module']) && $_REQUEST['module'] === 'ajx') {
	if (isset($_REQUEST['data']) && !empty($_REQUEST['data'])) {
		$client_request = htmlentities(strip_tags(filter_var($_REQUEST['data'], FILTER_SANITIZE_STRING)));

		switch ($client_request) {
			case 'cpu':
				$client_response = $libVirt->virsh_shell_exec('nodecpustats');
				break;

			case 'mem':
				$client_response = $libVirt->virsh_shell_exec('nodememstats');
				break;

			case 'node':
				$client_response = $libVirt->virsh_shell_exec('nodeinfo');
				break;

			case 'vhostcpu':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$client_response = $libVirt->virsh_shell_exec('cpu-stats ' . $_SESSION['selected_vm']);
				}
				else {
					$client_response = 'VM is not running.';
				}
				break;

			case 'vcpu':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$client_response = print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'cpu'), true);
				}
				else {
					// $client_response = 'VM is not running.';
					$client_response = 'VM is not running.' . PHP_EOL;
					// $client_response .= print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'cpu'), true);
				}
				break;

			case 'vdsk':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$client_response = print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'disk'), true);
				}
				else {
					// $client_response = 'VM is not running.';
					$client_response = 'VM is not running.' . PHP_EOL;
				}
				break;

			case 'vmem':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					// $client_response = $libVirt->virsh_shell_exec('dommemstat ' . $_SESSION['selected_vm']);
					$client_response = print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'memory'), true);
				}
				else {
					// $client_response = 'VM is not running.';
					$client_response = 'VM is not running.' . PHP_EOL;
				}
				break;

			case 'vnet':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$client_response = print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'network'), true);
				}
				else {
					$client_response = 'VM is not running.' . PHP_EOL;
				}
				break;

			case 'vhost':
				$client_response = $libVirt->virsh_shell_exec('domstats --raw ' . $_SESSION['selected_vm']);
				break;

			case 'preview':
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$client_response = $libVirt->create_vm_screenshots($_SESSION['selected_vm'], true);
				}
				else {
					$client_response = 'VM is not running.' . PHP_EOL;
				}
				break;
		}

		// Prepare ajax response
		$client_response = $libVirt->ajax_response($client_response, $_SESSION['selected_vm']);

		// Send ajax response as JSON
		$libVirt->send_json($client_response, true);

		// Stop processing
		exit;
	}
}
