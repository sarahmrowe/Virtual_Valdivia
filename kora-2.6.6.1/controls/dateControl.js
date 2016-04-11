
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
		$("#colorbox").on( "click",".kcdcopts_updatedefault", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var month = $('#colorbox .kcdcopts_defmonth').val();
				var day = $('#colorbox .kcdcopts_defday').val();
				var year = $('#colorbox .kcdcopts_defyear').val();
				var era = $('#colorbox .kcdcopts_defera').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateDDefaultValue',source:'DateControl',pid:pid,sid:sid,cid:cid,day:day,month:month,year:year,era:era}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});

		$("#colorbox").on( "change",".kcdcopts_rangestart", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var rangestart = $('#colorbox .kcdcopts_rangestart').val();
						var rangeend = $('#colorbox .kcdcopts_rangeend').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateDDateRange',source:'DateControl',pid:pid,sid:sid,cid:cid,rangestart:rangestart,rangeend:rangeend}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		$("#colorbox").on( "change",".kcdcopts_rangeend", function() {
				var c = $(this);
				$.when(
					c.focusout()).then(function() {
						var pid = $('#colorbox .kora_control_opts').attr('pid');
						var sid = $('#kora_globals').attr('sid');
						var cid = $('#colorbox .kora_control_opts').attr('cid');
						var rangestart = $('#colorbox .kcdcopts_rangestart').val();
						var rangeend = $('#colorbox .kcdcopts_rangeend').val();
						$.ajaxSetup({ async: false });
						
						$.post(ajaxhandler, {action:'updateDDateRange',source:'DateControl',pid:pid,sid:sid,cid:cid,rangestart:rangestart,rangeend:rangeend}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
						PrintControlOpts(pid,sid,cid);
						$.ajaxSetup({ async: true });
					});
		});
		
		$("#colorbox").on( "click",".kcdcopts_format", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var format = $('#colorbox .kcdcopts_format:checked').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateDFormat',source:'DateControl',pid:pid,sid:sid,cid:cid,format:format}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		
		$("#colorbox").on( "click",".kcdcopts_showera", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var era = $('#colorbox .kcdcopts_showera:checked').val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateDEra',source:'DateControl',pid:pid,sid:sid,cid:cid,era:era}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });
		});
		

		$("#colorbox").on( "click",".kcdcopts_prefixesadd", function() {
				var val = $('#colorbox .kcdcopts_prefixesaddval').val();
				$('#colorbox .kcdcopts_prefixes').append('<option value="'+val+'">'+val+'</option>');
				KCDC_SavePrefixes();
		});
		
		$("#colorbox").on( "click",".kcdcopts_prefixesremove", function() {
				$('#colorbox .kcdcopts_prefixes > option:selected').each(function() {
						$(this).remove();
				});
				KCDC_SavePrefixes();
		});
	
		$("#colorbox").on( "click",".kcdcopts_suffixesadd", function() {
				var val = $('#colorbox .kcdcopts_suffixesaddval').val();
				$('#colorbox .kcdcopts_suffixes').append('<option value="'+val+'">'+val+'</option>');
				KCDC_SaveSuffixes();
		});
		
		$("#colorbox").on( "click",".kcdcopts_suffixesremove", function() {
				$('#colorbox .kcdcopts_suffixes > option:selected').each(function() {
						$(this).remove();
				});
				KCDC_SaveSuffixes();
		});
});

//Saves prefix values of the DC
function KCDC_SavePrefixes()
{
	var ajaxhandler = 'ajax/control.php';
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var values = [];
	$('#colorbox .kcdcopts_prefixes option').each(function(i) {
			values.push(this.value);
			console.log(this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'updateDPrefixes',source:'DateControl',pid:pid,sid:sid,cid:cid,values:values}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	PrintControlOpts(pid,sid,cid);
	$.ajaxSetup({ async: true });
}

//Saves suffix values of the DC
function KCDC_SaveSuffixes()
{
	var ajaxhandler = 'ajax/control.php';
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var values = [];
	$('#colorbox .kcdcopts_suffixes option').each(function(i) {
			values.push(this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'updateDSuffixes',source:'DateControl',pid:pid,sid:sid,cid:cid,values:values}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	PrintControlOpts(pid,sid,cid);
	$.ajaxSetup({ async: true });
}

//Formats the visual date of the DC based on a specific passed in format
function FormatDateForDisplay(month, day, year, era, format, showera)
{
	// Kept an error that this variables were not defined. These allow the multi-date control to add things into the feild after selecting a date. -- NL
	var kgt_jan = "January";
	var kgt_feb = "February";
	var kgt_mar = "March";
	var kgt_apr = "April";
	var kgt_may = "May";
	var kgt_jun = "June";
	var kgt_jul = "July";
	var kgt_aug = "August";
	var kgt_sep = "September";
	var kgt_oct = "October";
	var kgt_nov = "November";
	var kgt_dec = "December";	
	
	// The empty member is to fill the 0 index of the array
	var monthArray = new Array("", 
		kgt_jan, kgt_feb, kgt_mar,
		kgt_apr, kgt_may, kgt_jun,
		kgt_jul, kgt_aug, kgt_sep,
		kgt_oct, kgt_nov, kgt_dec);

	var myString = "";
	
	// The + ensures that the string is type-cast to a number
	if (format == 'MDY')
	{
		if (+month > 0)
		{ myString = myString + monthArray[+month] + " "; }
		if (+day > 0)
		{
			myString = myString + String(day);
			if (+year > 0)
			{ myString = myString + ", "; }
		}
		if (+year > 0)
		{ myString = myString + String(+year); }
	}
	else if (format == 'DMY')
	{
		if (+day > 0)
		{ myString = myString + String(day) + " "; }
		if (+month > 0)
		{ myString = myString + monthArray[+month] + " "; }
		if (+year > 0)
		{ myString = myString + String(+year); }
	}
	else if (format == 'YMD')
	{
		if (+year > 0)
		{ myString = myString + String(+year) + " "; }
		if (+month > 0)
		{ myString = myString + monthArray[+month] + " "; }
		if (+day > 0)
		{ myString = myString + String(day); }
	}
	else
	{ myString = kgt_baddateformat; }
	
	// If CE/BCE is enabled, add Javascript Code to show it.
	if (showera == 'Yes')
	{ myString = myString + " " + String(era); }
	
	return myString;
}




