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

//update the real name of a user
function updateRealName(varuid, varrn) {
	
	$.post('ajax/admin.php', {action:'updateRealName',source:'UserFunctions',uid:varuid,realname:varrn}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
}
//update the organization a user belongs to
function updateOrganization(varuid, varorg) {
	
	$.post('ajax/admin.php', {action:'updateOrganization',source:'UserFunctions',uid:varuid,organization:varorg}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
}
//update admin status of a user
function updateAdmin(varuid, varset) {
	
	$.post('ajax/admin.php', {action:'updateAdmin',source:'UserFunctions',uid:varuid,admin:varset}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
}
//update activation status of a user
function updateActivated(varuid, varset) {
	
	$.post('ajax/admin.php', {action:'updateActivated',source:'UserFunctions',uid:varuid,activated:varset}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
}
//delete a user from KORA
function deleteUser(varuid) {
	var answer = confirm(kgt_reallydelete);
	if(answer) {
		
		$.post('ajax/admin.php', {action:'deleteUser',source:'UserFunctions',uid:varuid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
	}
	return; 
}
//Reset a users password manually
function resetPassword() {
	var uid = $('#username option:selected').text();
	var pw1 = $('#password1').val();
	var pw2 = $('#password2').val();
	if (pw1 == pw2) {
		
		$.post('ajax/admin.php', { uid:uid, password:pw1, action:'resetPassword',source:'UserFunctions'}, function(resp){$("#ajaxstatus").html(resp);});
		alert(kgt_pwchanged);
		$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
	} else {
		alert(kgt_pwdontmatch);
	}
}
//Create a search token
function createToken() {
	
	$.post('ajax/admin.php',{action:'createToken',source:'UserFunctions'},function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'PrintTokens',source:'UserFunctions'}, function(resp){$("#apmanagetokens").html(resp);}, 'html');
}
//Delete a search token
function deleteToken(varid) {
    var answer = confirm(kgt_reallydeltok);
    if(answer) {
		
		$.post('ajax/admin.php',{action:'deleteToken',source:'UserFunctions',tokenid:varid },function(resp){$("#ajaxstatus").html(resp);}, 'html');
		$.post('ajax/admin.php', {action:'PrintTokens',source:'UserFunctions'}, function(resp){$("#apmanagetokens").html(resp);}, 'html');
    }
}
//Assign a project to a token
function addProjectAccess(vartokenid,varproj) {
	
	$.post('ajax/admin.php',{action:'addAccess',source:'UserFunctions',tokenid:vartokenid,tokpid:varproj},function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'PrintTokens',source:'UserFunctions'}, function(resp){$("#apmanagetokens").html(resp);}, 'html');
}
//Remove project from a token
function removeProjectAccess(vartokenid, varproj) {
	
	$.post('ajax/admin.php',{action:'removeAccess',source:'UserFunctions',tokenid:vartokenid,tokpid:varproj},function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.post('ajax/admin.php', {action:'PrintTokens',source:'UserFunctions'}, function(resp){$("#apmanagetokens").html(resp);}, 'html');
}

$(function() {
	// NEED IF'S HERE FOR DIV EXISTS
	if ($('#apmanagetokens').length > 0)
	{
		loadSymbolOn();
		$.post('ajax/admin.php', {action:'PrintTokens',source:'UserFunctions'}, function(resp){$("#apmanagetokens").html(resp);}, 'html');
		loadSymbolOff();
	}
	if ($('#apmanageusers').length > 0)
	{
		loadSymbolOn();
		$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
		loadSymbolOff();
	}
	$("#apmanageusers" ).on( "change",'.userrn', function() {
		loadSymbolOn();
	    var c = $(this);
	    $.when(
	    c.focusout()).then(function() {
	    	var uid = c.attr('uid');
	    	updateRealName(uid, c.val());
	    });
		loadSymbolOff();
	});

	$("#apmanageusers").on( "change",'.userorg', function() {
		loadSymbolOn();
	    var c = $(this);
	    $.when(
	    c.focusout()).then(function() {
	    	var uid = c.attr('uid');
	    	updateOrganization(uid, c.val());
	    });
		loadSymbolOff();
	});

	$("#apmanageusers").on( "click",'.userisconfirmed', function() {
		loadSymbolOn();
		var c = $(this);
		var uid = c.attr('uid');
		updateActivated(uid, c.is(':checked'));
	});

	$("#apmanageusers").on( "click",'.userisadmin', function() {
		loadSymbolOn();
		var c = $(this);
	    	var uid = c.attr('uid');
	    	updateAdmin(uid, c.is(':checked'));
			loadSymbolOff();
	});

	$("#apmanageusers").on( "click",'.deluser', function() {
		loadSymbolOn();
		var c = $(this);
	    	var uid = c.attr('uid');
	    	deleteUser(uid);
			loadSymbolOff();
	});
	
	$("#apmanageusers").on( "click",'.kmu_resetpw_submit', function() {
		loadSymbolOn();
		var uid = $('.kmu_resetpw_username').val();
		var pw1 = $('.kmu_resetpw_password1').val();
		var pw2 = $('.kmu_resetpw_password2').val();
		if (pw1 == pw2) {
			
			$.post('ajax/admin.php', { uid:uid, password:pw1, action:'resetPassword',source:'UserFunctions'}, function(resp){$("#ajaxstatus").html(resp);});
			alert(kgt_pwchanged);
			$.post('ajax/admin.php', {action:'showUsers',source:'UserFunctions'}, function(resp){$("#apmanageusers").html(resp);}, 'html');
		} else {
			alert(kgt_pwdontmatch);
		}
		loadSymbolOff();
	});
	
	$("#apmanagetokens").on( "click",'.token_create', function() {
		loadSymbolOn();
		var c = $(this);
		createToken();
		loadSymbolOff();
	});

	$("#apmanagetokens").on( "click",'.token_delete', function() {
		loadSymbolOn();
		var c = $(this);
		var tok = c.parents('.token_row').attr('tokid');
		deleteToken(tok);
		loadSymbolOff();
	});

	$("#apmanagetokens").on( "click",'.token_addproj', function() {
		loadSymbolOn();
		var c = $(this);
		var tok = c.parents('.token_row').attr('tokid');
		var proj = c.parents('.token_row').first().find('.token_proj').val();
	    	addProjectAccess(tok,proj);
			loadSymbolOff();
	});

	$("#apmanagetokens").on( "click",'.token_delproj', function() {
		loadSymbolOn();
		var c = $(this);
		var tok = c.parents('.token_row').attr('tokid');
		var proj = c.parents('.token_proj_row').first().attr('tokprojid');
	    	removeProjectAccess(tok,proj);
			loadSymbolOff();
	});
	
	$("#apforgotpasswordreset").on( "click",'.password_reset_submit', function() {
		loadSymbolOn();
		var form = $('#apforgotpasswordreset').serialize();
	    var pw1 = $("#apforgotpasswordreset").find('.password_reset_password1').first().val();
	    var pw2 = $("#apforgotpasswordreset").find('.password_reset_password2').first().val();
	
	    if (pw1 == pw2)
	    {
	        if (pw1.length >= 8)
	        {
	        	$("#apforgotpasswordreset").submit();
	        }
	        else
	        {
	            alert(kgt_resetpasstooshort);
	        }
	    }
	    else
	    {
	        alert(kgt_resetpassnomatch);
	    }
		loadSymbolOff();
	});
	
	$("#koraAdminSysManage").on("click",".ka_sysMgt_updateCtrlList", function() {
		loadSymbolOn();
		$.ajaxSetup({ async: false });
		
		$.post('ajax/admin.php', {action:'UpdateControlList',source:'SystemManagement'}, function(resp){$("#ka_admin_result").text(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#koraAdminSysManage").on("click",".ka_sysMgt_updateStyleList", function() {
		loadSymbolOn();
		$.ajaxSetup({ async: false });
		
		$.post('ajax/admin.php', {action:'UpdateStyleList',source:'SystemManagement'}, function(resp){$("#ka_admin_result").text(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#koraAdminSysManage").on("click",".ka_sysMgt_updateDatabase", function() {
		loadSymbolOn();
		var pid = $("#kora_globals").attr("pid");
		var sid = $("#kora_globals").attr("sid");
		
		window.location = 'upgradeDatabase.php?pid='+pid+'&sid='+sid;
	});
});


