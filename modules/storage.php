
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>Storage Pools</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>State</th>
					<th>Autostart</th>
					<th>Persistent</th>
					<th>Capacity</th>
					<th>Allocation</th>
					<th>Available</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vsp = ''; $ret_vsp = '';
			exec('virsh pool-list --all --details', $output_vsp, $ret_vsp);
			if (isset($output_vsp) && !empty($output_vsp)) {
				$libVirt->create_table_generic_rows($output_vsp, '  ', 7, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_vsp);
		var_dump($ret_vsp);
		?></pre> -->
	</div>
</div>
<div class="row">
	<div class="col s12">
		<h3>Volumes</h3>
		<table class="striped responsive-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Path</th>
					<th>Type</th>
					<th>Capacity</th>
					<th>Allocation</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vsv = ''; $ret_vsv = '';
			exec('virsh vol-list --details --pool default', $output_vsv, $ret_vsv);
			if (isset($output_vsv) && !empty($output_vsv)) {
				$libVirt->create_table_generic_rows($output_vsv, ' ', 5, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h6>Raw data</h6>
		<pre><?php
		print_r($output_vsv);
		var_dump($ret_vsv);
		?></pre> -->
	</div>
</div>