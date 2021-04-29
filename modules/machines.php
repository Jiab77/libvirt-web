
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>Machines</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Id</th>
					<th>Name</th>
					<th>State</th>
					<th>Preview</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vms = ''; $ret_vms = '';
			exec('virsh list --all', $output_vms, $ret_vms);
			if (isset($output_vms) && !empty($output_vms)) {
				$libVirt->create_table_vms_rows($output_vms, '  ', 5, 'center-align');
			}
			?>

			</tbody>
			<tfoot>
				<tr>
					<td colspan="5"><?php echo 'Total: ' . (count($output_vms)-3); ?></td>
				</tr>
			</tfoot>
		</table>
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_vms);
		var_dump($ret_vms);
		?></pre> -->
		<?php if (isset($action, $output_action, $ret_action) && !empty($output_action)): ?>
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
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_ips);
		var_dump($ret_ips);
		?></pre> -->

		<?php endforeach; ?>

	</div>
</div>
