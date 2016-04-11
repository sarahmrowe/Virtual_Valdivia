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
 
 
// HANDLE SETTING OF THE DISABLED ELEMENTS IN CERTAIN CONDITIONS
function DisablePublicIngest()
{
	$('.scheme_control').each(function() {
		$(this).find('.publicentry').first().prop('disabled', $(this).find('.required').first().is(':checked'));
		$(this).find('.publicentry').first().prop('checked', $(this).find('.required').first().is(':checked'));
	});
}
 
 
$(function() {
	$("#apschemecontrols" ).on( "click",'.add_control', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_collection').first().attr('kcollid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/scheme.php',data:{action:"PrintAddControl",source:"SchemeFunctions",pid:pid,sid:sid,newcollid:cid}});
		$.ajaxSetup({ async: true });
		
		$("#scheme_add_control").on("change",".ks_addControl_type", function() {
			if($(".ks_addControl_type").val()=='FileControl'){
				$('.ks_addControl_req').prop("disabled",false);
				$('.ks_addControl_search').prop("disabled",false);
				$('.ks_addControl_adv').val('off');
				$('.ks_addControl_adv').prop("disabled",true);
				$('.ks_addControl_showRes').prop("disabled",false);
			} else if($(".ks_addControl_type").val()=='ImageControl'){
				$('.ks_addControl_req').prop("disabled",false);
				$('.ks_addControl_search').prop("disabled",false);
				$('.ks_addControl_adv').val('off');
				$('.ks_addControl_adv').prop("disabled",true);
				$('.ks_addControl_showRes').prop("disabled",false);
			} else if($(".ks_addControl_type").val()=='AssociatorControl'){
				$('.ks_addControl_req').val('off');
				$('.ks_addControl_req').prop("disabled",true);
				$('.ks_addControl_search').prop("disabled",false);
				$('.ks_addControl_adv').val('off');
				$('.ks_addControl_adv').prop("disabled",true);
				$('.ks_addControl_showRes').prop("disabled",false);
			} else{
				$('.ks_addControl_req').prop("disabled",false);
				$('.ks_addControl_search').prop("disabled",false);
				$('.ks_addControl_adv').prop("disabled",false);
				$('.ks_addControl_showRes').prop("disabled",false);
			}
		});
		
		$("#scheme_add_control").on('click','.scheme_addControl_submit', function() {
			var fd = new FormData();
			var check_error = 0; //1 means no error, so continue 
			fd.append('type',$('.ks_addControl_type').val());
			fd.append('name',$('.ks_addControl_name').val());
			fd.append('description',$('.ks_addControl_desc').val());
			fd.append('required',$('.ks_addControl_req').is(':checked'));
			fd.append('searchable',$('.ks_addControl_search').is(':checked'));
			fd.append('advanced',$('.ks_addControl_adv').is(':checked'));
			fd.append('showinresults',$('.ks_addControl_showRes').is(':checked'));
			fd.append('collectionid',$('.ks_addControl_collid').val());
			fd.append('pid',pid);
			fd.append('sid',sid);
			fd.append('action','CreateControl');
			fd.append('source','SchemeFunctions');
			
			
			$.ajax({
				url: 'ajax/scheme.php',
				data: fd,
				processData: false,
				contentType: false,
				async: false,
				type: 'POST',
				success: function(resp) { 
					if (resp == ""){ check_error = 1; }
					$("#cbox_error").html(resp);
					$.colorbox.resize();
					
				}
			});
			
			if ( check_error == 1){ $.colorbox.close(); } //If no error, close cbox
			
			$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		});
		loadSymbolOff();
	});
	
	$("#apschemecontrols" ).on( "click",'.add_collection', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/scheme.php',data:{action:"PrintAddCollection",source:"SchemeFunctions",pid:pid,sid:sid}});
		$.ajaxSetup({ async: true });
		
		$("#scheme_add_collection").on('click','.scheme_addColl_submit', function() {
			var fd = new FormData();
			var check_error = 0;
			fd.append('addGroup','true');
			fd.append('collName',$('.scheme_addColl_name').val());
			fd.append('description',$('.scheme_addColl_desc').val());
			fd.append('pid',pid);
			fd.append('sid',sid);
			fd.append('action','CreateCollection');
			fd.append('source','SchemeFunctions');
			
			
			$.ajax({
				url: 'ajax/scheme.php',
				data: fd,
				processData: false,
				contentType: false,
				async: false,
				type: 'POST',
				success: function(resp) { 
					if (resp == ""){ check_error = 1; }
					$("#cbox_error").append(resp);
					$.colorbox.resize();
					
				}
			});
			
			if ( check_error == 1){ $.colorbox.close(); } //If no error, close cbox
			
			$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		});
		loadSymbolOff();
	});
	
	$("#apschemecontrols" ).on( "click",'.update_collection', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var collid = c.parents('.scheme_collection').first().attr('kcollid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/scheme.php',data:{action:"PrintUpdateCollection",source:"SchemeFunctions",pid:pid,sid:sid,collid:collid}});
		$.ajaxSetup({ async: true });
		
		$("#scheme_edit_collection").on('click','.scheme_updateColl_submit',function() {
			var fd = new FormData();
			var check_error = 0;
			fd.append('editCollection','true');
			fd.append('collid',$('.scheme_updateColl_id').val());
			fd.append('name',$('.scheme_updateColl_name').val());
			fd.append('description',$('.scheme_updateColl_desc').val());
			fd.append('pid',pid);
			fd.append('sid',sid);
			fd.append('action','UpdateCollection');
			fd.append('source','SchemeFunctions');
			
			
			$.ajax({
				url: 'ajax/scheme.php',
				data: fd,
				processData: false,
				contentType: false,
				async: false,
				type: 'POST',
				success: function(resp) { 
					if (resp == ""){ check_error = 1; }
					$("#cbox_error").append(resp);
					$.colorbox.resize();
					
				}
			});
			
			if ( check_error == 1){ $.colorbox.close(); } //If no error, close cbox
			
			$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		});
		loadSymbolOff();
	});
	
	$("#apschemecontrols" ).on( "click",'.edit_control', function(e) {
		loadSymbolOn();
		e.preventDefault();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/control.php',data:{action:"ShowDialog",source:"ControlFunctions",pid:pid,sid:sid,cid:cid},onClosed: function() {$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);/*checkSelectAlls();*/}, 'html')}});
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.move_collection_up', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_collection').first().attr('kcollid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/scheme.php",{action:"MoveSchemeCollection",source:"SchemeFunctions",pid:pid,sid:sid,movecid:cid,direction:"up"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.move_collection_down', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.move_collection').prevAll('.scheme_collection').first().attr('kcollid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/scheme.php", {action:"MoveSchemeCollection",source:"SchemeFunctions",pid:pid,sid:sid,movecid:cid,direction:"down"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.move_control_up', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/scheme.php",{action:"MoveSchemeControl",source:"SchemeFunctions",pid:pid,sid:sid,movecid:cid,direction:"up"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.move_control_down', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/scheme.php", {action:"MoveSchemeControl",source:"SchemeFunctions",pid:pid,sid:sid,movecid:cid,direction:"down"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.showhide_permissions', function() {
		loadSymbolOn();
		var c = $(this);
		c.parents('.scheme_collection').first().find('.searchoption').toggle();
		c.parents('.scheme_collection').first().find('.controldescription').toggle();
		c.parents('.scheme_collection').first().find('.kcgcl-col-adv').toggle();
		c.parents('.scheme_collection').first().find('.kcgcl-col-desc').toggle();
		loadSymbolOff();
	});
	
	$("#apschemecontrols" ).on( "click",'.required', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/control.php", {action:"SetStdOption",source:"SchemeFunctions",pid:pid,sid:sid,cid:cid,ctrlopt:"required",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.searchable', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/control.php", {action:"SetStdOption",source:"SchemeFunctions",pid:pid,sid:sid,cid:cid,ctrlopt:"searchable",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.advsearchable', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/control.php", {action:"SetStdOption",source:"SchemeFunctions",pid:pid,sid:sid,cid:cid,ctrlopt:"advsearchable",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.showinresults', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/control.php", {action:"SetStdOption",source:"SchemeFunctions",pid:pid,sid:sid,cid:cid,ctrlopt:"showinresults",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.publicentry', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var cid = c.parents('.scheme_control').first().attr('kcid');
		$.ajaxSetup({ async: false });
		
		$.post("ajax/control.php", {action:"SetStdOption",source:"SchemeFunctions",pid:pid,sid:sid,cid:cid,ctrlopt:"publicentry",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#apschemecontrols" ).on( "click",'.delete_control', function() {
		loadSymbolOn();
		var answer = confirm(kgt_reallydelctrl);
		if(answer) {
			var c = $(this);
			var pid = $('#kora_globals').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = c.parents('.scheme_control').first().attr('kcid');
			$.ajaxSetup({ async: false });
			
			$.post("ajax/scheme.php",{action:"DeleteSchemeControl",source:"SchemeFunctions",pid:pid,sid:sid,delcid:cid},function(resp){$("#global_error").append(resp);}, 'html');
			$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
			$.ajaxSetup({ async: true });
		}
		loadSymbolOff();
	});

	$("#apschemecontrols" ).on( "click",'.delete_collection', function() {
		loadSymbolOn();
		var answer = confirm(kgt_reallydelcoll);
		if(answer) {
			var c = $(this);
			var pid = $('#kora_globals').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = c.parents('.scheme_collection').first().attr('kcollid');
			$.ajaxSetup({ async: false });
			
			$.post("ajax/scheme.php",{action:"DeleteSchemeCollection",source:"SchemeFunctions",pid:pid,sid:sid,delcid:cid},function(resp){$("#global_error").append(resp);}, 'html');
			$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);}, 'html');
			$.ajaxSetup({ async: true });
		}
		loadSymbolOff();
	});

	$("#apschemeallowedassoc" ).on( "click",'.delete_allowed_assoc', function() {
		loadSymbolOn();
		var answer = confirm(kgt_reallydelschemeassoc);
		if(answer) {
			var c = $(this);
			var globalpid = $('#kora_globals').attr('pid');
			var globalsid = $('#kora_globals').attr('sid');
			var tarpid = c.parents('.scheme_allowed_assoc').first().attr('pid');
			var tarsid = c.parents('.scheme_allowed_assoc').first().attr('sid');
			$.ajaxSetup({ async: false });
			
			$.post("ajax/scheme.php", {action:"DeleteAllowedAssoc",source:"SchemeFunctions",pid:globalpid,sid:globalsid,delpid:tarpid,delsid:tarsid},function(resp){$("#global_error").append(resp);}, 'html');
			$.post('ajax/scheme.php', {action:'PrintAllowedAssoc',source:'SchemeFunctions',pid:globalpid,sid:globalsid}, function(resp){$("#apschemeallowedassoc").html(resp);}, 'html');
			$.ajaxSetup({ async: true });
		}
		loadSymbolOff();
	});
	
	$("#apschemesetallowedassoc" ).on( "change",'.add_allowed_assoc_proj', function() {
		loadSymbolOn();
		var selpid = $(this).val();
		var selscheme = $(this).parents('#apschemesetallowedassoc').first().find('.add_allowed_assoc_scheme').first();
		// IF SELPID == 'all' WE DO THINGS A BIT DIFFERENTLY
		$(".add_allowed_assoc_scheme option:first").attr('selected','selected');
		if (selpid == 'all')
		{
			selscheme.find('.add_allowed_assoc_scheme_option').show();
			selscheme.find('.showall').hide();
		}
		else
		{
			selscheme.find('.add_allowed_assoc_scheme_option').hide();
			selscheme.find('.showall').show();
			selscheme.find('.proj'+selpid).show();
		}
		loadSymbolOff();
	});
	
	$("#apschemesetallowedassoc" ).on( "click",'.add_allowed_assoc', function() {
		loadSymbolOn();
		var globalpid = $('#kora_globals').attr('pid');
		var globalsid = $('#kora_globals').attr('sid');
		var selprojjq = $(this).parents('#apschemesetallowedassoc').first().find('.add_allowed_assoc_proj :selected').first();
		var selschemejq = $(this).parents('#apschemesetallowedassoc').first().find('.add_allowed_assoc_scheme :selected').first();
		var selpid = selprojjq.val();
		var selsid = selschemejq.val();
		var addsids = [];

		if (selsid !='all')
			{ addsids.push(selschemejq); }
		else
		{	// this condition should always be met unless someone has hacked the input box
			if (selpid != 'all')
			{
				$(this).parents('#apschemesetallowedassoc').first().find('.add_allowed_assoc_scheme').first().find('.proj'+selpid).each(function() {
					addsids.push($(this));
				});
			}
		}
		
		$.ajaxSetup({ async: false });
		
		for (var i=0; i<addsids.length; i++)
		{ $.post("ajax/scheme.php", {action:"AddAllowedAssoc",source:"SchemeFunctions",pid:globalpid,sid:globalsid,addpid:addsids[i].attr('pid'),addsid:addsids[i].val()},function(resp){$("#global_error").append(resp);}, 'html'); }
		$.post('ajax/scheme.php', {action:'PrintAllowedAssoc',source:'SchemeFunctions',pid:globalpid,sid:globalsid}, function(resp){$("#apschemeallowedassoc").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#apschemecontrols").on( "click",'.ks_btn_schemePreset', function() {
		loadSymbolOn();
		var globalpid = $('#kora_globals').attr('pid');
		var globalsid = $('#kora_globals').attr('sid');
		var preset = $(this).is(':checked');
		
		$.ajaxSetup({ async: false });
		
		$.post('ajax/scheme.php', {action:'UpdateSchemePreset',source:'SchemeFunctions',pid:pid,sid:sid,preset:preset}, function(resp){$("#apschemecontrols").html(resp);/*checkSelectAlls();*/}, 'html');
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);/*checkSelectAlls();*/}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#xmlUploadForm" ).on( "click",'.scheme_uploadXML_submit', function() {
		loadSymbolOn();
		var fd = new FormData();

		fd.append('xmlFileName',$('input[type=file]')[0].files[0]);
		fd.append('pid',$('#kora_globals').attr('pid'));
		fd.append('action','SchemeXMLUpload');
		fd.append('source','SchemeFunctions');
		
		
		$.ajax({
			url: 'ajax/scheme.php',
			data: fd,
			processData: false,
			contentType: false,
			async: false,
			type: 'POST',
			success: function(resp) { 
				$("#global_error").text(resp);
				
			}
		});
		loadSymbolOff();
	});
	
	$("#xmlUploadForm" ).on( "click",'.records_uploadXML_submit', function() {
		loadSymbolOn();
		var fd = new FormData();

		fd.append('xmlFileName',$('input[type=file]')[0].files[0]);
		fd.append('zipFolder',$('input[type=file]')[1].files[0]);
		fd.append('pid',$('#kora_globals').attr('pid'));
		fd.append('sid',$('#kora_globals').attr('sid'));
		fd.append('action','MultiRecordXMLUpload');
		fd.append('source','SchemeFunctions');
		
		
		$.ajax({
			url: 'ajax/scheme.php',
			data: fd,
			processData: false,
			contentType: false,
			async: false,
			type: 'POST',
			success: function(resp) { 
				$('#ingestXMLerror').html('');
				$('#xmlActionDisplay').html(resp);
				
			},
			error: function() {
				$('#ingestXMLerror').html('Invalid XML File');
				$('#xmlActionDisplay').html();
				
			}
		});
		loadSymbolOff();
	});

	if ($('#apschemecontrols').length > 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		$.ajaxSetup({ async: false });
		
		$.post('ajax/scheme.php', {action:'PrintSchemeLayout',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemecontrols").html(resp);/*checkSelectAlls();*/}, 'html');
		DisablePublicIngest();
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	}
	
	if ($('#apschemesetallowedassoc').length > 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		$.ajaxSetup({ async: false });
		
		$.post('ajax/scheme.php', {action:'PrintSetAllowedAssoc',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemesetallowedassoc").html(resp);}, 'html');
		$("#apschemesetallowedassoc").find('.add_allowed_assoc_proj').trigger('change');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	}
	
	if ($('#apschemeallowedassoc').length > 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		$.ajaxSetup({ async: false });
		
		$.post('ajax/scheme.php', {action:'PrintAllowedAssoc',source:'SchemeFunctions',pid:pid,sid:sid}, function(resp){$("#apschemeallowedassoc").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	}
	
	$("#right_container").on("change", '.ksquickjump', function() {
		loadSymbolOn();
		var pid = $(this).attr('pid');
		window.location = 'schemeLayout.php?pid='+pid+'&sid='+$(this).val();
	});
	
});

