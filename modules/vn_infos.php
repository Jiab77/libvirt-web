
<!-- Module -->
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
		<!-- <h5>Raw data</h5>
		<pre><?php
		print_r($output_ips);
		var_dump($ret_ips);
		?></pre> -->

		<?php endforeach; ?>

	</div>
</div>