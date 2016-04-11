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
		
		//add
		$("#colorbox").on( "click",".kcgcopts_clBtnAdd", function() {
			$('#colorbox .kcgcopts_selCont').append($('#colorbox .kcgcopts_contList').find('option:selected'));
			
			saveSelectedControls();
		});
		
		//up
		$("#colorbox").on( "click",".kcgcopts_scBtnUp", function() {
			$('#colorbox .kcgcopts_selCont').find('option:selected').each(function() {
				$(this).insertBefore($(this).prev());
			});
			
			saveSelectedControls();
		});
		
		//down
		$("#colorbox").on( "click",".kcgcopts_scBtnDown", function() {
			$('#colorbox .kcgcopts_selCont').find('option:selected').each(function() {
				$(this).insertAfter($(this).next());
			});
			
			saveSelectedControls();
		});
		
		//remove
		$("#colorbox").on( "click",".kcgcopts_scBtnRemove", function() {
			$('#colorbox .kcgcopts_selCont').find('option:selected').each(function() {
				$(this).remove();
			});
			
			saveSelectedControls();
		});
		
		//update
		$("#colorbox").on( "click",".kcgcopts_ugBtnUpdate", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			var answer = confirm("<?php echo gettext('Are you sure you want to update geocodes?  This may take some time, and all existing geocodes will be replaced')?>");
			
			if(answer){
				$.ajaxSetup({ async: false });
				$.post(ajaxhandler, {action:'updateGeocodes',source:'GeolocatorControl',pid:pid,sid:sid,cid:cid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
			}
		});
		
		function saveSelectedControls()
		{
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			//gather options
			var options = new Array();
			$('#colorbox .kcgcopts_selCont').find('option').each(function(){
				options.push($(this).val());
			});
			
			//send them
			$.ajaxSetup({ async: false });
			$.post(ajaxhandler, {action:'updateControls',source:'GeolocatorControl',pid:pid,sid:sid,cid:cid,options:options}, function(resp){
				var geocodes = resp.responseText.split("<\/script>");
				for(var i = 0; i < geocodes.length; i++){
    				geocodes[i] = geocodes[i].substr(geocodes[i].indexOf('>')+1);
    				eval(geocodes[i]);
				}
				//alert(geocodes);
				eval(resp.responseText);
				$("#ajaxstatus").html(resp);
			}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		}
		
		
});