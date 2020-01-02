
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>Networks</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>State</th>
					<th>Autostart</th>
					<th>Persistent</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vns = ''; $ret_vns = '';
			exec('virsh net-list', $output_vns, $ret_vns);
			if (isset($output_vns) && !empty($output_vns)) {
				$libVirt->create_table_generic_rows($output_vns, '  ', 4, 'center-align');
			}
			?>

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
<div class="row">
	<div class="col s12">
		<h3>Network Filters</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>UUID</th>
					<th>Name</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vnfs = ''; $ret_vnfs = '';
			exec('virsh nwfilter-list', $output_vnfs, $ret_vnfs);
			if (isset($output_vnfs) && !empty($output_vnfs)) {
				$libVirt->create_table_generic_rows($output_vnfs, '  ', 2, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_vnfs);
		var_dump($ret_vnfs);
		?></pre> -->
	</div>
</div>