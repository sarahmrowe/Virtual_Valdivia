/**
Copyright (2008) Matrix: Michigan State University

This file is part of KORA.

KORA is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

KORA is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * MappingManager is a JavaScript singleton class that handles the actions
 * for the mapping table when uploading an XML file to KORA
 * 
 * Initial Version: Meghan McNeil, 2009
 */

var MappingManager = new function() {
		
	//variables to be held in the class
	var instance = this;
	var unmappedControls;
	var selectedValue;
	
	//Ids of elements to be accessed
	var displayDivId = "xmlActionDisplay";
	var continueButtonId = "continueButton";
	var cancelButtonId = "cancelButton";
	var indicatorId = "indicator";
	
	//Ids that are row specific, a # will follow the '_'
	var controlId = "controlCell_";
	var controlSelectId = "tagnameSelect_";
	var tagnameId = "tagCell_";
	var actionId = "action_";
	
	//Classes of elements to be accessed
	var selectBoxClass = "tagnameSelect";
	
	//Control types that have special associated functionality
	var assocControl = "AssociatorControl";
	
	var allingestdata = new Array();
	var allingestmaps = new Array();
	
	/**
	 * Adds control to the selection boxes
	 * @param control
	 *		control name to be added
	 */
	function addControl() {
		//add control name to list
		unmappedControls.push(selectedValue);
		unmappedControls.sort()
		
		//add control to select boxes
		var selectBoxes = $('select.'+selectBoxClass);		
		for (var i=0 ; i<selectBoxes.length ; ++i) {
			for (var j=0 ; j<unmappedControls.length ; ++j) {
				selectBoxes[i].options[j] = new Option (unmappedControls[j],unmappedControls[j]);
			}
		}
	}
	
	/**
	 * Check for association control
	 * @param response - response text for ajax call formatted as controlId->controlType
	 * @param rowId - id of the row selected 
	 * @param showTable - true = show additional mapping table, false = hide additional mapping table
	 */
	function checkForAssociator(response,rowId,showTable) {
		var controlData = response.split('->');
		
		if (controlData[1] == assocControl) {
			if(showTable) {
				$.post('ajax/record.php', {source:'Importer',action:'getAllowedAssociations',cid:controlData[0] }, 
						function(response) { drawNewMappingTable(response,rowId);}, 'html');

			} else {
				var fromsid = rowId.split('_')[0];
				var fromTag = $('#'+tagnameId+rowId)[0].innerHTML;
				
				$('#'+fromTag+"_"+fromsid)[0].style.display = "none";
			}
		}
	}
	
	/**
	 * Draw new mapping table for associations
	 * @param response - response from ajax call formatted as schemeid->schemeName///schemeid->schemeName//etc.
	 * @param rowId - row id 
	 */
	function drawNewMappingTable(response,rowId) {
		var assocSchemes = response.responseText.split('///');
		
		//take the first association for now.  Multiple scheme 
		//association support may come later
		var association = assocSchemes[0];
		association = association.split('->');
		
		var fromsid = rowId.split('_')[0];
		var fromTag = $('#'+tagnameId+rowId)[0].innerHTML;
		
		var tableTag = $('#'+fromTag+"_"+fromsid)[0];
		
		if (!tableTag) {
			$.post('includes/controlDataFunctions.php', {source:'Importer',action:'addNewMappingTable',toSchemeId:association[0],toSchemeName:association[1],
				  fromSchemeId:fromsid,fromTagname:fromTag}, 
				  function(o) { $("#additionalMapping").innerHTML += o.responseText; }, 'html');

		} else {
			tableTag.style.display = "block";
		}
	}
	
	/**
	 * Enables/Disable continue Button
	 * @param enable : boolean true=enables button, false=disables button
	 */
	function enableContinueButton() {
		$('#'+continueButtonId)[0].disabled = $('select.'+selectBoxClass).length == 0 ? false : true;
	}
	
	/**
	 * Remove or add specified control names returned from
	 * Ajax call from selection box
	 */
	function fileControlCallback(response,action) {
		var options = response.responseText.split('///');
		
		for (var i=0 ; i<options.length ; ++i) {
			selectedValue = options[i].split('->').pop();
			if (action=='remove') {
				removeControl();
			} else if (action=='add') {
				addControl();
			}
		}
	}
	
	/**
	 * Builds a selectBox's options
	 * @param selectBox : selectBox object in which options will be added to
	 */
	function getSelectBoxOptions(selectBox) {
		//add all unmapped controls to a selectbox
		for (var i=0 ; i<unmappedControls.length ; ++i) {
			selectBox.options[selectBox.options.length] = new Option(unmappedControls[i],unmappedControls[i]);
		}
	}
	
	/**
	 * Remove all of the children of an element
	 * @param element - element to remove all children from 
	 */
	function removeAllChildren(element) {
		$(element).children().empty().remove();
	}
	
	/**
	 * Removes control name from unmappedControl list and selectBoxes
	 * @param control : control name to be removed
	 */
	function removeControl() {
		//remove tagname from unmapped control names
		for (var i=0 ; i<unmappedControls.length ; ++i) {
			if (unmappedControls[i] == selectedValue) {
				unmappedControls.splice(i,1);
				break;
			}
		}
		
		//remove tagname from each of the selection boxes
		var selectBoxes = $('select.'+selectBoxClass);
		for (var i=0 ; i<selectBoxes.length ; ++i) {
			for (var j=0 ; j<selectBoxes[i].length ; ++j) {
				if (selectBoxes[i].options[j].value == selectedValue) {
					selectBoxes[i].remove(j);
				}
			}
		}
		
	}
	
	/**
	 * Add tagname and control mapping
	 */
	instance.addMapping = function (sid,rowId) {
		//if unmappedControls is empty, get current options
		if (!unmappedControls) {
			instance.setUnmappedControls();
		}
		additionalControlCellData = new Array();
		
		var idExtension = sid+"_"+rowId;
		
		//get tagname and control to map together
		var tagnameValue = $('#'+tagnameId+idExtension)[0].innerHTML;
		selectedValue = $('#'+controlSelectId+idExtension)[0].value;
		
		//clearout controlTD
		var controlTD = $('#'+controlId+idExtension)[0];
		removeAllChildren(controlTD);
		
		if (selectedValue == "All File Controls") {
			
			$.post('ajax/record.php',{source:'Importer',action:'getFileControls','controls[]':'name'},
					function(response) { fileControlCallback(response,'remove');}, 'html');
			
		} else {
			$.post('ajax/record.php', {source:'Importer',action:"getControlType", controlName: selectedValue, schemeId:sid}, 
					function(response) { //alert(response.responseText);
				checkForAssociator(response,idExtension,true); } );
		}
		
		//display the selected value as the control to map to 
		controlTD.innerHTML = selectedValue;
		
		//create Edit link to undo this selection
		var actionTD = $('#'+actionId+idExtension)[0];
		var x = document.createElement("a");
		x.onclick = function() {
			instance.removeMapping(sid,rowId);
		}
		x.innerHTML = "Edit";
		removeAllChildren(actionTD);
		actionTD.appendChild(x);
		
		//remove control name from select boxes
		if (selectedValue != ' -- Ignore -- ') {
			removeControl();
		}
		
		//check to see if continue button should be enabled
		enableContinueButton();
		
		selectedValue = null;
	};
	
	/**
	 *	Remove tagname and control mapping
	 */
	instance.removeMapping = function (sid,rowId) {
		//if unmappedControls is empty, get current options
		if (!unmappedControls) {
			instance.setUnmappedControls();
		}
		
		var idExtension = sid+"_"+rowId;
		
		//get control name to remove from mapping
		var controlTD = $('#'+controlId+idExtension)[0];
		selectedValue = $('#'+controlId+idExtension)[0].innerHTML;
		
		if (selectedValue == "All File Controls") {
			$.post('ajax/record.php',{source:'Importer',action:'getFileControls','controls[]':'name'},
					function(response) { fileControlCallback(response,'add');} , 'html');
			
		} else {
			$.post('ajax/record.php', {source:'Importer',action:"getControlType", controlName: selectedValue}, 
					function(response) { checkForAssociator(response,idExtension,false); } , 'html');
		}
		
		//add control to unmapped controls
		if(selectedValue != ' -- Ignore -- ') {
			addControl();
		}
		
		//remove text from control td
		removeAllChildren(controlTD);
		//insert a selection box in it's place
		var selectBox = document.createElement("select");
		selectBox.id = controlSelectId+idExtension;
		selectBox.className = selectBoxClass;
		getSelectBoxOptions( selectBox );
		controlTD.appendChild( selectBox );
		
		//create OK button to save selection
		var actionTD = $('#'+actionId+idExtension)[0];
		var ok = document.createElement("a");
		ok.onclick = function () {
			instance.addMapping(sid,rowId);
		}
		ok.innerHTML = "OK";
		removeAllChildren(actionTD);
		actionTD.appendChild(ok);
		
		//disable continue button
		enableContinueButton();
		
		selectedValue = null;
	};
	
	/**
	 * Set the unmappedControls so that if a tagname:controlName 
	 * mapping is removed, the select box still displays correctly
	 */
	instance.setUnmappedControls = function(overrideControls) {
		if (!unmappedControls) {
			if (overrideControls) {
				unmappedControls = overrideControls.split("///");
			} else {
				unmappedControls = new Array();
				var selectBoxes = $('select.'+selectBoxClass);
				
				if (selectBoxes.length > 0) {
					selectBoxes = selectBoxes[0];
					for (var i=0 ; i<selectBoxes.options.length ; ++i) {
						unmappedControls.push(selectBoxes.options[i].value);
					}
				}
			}
		}
		
	};
	
	/**
	 * Ingest the record data using the tagname:controlName mapping
	 */
	instance.submit = function () {
		var mapping = new Array();
		
		var tags = $('td.tagname');
		
		var id;
		var idParts;
		var controlTd;
		var statusdiv = '#ingestprogress';
		var ignoremap = ' -- Ignore -- ';
		var ingfailstr = 'Ingestion Failed.';
	
		//foreach row in the table, get the control/tagname mapping
		for(var i=0 ; i<tags.length ; ++i) {
			id = tags[i].id;
			
			idParts = id.split("_");
			controlTd = $('#'+controlId+idParts[1]+"_"+idParts[2])[0];
			
			if (controlTd.innerHTML != ignoremap)
			{ mapping[tags[i].innerHTML] = controlTd.innerHTML; }
		}
		
		// I HATE USING 2 ARRAYS HERE BUT COULDN'T PASS ASSOC/DICT ARRAY TO PHP AND COULDN'T PASS NON-PHP-EXISTING OBJECT EITHER
		$.each(importdata, function(index, obj){
	
			var ingestdata = [];
			var ingestmap = [];
			
			for (var key in mapping)
			{
				ingestdata.push(obj[key]);
				ingestmap.push(mapping[key]);
			}
	
			var ingestdiv = '<div class="pending_ingest" id="pending_ingest_'+index+'" ki_index="'+index+'">Ingesting ...';
			ingestdiv += '<div class="pending_ingest_alldata">';
			for (var key in ingestdata)
			{
				// NO =( I HAVE TO DO A SPECIAL CONDITION FOR FILES/IMAGES HERE RANDOMLY IN JAVASCRIPT
				// ELSE THEIR DATA WILL TURN INTO [object Object]
				ingestdiv += '<span class="pending_ingest_ctrl">'+ingestmap[key]+'</span>';
				ingestdiv += '<span class="pending_ingest_data">'+ingestdata[key]+'</span><br />';
			}
			ingestdiv += '</div>';
			ingestdiv += '</div>';
			$(statusdiv).append(ingestdiv);
			
			allingestdata[index] = ingestdata;
			allingestmaps[index] = 	ingestmap;	
		});
		
		$('#'+continueButtonId)[0].disabled = true;
		$('#'+cancelButtonId)[0].disabled = true;
		$('#'+indicatorId).show();
		$('.importer_table_keys').attr('hidden','');
		
		var count = 0;
		var succ = 0;
		
		$.each(allingestdata, function(index, obj) {
			var thedata = allingestdata[index];
			
			$.ajax({
				type: "POST",
				url:  'handleRecord.php',
				data: { pid: pid, sid: sid, ingestdata: allingestdata[index], ingestmap: allingestmaps[index] },
				datatype: 'html',
				async: true,
				beforeSend: function() {
					$('#pending_ingest_'+index).addClass('pending_ingest_active');
					
				},
				success: function(data) {
					$('#pending_ingest_'+index).append(data);
					if (data.indexOf(ingfailstr) >= 0)
					{ 
						$('#pending_ingest_'+index).addClass('pending_ingest_fail');
						$('#pending_ingest_'+index).append('<div class="link kri_showdata">Click here to show/hide data</div>'); 
					}
					else
					{ 
						$('#pending_ingest_'+index).addClass('pending_ingest_good'); 
						succ++;
					}
					count++;
					$('.kora_import_progress').text("Ingested record "+count+" of "+allingestdata.length);
					if(count>=(allingestdata.length)){
						var pid = $('#kora_globals').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						$('.kora_import_progress').html("Finished. "+succ+ " out of "+allingestdata.length+" ingested. <a href='searchResults.php?pid="+pid+"&sid="+sid+"'>View Scheme Records</a>");
					}
				},
				error: function(data) {
					$('#pending_ingest_'+index).append(data);
					$('#pending_ingest_'+index).addClass('pending_ingest_fail');
				}
			});
		});
		
		$('#'+indicatorId).hide();
		
		// NEED TO CLEAR UPLOADED FILES EVENTUALLY, BUT DO WE WANT TO LEAVE THIS HERE TO ALLOW RESUME?
		//$.post('includes/controlDataFunctions.php', {action:"clearUploadedFiles"}, function(response) { return true; } , 'html');

	}
	
	// kora import function for lack of better place
	instance.exportresults = function (strselector) {
		
		// THIS SHOULD PROBABLY BE DONE VIA AJAX TO GET THIS HEADER INFO SOMEDAY...
		var xmlout = '<?xml version="1.0" encoding="ISO-8859-1"?><Data>';
		xmlout += '<ConsistentData></ConsistentData>';
		
		$(strselector).each(function( index ) {
			console.log('index:'+$(this).attr('ki_index'));
			var ingestdata = allingestdata[$(this).attr('ki_index')];
			var ingestmap = allingestmaps[$(this).attr('ki_index')];
			xmlout += '<Record>';
			for (var i in ingestdata)
			{
				console.log('i:'+i);
				console.log('dt:'+$.type(ingestdata[i]));
				
				if ($.type(ingestdata[i]) === 'undefined') { continue; }
				
				var atts = '';
				
				// IF IS_OBJECT, ASSUME WE HAVE XML TAG WITH ATTS
				if (($.type(ingestdata[i]) === "object") && (ingestdata[i].hasOwnProperty('_attributes')))
				{
					for ( var prop in ingestdata[i]._attributes ) {
						atts += prop+'="'+ingestdata[i]._attributes[prop]+'" ';
					}														
				}
				ingestmap[i] = ingestmap[i].replace(" ","_");
				// IF IT IS ARRAY, ASSUME WE HAVE MULTIPLE EVENTS
				if (($.type(ingestdata[i]) === "array") || ($.type(ingestdata[i]) === "object"))
				{
					for (var ii in ingestdata[i])
					{
						if (!isNaN(parseInt(ii)))
						{
							if (atts!="")
								xmlout += '<'+ingestmap[i]+' '+atts+'>';
							else
								xmlout += '<'+ingestmap[i]+'>';
							xmlout += ingestdata[i][ii];
							xmlout += '</'+ingestmap[i]+'>';
						}
					}
				}
				// ELSE IS IS JUST PLAIN TEXT FOR SINGLE-FIELD ENTRIES
				else
				{
					if (atts!="")
						xmlout += '<'+ingestmap[i]+' '+atts+'>';
					else
						xmlout += '<'+ingestmap[i]+'>';
					xmlout += ingestdata[i][ii];
					xmlout += '</'+ingestmap[i]+'>';
				}
			}
			xmlout += '</Record>';
		});
		
		xmlout += '</Data>';
		
		console.log(xmlout);
		
		
		var datauri = 'data:application/octet-stream;charset=ISO-8859-4,' + encodeURIComponent(xmlout);
		
		var downloadLink = document.createElement("a");
		downloadLink.href = datauri;
		downloadLink.download = "kora_ingest_data.xml";
		
		document.body.appendChild(downloadLink);
		downloadLink.click();
		document.body.removeChild(downloadLink);
		
	}
}

$(function() {
	$("#xmlActionDisplay" ).on( "click",'.ks_exportfailedrecords', function() {	
		MappingManager.exportresults('.pending_ingest_fail');
	});
	
	$("#xmlActionDisplay" ).on( "click",'.ks_exportsuccessrecords', function() {
		MappingManager.exportresults('.pending_ingest_good');
	});
	
});
