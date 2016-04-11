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

//Validates an item in a list
function ValidateListItem(vname_, v_, kcdiv_)
{
	var fd = new FormData();
	fd.append('action','validateControl');
	fd.append('source','DataFunctions');
	fd.append('pid',kcdiv_.attr('kpid'));
	fd.append('sid',kcdiv_.attr('ksid'));
	fd.append('cid',kcdiv_.attr('kcid'));
	fd.append(vname_, v_);
	
	var retval = true;
	
	var baseURI = $('#kora_globals').attr('baseURI');
	
	
	$.ajax({
		url: baseURI+'ajax/control.php',
		data: fd,
		processData: false,
		contentType: false,
		type: 'POST',
		success: function(data){
			kcdiv_.find('.ajaxerror').html(data);
			if (data != '') { retval = false; }
			
		}
	});
	
	return retval;
}

//Prints out presets for a control
function PrintControlPresets(pid,sid)
{
	if (($('#colorbox').length > 0) && ($('#cboxContent').length > 0))
	{
		
		$.post('ajax/control.php',{action:"showControlPresetDialog",source:"PresetFunctions",pid:pid,sid:sid}, function(resp){$("#presetControl").html(resp);}, 'html');
	}
}

$(function() {
	
	var ajaxhandler = 'ajax/control.php';
	
	$("#presetControl").on( "click",".preset_control_rename", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varpreID = $(this).attr('id');
	    var varnewname = $('#newName'+varpreID).val();
	    
	    $.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'updateControlPresetName',source:'PresetFunctions',preID:varpreID,name:varnewname,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		PrintControlPresets(pid,sid);
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#presetControl").on( "click",".preset_control_delete", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varpreID = $(this).attr('id');
	    
	    $.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'deleteControlPreset',source:'PresetFunctions',preID:varpreID,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		PrintControlPresets(pid,sid);
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#presetControl").on( "click",".preset_control_global", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varpreID = $(this).attr('id');
		var global = '0';
		if($(this).is(':checked')){
			global = '1';
		}
	    
	    $.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'updateControlPresetGlobal',source:'PresetFunctions',global:global,preID:varpreID,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		PrintControlPresets(pid,sid);
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
});
	


