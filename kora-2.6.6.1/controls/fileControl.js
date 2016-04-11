$(function() {
		
		var ajaxhandler = 'ajax/control.php';
		
		//************************************************************************
		//                     INGESTION FORM HANDLERS
		//************************************************************************
		if ($('.ingestionForm').length > 0) {
			$(".ingestionForm" ).find('.kora_control').each(function() {
					var kcdiv = $(this);
					if (($(this).attr('kctype') == 'File') || ($(this).attr('kctype') == 'Image'))
					{
						$(this).find('.kcfc_delfile').click(function(e) {
								e.preventDefault();
								var answer = confirm(kgt_reallydelfile);
								var rid = $(this).parents('.ingestionForm').first().find('input[name=rid]').first().val();
								var efilediv = kcdiv.find('.kcfc_existingfile').first();
								var pid = $('#kora_globals').attr('pid');
								
								if (answer)
								{
									$.ajaxSetup({ async: false });
									
									$.post(ajaxhandler, {action:'deleteFile',source:'FileControl',kid:rid,pid:pid,cid:kcdiv.attr('kcid') }, function(resp){efilediv.html(resp);}, 'html');
									$.ajaxSetup({ async: true });
								}
						});
					}
			});
		}

		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox").on( "change",".kcfcopts_maxsize", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var maxsize = c.val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateFileSize',source:'FileControl',pid:pid,sid:sid,cid:cid,maxsize:maxsize}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		$("#colorbox").on( "click",".kcfcopts_restrictedtypes", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var restrict = $('#colorbox .kcfcopts_restrictedtypes:checked').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateFileRestrictions',source:'FileControl',pid:pid,sid:sid,cid:cid,restrict:restrict}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kcfcopts_archival", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var archival = $('#colorbox .kcfcopts_archival:checked').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateArchival',source:'FileControl',pid:pid,sid:sid,cid:cid,archival:archival}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
				
		$("#colorbox").on( "click",".kcfcopts_presetuse", function() {
				var answer = confirm(kgt_useregexpreset);
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var value = $('#colorbox .kcfcopts_presetname').val();
				if (answer == true) {
					$.ajaxSetup({ async: false });
					
					$.post(ajaxhandler, {action:'usePreset',source:'FileControl',pid:pid,sid:sid,cid:cid,preset:value}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
					PrintControlOpts(pid,sid,cid);
					$.ajaxSetup({ async: true });
				}
		});
		
		$("#colorbox").on( "click",".kcfcopts_presetsave", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var newName = $('#colorbox .kcfcopts_presetnew').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'savePreset',source:'FileControl',pid:pid,sid:sid,cid:cid,name:newName}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});

		// THESE NEXT 4 ARE ALL FOR THE MIME-TYPE BOX
		$("#colorbox").on( "click",".kcfcopts_allowedtypesadd", function() {
				var val = $('#colorbox .kcfcopts_allowedtypesnew').val();
				$('#colorbox .kcfcopts_allowedtypes').append('<option>'+val+'</option>');
				KCFC_SaveMimeTypes();
		});
		
		$("#colorbox").on( "click",".kcfcopts_allowedtypesremove", function() {
				$('#colorbox .kcfcopts_allowedtypes > option:selected').each(function() {
						$(this).remove();
				});
				KCFC_SaveMimeTypes();
		});
		
		$("#colorbox").on( "click",".kcfcopts_allowedtypesmoveup", function() {
				$('#colorbox .kcfcopts_allowedtypes > option:selected').each(function() {
						$(this).insertBefore($(this).prev());
				});
				KCFC_SaveMimeTypes();
		});
		
		$("#colorbox").on( "click",".kcfcopts_allowedtypesmovedown", function() {
				$('#colorbox .kcfcopts_allowedtypes > option:selected').each(function() {
						$(this).insertAfter($(this).next());
				});
				KCFC_SaveMimeTypes();
		});

        //************************************************************************
        //                     MediaElement
        //************************************************************************

        $('video,audio').mediaelementplayer(/* Options */);
		
});

//Updates FC mime types in the system
function KCFC_SaveMimeTypes()
{
	var ajaxhandler = 'ajax/control.php';
	
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var mimetypes = [];
	$('#colorbox .kcfcopts_allowedtypes option').each(function(i) {
			mimetypes.push(this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'updateMimeTypes',source:'FileControl',pid:pid,sid:sid,cid:cid,mimetypes:mimetypes}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.ajaxSetup({ async: true });
}

