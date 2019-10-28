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
	var progressBarContainer = document.getElementById("uploadProgress"),
		progressBar = document.getElementById("progressValue"),
		progressStatus = document.getElementById("uploadStatus");

	progressStatus.innerHTML = 'Uploading...';

	if (progressBarContainer.style.display === 'none') {
		progressBarContainer.style.display = 'block';
	}
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
	if (type && typeof response === 'object') {
		switch (type) {
			case 'cpu':
				$('#cpu-stats').text(response.html);
				break;
			case 'mem':
				$('#mem-stats').text(response.html);
				break;
			case 'node':
				$('#node-info').text(response.html);
				break;
			case 'vhostcpu':
				$('#vhostcpu-stats').text(response.html);
				break;
			case 'vcpu':
				$('#vcpu-stats').text(response.html);
				break;
			case 'vdsk':
				$('#vdsk-stats').text(response.html);
				break;
			case 'vmem':
				$('#vmem-stats').text(response.html);
				break;
			case 'vnet':
				$('#vnet-stats').text(response.html);
				break;
			case 'vhost':
				$('#vhost-stats').text(response.html);
				break;
		}
	}
	else {
		return false;
	}
}