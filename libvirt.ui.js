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

	// Parse client query
	if (window.location.search !== '') {
		var params = new URLSearchParams(window.location.search);
		console.info('Parsed query:', params.toString());
		for (var p of params) {
			console.log(p);
		}
	}

	// Init polling from modules
	if (params && params.get('module') === 'dsh') {
		var elements = ['cpu', 'mem', 'node'];
		var delayedPolling = setTimeout(function() {
			var polling = setInterval(function() {
				elements.forEach(function (value, index) {
					console.info('Request data id:', index, '| type:', value);
					getJSONData(value);
				});
			}, 1000);
			clearTimeout(delayedPolling);
		}, 500);
	}
	if (params && params.get('module') === 'vmi') {
		var elements = ['vhostcpu', 'vcpu', 'vdsk', 'vmem', 'vnet', 'vhost'];
		var delayedPolling = setTimeout(function() {
			var polling = setInterval(function() {
				elements.forEach(function (value, index) {
					console.info('Request data id:', index, '| type:', value);
					getJSONData(value, params.get('name'));
				});
			}, 1000);
			clearTimeout(delayedPolling);
		}, 500);
	}
});