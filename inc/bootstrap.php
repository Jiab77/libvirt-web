<?php
/*
MIT License

Copyright (c) 2021 Jonathan Barda

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

// Call the 'PPM' Image reader class
// Class code is taken from: https://www.webdecker.de/artikel/items/php-ppm-image-file-reader.html
require_once __DIR__ . '/../libs/PpmImageReader.php';

// Include our own classes
require_once __DIR__ . '/../libs/libvirt.php';
require_once __DIR__ . '/../libs/libvirt.xml.php';
require_once __DIR__ . '/../libs/libvirt.sess.php';

// Init our own classes
$libVirt = new libVirt();
$libVirtXML = new libVirtXML();

// Interface config
if (!isset($_SESSION['vm_networks']) ||
	(isset($_SESSION['vm_networks']) && !is_array($_SESSION['vm_networks']))) {
		$_SESSION['vm_networks'] = $libVirt->get_vm_networks();
}
if (!isset($_SESSION['vm_pools']) ||
	(isset($_SESSION['vm_pools']) && !is_array($_SESSION['vm_pools']))) {
		$_SESSION['vm_pools'] = $libVirt->get_vm_pools();
}
if (isset($_SESSION) && is_array($_SESSION) &&
	isset($_SESSION['module']) && $_SESSION['module'] !== 'ajx') {
		/* echo '<!-- ' . PHP_EOL;
		echo 'Server session:' . PHP_EOL;
		print_r($_SESSION);
		echo ' -->' . PHP_EOL; */
}

// Content selector
if (isset($_GET['module']) && !empty($_GET['module'])) {
	$module = htmlentities(strip_tags(filter_var($_GET['module'], FILTER_SANITIZE_STRING)));
	$_SESSION['module'] = $module;
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
	$vm_action = htmlentities(strip_tags(filter_var($_GET['action'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['do']) && !empty($_GET['do'])) {
	$module_action = htmlentities(strip_tags(filter_var($_GET['do'], FILTER_SANITIZE_STRING)));
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
	$selected_vm = htmlentities(strip_tags(filter_var($_GET['name'], FILTER_SANITIZE_STRING)));
	$_SESSION['selected_vm'] = $selected_vm;
}
if (isset($_GET['uri']) && !empty($_GET['uri'])) {
	$connect_uri = htmlentities(strip_tags(filter_var($_GET['uri'], FILTER_SANITIZE_STRING)));
	$_SESSION['connect_uri'] = $connect_uri;
}
if (isset($_GET['user']) && !empty($_GET['user'])) {
	$connect_user = htmlentities(strip_tags(filter_var($_GET['user'], FILTER_SANITIZE_STRING)));
	$_SESSION['connect_user'] = $connect_user;
}

// Displayed title
$hypervisor  = json_decode($libVirtXML->xml2json($libVirt->virsh_shell_exec('sysinfo'), false, false));
$host_title  = $hypervisor->system->entry[0]->attributes->{'nodeValue'};
$host_title .= ' ';
$host_title .= $hypervisor->system->entry[1]->attributes->{'nodeValue'};
$project_title = 'libVirt Web';
if (isset($module) && !empty($module)) {
	switch ($module) {
		case 'dsh': $module_title = 'Dashboard'; break;
		case 'hyp': $module_title = 'Hypervisor'; break;
		case 'vmi': $module_title = 'Virtual Machine &ndash; ' . $_SESSION['selected_vm']; break;
		case 'vms': $module_title = 'Virtual Machines'; break;
		case 'vni': $module_title = 'Virtual Network &ndash; ' . $_SESSION['selected_vm']; break;
		case 'vns': $module_title = 'Virtual Networks'; break;
		case 'vst': $module_title = 'Virtual Storage'; break;
		case 'hlp': $module_title = 'Help'; break;
		default: $module_title = ''; break;
	}

	$page_title  = $project_title;
	$page_title .= (!empty($host_title) ? ' &ndash; ' . $host_title : '');
	$page_title .= (!empty($module_title) ? ' &ndash; ' . $module_title : '');
}
else {
	$page_title  = $project_title;
	$page_title .= (!empty($host_title) ? ' &ndash; ' . $host_title : '');
}
