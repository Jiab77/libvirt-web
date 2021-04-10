"use strict";

// File upload
function updateSize() {
	var nBytes = 0,
		oFiles = document.getElementById("uploadFiles").files,
		nFiles = oFiles.length,
		file;

	// Display file info
	console.info('Uploading files...', oFiles);
	for (var i = 0; i < oFiles.length; i++) {
		file = oFiles[i];
		console.table(file);
		uploadFile(file);
	}

	// Get human sizes
	for (var nFileId = 0; nFileId < nFiles; nFileId++) {
		nBytes += oFiles[nFileId].size;
	}
	var sOutput = nBytes + " bytes";
	for (var aMultiples = ["KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"], nMultiple = 0, nApprox = nBytes / 1024; nApprox > 1; nApprox /= 1024, nMultiple++) {
		sOutput = nApprox.toFixed(3) + " " + aMultiples[nMultiple] + " (" + nBytes + " bytes)";
	}

	// Display file size
	document.getElementById("uploadSize").style.display = 'block';
	document.getElementById("fileNum").innerHTML = nFiles;
	document.getElementById("fileSize").innerHTML = sOutput;
}
function uploadFile(file){
	var url = '/?module=hyp';
	var xhr = new XMLHttpRequest();
	var fd = new FormData();

	// Assign required events
	xhr.upload.addEventListener('progress', updateProgress);
	xhr.upload.addEventListener('load', uploadComplete);
	xhr.upload.addEventListener("error", uploadFailed);
	xhr.upload.addEventListener("abort", uploadCanceled);

	// Create upload request
	xhr.open("POST", url, true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && xhr.status == 200) {
			// Everything ok, file uploaded
			console.log(xhr.responseText); // handle response.
		}
	};
	fd.append("upload_file", file);
	xhr.send(fd);
}
function updateProgress(event) {
	console.log('Progress Event:', event);

	// Progress bar elements
	var progressBarContainer = document.getElementById("uploadProgress"),
		progressBar = document.getElementById("progressValue"),
		progressStatus = document.getElementById("uploadStatus");

	// Progress bar status
	progressStatus.innerHTML = 'Uploading...';

	// Progress bar display
	if (progressBarContainer.style.display === 'none') {
		progressBarContainer.style.display = 'block';
	}

	// Compute progress bar data
	if (event.lengthComputable) {
		var percentComplete = Math.ceil(event.loaded / event.total * 100);

		progressBar.style.width = percentComplete + '%';
		progressStatus.innerHTML = 'Uploading (' + percentComplete + '%)...';
		console.log('Progress:', percentComplete + '%');

		if (percentComplete === 100) {
			progressBarContainer.style.display = 'none';
			progressBar.style.width = '0%';
			console.log('Progress: Finished.');
		}
	}

	// Failed to compute data
	else {
		// Unable to compute progress information since the total size is unknown
		progressBarContainer.style.display = 'none';
		progressBar.style.width = '0%';
		console.log('Progress: Error.');
	}
}
function uploadComplete(event) {
	console.log('Load Event:', event);
	document.getElementById("uploadStatus").innerHTML = 'Finished.';
}
function uploadFailed(event) {
	console.log('Load Event:', event);
	document.getElementById("uploadStatus").innerHTML = 'Error.';
}
function uploadCanceled(event) {
	console.log('Load Event:', event);
	document.getElementById("uploadStatus").innerHTML = 'Canceled.';
}
// From: https://stackoverflow.com/questions/5796718/html-entity-decode#comment98534802_42182294
// And: https://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery/46685127#46685127
function escapeHtml(str, preserve) {
	if (preserve && preserve === true) {
		var textArea = document.createElement('textarea');
		textArea.innerHTML = str;
		return textArea.value;
	}
	else {
		var div = document.createElement('div');
		div.innerText = str;
		return div.innerHTML;
	}
}
// From: https://stackoverflow.com/questions/5796718/html-entity-decode/28543554#28543554
function htmlDecode(str, preserve) {
	if (preserve && preserve === true) {
		return $("<textarea/>").html(value).text();
	}
	else {
		return $("<div/>").html(str).text();
	}
}
function htmlEncode(str, preserve) {
	if (preserve && preserve === true) {
		return $('<textarea/>').text(value).html();
	}
	else {
		return $('<div/>').text(str).html();
	}
}

// Data polling
function getJSONData(data, name) {
	if (!data) return false;
	var url = '/?module=ajx&data=' + data + (name ? '&name=' + name : '');
	$.getJSON(url, function(response, status, jqXHR) {
		console.log('Fetching %s...', url);
		// console.log(response, status, jqXHR);
		return refreshStats(data, response);
	});
}
function refreshStats(type, response) {
	console.log('Received:', response);

	if (type && typeof response === 'object') {
		switch (type) {
			case 'cpu':
				$('#cpu-stats').text(response.content);
				break;
			case 'mem':
				$('#mem-stats').text(response.content);
				break;
			case 'node':
				$('#node-info').text(response.content);
				break;
			case 'vhostcpu':
				$('#vhostcpu-stats').text(response.content);
				break;
			case 'vcpu':
				$('#vcpu-stats').text(response.content);
				break;
			case 'vdsk':
				$('#vdsk-stats').text(response.content);
				break;
			case 'vmem':
				$('#vmem-stats').text(response.content);
				break;
			case 'vnet':
				$('#vnet-stats').text(response.content);
				break;
			case 'vhost':
				$('#vhost-stats').text(response.content);
				break;
			case 'preview':
				$('.live-preview').filter('[data-vm="' + response.vm + '"]').find('img').attr('src', response.content);
				break;
		}
		return true;
	}
	else {
		return false;
	}
}
