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
 
$(function() {
	if ($('#approjectschemes').length >= 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		
		$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
		loadSymbolOff();
	}
	
	$("#approjectschemes" ).on( "click",'.add_scheme', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/project.php',data:{action:"PrintNewScheme",source:"ProjectFunctions",pid:pid}});
		$.ajaxSetup({ async: true });
		
		$(".project_addScheme_form").on( "click",'.project_addScheme_submit', function() {
			var fd = new FormData();
			var check_error = 0; //0 dont let pass, 1 is no error
			fd.append('pid',pid);
			fd.append('schemeSubmit','true');
			fd.append('action','CreateScheme');
			fd.append('source','ProjectFunctions');
			fd.append('schemeName',$('.project_addScheme_name').val());
			fd.append('description',$('.project_addScheme_desc').val());
			fd.append('preset',$('.project_addScheme_preset').val());
			fd.append('publicIngestion',$('.project_addScheme_publicIngest').val());
			fd.append('legal',$('.project_addScheme_legal').val());
			
			
			$.ajax({
				url: 'ajax/project.php',
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
			
			$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
		});
		loadSymbolOff();
	});

	$("#approjectschemes" ).on( "click",'.edit_scheme', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = c.parents('.project_scheme').first().attr('sid');
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/project.php',data:{action:"PrintEditScheme",source:"ProjectFunctions",pid:pid,editsid:sid}});
		$.ajaxSetup({ async: true });
		
		$("#project_editScheme_form").on( "click",'.project_editScheme_submit', function() {
			var fd = new FormData();
			fd.append('pid',pid);
			fd.append('editsid',sid);
			fd.append('schemeSubmit','true');
			fd.append('action','EditScheme');
			fd.append('source','ProjectFunctions');
			fd.append('schemeName',$('.project_editScheme_name').val());
			fd.append('description',$('.project_editScheme_desc').val());
			fd.append('publicIngestion',$('.project_editScheme_publicIngest').val());
			fd.append('legal',$('.project_editScheme_legal').val());
			
			
			$.ajax({
				url: 'ajax/project.php',
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
			
			$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
		});
		loadSymbolOff();
	});

	$("#approjectschemes" ).on( "click",'.move_scheme_up', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = c.parents('.project_scheme').first().attr('sid');
		
		$.post("ajax/project.php",{action:"MoveProjectScheme",source:"ProjectFunctions",pid:pid,movesid:sid,direction:"up"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
		loadSymbolOff();
	});

	$("#approjectschemes" ).on( "click",'.move_scheme_down', function() {
		loadSymbolOn();
		var c = $(this);
		var pid = $('#kora_globals').attr('pid');
		var sid = c.parents('.project_scheme').first().attr('sid');
		
		$.post("ajax/project.php",{action:"MoveProjectScheme",source:"ProjectFunctions",pid:pid,movesid:sid,direction:"down"},function(resp){$("#global_error").append(resp);}, 'html');
		$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
		loadSymbolOff();
	});

	$("#approjectschemes" ).on( "click",'.delete_scheme', function() {
		loadSymbolOn();
		var answer = confirm(kgt_reallydelscheme);
		if(answer) {
			var c = $(this);
			var pid = $('#kora_globals').attr('pid');
			var sid = c.parents('.project_scheme').first().attr('sid');
			$.ajaxSetup({ async: false });
			
			$.post("ajax/project.php",{action:"DeleteProjectScheme",source:"ProjectFunctions",pid:pid,delsid:sid},function(resp){$("#global_error").append(resp);}, 'html');
			$.post("ajax/project.php",{action:"PrintProjectSchemes",source:"ProjectFunctions",pid:pid},function(resp){$("#approjectschemes").html(resp);}, 'html');
			$.ajaxSetup({ async: true });
		}
		loadSymbolOff();
	});

	if ($('#apmanageprojusers').length > 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
				
		$.post('ajax/project.php', {action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanageprojusers").html(resp);}, 'html');
		loadSymbolOff();
	}
	if ($('#apmanagegroups').length > 0)
	{
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		
		$.post('ajax/project.php', {action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
		loadSymbolOff();
	}
	
	$("#apmanageprojusers").on( "click",'.addprojectuser', function() {
		loadSymbolOn();
	    	addProjectUser();
			loadSymbolOff();
	});

	$("#apmanageprojusers").on( "click",'.delprojectuser', function() {
		loadSymbolOn();
		var c = $(this);
	    	var uid = c.attr('uid');
	    	deleteProjectUser(uid);
			loadSymbolOff();
	});

	$("#apmanagegroups").on( "click",'.addgroup', function() {
		loadSymbolOn();
	    	addGroup();
			loadSymbolOff();
	});

	$("#apmanagegroups").on( "click",'.delgroup', function() {
		loadSymbolOn();
		var c = $(this);
	    	var gid = c.attr('gid');
	    	deleteGroup(gid);
			loadSymbolOff();
	});

	$("#apmanagegroups").on( "click",'input[name^="gp"]', function() {
		loadSymbolOn();
		var c = $(this);
	    	var gid = c.attr('gid');
	    	var perm = c.attr('perm');
	    	modGroupPerms(gid,perm,c.is(':checked'));
			loadSymbolOff();
	});
	
	$("#right_container").on("change", '.kpquickjump', function() {
		loadSymbolOn();
		window.location = 'selectScheme.php?pid='+$(this).val();
	});
	
	$("#projectmanagerform").on("click",'.kp_manage_newProjBtn', function(){
		loadSymbolOn();
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/project.php',data:{action:"PrintNewProject",source:"ProjectFunctions"}});
		$.ajaxSetup({ async: true });
		
		$(".kp_newProject_form").on("click",'.kp_newProject_submit', function(){
			var fd = new FormData();
			var check_error = 0;
			fd.append('name',$('.kp_newProject_name').val());
			fd.append('description',$('.kp_newProject_desc').val());
			fd.append('active',$('.kp_newProject_active').val());
			fd.append('style',$('.kp_newProject_style').val());
			fd.append('quota',$('.kp_newProject_quota').val());
			fd.append('admin',$('.kp_newProject_admin').val());
			fd.append('action','createProject');
			fd.append('source','ProjectFunctions');
			
			
			$.ajax({
				url: 'ajax/project.php',
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
			
			if ( check_error == 1){ 
				$.colorbox.close(); 
				PrintUpdateProjectsTable();
			}
		});
		loadSymbolOff();
	});
	
	$("#projectmanagerform").on("click",'.kp_manage_editProjBtn', function(){
		loadSymbolOn();
		var pid = [];
		if($('#kp_manage_active').val()!=null)
			pid = pid.concat($('#kp_manage_active').val());
		if($('#kp_manage_inactive').val()!=null)
			pid = pid.concat($('#kp_manage_inactive').val());
		if(pid.length == 0){
			$("#global_error").text("No projects selected. Please select a project to be editted.");
			$("#global_error").attr('style','color:red');
		}
		if(pid.length == 1){
			$("#global_error").text("");
			$.ajaxSetup({ async: false });
			$.colorbox({href:'ajax/project.php',data:{action:"PrintEditProject",source:"ProjectFunctions",pid:pid[0]}});
			$.ajaxSetup({ async: true });
			
			$(".kp_editProject_form").on("click",'.kp_editProject_submit', function(){
				var fd = new FormData();
				var check_error = 0;
				var name = $('.kp_editProject_name').val();
				fd.append('name',name);
				fd.append('description',$('.kp_editProject_desc').val());
				fd.append('active',$('.kp_editProject_active').val());
				fd.append('style',$('.kp_editProject_style').val());
				fd.append('quota',$('.kp_editProject_quota').val());
				fd.append('pid',pid[0]);
				fd.append('action','editProject');
				fd.append('source','ProjectFunctions');
				
				
				$.ajax({
					url: 'ajax/project.php',
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
				
				if ( check_error == 1){ 
					$.colorbox.close();
					PrintUpdateProjectsTable();
				}
			});
		}
		else{
			$("#global_error").text("More than one project selected. Please select only one project for editting.");
			$("#global_error").attr('style','color:red');
		}
		loadSymbolOff();
	});
	
	$("#projectmanagerform" ).on( "click",'.kp_manage_delProjBtn', function() {
		loadSymbolOn();
		var pids = [];
		if($('#kp_manage_active').val()!=null)
			pids = pids.concat($('#kp_manage_active').val());
		if($('#kp_manage_inactive').val()!=null)
			pids = pids.concat($('#kp_manage_inactive').val());
		
		if(pids.length == 0){
			$("#global_error").text("No projects selected. You must select at least one project for deletion.");
			$("#global_error").attr('style','color:red');
		}
		else{
			$("#global_error").text("");
			var answer = confirm(kgt_reallydelproj);
			if(answer) {
				$.ajaxSetup({ async: false });
				
				$.post("ajax/project.php",{action:"deleteProjects",source:"ProjectFunctions",pids:pids},function(resp){$("#global_error").append(resp);}, 'html');
				$.ajaxSetup({ async: true });
				PrintUpdateProjectsTable();
			}
		}
		loadSymbolOff();
	});
	
	$("#projectmanagerform" ).on( "click",'.kp_manage_deactivateProj', function() {
		loadSymbolOn();
		var pids = [];
		if($('#kp_manage_active').val()!=null)
			pids = pids.concat($('#kp_manage_active').val());
		
		if(pids.length == 0){
			$("#global_error").text("No projects selected. You must select at least one project for deactivation.");
			$("#global_error").attr('style','color:red');
		}
		else{	
			$.ajaxSetup({ async: false });
			
			$.post("ajax/project.php",{action:"deactivateProjects",source:"ProjectFunctions",pids:pids},function(resp){$("#global_error").append(resp);}, 'html');
			$.ajaxSetup({ async: true });
			PrintUpdateProjectsTable();
		}
		loadSymbolOff();
	});
	
	$("#projectmanagerform" ).on( "click",'.kp_manage_activateProj', function() {
		loadSymbolOn();
		var pids = [];
		if($('#kp_manage_inactive').val()!=null)
			pids = pids.concat($('#kp_manage_inactive').val());
		
		if(pids.length == 0){
			$("#global_error").text("No projects selected. You must select at least one project for activation.");
			$("#global_error").attr('style','color:red');
		}
		else{	
			$.ajaxSetup({ async: false });
			
			$.post("ajax/project.php",{action:"activateProjects",source:"ProjectFunctions",pids:pids},function(resp){$("#global_error").append(resp);}, 'html');
			$.ajaxSetup({ async: true });
			PrintUpdateProjectsTable();
		}
		loadSymbolOff();
	});
	
});

//For refreshing the manage projects page after an action
function PrintUpdateProjectsTable(){
	$.ajaxSetup({ async: false });
	
	$.post("ajax/project.php",{action:"PrintUpdatedProjectsTable",source:"ProjectFunctions"},function(resp){$("#projectmanagerform").html(resp);}, 'html');
	$.ajaxSetup({ async: true });
}

//Add a user to a project
function addProjectUser() {
	var pid = $('#kora_globals').attr('pid');
		
	$.post('ajax/project.php',{action:'addProjectUser',source:'ProjectFunctions',pid:pid,user:$('#useradd').val(),
		group:$('#groupadd').val() },function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid },function(resp){$("#apmanageprojusers").html(resp);}, 'html');
}

//Remove a user from a project
function deleteProjectUser(varuser) {
	var answer = confirm(kgt_reallydelpuser);
	if(answer) {
		var pid = $('#kora_globals').attr('pid');
				
		$.post('ajax/project.php',{action:'deleteProjectUser',source:'ProjectFunctions',pid:pid,user:varuser},function(resp){$("#ajaxstatus").html(resp);}, 'html');
		$.post('ajax/project.php',{action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
		$.post('ajax/project.php',{action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid },function(resp){$("#apmanageprojusers").html(resp);}, 'html');
	}
	return;
}

//Modify the permissions of a particular group
function modGroupPerms(vargid,varperm,varset) {
	var pid = $('#kora_globals').attr('pid');	
	
	$.post('ajax/project.php', {action:'updateGroupPerms',source:'ProjectFunctions',pid:pid,gid:vargid,permission:varperm,checked:varset}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid },function(resp){$("#apmanageprojusers").html(resp);}, 'html');
}

//Delete a group from a project
function deleteGroup(vargid) {
	var answer = confirm(kgt_reallydelpgrp);
	if(answer) {
		var pid = $('#kora_globals').attr('pid');	
		
		$.post('ajax/project.php', {action:'deleteGroup',source:'ProjectFunctions',pid:pid,gid:vargid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');        
		$.post('ajax/project.php',{action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
		$.post('ajax/project.php',{action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid },function(resp){$("#apmanageprojusers").html(resp);}, 'html');
	}
	return; 
}

//Add a group to a project
function addGroup() {
	var pid = $('#kora_globals').attr('pid');
		
	$.post('ajax/project.php', {action:'addGroup',source:'ProjectFunctions', pid:pid, name:$('#groupname').val(), admin:$('#newadmin').is(':checked'),
			ingestobj:$('#newingestobj').is(':checked'), delobj:$('#newdelobj').is(':checked'), edit:$('#newedit').is(':checked'), create:$('#newcreate').is(':checked'), delscheme:$('#newdelscheme').is(':checked'),
			exports:$('#newexport').is(':checked'), moderator:$('#newmoderator').is(':checked')}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintGroups',source:'ProjectFunctions',pid:pid}, function(resp){$("#apmanagegroups").html(resp);}, 'html');
	$.post('ajax/project.php',{action:'PrintProjectUsers',source:'ProjectFunctions',pid:pid },function(resp){$("#apmanageprojusers").html(resp);}, 'html');
}



