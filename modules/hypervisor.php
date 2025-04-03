
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h1>Hypervisor</h1>

		<?php if (!isset($_GET['do']) || (isset($_GET['do']) && empty($_GET['do']))): ?>

		<div class="row">
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=view'; ?>">
							<i class="material-icons">developer_board</i>
							<br><span class="truncate">System Info</span>
						</a>
					</p>
				</div>
			</div>
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=vm'; ?>">
							<i class="material-icons">computer</i>
							<br><span class="truncate">Create VM</span>
						</a>
					</p>
				</div>
			</div>
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=net'; ?>">
							<i class="material-icons">router</i>
							<br><span class="truncate">Create Network</span>
						</a>
					</p>
				</div>
			</div>
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=img'; ?>">
							<i class="material-icons">storage</i>
							<br><span class="truncate">Create Images</span>
						</a>
					</p>
				</div>
			</div>
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="<?php echo '?module=' . $_SESSION['module'] . '&do=create&type=sto'; ?>">
							<i class="material-icons">storage</i>
							<br><span class="truncate">Create Storage</span>
						</a>
					</p>
				</div>
			</div>
			<div class="col s6 m4 l3">
				<div class="card-panel hoverable">
					<p class="flow-text center-align">
						<a href="#modal-upload" class="modal-trigger">
							<i class="material-icons">storage</i>
							<br><span class="truncate">Upload Images</span>
						</a>
					</p>
				</div>
			</div>
		</div>

		<?php endif; ?>
		<?php
		if (isset($_GET['do']) && !empty($_GET['do'])) {
			switch ($_GET['do']) {
				case 'create':
					if (isset($_GET['type']) && !empty($_GET['type'])) {
						switch ($_GET['type']) {
							case 'img':
								$create_type = ' Images';
								$create_cmd  = 'virt-builder --help';
								$virsh = false;
								break;

							case 'net':
								$create_type = ' Network';
								$create_cmd  = 'help net-create';
								$virsh = true;
								break;

							case 'sto':
								$create_type = ' Storage';
								$create_cmd  = 'help pool-create-as';
								$virsh = true;
								break;

							case 'vm':
								$create_type = ' VM';
								// $create_cmd  = 'help create';
								$create_cmd  = 'virt-install --help';
								$virsh = false;
								break;

							default:
								$create_type = '';
								$create_cmd  = '';
								$virsh = false;
								break;
						}
					}
					?>

		<div class="row">
			<div class="col s12">
				<h3><?php echo 'Create' . $create_type; ?></h3>
				<!-- <h5>Parsed data</h5> -->
				<pre style="height: 300px;"><?php
				if (isset($create_cmd) && !empty($create_cmd)) {
					if ($virsh === true) {
						echo htmlentities($libVirt->virsh_shell_exec($create_cmd));
					}
					else {
						echo htmlentities($libVirt->shell_exec_cmd($create_cmd));
					}
				}
				?></pre>
			</div>
		</div>

					<?php
					break;

				case 'view':
					?>

		<div class="row">
			<div class="col s12">
				<h3>System Info</h3>

				<?php if ($libVirt->connect_info()['auth'] !== 'session'): ?>

				<h6>Parsed data</h6>
				<pre style="height: 300px;"><?php
				print_r($libVirtXML->xml2json($libVirt->virsh_shell_exec('sysinfo'), false, true));
				?></pre>
				<h6>Raw data</h6>
				<pre style="height: 300px;"><?php
				echo htmlentities($libVirt->virsh_shell_exec('sysinfo'));
				?></pre>

				<?php else: ?>

				<p><strong style="color: red;">Please run this project as 'root' to view this section.</strong></p>

				<?php endif; ?>

			</div>
		</div>

					<?php
					break;

				default:
					# code...
					break;
			}
		}
		?>
	</div>
</div>

<!-- Modals -->
<div id="modal-upload" class="modal modal-fixed-footer">
	<div class="modal-content grey-text text-darken-3">
		<h4>Upload Files</h4>
		<div class="row">
			<div class="col s12">
				<!-- <form action="<?php echo htmlentities(strip_tags((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])); ?>"> -->
				<form id="uploadForm">
					<div class="file-field input-field">
						<div class="btn">
							<span>File</span>
							<input type="file" id="uploadFiles" onchange="updateSize();" multiple>
						</div>
						<div class="file-path-wrapper">
							<input class="file-path validate" type="text" placeholder="Upload one or more files">
						</div>
					</div>
					<div id="uploadProgress" class="progress" style="display: none;">
						<div id="progressValue" class="determinate" style="width: 0%"></div>
					</div>
					<p id="uploadSize" style="display: none;">
						Total: <span id="fileNum"></span><br>
						Size: <span id="fileSize"></span><br>
						Status: <span id="uploadStatus"></span>
					</p>
				</form>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
	</div>
</div>
