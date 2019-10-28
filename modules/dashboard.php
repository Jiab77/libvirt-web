
<!-- Module -->
<div class="row">
	<div class="col s6">
		<h3>CPU</h3>
		<pre id="cpu-stats"><?php
		$libVirt->virsh_passthru('nodecpustats');
		?></pre>
	</div>
	<div class="col s6">
		<h3>Memory</h3>
		<pre id="mem-stats"><?php
		$libVirt->virsh_passthru('nodememstats');
		?></pre>
	</div>
	<div class="col s12">
		<blockquote>The data displayed above will be converted into realtime graph soon.</blockquote>
	</div>
	<div class="col s6">
		<h3>Node</h3>
		<pre id="node-info"><?php
		$libVirt->virsh_passthru('nodeinfo');
		?></pre>
	</div>
	<div class="col s6">
		<h3>Map</h3>
		<pre><?php
		$libVirt->virsh_passthru('nodecpumap');
		?></pre>
	</div>
</div>
<div class="row">
	<div class="col s12">
		<h3>Running VM's</h3>
		<table class="striped responsive-table">
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

			<?php
			$output_vms = ''; $ret_vms = '';
			exec('virsh list', $output_vms, $ret_vms);
			if (isset($output_vms) && !empty($output_vms)) {
				$libVirt->create_table_active_vms_rows($output_vms, '  ', 5, 'center-align');
			}
			?>

			</tbody>
			<tfoot>
				<tr>
					<td colspan="3">Max instances: <?php $libVirt->virsh_passthru('maxvcpus'); ?></td>
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
		<h3>Active IP's</h3>

		<?php foreach ($_SESSION['vm_networks'] as $network): ?>

		<table class="striped responsive-table">
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

			<?php
			$output_ips = ''; $ret_ips = '';
			exec('virsh net-dhcp-leases ' . $network, $output_ips, $ret_ips);
			if (isset($output_ips) && !empty($output_ips)) {
				$libVirt->create_table_active_ips_rows($output_ips, '  ', 6, 'center-align');
			}
			?>

			</tbody>
		</table>
		<blockquote>Network &ldquo;<?php echo $network; ?>&rdquo;</blockquote>
		<!-- <h5>Raw data</h5>
		<pre><?php
		print_r($output_ips);
		var_dump($ret_ips);
		?></pre> -->

		<?php endforeach; ?>

	</div>
</div>