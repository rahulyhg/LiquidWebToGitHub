
//start the service to update page data
function startUpdateService() {
	var updateServiceID = window.setInterval(function() { updateService() }, 10000); //check for new data every 10 seconds
	localStorage.setItem(window.name +',updateServiceID',updateServiceID); //store the ID from setInterval
	var updateWatchdogServiceID = window.setInterval(function() { updateWatchdogService() }, 60000); //check the update service every 60 seconds
	localStorage.setItem(window.name +',updateWatchdogServiceID',updateWatchdogServiceID); //store the ID from setInterval
}

function updateQB(source) { //push updates to Quickbase via php for defocused input
	var fieldName = source.name;
	var nameInfo = fieldName.split(',');
	var action = nameInfo[0];
	if (!source.id) { source.setAttribute('id', 'pgen'+(new Date).getTime()); } 
	$.ajax({
		cache: false,
		url: 'res/func.php',
		type: 'POST',
		context: document.body,
		dataType: 'json',
		data: { ajax: action,
			name: fieldName,
			value: source.value,
			id: source.id,
			lastUpdate: localStorage.getItem(window.name +',updateServiceServerTime'),
			job: localStorage.getItem(window.name +',job'),
			unit: localStorage.getItem(window.name +',unit') },
		success: function(response) { //returns a json object of updates
			localStorage.setItem(window.name + ',updateServiceServerTime', response.time);
			delete response.time;
			doJsonUpdates(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.dir(jqXHR);
			console.dir(textStatus);
			console.dir(errorThrown);
		}
	});
}

function updateService() {  //periodically check for and get updates to the page we're on
	$.ajax({
		cache: false,
		url: 'res/func.php',
		type: 'POST',
		context: document.body,
		dataType: 'json',
		data: { ajax: 'updateClient',
				lastUpdate: localStorage.getItem(window.name +',updateServiceServerTime'),
				job: localStorage.getItem(window.name +',job'),
				unit: localStorage.getItem(window.name +',unit') },
		success: function(response) { //returns a json object of updates
			if (response.time) {
				localStorage.setItem(window.name + ',updateServiceServerTime', response.time);
				delete response.time;
			}
			doJsonUpdates(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.dir(jqXHR);
			console.dir(textStatus);
			console.dir(errorThrown);
		}
	});
	localStorage.setItem(window.name +',updateServiceLastRun',(new Date).getTime()); //store the run time
}

function updateWatchdogService() {
	if (localStorage.getItem(window.name +',updateServiceLastRun') == localStorage.getItem(window.name + ',watchdogServiceLastRun')) { //update service has not run
		console.log('update service stopped updating, killing and restarting...');
		window.clearInterval(localStorage.getItem(window.name + ',updateServiceID'));  //clear the old timer incase it's still alive
		var updateServiceID = window.setInterval(updateService(), 500); //check for new data every half second
		localStorage.setItem(window.name +',updateServiceID',updateServiceID); //store the ID from setInterval
	}
	localStorage.setItem(window.name +',watchdogServiceLastRun',localStorage.getItem(window.name +',updateServiceLastRun')); //store the run time
	console.log('updateWatchdogService run: '+localStorage.getItem(window.name + ',updateServiceLastRun'));
}

function doJsonUpdates(json) {
	if (json.length) {
		console.log ('jason updates');
		console.dir(json);
		for (var key in json) {
			if (document.getElementById(key)) { //element exists
				var result = document.getElementById(key)[json[key]['type']] = json[key]['value']; //update element
				console.log(key + " " + json[key]['type'] + " = " + json[key]['value']);
				console.log(result);
			}
		}
	}
}

function addOnBlur() {
	var inputs = document.getElementsByTagName('input');
	for (var inputNo=0; inputNo < inputs.length; inputNo++) {
		if (inputs[inputNo].type != 'hidden') {
			inputs[inputNo].setAttribute("onblur", "updateQB(this);");
		}
	}
}

//do our startup stuff
document.addEventListener("DOMContentLoaded", function(event) {
	//wait half a second for slow devices
	window.setTimeout(function() {
		localStorage.setItem(window.name + ',job', document.getElementById('job').value);
		localStorage.setItem(window.name + ',unit', document.getElementById('unit').value);
		localStorage.setItem(window.name + ',updateServiceServerTime', document.getElementById('serverLoadTime').value);
		if (window.name == '') { window.name = 'qb_portal' + (new Date).getTime(); } //name an unamed window
		startUpdateService();
		addOnBlur();
	}, 500);
});