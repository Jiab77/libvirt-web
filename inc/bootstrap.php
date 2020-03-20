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

// Include our own class
require_once 'libs/libvirt.php';
require_once 'libs/libvirt.xml.php';
require_once 'libs/libvirt.sess.php';

// Init our own class
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
	$_SESSION['active_vm'] = $selected_vm;
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
$project_title = 'libVirt Web';
if (isset($module) && !empty($module)) {
	switch ($module) {
		case 'dsh': $page_title = 'Dashboard'; break;
		case 'hyp': $page_title = 'Hypervisor'; break;
		case 'vmi': $page_title = 'Virtual Machine'; break;
		case 'vms': $page_title = 'Virtual Machines'; break;
		case 'vni': $page_title = 'Virtual Network'; break;
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

// Actions (All Modules)
if (isset($vm_action) && !empty($vm_action)) {
	$virsh_output = '';
	switch ($vm_action) {
		case 'clone':
			if ($libVirt->vm_is_paused($_SESSION['active_vm']) === true) {
				$exec_cmd  = 'virt-clone --original ' . $_SESSION['active_vm'];
				$exec_cmd .= ' --name ' . $_SESSION['active_vm'] . '-clone';
				// $exec_cmd .= ' --file ' . $_SESSION['active_vm'] . '-clone-disk';
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
			/* $virsh_cmd  = 'undefine --domain ' . $_SESSION['active_vm'];
			$virsh_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
			$virsh_cmd .= ' --snapshots-metadata --nvram'; */

			// Disabled for now, it requires much more debug to be in place right now...
			// $libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Retry to delete using another way in case of error
			/* if ($ret_action !== 0) {
				$virsh_cmd  = 'undefine --domain ' . $_SESSION['active_vm'];
				$virsh_cmd .= ' --remove-all-storage --managed-save --delete-snapshots';
				$virsh_cmd .= ' --snapshots-metadata --nvram';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			} */
			break;

		case 'prep':
			if ($libVirt->vm_is_paused($_SESSION['active_vm']) === true) {
				$excluded_operations = "$(virt-sysprep --list-operations | egrep -v 'fs-uuids|lvm-uuids|ssh-userdir' | awk '{ printf \"%s,\", $1}' | sed 's/,$//')";
				$exec_cmd  = 'virt-sysprep --domain ' . $_SESSION['active_vm'] . '-clone';
				$exec_cmd .= ' --hostname ' . $_SESSION['active_vm'] . '-clone';
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
			$virsh_cmd = 'reboot ' . $_SESSION['active_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'resume':
			$virsh_cmd = 'resume ' . $_SESSION['active_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'snap':
			$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['active_vm'];
			$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'live' : 'offline') . '-snapshot-' . date("dmYHis") . '"';
			$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'Live' : 'Offline') . ' snapshot taken on ' . date("d/m/Y H:i:s") . '"';
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Create a 'disk-only' snapshot in case of error
			if ($ret_action !== 0) {
				$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['active_vm'];
				$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'live' : 'offline') . '-disk-only-snapshot-' . date("dmYHis") . '"';
				$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'Live' : 'Offline') . ' disk-only snapshot taken on ' . date("d/m/Y H:i:s") . '"';
				// $virsh_cmd .= ' --quiesce'; // Only when qemu agent is installed
				$virsh_cmd .= ' --disk-only';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action2);
			}

			// Retry while stopping the VM first
			if ($ret_action2 !== 0) {
				$virsh_cmd  = 'shutdown --domain ' . $_SESSION['active_vm'];
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

				$virsh_cmd  = 'snapshot-create-as --domain ' . $_SESSION['active_vm'];
				$virsh_cmd .= ' --name "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'live' : 'offline') . '-snapshot-' . date("dmYHis") . '"';
				$virsh_cmd .= ' --description "' . ($libVirt->vm_is_active($_SESSION['active_vm']) ? 'Live' : 'Offline') . ' snapshot taken on ' . date("d/m/Y H:i:s") . '"';
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			}
			break;

		case 'start':
			$virsh_cmd = 'start ' . $_SESSION['active_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'stop':
			$virsh_cmd = 'shutdown ' . $_SESSION['active_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);

			// Force shutdown in case of error
			if ($ret_action !== 0 || $libVirt->vm_is_active($_SESSION['active_vm']) === true) {
				$virsh_cmd = 'destroy ' . $_SESSION['active_vm'];
				$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			}
			break;

		case 'suspend':
			$virsh_cmd = 'suspend ' . $_SESSION['active_vm'];
			$libVirt->virsh_exec_notify($virsh_cmd, $virsh_output, $ret_action);
			break;

		case 'view':
			$libVirt->notify('Starting virt-viewer...');
			$exec_cmd = 'virt-viewer -v -w ' . escapeshellarg($_SESSION['active_vm']) . ' &';
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
		$stat_data = htmlentities(strip_tags(filter_var($_REQUEST['data'], FILTER_SANITIZE_STRING)));

		switch ($stat_data) {
			case 'cpu':
				$stat_cmd = $libVirt->virsh_shell_exec('nodecpustats');
				break;

			case 'mem':
				$stat_cmd = $libVirt->virsh_shell_exec('nodememstats');
				break;

			case 'node':
				$stat_cmd = $libVirt->virsh_shell_exec('nodeinfo');
				break;

			case 'vhostcpu':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = $libVirt->virsh_shell_exec('cpu-stats ' . $_SESSION['active_vm']);
				}
				else {
					$stat_cmd = 'VM is not running.';
				}
				break;

			case 'vcpu':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'cpu'), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					// $stat_cmd .= print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'cpu'), true);
				}
				break;

			case 'vdsk':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'disk'), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					// $stat_cmd .= print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'disk'), true);
				}
				break;

			case 'vmem':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					// $stat_cmd = $libVirt->virsh_shell_exec('dommemstat ' . $_SESSION['active_vm']);
					$stat_cmd = print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'memory'), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					// $stat_cmd .= print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'memory'), true);
				}
				break;

			case 'vnet':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'network'), true);
				}
				else {
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					// $stat_cmd .= print_r($libVirt->get_vm_stats($_SESSION['active_vm'], 'network'), true);
				}
				break;

			case 'vhost':
				$stat_cmd = $libVirt->virsh_shell_exec('domstats --raw ' . $_SESSION['active_vm']);
				break;
		}

		// Prepare ajax response
		$ajax_response = $libVirt->ajax_response($stat_cmd);
		
		// Send ajax response as JSON
		$libVirt->send_json($ajax_response, true);

		// Stop processing
		exit;
	}
}