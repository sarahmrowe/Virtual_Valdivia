/**
Copyright (2008) Matrix: Michigan State University

This file is part of KORA.

KORA is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

KORA is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
//Gets nav links for search results that span multiple pages
function GetPageNavLinks(form, page) {
	// FOR ALL CONTROL COLLECTIONS
	var colls = $('.ingestionForm').find('.controlCollection');
	var numpages = colls.size();
	var html = '';
	
	// PRINT THE PREV BUTTON
	if (page > 1) { html += '<a class="navpage" page="'+(parseInt(page)-1)+'">'; }
	html += 'Prev';
	if (page > 1) { html += '</a>'; }
	
	for (var i=1; i<numpages; i++)
	{
		html += ' | ';
		if (page != i) { html += '<a class="navpage" page="'+i+'">'; }
		html += i;
		if (page != i) { html += '</a>'; }
		
	}
	
	// PRINT THE NEXT BUTTON
	html += ' | ';
	if (page < numpages) { html += '<a class="navpage" page="'+(parseInt(page)+1)+'">'; }
	html += 'Next';
	if (page < numpages) { html += '</a>'; }
	
	return html;
}

//Enables a specific page in the search results
function GetPage(form, page) {

	var pageNavHTML = GetPageNavLinks(form, page);
	
	form.find('.kora_navNumbers').html(pageNavHTML);
	
	form.find('.controlCollection').each(function() {
		$(this).hide();
	});
	form.find('.id'+page).show();
	
	window.scrollTo(0,0);
}

//Prints out record presets for the scheme
function PrintRecordPresets(pid,sid)
{
	if (($('#colorbox').length > 0) && ($('#cboxContent').length > 0))
	{
		
		$.post('ajax/record.php',{action:"PrintForm",source:"PresetFunctions",pid:pid,sid:sid}, function(resp){$("#presetRecord").html(resp);}, 'html');
	}
}

//Checks all controls in an ingestion form. If they are all valid, enable the submit button
function enableValidIngestion(){
	var invalid = false;
	$('.kora_control').each(function(){
	    if($(this).attr('kcvalid')=='invalid'){
	    	invalid = true;
	    }
	});
	$('.kcri_submit').attr('disabled',invalid);
}

$(function() {
	
	var ajaxhandler = 'ajax/record.php';
	
	
	// TODO: THIS IS ACTUALLY BAD, YOU'LL NOTICE ELSEWHERE WE DO THIS CALL TO TRUE/FALSE RIGHT AROUND EACH .GET/.AJAX CALL AS NECESSARY
	// SO THIS CALL SHOULD BE REWORKED TO FUNCTION THE SAME INSTEAD OF SETTING IT GLOBALLY, IT IS JUST UNSET ELSEWHERE BY OTHER FUNCTIONS
	$.ajaxSetup({ async: false });
	
	enableValidIngestion();
	
	$("#ingestprogress").on( "click", '.kri_showdata', function() {
		loadSymbolOn();
		$(this).parents('.pending_ingest').first().find('.pending_ingest_alldata').first().toggle();
		loadSymbolOff();
	});
	
	///this handles rename of a record preset
	$("#presetRecord").on( "click",".preset_record_rename", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varkid = $('#oldName').find('option:selected').val();
	    var varnewname = $('#newName').val();
	    
	    $.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'renameRecordPreset',source:'PresetFunctions',kid:varkid,name:varnewname,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		PrintRecordPresets(pid,sid);
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#presetRecord").on( "click",".preset_record_demote", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varkid = $(this).attr('name');
		
		$.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'demoteRecordPreset',source:'PresetFunctions',kid:varkid,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		PrintRecordPresets(pid,sid);
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$("#AddPresetTable").on( "click",".preset_record_create", function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var varkid = $('#kora_globals').attr('rid');
		var name = $('#presetName').val();
		
		$.ajaxSetup({ async: false });
		
		$.post(ajaxhandler, {action:'addRecordPreset',source:'PresetFunctions',name:name,kid:varkid,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
		$.ajaxSetup({ async: true });
		loadSymbolOff();
	});
	
	$('.krusepreset').click( function() {
		loadSymbolOn();
		if (confirm(kgt_schemeusepreset)){
			var pid = $('#kora_globals').attr('pid');
			var sid = $('#kora_globals').attr('sid');
			var presetid = $('.krwhatpreset').val();
			window.location = 'ingestObject.php?pid='+pid+'&sid='+sid+'&preset='+presetid;
		}
	});
	
	$(".kora_navNumbers" ).on("click",'.navpage', function() {
		loadSymbolOn();
		GetPage($(this).parents('.ingestionForm').first(), $(this).attr('page'));
		loadSymbolOff();
	});
	
	////////////////////////////////////////////VALIDATION CHECKS///////////////////
	// THIS TRIGGERS VALIDATION FOR ALL INPUT FIELDS
	$(".ingestionForm" ).on( "change",':input', function() {
		loadSymbolOn();
		var c = $(this);
		$.when(
			c.focusout()).then(function() {
				// A STANDARD KORA INPUT FIELD SHOULD HAVE NAME LIKE THIS...
				var regex = /p(\w+)c(\w+)$/g;
				var match = regex.exec($(this).attr('name'));
				
				if ((match == null) || (match.length < 2)) { return; }
				
				var kcdiv = $(this).parents('.kora_control').first();
				
				var fd = new FormData();
				fd.append('action','validateControl');
				fd.append('source','DataFunctions');
				fd.append('pid',kcdiv.attr('kpid'));
				fd.append('sid',kcdiv.attr('ksid'));
				fd.append('cid',kcdiv.attr('kcid'));
				if(kcdiv.attr('kctype')=='File' || kcdiv.attr('kctype')=='Image'){
					fd.append($(this).attr('name'), $(this)[0].files[0]);
				}else{
					fd.append($(this).attr('name'), $(this).val());
				}
				
				$.ajax({
					url: 'ajax/control.php',
					data: fd,
					processData: false,
					contentType: false,
					type: 'POST',
					success: function(data){
						kcdiv.find('.ajaxerror').html(data);
						if(data==''){
							kcdiv.attr('kcvalid','valid');
						}else{
							kcdiv.attr('kcvalid','invalid');
						}
						
					}
				});	
				
				enableValidIngestion();
			});
			loadSymbolOff();
	});
	
	//this fires off everytime editor is checked or unchecked
	//will validate if statement if there is something in the editor
	CKEDITOR.on('currentInstance', function(){
		loadSymbolOn();
        $('.kora_rText').each(function(){
            var kcdiv = $(this).parents('.kora_control').first();
            if(CKEDITOR.instances[$(this).attr('id')].getData()!=''){
                kcdiv.attr('kcvalid','valid');
            }else if($(this).parents('.kc_required').first()!=null){
                kcdiv.attr('kcvalid','valid');
            }else{
                kcdiv.attr('kcvalid','invalid');
            }
            enableValidIngestion();
        });
		loadSymbolOff();
	});
	
	//validates a MLC
	$(".ingestionForm" ).on( "click",'.kcmlc_curritems', function() {
		loadSymbolOn();
		var kcdiv = $(this).parents('.kora_control').first();
		KCMLC_Validate(kcdiv);
		if($(this).val()==null){
			if($(this).parents('.kc_required').first()!=null){
				kcdiv.attr('kcvalid','valid');
			}
			else{
				kcdiv.attr('kcvalid','invalid');
			}
		}
		enableValidIngestion();
		loadSymbolOff();
	});
	
	//validates a MTC
	$(".ingestionForm" ).on( "click",'.kcmtc_curritems', function() {
		loadSymbolOn();
		var kcdiv = $(this).parents('.kora_control').first();
		KCMTC_Validate(kcdiv);
		enableValidIngestion();
		loadSymbolOff();
	});
	
	//validates a MDC
	$(".ingestionForm" ).on( "change",'.kcmdc_curritems', function() {
		loadSymbolOn();
		var kcdiv = $(this).parents('.kora_control').first();
		KCMDC_Validate(kcdiv);
		if($(this).val()==null){
			if($(this).parents('.kc_required').first()!=null){
				kcdiv.attr('kcvalid','valid');
			}
			else{
				kcdiv.attr('kcvalid','invalid');
			}
		}
		enableValidIngestion();
		loadSymbolOff();
	});
	
	//validates a RAC
	$(".ingestionForm" ).on( "click",'.kcac_curritems', function() {
		loadSymbolOn();
		var kcdiv = $(this).parents('.kora_control').first();
		KCAC_Validate(kcdiv);
		if($(this).val()==null){
			if($(this).parents('.kc_required').first()!=null){
				kcdiv.attr('kcvalid','valid');
			}
			else{
				kcdiv.attr('kcvalid','invalid');
			}
		}
		enableValidIngestion();
		loadSymbolOff();
	});
	
	/////////////////////////////////END VALIDATION CHECKS////////////////////////////
	
	//Handles front end submission of an ingestion form
	$( ".ingestionForm" ).submit(function( event ) {
		loadSymbolOn();
		
		event.preventDefault();

        $('.kora_rText').each(function() {
            if (CKEDITOR.instances != null) {
                CKEDITOR.instances[$(this).attr('id')].updateElement();
            }
        });
		
		// WE NEED TO SELECT EVERY OPTION ADDED TO THESE LISTS BEFORE SUBMIT
		var multiselects = ['Date (Multi-Input)', 'Record Associator'];
		$(this).find('.kora_control').each(function() {
			if ($.inArray($(this).attr('kctype'), multiselects) > -1)
			{
				$(this).find('select option').each(function() {
					$(this).attr('selected', 'selected');
				});
			}
		});
		
		// MUST USE FORM-DATA OR IMAGE/FILE UPLOADS WILL BE SKIPPED WITH .SERIALIZE()
		var fd = new FormData(this);
		fd.append('action','RecordIngest');

		// MAYBE COULD USE INTERJECT A PROGRESS HANDLER HERE TOO?
		$.ajaxSetup({ async: true });
		$.ajax({
			url: 'ajax/control.php',
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				var splstr = data.split("%");
				var baseURI = $('#kora_globals').attr('baseURI');
				window.location.replace(baseURI+'viewObject.php?rid='+splstr[1]);
				
			}
		});
		$.ajaxSetup({ async: true });		
		
		// WE NEED TO HANDLE LEGACY SUBMITS TO THE LEGACY-SUBMIT FORM
	});

	$('.ingestionForm').each(function() { GetPage($(this), 1); });
	
	//.showHTML function for Public Ingestion
	(function($)
	{
	   $.fn.showHtml = function(html, speed, callback)
	   {
		  return this.each(function()
		  {
			 // The element to be modified
			 var el = $(this);

			 // Preserve the original values of width and height
			 var finish = {width: this.style.width, height: this.style.height};

			 // The original width and height represented as pixel values.
			 var cur = {width: el.width()+'px', height: el.height()+'px'};

			 // Modify the element's contents. Element will resize.
			 el.html(html);

			 // Capture the final dimensions of the element 
			 var next = {width: el.width()+'px', height: el.height()+'px'};

			 el .css(cur) // restore initial dimensions
            .animate(next, speed, function()  // animate to final dimensions
            {
               el.css(finish); // restore initial style settings
               if ( $.isFunction(callback) ) callback();
            });
		  });
	   };


	})(jQuery);
	
	//Public Ingestion Approval
	$('.public_ingest_approve').click( function(event) {
		loadSymbolOn();
		var rid = $(this).attr("rid");
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var data = 'rid='+rid+'&pid='+pid+'&sid='+sid+'&approved=1';
		$('div.record'+rid).block({ message: '<h1>'+kgt_pi_approving_data+'</h1>'});
		
		$.ajax({
			url: "ingestApprovedData.php",
			type: "GET",
			data: data,
			cache: false,
			success: function () {
				$('div.record').unblock();
				$("div.record"+rid).showHtml("Approved", 400);
				
				//Find a way to return results from ingestApprovedData.php
				//Shouldn't be a string "Approved" should be from .php
			}
		});
		
		
		//return false;
		event.preventDefault();
		loadSymbolOff();
	});
	
	//Public Ingestion Deny
	$('.public_ingest_deny').click( function(event) {
		loadSymbolOn();
		if (confirm(kgt_pi_confirm_denying_data)){
		var rid = $(this).attr("rid");
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var data = 'rid='+rid+'&pid='+pid+'&sid='+sid+'&approved=0';
			$('div.record'+rid).block({ message: '<h1>'+kgt_pi_denying_data+'</h1>'});
			
			$.ajax({
				url: "ingestApprovedData.php",
				type: "GET",
				data: data,
				cache: false,
				success: function () {
					$("div.record"+rid).showHtml("Denied", 400);
					
				}
			});
		}
		
		//return false;
		event.preventDefault();
		loadSymbolOff();
	});
	
	//Public Ingestion Deny ALL
	$('.public_ingest_deny_ALL').click( function(event) {
		loadSymbolOn();
		if (confirm(kgt_pi_confim_denying_all_data)){
		var rid = $(this).attr("rid");
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var data = 'rid=all'+'&pid='+pid+'&sid='+sid+'&approved=0';
			$.blockUI({ message: '<h1>'+kgt_pi_denying_all_data+'</h1>' });
			
			$.ajax({
				url: "ingestApprovedData.php",
				type: "GET",
				data: data,
				cache: false,
				success: function () {
					$.unblockUI();
					$("div.recordAll").showHtml("Denied All", 400);
										
				}
			});
		}
		
		event.preventDefault();
		loadSymbolOff();
	});
	
	$('.kora_export_zip').click( function() {
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var zipindex = $(this).attr('zipindex');
		
		console.log('here');

		$("#dlmsg").html('<strong>'+kgt_exportgeneratingfile+'</strong>');
		window.location = "schemeExportLanding.php?pid="+pid+"&sid="+sid+"&zip="+zipindex;
		loadSymbolOff();
	});
	
	$('.kr_delete_form').on('click','.kr_delete_yes',function(){
		loadSymbolOn();
		var pid = $('#kora_globals').attr('pid');
		var sid = $('#kora_globals').attr('sid');
		var rid = $('#kora_globals').attr('rid');
	    
	    $.ajaxSetup({ async: true });
		
		$.post(ajaxhandler, {action:'deleteRecord',rid:rid,pid:pid,sid:sid}, function(resp){$("#ajaxstatus").html(resp);window.location = 'deleteObject.php?pid='+pid+'&sid='+sid+'&deleted=true';}, 'html');
		$.ajaxSetup({ async: true });
		
		
	});
	
	$('.kr_delete_form').on('click','.kr_delete_no',function(){
		loadSymbolOn();
		var rid = $('#kora_globals').attr('rid');
		
		window.location = 'viewObject.php?rid='+rid;
	});
	
	$('.koraglobal_recordSearch_form').keypress(function(e) {
		loadSymbolOn();
	    if(e.which == 13) {
	        var rid = $('.koraglobal_recordSearch_rid').val();
	        window.location = $('#kora_globals').attr("baseuri")+'viewObject.php?rid='+rid;
	    }
	});
});



