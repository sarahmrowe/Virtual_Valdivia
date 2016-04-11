
$(function() {
		//************************************************************************
		//                     INGESTION FORM HANDLERS
		//************************************************************************
		if ($('.ingestionForm').length > 0) {
			$(".ingestionForm" ).find('.kora_control').each(function() {
					var kcdiv = $(this);
					if ($(this).attr('kctype') == 'Text (Multi-Input)')
					{
						// ADD OPTION
						$(this).find('.kcmtc_additem:button').click(function() {
								var val = kcdiv.find('.kcmtc_additem:text').first().val();
								var valname = kcdiv.find('.kcmtc_curritems').first().attr('name');
								if (val != '')
								{
									if (ValidateListItem(valname, val, kcdiv))
										{ 
											kcdiv.find('.kcmtc_curritems').append('<option value="'+val+'" selected>'+val+'</option>'); 
										} 
								}
						});
						
						// REMOVE OPTION
						$(this).find('.kcmtc_removeitem').click(function() {
								kcdiv.find('.kcmtc_curritems').find('option:selected').each(function() {
										$(this).remove();
								});
						});
						
						// MOVE UP
						$(this).find('.kcmtc_moveitemup').click(function() {
								kcdiv.find('.kcmtc_curritems > option:selected').each(function() {
										$(this).insertBefore($(this).prev());
								});
						});
						
						// MOVE DOWN
						$(this).find('.kcmtc_moveitemdown').click(function() {
								kcdiv.find('.kcmtc_curritems > option:selected').each(function() {
										$(this).insertAfter($(this).next());
								});
						});
					}
			});
		}
		
		$("#advSearch_table_other" ).find('.kora_control').each(function() {
			var kcdiv = $(this);
			if ($(this).attr('kctype') == 'Text (Multi-Input)')
			{
				// ADD OPTION
				$(this).find('.kcmtc_additem:button').click(function() {
						var val = kcdiv.find('.kcmtc_additem:text').first().val();
						var valname = kcdiv.find('.kcmtc_curritems').first().attr('name');
						if (val != '')
						{
							if (ValidateListItem(valname, val, kcdiv))
								{ 
									kcdiv.find('.kcmtc_curritems').append('<option value="'+val+'" selected>'+val+'</option>'); 
								} 
						}
				});
				
				// REMOVE OPTION
				$(this).find('.kcmtc_removeitem').click(function() {
						kcdiv.find('.kcmtc_curritems').find('option:selected').each(function() {
								$(this).remove();
						});
				});
				
				// MOVE UP
				$(this).find('.kcmtc_moveitemup').click(function() {
						kcdiv.find('.kcmtc_curritems > option:selected').each(function() {
								$(this).insertBefore($(this).prev());
						});
				});
				
				// MOVE DOWN
				$(this).find('.kcmtc_moveitemdown').click(function() {
						kcdiv.find('.kcmtc_curritems > option:selected').each(function() {
								$(this).insertAfter($(this).next());
						});
				});
			}
		});

		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox").on( "click",".kcmtcopts_defadd", function() {
				var val = $('#colorbox .kcmtcopts_defnew').val();
				$('#colorbox .kcmtcopts_defval').append('<option>'+val+'</option>');
				KCMTC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcmtcopts_defremove", function() {
				if(confirm('Are you sure you would like to remove these list options?')){
					$('#colorbox .kcmtcopts_defval > option:selected').each(function() {
							$(this).remove();
					});
					KCMTC_SaveDefaultValue();
				}
		});
		
		$("#colorbox").on( "click",".kcmtcopts_defmoveup", function() {
				$('#colorbox .kcmtcopts_defval > option:selected').each(function() {
						$(this).insertBefore($(this).prev());
				});
				KCMTC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcmtcopts_defmovedown", function() {
				$('#colorbox .kcmtcopts_defval > option:selected').each(function() {
						$(this).insertAfter($(this).next());
				});
				KCMTC_SaveDefaultValue();
		});
		
});

//Saves default value for a MTC
function KCMTC_SaveDefaultValue()
{
	var ajaxhandler = 'ajax/control.php';
	
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var defvals = [];
	$('#colorbox .kcmtcopts_defval option').each(function(i) {
			defvals.push(this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'saveDefault',source:'MultiTextControl',pid:pid,sid:sid,cid:cid,values:defvals}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.ajaxSetup({ async: true });
}

//Performs special validation method for MTC
function KCMTC_Validate(kcdiv)
{
	var datadom = kcdiv.find('.kcmtc_curritems').first();
	
	var fd = new FormData();
	fd.append('action','validateControl');
	fd.append('source','DataFunctions');
	fd.append('pid',kcdiv.attr('kpid'));
	fd.append('sid',kcdiv.attr('ksid'));
	fd.append('cid',kcdiv.attr('kcid'));
	var datavals = [];
	datadom.find('option:selected').each(function() {
			datavals.push(this.value);
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
				///For MDC, maybe we do validation elsewhere
				if(data==''){
					kcdiv.attr('kcvalid','valid');
				}else{
					kcdiv.attr('kcvalid','invalid');
				}
				
			}
	});		
	
}