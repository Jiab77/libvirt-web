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
require_once __DIR__ . '/libs/PpmImageReader.php';

// Include our own class
require_once __DIR__ . '/libs/libvirt.php';
require_once __DIR__ . '/libs/libvirt.xml.php';
require_once __DIR__ . '/libs/libvirt.sess.php';

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
		echo '<!-- ' . PHP_EOL;
		// var_dump($_SESSION);
		print_r($_SESSION);
		echo ' -->' . PHP_EOL;
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
				$libVirt->exec_notify($exec_cmd, $exec_output, $ret_action);
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
				$libVirt->exec_notify($exec_cmd, $exec_output, $ret_action);
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
			$libVirt->exec_notify($exec_cmd, $exec_output, $ret_action);
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
					$stat_cmd = print_r($libVirt->get_vm_cpu_stats($_SESSION['active_vm']), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					$stat_cmd .= print_r($libVirt->get_vm_cpu_stats($_SESSION['active_vm']), true);
				}
				break;

			case 'vdsk':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = print_r($libVirt->get_vm_disk_stats($_SESSION['active_vm']), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					$stat_cmd .= print_r($libVirt->get_vm_disk_stats($_SESSION['active_vm']), true);
				}
				break;

			case 'vmem':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					// $stat_cmd = $libVirt->virsh_shell_exec('dommemstat ' . $_SESSION['active_vm']);
					$stat_cmd = print_r($libVirt->get_vm_mem_stats($_SESSION['active_vm']), true);
				}
				else {
					// $stat_cmd = 'VM is not running.';
					$stat_cmd  = 'VM is not running.' . PHP_EOL;
					$stat_cmd .= print_r($libVirt->get_vm_mem_stats($_SESSION['active_vm']), true);
				}
				break;

			case 'vnet':
				if ($libVirt->vm_is_active($_SESSION['active_vm'])) {
					$stat_cmd = print_r($libVirt->get_vm_net_stats($_SESSION['active_vm']), true);
				}
				else {
					$stat_cmd = 'VM is not running.';
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
						<li><a href="#modal-connect" class="modal-trigger"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
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
											<li><a href="#modal-connect" class="modal-trigger"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
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
						switch ($module) {
							// TODO: Should be rewritten to handle all networks
							case 'dsh':
								require_once __DIR__ . '/modules/dashboard.php';
								break;

							// TODO: Finish this part
							case 'hyp':
								require_once __DIR__ . '/modules/hypervisor.php';
								break;

							// TODO: Finish this part
							case 'vmi':
								require_once __DIR__ . '/modules/vm_infos.php';
								break;

							// TODO: Should be rewritten to handle all networks
							case 'vms':
								require_once __DIR__ . '/modules/machines.php';
								break;

							// TODO: Should be rewritten to handle all networks
							case 'vns':
								require_once __DIR__ . '/modules/networks.php';
								break;

							// TODO: Should be rewritten to handle selected network
							case 'vni':
								require_once __DIR__ . '/modules/vn_infos.php';
								break;

							// TODO: Should be rewritten to handle all storage pools
							case 'vdi':
								require_once __DIR__ . '/modules/vd_infos.php';
								break;

							// TODO: Should be rewritten to handle all storage pools
							case 'vst':
								require_once __DIR__ . '/modules/storage.php';
								break;

							// TODO: Make commands clickable
							case 'hlp':
								require_once __DIR__ . '/modules/help.php';
								break;
							
							// TODO: Improve design
							default:
								require_once __DIR__ . '/modules/invalid.php';
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
					<small class="grey-text text-lighten-4"><?php echo 'Generated in ' . $libVirt->get_loading_time() . ' seconds'; ?></small>
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

	<!-- Modals -->
	<div id="modal-help" class="modal modal-fixed-footer">
		<div class="modal-content grey-text text-darken-3">
			<h4>Command list</h4>
			<pre><?php
			$libVirt->virsh_passthru('help');
			?></pre>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
		</div>
	</div>
	<div id="modal-connect" class="modal">
		<div class="modal-content grey-text text-darken-3">
			<h4 style="margin-bottom: 0;">Connection</h4>
			<div class="row">
				<form class="col s12" id="connectForm">
					<p class="flow-text">Select a connection mode:</p>
					<div class="row">
						<div class="col s4">
							<p>
								<input class="with-gap" name="connect-mode" type="radio" id="connect-mode-system" checked />
								<label for="connect-mode-system">System</label>
							</p>
						</div>
						<div class="col s4">
							<p>
								<input class="with-gap" name="connect-mode" type="radio" id="connect-mode-session" />
								<label for="connect-mode-session">Session</label>
							</p>
						</div>
						<div class="col s4">
							<p>
								<input class="with-gap" name="connect-mode" type="radio" id="connect-mode-ssh" />
								<label for="connect-mode-ssh">SSH</label>
							</p>
						</div>
					</div>
					<div id="connect-ssh" class="row" style="display: none;">
						<div class="input-field col s6">
							<input id="connect-user" type="text" class="validate" autofocus required>
							<label for="connect-user">User</label>
						</div>
						<div class="input-field col s6">
							<input id="connect-host" type="text" class="validate" required>
							<label for="connect-host">Host</label>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat red-text text-accent-3">Cancel</a>
			<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat green-text text-accent-3">Connect</a>
		</div>
	</div>

	<!-- Import jQuery before materialize.js -->
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>

	<!-- App JS -->
	<script type="text/javascript">
	<?php
	echo file_get_contents(__DIR__ . '/libvirt.js') . PHP_EOL;
	?>
	</script>
	<script type="text/javascript">
	<?php
	echo file_get_contents(__DIR__ . '/libvirt.ui.js') . PHP_EOL;
	?>
	</script>

	<!-- Connection -->
	<?php
	if (!isset($_SESSION['connected']) || (isset($_SESSION['connected']) && $_SESSION['connected'] !== true)) {
		// var_dump($libVirt->virsh_connect(), $_SESSION);
		$libVirt->virsh_connect();
	}
	?>

	<!-- Notifications -->
	<?php
	if (isset($_SESSION['notifications']) && is_object($_SESSION['notifications'])) {
		if (count($_SESSION['notifications']->info) > 0) {
			$libVirt->create_notification($_SESSION['notifications']->info);
		}
		if (count($_SESSION['notifications']->error) > 0) {
			$libVirt->create_error_notification($_SESSION['notifications']->error);
		}
	}
	?>

</body>
</html>