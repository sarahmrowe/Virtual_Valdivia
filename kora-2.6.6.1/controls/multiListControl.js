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
		
		//right
		$("#colorbox").on( "click",".kcmlcopts_dvBtnAddDef", function() {
			///change the lists
			$('#colorbox .kcmlcopts_selDef').append($('#colorbox .kcmlcopts_unSelDef').find('option:selected'));
			$('#colorbox .kcmlcopts_unSelDef').find('option:selected').each(function() {
				$(this).remove();
			});
			
			saveDefValOptions();
		});
		
		//left
		$("#colorbox").on( "click",".kcmlcopts_dvBtnRemDef", function() {
			///change the lists
			$('#colorbox .kcmlcopts_unSelDef').append($('#colorbox .kcmlcopts_selDef').find('option:selected'));
			$('#colorbox .kcmlcopts_selDef').find('option:selected').each(function() {
				$(this).remove();
			});
			
			saveDefValOptions();
		});
		
		function saveDefValOptions()
		{
			var pid = $('#colorbox .kora_control_opts').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var cid = $('#colorbox .kora_control_opts').attr('cid');
			
			//gather all options
			var options = new Array();
			$('#colorbox .ctrl_list_listOpt').find('option').each(function(){
				options.push($(this).val());
			});
			
			//gather default values
			var defVal = new Array();
			$('#colorbox .kcmlcopts_selDef').find('option').each(function(){
				defVal.push($(this).val());
			});
			
			//send them
			$.ajaxSetup({ async: false });
			
			$.post(ajaxhandler, {action:'updateMultiDef',source:'MultiListControl',pid:pid,sid:sid,cid:cid,options:options,defVal:defVal}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
			PrintControlOpts(pid,sid,cid);
			$.ajaxSetup({ async: true });
		}
		
});

//Performs special validation method for MLC
function KCMLC_Validate(kcdiv)
{
	var datadom = kcdiv.find('.kcmlc_curritems').first();
	
	var fd = new FormData();
	fd.append('action','validateControl');
	fd.append('source','DataFunctions');
	fd.append('pid',kcdiv.attr('kpid'));
	fd.append('sid',kcdiv.attr('ksid'));
	fd.append('cid',kcdiv.attr('kcid'));
	var datavals = '';
	var first = true;
	datadom.find('option:selected').each(function() {
			if(first){
				datavals += this.value;
				first = false;
			}else{
				datavals += '<MLC>'+this.value;
			}
	});
	fd.append(datadom.attr('name'), datavals);
	
	
	$.ajax({
			url: 'ajax/control.php',
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				kcdiv.find('.ajaxerror').html(data);
				///For MLC, maybe we do validation elsewhere
				if(data==''){
					kcdiv.attr('kcvalid','valid');
				}else{
					kcdiv.attr('kcvalid','invalid');
				}
				
			}
	});		
	
}