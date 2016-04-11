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
		
		//up
		$("#colorbox").on( "click",".kclcopts_loBtnUp", function() {
			$('#colorbox .kclcopts_listOpt').find('option:selected').each(function() {
				$(this).insertBefore($(this).prev());
			});
			
			saveListOptions();
		});
		
		//down
		$("#colorbox").on( "click",".kclcopts_loBtnDown", function() {
			$('#colorbox .kclcopts_listOpt').find('option:selected').each(function() {
				$(this).insertAfter($(this).next());
			});
			
			saveListOptions();
		});
		
		//remove
		$("#colorbox").on( "click",".kclcopts_loBtnRemove", function() {
			if(confirm('Are you sure you would like to remove these list options?')){
				$('#colorbox .kclcopts_listOpt').find('option:selected').each(function() {
					$(this).remove();
				});
			}
			
			saveListOptions();
		});
		
		//add option
		$("#colorbox").on( "click",".kclcopts_noBtnAddOption", function() {
			var value = '<option>'+$('#colorbox .kclcopts_newOpt').val()+'</option>';
			$('#colorbox .kclcopts_listOpt').append(value);
			
			saveListOptions();
		});
		
		//update
		$("#colorbox").on( "click",".kclcopts_doUpdate", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			var defVal = '';
			var count = 1;
			
			//gather default value
			$('#colorbox .kclcopts_defOpt').find('option:selected').each(function(){
				if (count == 1){
					defVal = $(this).val();
				}
				count = count + 1;
			});
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'updateDefValue',source:'ListControl',pid:pid,sid:sid,cid:cid,defVal:defVal}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		});
		
		//use preset
		$("#colorbox").on( "click",".kclcopts_lpBtnUsePreset", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			var selPre = '';
			
			//gather selected preset
			selPre = $('#colorbox .kclcopts_listPre').val();
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'updatePresets',source:'ListControl',pid:pid,sid:sid,cid:cid,selPre:selPre}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		});
		
		//save preset
		$("#colorbox").on( "click",".kclcopts_pnBtnSavePreset", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			//gather new preset name
			var name = $('#colorbox .kclcopts_preName').val();
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'saveNewPreset',source:'ListControl',pid:pid,sid:sid,cid:cid,name:name}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kclcopts_afsBtnSelectCon", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			var afCid = $('#colorbox .kclcopts_autoFillSel').val();
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'setAutoFill',source:'ListControl',pid:pid,sid:sid,cid:cid,afCid:afCid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kclcopts_afsBtnAddRule", function() {
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			//gather 'fillVal','params','numRules'
			var fillVal = $('#colorbox .af_value').val();
			var numRules = $('#colorbox .autoFillRuleRow').length;
			
			params = {};
			
	   		var idParts;
	   		var values;
			for(var i=0 ; true ; ++i) {
				values = $('.af_param_val'+i);
				console.log(values);
				if (values.length <= 0) {
					break;
				}
				
				for (var j=0 ; j<values.length ; ++j) {
					idParts = values[j].id.split('_');
					if (idParts[idParts.length - 1] != 'val'+i) {
						params['val' + i + '[' + idParts[idParts.length - 1] + ']'] = values[j].value;
					} else {
						params['val'+i+'[]'] = values[j].value;
					}
				}
			}
			
			console.log(params);
			
			var op = $('#af_param_val_op').val();
			if (op) {
				params['op']=op;
			}
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'addAutoFillRule',source:'ListControl',pid:pid,sid:sid,cid:cid,fillVal:fillVal,params:params,numRules:numRules}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		});
		
//Updates and saves the list options for a LC
function saveListOptions()
{
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	
	//gather options
	var options = new Array();
	$('#colorbox .kclcopts_listOpt').find('option').each(function(){
		options.push($(this).val());
	});
	
	//send them
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'updateListOpts',source:'ListControl',pid:pid,sid:sid,cid:cid,options:options}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	//PrintControlOpts(pid,sid,cid);
	$.ajaxSetup({ async: true });
}
		
});