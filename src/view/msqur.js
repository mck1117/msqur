/* msqur - MegaSquirt .msq file viewer web application
Copyright 2014-2019 Nicholas Earwood nearwood@gmail.com https://nearwood.dev

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

"use strict";

var msqur = angular.module('msqur', []);

msqur.controller('BrowseController', function ($scope) {
	return true;
});

msqur.controller('SearchController', function ($scope) {
	
});

$(document).ready(function() {
    $('#browseResults')
		.bind('dynatable:init', function(e, dynatable) {
		    dynatable.sorts.add('uploaded', -1);
		})
    	.dynatable( {
			dataset: {
      			perPageDefault: 100
    		}
  		});

	$('.deleteLink').unbind('click').bind('click', function () {
        return confirm('Are you sure?');
    });
});

$(function() {
	$('div#upload').dialog({
		modal: true,
		autoOpen: false,
		title: "Upload Tune Files",
		width: "512px",
		buttons: hideUpload ? [
			{ text: "Cancel", click: function() { $(this).dialog('close'); } }
		] :
		[
			{ id: "dialogUpload", text: "Upload", click: uploadClick, },
			{ text: "Cancel", click: function() { $(this).dialog('close'); } }
		]
	});
	
	enableUploadButton(false);
	$('#btnUpload').click(function(e) {
		if (window.File && window.FileReader && window.FileList && window.Blob)
		{
			$('div#upload').dialog('open');
		} else {
			alert('The File APIs are not fully supported in this browser.');
			//TODO no ajax file upload
		}
	});
	
	$('#settingsIcon').click(function(e) {
		$('#settingsPanel').toggle();
	});
	
	$('select#firmware').change(function(){
		getFirmwareVersions($(this).children(":selected").html());
	});
	
	function getFirmwareVersions(fw) {
		$.ajax({
			url: "api.php?req=",
		}).done(function( html ) {
			$( "#results" ).append( html );
		});
	};
	
	var accordionOptions = {
		animate: false,
		active: false, collapsible: true, //to avoid hooking 'create' to make the first graph render
		heightStyle: "content",
		activate: doChart
	};

	$('div#tabList').tabs();
	
	$('div#tab_tables').accordion(accordionOptions);
	$('div#tab_curves').accordion(accordionOptions);
	$('div#tab_constants').tooltip();
	
	Chart.defaults.global.animation = false;
	//Chart.defaults.global.responsive = true;
	
	//2D charts
	function doChart(event, ui) {
		var that = ui.newPanel;

		// sort ASC for 2D and DESC for 3D tables
		$('table').tablesorter({sortList: [[0, (that.find('div.table').length != 0) ? 1 : 0]] });
		
		if (that.find('tbody').length == 0 || that.find('div.table').length != 0) return; //do nothing if panel is closing, or if 3d table
		
		//Find data
		var tbl = that.find('tbody').get(0);
		var data = tbl2data($(tbl));
		
		var ctx = that.find('canvas').get(0).getContext("2d");
		
		var config = {
			type: 'line',
			data: data,
			options: {
				legend: { display: false },
			}
		};

		var chart = new Chart(ctx, config);
	}
	
	function tbl2data(tbl)
	{
		var rows = tbl.find('tr');
		var lbls = [];
		var cells = [];
		
		rows.each(function(i) {
			var that = $(this); //ick
			
			//.html() gets first element in set, .text() all matched elements
			lbls.push(parseFloat(that.find('th').text()));
			cells.push(parseFloat(that.find('td').text()));
		});
		
		var data = {
			labels: lbls,
			datasets: [{
				label: "test", 
				data: cells,
				borderColor: 'blue',
			}],
		};
		
		return data;
	}
	
	function normalizeTable(table)
	{
		var min = Number.MAX_SAFE_INTEGER;
		var max = Number.MIN_SAFE_INTEGER;
		var nmin = 5;
		var nmax = 250;
		
		//Find min and max
		table.find('td').each(function(i) {
			var v = parseFloat(this.textContent);
			if (v < min) min = v;
			if (v > max) max = v;
		});
		
		//Precalculate some stuff
		var a = (nmax - nmin) / (max - min);
		var b = nmin - (a * min);
		
		//apply normalization
		table.find('td').each(function(i) {
			var v = parseFloat(this.textContent);
			var r = Math.round(a * v + b);
			this.textContent = "" + r;
		});
	}
	
	function resetTable(table)
	{
		//TODO Need to store old value (and new one if I care about client end)
	}
	
	function clearTableColor(table)
	{
		table.find('td').each(function(i) {
			this.style.backgroundColor = '';
		});
	}
	
	function colorTable(table, reverseColor)
	{//reverseColor could be an override, value passed in is currently ignored
		var min = Number.MAX_SAFE_INTEGER;
		var max = Number.MIN_SAFE_INTEGER;
		
		//Find min and max
		table.find('td').each(function(i) {
			var v = parseFloat(this.textContent);
			if (v < min) min = v;
			if (v > max) max = v;
		});
		
		if (table.attr('hot') == 'ascending')
			reverseColor = true;
		if (table.attr('hot') == 'descending')
			reverseColor = false;
		
		var range = (max - min);
		//console.debug("Range: " + range);
		var r = 0, g = 0, b = 0, percent = 0, intensity = 0.6;
		
		//MegaTune coloring scheme
		table.find('td').each(function(i) {
			var v = parseFloat(this.textContent);
			percent = (v - min) / range;
			
			if (reverseColor)
				percent = 1.0 - percent;
			
			if (percent < 0.33)
			{
				r = 1.0;
				g = Math.min(1.0, (percent * 3));
				b = 0.0;
			}
			else if (percent < 0.66)
			{
				r = Math.min(1.0, ((0.66 - percent) * 3));
				g = 1.0;
				b = 0.0;
			}
			else
			{
				r = 0.0;
				g = Math.min(1.0, ((1.0 - percent) * 3));
				b = 1.0 - g;
			}
			
			r = Math.round((r * intensity + (1.0 - intensity)) * 255);
			g = Math.round((g * intensity + (1.0 - intensity)) * 255);
			b = Math.round((b * intensity + (1.0 - intensity)) * 255);
			
			this.style.backgroundColor = 'rgb(' + r + ',' + g + ',' + b + ')';
		});
	}
	
	$('input#colorizeData').change(function () {
		if (this.checked)
			$('table.msq tbody').each(function(i) { colorTable($(this), true); });
		else
			$('table.msq tbody').each(function(i) { clearTableColor($(this)); });
	});
	
	//default
	$('input#colorizeData').prop('checked', true);
	$('table.msq tbody').each(function(i) { colorTable($(this), true); });
	
	$('input#normalizeData').change(function () {
		if (this.checked)
			$('table.msq.ve').each(function(i) { normalizeTable($(this)); });
		else
			$('table.msq.ve').each(function(i) { resetTable($(this)); });
	});
	
	function uploadAdd(e)
	{
		e.stopPropagation();
		e.preventDefault();
		
		onUpload();
		
		var files = e.target.files || e.dataTransfer.files
		//TODO type check
		var output = [];
		for (var i = 0, f; f = files[i]; ++i)
		{
			var ft = getFileType(f.name);
			output.push('<li><strong>', escape(f.name), '</strong> (', ft || 'n/a', ') - ',
			f.size, ' bytes',
			f.lastModifiedDate ? ', last modified: ' + f.lastModifiedDate.toLocaleDateString() : '',
			'</li>');
			if (ft == "Tune")
			{
				startGettingEngineInfoFromTuneFile(f);
			}
			else
			{
				// todo: add Log real-time parsing?
				//enableUploadButton(true);
			}
		}
		$('output#fileList').html('<ul>' + output.join('') + '</ul>');
	}

	function uploadDragOver(e)
	{
		e.stopPropagation();
		e.preventDefault();
		e.dataTransfer.dropEffect = 'copy';
	}
	
	function simpleValidation(s)
	{
		if (typeof s === 'string' && s.length > 0)
			return true;
		else
			return false;
	}
	
	function uploadClick()
	{
		$('div#upload form').submit();
	}
	
	function searchClick()
	{
		$('form#search').submit();
	}

	//////////////////////////////////////////////////////////////////////////////
	// [andreika]: rusEFI scripts

	function onUpload()
	{
		enableUploadButton(false);
		$('#engineInfo').hide();
		$('output#processing').html("");
	}

	function getFileType(filename)
	{
		var ext = filename.split('.').pop();
		if (ext == "msq")
			return "Tune";
		else if (ext == "msl" || ext == "msg")
			return "Log";
		return null;
	}

	function startGettingEngineInfoFromTuneFile(file)
	{
		var fr = new FileReader();
		fr.onload = onFileRead;
        fr.readAsText(file);

		function onFileRead()
		{
			getEngineInfoFromTuneFile(fr.result);
		}
	}

	function getEngineInfoFromTuneFile(data)
	{
		var eInfo = {
			// "msqur_field_name": ["TS_tune_file_constant_name"]
			"name": ["vehicleName"],
			"make": ["engineMake"],
			"code": ["engineCode"],
			"displacement": ["displacement"],
			"compression": ["compressionRatio"],
			"induction": ["isForcedInduction"]
		};
		for (const field in eInfo)
		{
			var re = new RegExp('<constant.*?name="' + eInfo[field][0] + '"[^>]*>([^<]+)</constant>', 'g');
			var value = "", val = re.exec(data);
			if (val)
			{
				// strip quotes
				var value = val[1].replace(/['"]+/g, '');
				// round floats
				if (!isNaN(value))
				{
					value = parseFloat(parseFloat(value).toFixed(2));
				}
			}
			// store the value
			eInfo[field].push(value);
			var f = $('input#' + field);
			// highlight absent values
			f.css({ "border": (value == "") ? '#FF0000 1px solid' : '#707070 1px solid'});
			f.val(value == "" ? "?" : value);
		}
		$('#engineInfo').show();
		// center dialog
		$("div#upload").dialog("option", "position", {my: "center", at: "center", of: window});
		
		preprocessEngineInfo(eInfo);
	}

	function preprocessEngineInfo(eInfo)
	{
		//alert(JSON.stringify(eInfo, null, 4));
		$.get("api.php?method=preprocessTune", { einfo: eInfo }, function(data) {
			// output the text result
			$('output#processing').html(data.preprocessTune.text);

			var status = data.preprocessTune.status;
			// change the message color
			var clr = "red";
			if (status == "ok")
				clr = "green";
			else if (status == "warn")
				clr = "darkorange";
			$('output#processing').css("color", clr);
			
			// enable upload button if the status is good enough
			enableUploadButton(status == "ok" || status == "warn");

		}, "json");

	}

	function enableUploadButton(isEnabled)
	{
		$('#dialogUpload').attr("disabled", !isEnabled).css("opacity", isEnabled ? 1.0 : 0.5);
	}
	

	
	$('input#fileSelect').change(uploadAdd);
	var dropZone = document.getElementById('fileDropZone');
	if (dropZone != null)
	{
		dropZone.addEventListener('dragover', uploadDragOver);
		dropZone.addEventListener('drop', uploadAdd);
	}
});
