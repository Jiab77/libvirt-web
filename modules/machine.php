
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>Details</h3>
		<div class="row">
			<div class="col s7">
				<h5>Summary</h5>
				<pre><?php
				$libVirt->virsh_passthru('dominfo ' . $_SESSION['selected_vm']);
				?></pre>
			</div>
			<div class="col s5">
				<h5>Hypervisor</h5>
				<pre id="vhostcpu-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					$libVirt->virsh_passthru('cpu-stats ' . $_SESSION['selected_vm']);
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
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'cpu'));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'cpu'));
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Memory</h5>
				<pre id="vmem-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					// $libVirt->virsh_passthru('dommemstat ' . $_SESSION['selected_vm']);
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'memory'));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'memory'));
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Network</h5>
				<pre id="vnet-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'network'));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'network'));
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Disk</h5>
				<pre id="vdsk-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'disk'));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_stats($_SESSION['selected_vm'], 'disk'));
				}
				?></pre>
			</div>
			<div class="col s8">
				<h5>Global</h5>
				<pre id="vhost-stats" style="height: 300px;"><?php
				$libVirt->virsh_passthru('domstats --raw ' . $_SESSION['selected_vm']);
				?></pre>
				<!-- <pre style="height: 300px;"><?php
				print_r($libVirt->parse_vm_stats($_SESSION['selected_vm'], true, true));
				?></pre> -->
			</div>
		</div>

		<?php /*
		<h3>
			Virtual CPUs
			<i class="material-icons tooltipped light-blue-text text-darken-1" style="cursor: pointer;" data-position="right" data-tooltip="View CPU Stats" onclick="$('#modal-cpu-stats').modal('open');">info_outline</i>
		</h3>
		<pre><?php
		$libVirt->virsh_passthru('vcpucount ' . $_SESSION['selected_vm']);
		?></pre>
		<div id="modal-cpu-stats" class="modal modal-fixed-footer">
			<div class="modal-content">
				<h4>CPU Stats</h4>
				<pre><?php
				$libVirt->virsh_passthru('vcpuinfo ' . $_SESSION['selected_vm']);
				?></pre>
			</div>
			<div class="modal-footer">
				<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
			</div>
		</div>
		*/ ?>

		<h3>Network</h3>
		<h5>Interfaces</h5>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Interface</th>
					<th>Type</th>
					<th>Source</th>
					<th>Model</th>
					<th>MAC</th>
				</tr>
			</thead>
			<tbody>

			<?php
			// $libVirt->virsh_passthru('domiflist ' . $_SESSION['selected_vm']);

			$output_ifaces = []; $ret_ifaces = null;
			$libVirt->virsh_exec('domiflist --domain ' . $_SESSION['selected_vm'], $output_ifaces, $ret_ifaces);
			if (isset($output_ifaces) && !empty($output_ifaces)) {
				// $libVirt->create_table_generic_rows($output_ifaces, '  ', 5, 'center-align');
				$libVirt->create_table_rows('generic', $output_ifaces, '  ', 5, 'center-align');
			}
			?>

			</tbody>
		</table>
		<h6>Raw data</h6>
		<pre><?php
		print_r($output_ifaces);
		$parsed_iface = explode('      ', $output_ifaces[2])[0];
		var_dump($ret_ifaces);
		?></pre>
		<h5>Addresses</h5>
		<pre><?php
		if ($libVirt->vm_is_active($_SESSION['selected_vm']) && $parsed_iface !== '') {
			$libVirt->virsh_passthru('domifaddr --domain ' . $_SESSION['selected_vm'] . ' --interface ' . $parsed_iface);
		}
		else {
			echo 'VM is not running.' . PHP_EOL;
		}
		?></pre>
		<h3>Attached devices</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Type</th>
					<th>Device</th>
					<th>Target</th>
					<th>Source</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_devs = []; $ret_devs = null;
			$libVirt->virsh_exec('domblklist --details --domain ' . $_SESSION['selected_vm'], $output_devs, $ret_devs);
			if (isset($output_devs) && !empty($output_devs)) {
				// $libVirt->create_table_generic_rows($output_devs, '  ', 4, 'center-align');
				$libVirt->create_table_rows('generic', $output_devs, '  ', 4, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_devs);
		var_dump($ret_devs);
		?></pre> -->
		<h3>Snapshots</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Creation Time</th>
					<th>State</th>
				</tr>
			</thead>
			<tbody>

			<?php
			// $libVirt->virsh_passthru('snapshot-list ' . $_SESSION['selected_vm']);

			$output_snaps = []; $ret_snaps = null;
			$libVirt->virsh_exec('snapshot-list --domain ' . $_SESSION['selected_vm'], $output_snaps, $ret_snaps);
			if (isset($output_snaps) && !empty($output_snaps)) {
				// $libVirt->create_table_generic_rows($output_snaps, '  ', 3, 'center-align');
				$libVirt->create_table_rows('generic', $output_snaps, '  ', 3, 'center-align');
			}
			?>

			</tbody>
		</table>
		<h6>Raw data</h6>
		<pre><?php
		print_r($output_snaps);
		var_dump($ret_snaps);
		?></pre>
		<h3>Running Jobs</h3>
		<pre><?php
		if ($libVirt->vm_is_active($_SESSION['selected_vm'])) {
			$libVirt->virsh_passthru('domjobinfo --domain ' . $_SESSION['selected_vm']);
		}
		else {
			echo 'VM is not running.' . PHP_EOL;
		}
		?></pre>
		<!-- <h1>domtime</h1>
		<pre><?php

		// passthru('virsh domtime ' . escapeshellarg($_SESSION['selected_vm']) . ' 2>&1');
		// Working: false
		// Outputs: error: argument unsupported: QEMU guest agent is not configured

		?></pre> -->
		<!-- <h1>domfsinfo</h1>
		<pre><?php

		// passthru('virsh domfsinfo ' . escapeshellarg($_SESSION['selected_vm']) . ' 2>&1');

		// Working: false
		// Outputs: error: Unable to get filesystem information
		// Outputs: error: argument unsupported: QEMU guest agent is not configured

		?></pre> -->
		<!-- <h1>domhostname</h1>
		<pre><?php

		// passthru('virsh domhostname ' . escapeshellarg($_SESSION['selected_vm']) . ' 2>&1');

		// Working: false
		// Outputs: error: failed to get hostname
		// Outputs: error: this function is not supported by the connection driver: virDomainGetHostname
		
		?></pre> -->
	</div>
</div>