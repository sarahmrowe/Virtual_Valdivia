$(function() {
		
		var ajaxhandler = 'ajax/control.php';
		
		//************************************************************************
		//                     INGESTION FORM HANDLERS
		//************************************************************************
		if ($('.ingestionForm').length > 0) {
		}

		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox").on( "change",".kctcopts_rows", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var rows = $('#colorbox .kctcopts_rows').val();
						var cols = $('#colorbox .kctcopts_cols').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateSize',source:'TextControl',pid:pid,sid:sid,cid:cid,rows:rows,cols:cols}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		$("#colorbox").on( "change",".kctcopts_cols", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var rows = $('#colorbox .kctcopts_rows').val();
						var cols = $('#colorbox .kctcopts_cols').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateSize',source:'TextControl',pid:pid,sid:sid,cid:cid,rows:rows,cols:cols}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		$("#colorbox").on( "click",".kctcopts_regexset", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var regex = $('#colorbox .kctcopts_regex').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateRegEx',source:'TextControl',pid:pid,sid:sid,cid:cid,regex:regex}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kctcopts_editor", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var editor = $('#colorbox .kctcopts_editor:checked').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateEditor',source:'TextControl',pid:pid,sid:sid,cid:cid,editor:editor}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kctcopts_defset", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var defaultValue = $('#colorbox .kctcopts_defval').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateDefaultValue',source:'TextControl',pid:pid,sid:sid,cid:cid,defaultV:defaultValue}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kctcopts_presetuse", function() {
				var answer = confirm(kgt_useregexpreset);
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var value = $('#colorbox .kctcopts_presetname').val();
				if (answer == true) {
					$.ajaxSetup({ async: false });
					
					$.post(ajaxhandler, {action:'usePreset',source:'TextControl',pid:pid,sid:sid,cid:cid,preset:value}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
					PrintControlOpts(pid,sid,cid);
					$.ajaxSetup({ async: true });
				}
		});
		
		$("#colorbox").on( "click",".kctcopts_presetsave", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var regExValue = $('#colorbox .kctcopts_regex').val();
				var newName = $('#colorbox .kctcopts_presetnew').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'savePreset',source:'TextControl',pid:pid,sid:sid,cid:cid,regex:regExValue,name:newName}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
});

