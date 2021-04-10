"use strict";

// App
$(document).ready(function() {
	// UI Elements
	$('.button-collapse').sideNav();
	$('.tooltipped').tooltip({delay: 50});
	$('.materialboxed').materialbox();
	$('.dropdown-button').dropdown({
		inDuration: 300,
		outDuration: 225,
		constrainWidth: false, // Does not change width of dropdown to that of the activator
		hover: true, // Activate on hover
		gutter: 0, // Spacing from edge
		belowOrigin: false, // Displays dropdown below the button
		alignment: 'left', // Displays dropdown with edge aligned to the left of button
		stopPropagation: false // Stops event propagation
	});
	$('.modal').modal({
		dismissible: true, // Modal can be dismissed by clicking outside of the modal
		opacity: .5, // Opacity of modal background
		inDuration: 300, // Transition in duration
		outDuration: 200, // Transition out duration
		startingTop: '4%', // Starting top style attribute
		endingTop: '10%', // Ending top style attribute
		ready: function(modal, trigger) { // Callback for Modal open. Modal and trigger parameters available.
			console.group('Modal');
			console.info('Modal open.', modal, trigger);
			console.groupEnd();
		},
		complete: function(modal) { // Callback for Modal close
			console.group('Modal');
			console.info('Modal close.', modal);

			// Code for hypervisor connection
			if (modal[0].id === 'modal-connect') {
				var $user = $('#connect-user'),
					$host = $('#connect-host');

				if ($user.hasClass('valid') && $host.hasClass('valid')) {
					console.log('do remote connection!', $user, $host);
				}
				else {
					console.log('do local connection.');
				}
			}

			console.groupEnd();
		}
	});

	// User events bindings
	$('a[href="#!"]').on('click', function(event) {
		event.preventDefault();
	});
	$('.display-expand').on('click', function(event) {
		event.preventDefault();
		$('#variable-container').toggleClass('container');
	});
	$('#uploadForm').on('submit', function(event) {
		event.preventDefault();
	});
	$('#connectForm').on('submit', function(event) {
		event.preventDefault();
	});
	$('input[name="connect-mode"]').on('change', function(event) {
		var $connector = $('#connect-ssh');
		if (event.target.id === 'connect-mode-ssh') {
			console.log('Connect mode "ssh" selected.', event, $connector);
			if (event.target.checked === true) {
				console.log('Show input field.');
				$connector.show('slow');
			}
		}
		else {
			if ($connector && $connector.is(':visible') === true) {
				console.log('Hide input field.');
				$connector.hide('slow');
			}
		}
	});

	// Parse client query
	if (window.location.search !== '') {
		var params = new URLSearchParams(window.location.search);
		console.info('Parsed query:', params.toString());
		for (var p of params) {
			console.log(p);
		}
	}

	// Init polling from modules
	if (params) {
		switch (params.get('module')) {
			case 'dsh':
				var elements = ['cpu', 'mem', 'node', 'preview'];
				var delayedPolling = setTimeout(function() {
					var shortPolling = setInterval(function() {
						elements.forEach(function (value, index) {
							if (value === 'preview') { return; }
							console.info('Request data id:', index, '| type:', value);
							getJSONData(value);
						});
					}, 1000);
					clearTimeout(delayedPolling);
					$(window).one('unload', function () {
						clearInterval(shortPolling);
					});
				}, 100);
				var delayedPolling2 = setTimeout(function() {
					var shortPolling2 = setInterval(function() {
						elements.forEach(function (value, index) {
							if (value !== 'preview') { return; }
							$('.live-preview').each(function() {
								console.info('Request data id:', index, '| type:', value);
								getJSONData(value, $(this).data('vm'));
							});
						});
					}, 5000);
					clearTimeout(delayedPolling2);
					$(window).one('unload', function () {
						clearInterval(shortPolling2);
					});
				}, 100);
				break;

			case 'vms':
				var elements = ['preview'];
				var delayedPolling = setTimeout(function() {
					var longPolling = setInterval(function() {
						elements.forEach(function (value, index) {
							console.info('Request data id:', index, '| type:', value);
							$('.live-preview').each(function() {
								getJSONData(value, $(this).data('vm'));
							});
						});
					}, 5000);
					clearTimeout(delayedPolling);
					$(window).one('unload', function () {
						clearInterval(longPolling);
					});
				}, 100);
				break;

			case 'vmi':
				var elements = ['vhostcpu', 'vcpu', 'vdsk', 'vmem', 'vnet', 'vhost'];
				var delayedPolling = setTimeout(function() {
					var shortPolling = setInterval(function() {
						elements.forEach(function (value, index) {
							console.info('Request data id:', index, '| type:', value);
							getJSONData(value, params.get('name'));
						});
					}, 1000);
					clearTimeout(delayedPolling);
					$(window).one('unload', function () {
						clearInterval(shortPolling);
					});
				}, 100);
				break;

			default:
				break;
		}
	}
});
