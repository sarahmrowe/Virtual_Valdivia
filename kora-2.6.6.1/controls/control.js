
$(function() {
		var ajaxhandler = 'ajax/control.php';
		
		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox" ).on( "click",'.ctrlopt_setname', function() {
				var answer = confirm(kgt_changectlname);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });					
				if (answer == true) {
					var cname = $(this).parents('.kora_control_opts').first().find('.ctrlopt_name').val();
					
					$.post(ajaxhandler, {action:"SetName",source:"Control",pid:pid,sid:sid,cid:cid,cname:cname},function(resp){$("#global_error").append(resp);}, 'html');
				}
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox" ).on( "change",'.ctrlopt_desc', function() {
				var c = $(this);
				$.when(c.focusout()).then(function() {
						var pid = $('#kora_globals').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $(this).parents('.kora_control_opts').first().attr('cid');
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:"SetDesc",source:"Control",pid:pid,sid:sid,cid:cid,cdesc:$(this).val()},function(resp){$("#global_error").append(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
				});
		});
		
		$("#colorbox" ).on( "click",'.required', function() {
				var c = $(this);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:"SetStdOption",source:"Control",pid:pid,sid:sid,cid:cid,ctrlopt:"required",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox" ).on( "click",'.searchable', function() {
				var c = $(this);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:"SetStdOption",source:"Control",pid:pid,sid:sid,cid:cid,ctrlopt:"searchable",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox" ).on( "click",'.advsearchable', function() {
				var c = $(this);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:"SetStdOption",source:"Control",pid:pid,sid:sid,cid:cid,ctrlopt:"advsearchable",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox" ).on( "click",'.showinresults', function() {
				var c = $(this);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:"SetStdOption",source:"Control",pid:pid,sid:sid,cid:cid,ctrlopt:"showinresults",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox" ).on( "click",'.publicentry', function() {
				var c = $(this);
				var pid = $('#kora_globals').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $(this).parents('.kora_control_opts').first().attr('cid');
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:"SetStdOption",source:"Control",pid:pid,sid:sid,cid:cid,ctrlopt:"publicentry",ctrloptval:$(this).is(':checked')},function(resp){$("#global_error").append(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
});

//Prints out control options. Used as a jQuery way to refresh the options when an action is taken
function PrintControlOpts(pid,sid,cid)
{
	var ajaxhandler = 'ajax/control.php';
	
	if (($('#colorbox').length > 0) && ($('#cboxContent').length > 0))
	{
		
		$.post(ajaxhandler,{action:"ShowDialog",source:"Control",pid:pid,sid:sid,cid:cid}, function(resp){$("#cboxContent").html(resp);}, 'html');
	}
}
