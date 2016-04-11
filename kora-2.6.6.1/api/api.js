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
 * when uploading an XML file to KORA
 * 
 * Initial Version: Meghan McNeil, 2009
 * Revamped for restful API: James Green, 2015
 */

var MappingManager = new function() {
	var instance = this;
	
	var allingestdata = new Array();
	var allingestmaps = new Array();
		
	/**
	 * Ingest the record data using the assumed xml mapping 
	 * Base URI needed for restful api 
	 */
	instance.postSubmit = function (pid, sid, recordArray, baseURI) {
		recordArray.forEach(function(record) {
			$.each(record, function(index, obj) {
				allingestmaps.push(index);
				allingestdata.push(obj[0]);
			});
			
			if(baseURI==null | baseURI==""){
				baseURI = "../";
			}
			
			$.ajax({
				type: "POST",
				url:  baseURI+'handleRESTRecord.php',
				data: { pid: pid, sid: sid, ingestdata: allingestdata, ingestmap: allingestmaps },
				datatype: 'html',
				async: true,
				beforeSend: function(data) {
					//console.log(data);
				},
				success: function(data) {
					//console.log("SUCCESS");
				},
				error: function(data) {
					//console.log("FAIL");
					//console.log(data);
				}
			});
		});
	}
	
	instance.putSubmit = function (pid, sid, rid, recordArray, baseURI) {
		$.each(recordArray[0], function(index, obj) {
			allingestmaps.push(index);
			allingestdata.push(obj[0]);
		});
		kid = pid+'-'+sid+'-'+rid;
		
		if(baseURI==null | baseURI==""){
			baseURI = "../";
		}
		
		$.ajax({
			type: "POST",
			url:  baseURI+'handleRESTRecord.php',
			data: { pid: pid, sid: sid, rid: kid, ingestdata: allingestdata, ingestmap: allingestmaps },
			datatype: 'html',
			async: true,
			beforeSend: function(data) {
				//console.log(data);
			},
			success: function(data) {
				//console.log("SUCCESS");
			},
			error: function(data) {
				//console.log("FAIL");
				//console.log(data);
			}
		});
	}
	
	instance.deleteSubmit = function (pid, sid, rid) {
		kid = pid.toString()+'-'+sid.toString()+'-'+rid.toString();
		$.ajax({
			type: "POST",
			url:  '../ajax/record.php',
			data: { action:'deleteRecord', rid:kid, pid:pid, sid:sid},
			datatype: 'html',
			async: true,
			beforeSend: function(data) {
				//console.log(data);
			},
			success: function(data) {
				//console.log("SUCCESS");
			},
			error: function(data) {
				//console.log("FAIL");
				//console.log(data);
			}
		});
	}
}