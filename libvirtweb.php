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

// Routing
// https://www.php.net/manual/en/features.commandline.webserver.php
if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|js|css)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
}

// Load bootstrapping code
require_once 'inc/bootstrap.php';
?>
<!DOCTYPE html>
<html>
<head>
	<!-- Performance Tags -->
	<link rel="dns-prefetch" href="https://fonts.googleapis.com/">

	<!-- Let browser know website is optimized for mobile -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Import Google Icon Font -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<!-- Import materialize.css -->
	<link type="text/css" rel="stylesheet" href="/vendor/materialize/0.100.2/css/materialize.min.css" media="screen,projection">

	<!-- Block favicon requests -->
	<link rel="icon" href="data:,">

	<!-- Page Title -->
	<title><?php echo $page_title; ?></title>

	<!-- App CSS -->
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
						<li><a href="#modal-help" class="tooltipped modal-trigger" data-position="bottom" data-html="true" data-tooltip="Show &lt;strong&gt;virsh&lt;/strong&gt; commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li><a href="#!" class="dropdown-button" data-activates="settings-dropdown" data-hover="false" data-alignment="right" data-belowOrigin="true"><i class="material-icons left">settings</i>Settings<i class="material-icons right">arrow_drop_down</i></a></li>
					</ul>
					<ul id="settings-dropdown" class="dropdown-content">
						<li><a href="#!"><strong style="color: black;"><?php echo $host_title; ?></strong></a></li>
						<li class="divider"></li>
						<li><a href="#!" class="display-expand"><i class="material-icons left">swap_horiz</i>Expand display</a></li>
						<li><a href="#modal-connect" class="modal-trigger"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
						<li class="divider"></li>
						<li><a href="?module=inf"><i class="material-icons left">info_outline</i>PHP Info</a></li>
					</ul>
					<ul class="side-nav" id="mobile-demo">
						<li><a href="?module=dsh" class="tooltipped" data-position="bottom" data-tooltip="Show dashboard"><i class="material-icons left">dashboard</i>Dashboard</a></li>
						<li><a href="./" class="tooltipped" data-position="bottom" data-tooltip="Show modules"><i class="material-icons left">apps</i>Modules</a></li>
						<li><a href="?module=hlp" title="Show 'virsh' commands"><i class="material-icons left">help_outline</i>Help</a></li>
						<li><a href="#!" onclick="window.location.reload();" class="tooltipped" data-position="bottom" data-tooltip="Refresh"><i class="material-icons left">refresh</i>Refresh</a></li>
						<li class="no-padding">
							<ul class="collapsible collapsible-accordion">
								<li>
									<a class="collapsible-header"><i class="material-icons left" style="margin-left: 16px;">settings</i>Settings<i class="material-icons right">arrow_drop_down</i></a>
									<div class="collapsible-body">
										<ul>
											<li><a href="#!" class="display-expand"><i class="material-icons left">swap_horiz</i>Expand display</a></li>
											<li><a href="#modal-connect" class="modal-trigger"><i class="material-icons left">settings_ethernet</i>Connection</a></li>
											<li><a href="?module=inf"><i class="material-icons left">info_outline</i>PHP Info</a></li>
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
									<a href="?module=inf">
										<i class="material-icons">info_outline</i>
										<br><span class="truncate">PHP Info</span>
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
						switch ($_GET['module']) {
							// TODO: Should be rewritten to handle all networks
							case 'dsh':
								require_once __DIR__ . '/modules/dashboard.php';
								break;

							// TODO: Finish this part
							case 'hyp':
								require_once __DIR__ . '/modules/hypervisor.php';
								break;

							// TODO: Finish this part
							case 'inf':
								require_once __DIR__ . '/modules/info.php';
								break;

							// TODO: Finish this part
							case 'vmi':
								require_once __DIR__ . '/modules/machine.php';
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
								require_once __DIR__ . '/modules/network.php';
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
				<div class="col l3 offset-l3 s12">
					<h5 class="white-text">Links</h5>
					<ul>
						<li>
							<a href="https://github.com/Jiab77/libvirt-web" rel="nofollow noopener noreferrer" target="_blank">
								<i class="material-icons left grey-text text-lighten-3">link</i>Project
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="footer-copyright">
			<div class="container">
				<?php echo '&copy; ' . date("Y") . ' &ndash; <a href="https://github.com/jiab77" rel="nofollow noopener noreferrer" target="_blank">Jiab77</a>'; ?>
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
								<input class="with-gap" name="connect-mode-system" type="radio" id="connect-mode-system" <?php echo $libVirt->connect_info()['auth'] === 'system' ? 'checked ' : '' ?>/>
								<label for="connect-mode-system">System</label>
							</p>
						</div>
						<div class="col s4">
							<p>
								<input class="with-gap" name="connect-mode-session" type="radio" id="connect-mode-session" <?php echo $libVirt->connect_info()['auth'] === 'session' ? 'checked ' : '' ?>/>
								<label for="connect-mode-session">Session</label>
							</p>
						</div>
						<div class="col s4">
							<p>
								<input class="with-gap" name="connect-mode-ssh" type="radio" id="connect-mode-ssh" <?php echo $libVirt->connect_info()['auth'] === 'ssh' ? 'checked ' : '' ?>/>
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
	<script type="text/javascript" src="/vendor/jquery/3.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="/vendor/materialize/0.100.2/js/materialize.min.js"></script>

	<!-- App JS -->
	<script type="text/javascript" src="/libvirt.js"></script>
	<script type="text/javascript" src="/libvirt.ui.js"></script>

	<!-- Connection -->
	<?php
	if (!isset($_SESSION['connected']) || (isset($_SESSION['connected']) && $_SESSION['connected'] !== true)) {
		// var_dump($libVirt->connect(), $_SESSION);
		$libVirt->connect();
	}
	?>

	<!-- Connected to: <?php echo $libVirt->connect_uri(); ?> -->

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

	<!--
	<?php echo 'Session: ' . print_r($_SESSION, true); ?>
	-->

	<!--
	<?php echo 'Debug: ' . print_r($_SERVER, true); ?>
	-->

</body>
</html>
