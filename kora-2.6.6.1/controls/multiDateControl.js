
$(function() {
		//************************************************************************
		//                     INGESTION FORM HANDLERS
		//************************************************************************
		if ($('.ingestionForm').length > 0) {
			$(".ingestionForm" ).find('.kora_control').each(function() {
					var kcdiv = $(this);
					if ($(this).attr('kctype') == 'Date (Multi-Input)')
					{
						// ADD OPTION
						$(this).find('.kcmdc_additem').click(function() {
								var format = $(this).attr('kcmdc_format');
								var showera = $(this).attr('kcmdc_showera');
								var month = kcdiv.find('.kcdc_month').first().val();
								var day = kcdiv.find('.kcdc_day').first().val();
								var year = kcdiv.find('.kcdc_year').first().val();
								var era = kcdiv.find('.kcdc_era').first().val();
								var valname = kcdiv.find('.kcmdc_curritems').first().attr('name');
								
								if (month != "" || day != "" || year != "")
								{
									var opttext = FormatDateForDisplay(month, day, year, era, format, showera);
									var optval = "<date><month>" + String(month) + "</month><day>" + String(day) + "</day><year>" + String(year) + "</year><era>" + String(era) + "</era></date>";
									if (ValidateListItem(valname, optval, kcdiv))
									{ 
										kcdiv.find('.kcmdc_curritems').append('<option value="'+optval+'">'+opttext+'</option>');
									}
								}
						});
						
						// REMOVE OPTION
						$(this).find('.kcmdc_removeitem').click(function() {
								kcdiv.find('.kcmdc_curritems').find('option:selected').each(function() {
										$(this).remove();
								});
						});
						
						// MOVE UP
						$(this).find('.kcmdc_moveitemup').click(function() {
								kcdiv.find('.kcmdc_curritems > option:selected').each(function() {
										$(this).insertBefore($(this).prev());
								});
						});
						
						// MOVE DOWN
						$(this).find('.kcmdc_moveitemdown').click(function() {
								kcdiv.find('.kcmdc_curritems > option:selected').each(function() {
										$(this).insertAfter($(this).next());
								});
						});
					}
			});
		}

		
		$("#advSearch_table_other" ).find('.kora_control').each(function() {
			var kcdiv = $(this);
			if ($(this).attr('kctype') == 'Date (Multi-Input)')
			{
				// ADD OPTION
				$(this).find('.kcmdc_additem').click(function() {
						var format = $(this).attr('kcmdc_format');
						var showera = $(this).attr('kcmdc_showera');
						var month = kcdiv.find('.kcdc_month').first().val();
						var day = kcdiv.find('.kcdc_day').first().val();
						var year = kcdiv.find('.kcdc_year').first().val();
						var era = kcdiv.find('.kcdc_era').first().val();
						var valname = kcdiv.find('.kcmdc_curritems').first().attr('name');
						
						if (month != "" || day != "" || year != "")
						{
							var opttext = FormatDateForDisplay(month, day, year, era, format, showera);
							var optval = "<date><month>" + String(month) + "</month><day>" + String(day) + "</day><year>" + String(year) + "</year><era>" + String(era) + "</era></date>";
							if (ValidateListItem(valname, optval, kcdiv))
							{ 
								kcdiv.find('.kcmdc_curritems').append('<option value="'+optval+'">'+opttext+'</option>');
							}
						}
				});
				
				// REMOVE OPTION
				$(this).find('.kcmdc_removeitem').click(function() {
						kcdiv.find('.kcmdc_curritems').find('option:selected').each(function() {
								$(this).remove();
						});
				});
				
				// MOVE UP
				$(this).find('.kcmdc_moveitemup').click(function() {
						kcdiv.find('.kcmdc_curritems > option:selected').each(function() {
								$(this).insertBefore($(this).prev());
						});
				});
				
				// MOVE DOWN
				$(this).find('.kcmdc_moveitemdown').click(function() {
						kcdiv.find('.kcmdc_curritems > option:selected').each(function() {
								$(this).insertAfter($(this).next());
						});
				});
			}
		});
		
		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox").on( "click",".kcmdcopts_defadd", function() {
				var format = $('#colorbox .kcdcopts_format:checked').val();
				var showera = $('#colorbox .kcdcopts_showera:checked').val();
				var month = $('#colorbox .kcmdcopts_addmonth').val();
				var day = $('#colorbox .kcmdcopts_addday').val();
				var year = $('#colorbox .kcmdcopts_addyear').val();
				var era = $('#colorbox .kcmdcopts_addera').val();
				//var valname = kcdiv.find('.kcmdc_curritems').first().attr('name');
				
				if (month != "" || day != "" || year != "")
				{
					var opttext = FormatDateForDisplay(month, day, year, era, format, showera);
					var optval = "<date><month>" + String(month) + "</month><day>" + String(day) + "</day><year>" + String(year) + "</year><era>" + String(era) + "</era></date>";
					{ $('#colorbox .kcmdcopts_defval').append('<option value="'+optval+'">'+opttext+'</option>'); }
				}
				KCMDC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcmdcopts_defremove", function() {
				if(confirm('Are you sure you would like to remove these list options?')){
					$('#colorbox .kcmdcopts_defval > option:selected').each(function() {
							$(this).remove();
					});
					KCMDC_SaveDefaultValue();
				}
		});
		
		$("#colorbox").on( "click",".kcmdcopts_defmoveup", function() {
				$('#colorbox .kcmdcopts_defval > option:selected').each(function() {
						$(this).insertBefore($(this).prev());
				});
				KCMDC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcmdcopts_defmovedown", function() {
				$('#colorbox .kcmdcopts_defval > option:selected').each(function() {
						$(this).insertAfter($(this).next());
				});
				KCMDC_SaveDefaultValue();
		});
		
		// EVERYTHING ELSE IS INHERITED PROPERLY FROM DATE CONTROL
		
		$('.kcmdc_curritems').on('change',':option', function(){
			var datadom = kcdiv.find('.kcmdc_curritems').first();
		});
});

//Saves default value for an MDC
function KCMDC_SaveDefaultValue()
{
	var ajaxhandler = 'ajax/control.php';
	
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var defdates = [];
	$('#colorbox .kcmdcopts_defval option').each(function(i) {
			defdates.push(this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'saveMDDefault',source:'MultiDateControl',pid:pid,sid:sid,cid:cid,values:defdates}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	$.ajaxSetup({ async: true });
}

//Performs special validation for MDC
function KCMDC_Validate(kcdiv)
{
	var datadom = kcdiv.find('.kcmdc_curritems').first();
	
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