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
		$("#colorbox").on( "change",".kcicopt_thumbwidth", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var twidth = $('#colorbox .kcicopt_thumbwidth').val();
						var theight = $('#colorbox .kcicopt_thumbheight').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateThumbnailSize',source:'ImageControl',pid:pid,sid:sid,cid:cid,twidth:twidth,theight:theight}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		
		$("#colorbox").on( "change",".kcicopt_thumbheight", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var twidth = $('#colorbox .kcicopt_thumbwidth').val();
						var theight = $('#colorbox .kcicopt_thumbheight').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateThumbnailSize',source:'ImageControl',pid:pid,sid:sid,cid:cid,twidth:twidth,theight:theight}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
});


