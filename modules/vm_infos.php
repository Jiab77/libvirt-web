
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>Details</h3>
		<div class="row">
			<div class="col s7">
				<h5>Summary</h5>
				<pre><?php
				$libVirt->virsh_passthru('dominfo ' . $selected_vm);
				?></pre>
			</div>
			<div class="col s5">
				<h5>Hypervisor</h5>
				<pre id="vhostcpu-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($selected_vm)) {
					$libVirt->virsh_passthru('cpu-stats ' . $selected_vm);
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
				if ($libVirt->vm_is_active($selected_vm)) {
					print_r($libVirt->get_vm_cpu_stats($selected_vm));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_cpu_stats($selected_vm));
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Memory</h5>
				<pre id="vmem-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($selected_vm)) {
					// $libVirt->virsh_passthru('dommemstat ' . $selected_vm);
					print_r($libVirt->get_vm_mem_stats($selected_vm));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
					print_r($libVirt->get_vm_mem_stats($selected_vm));
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Network</h5>
				<pre id="vnet-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($selected_vm)) {
					print_r($libVirt->get_vm_net_stats($selected_vm));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
				}
				?></pre>
			</div>
			<div class="col s4">
				<h5>Disk</h5>
				<pre id="vdsk-stats" style="height: 300px;"><?php
				if ($libVirt->vm_is_active($selected_vm)) {
					print_r($libVirt->get_vm_disk_stats($selected_vm));
				}
				else {
					echo 'VM is not running.' . PHP_EOL;
				}
				?></pre>
			</div>
			<div class="col s8">
				<h5>Global</h5>
				<pre id="vhost-stats" style="height: 300px;"><?php
				$libVirt->virsh_passthru('domstats --raw ' . $selected_vm);
				?></pre>
				<!-- <pre style="height: 300px;"><?php
				print_r($libVirt->parse_vm_stats($selected_vm, true, true));
				?></pre> -->
			</div>
		</div>

		<?php /*
		<h3>
			Virtual CPU's
			<i class="material-icons tooltipped light-blue-text text-darken-1" style="cursor: pointer;" data-position="right" data-tooltip="View CPU Stats" onclick="$('#modal-cpu-stats').modal('open');">info_outline</i>
		</h3>
		<pre><?php
		$libVirt->virsh_passthru('vcpucount ' . $selected_vm);
		?></pre>
		<div id="modal-cpu-stats" class="modal modal-fixed-footer">
			<div class="modal-content">
				<h4>CPU Stats</h4>
				<pre><?php
				$libVirt->virsh_passthru('vcpuinfo ' . $selected_vm);
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
			// $libVirt->virsh_passthru('domiflist ' . $selected_vm);

			$output_ifaces = ''; $ret_ifaces = '';
			$libVirt->virsh_exec('domiflist ' . $selected_vm, $output_ifaces, $ret_ifaces);
			if (isset($output_ifaces) && !empty($output_ifaces)) {
				$libVirt->create_table_generic_rows($output_ifaces, '  ', 5, 'center-align');
			}
			?>

			</tbody>
		</table>
		<h6>Raw data</h6>
		<pre><?php
		print_r($output_ifaces);
		var_dump($ret_ifaces);
		?></pre>
		<h5>Addresses</h5>
		<pre><?php
		if ($selected_vm === 'ceph-admin') {
			$libVirt->virsh_passthru('domifaddr ' . $selected_vm . ' --interface vnet0');
		}
		else {
			echo 'Not written yet...' . PHP_EOL;
		}
		?></pre>
		<h3>Attached devices</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Target</th>
					<th>Source</th>
				</tr>
			</thead>
			<tbody>

			<?php
			// $libVirt->virsh_passthru('domblklist ' . $selected_vm);

			$output_devs = ''; $ret_devs = '';
			$libVirt->virsh_exec('domblklist ' . $selected_vm, $output_devs, $ret_devs);
			if (isset($output_devs) && !empty($output_devs)) {
				$libVirt->create_table_generic_rows($output_devs, '  ', 2, 'center-align');
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
			// $libVirt->virsh_passthru('snapshot-list ' . $selected_vm);

			$output_snaps = ''; $ret_snaps = '';
			$libVirt->virsh_exec('snapshot-list ' . $selected_vm, $output_snaps, $ret_snaps);
			if (isset($output_snaps) && !empty($output_snaps)) {
				$libVirt->create_table_generic_rows($output_snaps, '  ', 3, 'center-align');
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
		if ($libVirt->vm_is_active($selected_vm)) {
			$libVirt->virsh_passthru('domjobinfo ' . $selected_vm);
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