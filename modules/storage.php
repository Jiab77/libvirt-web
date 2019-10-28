
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
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vsp = ''; $ret_vsp = '';
			exec('virsh pool-list', $output_vsp, $ret_vsp);
			if (isset($output_vsp) && !empty($output_vsp)) {
				$libVirt->create_table_generic_rows($output_vsp, '  ', 3, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h5>Raw data</h5>
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
				</tr>
			</thead>
			<tbody>

			<?php
			$output_vsv = ''; $ret_vsv = '';
			exec('virsh vol-list default', $output_vsv, $ret_vsv);
			if (isset($output_vsv) && !empty($output_vsv)) {
				$libVirt->create_table_generic_rows($output_vsv, ' ', 2, 'center-align');
			}
			?>

			</tbody>
		</table>
		<!-- <h5>Raw data</h5>
		<pre><?php
		print_r($output_vsv);
		var_dump($ret_vsv);
		?></pre> -->
	</div>
</div>