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

// THE CURRENT PAGE BEING SHOWN, GLOBAL
var ks_currpage = 0;
var ks_projacts = { };

$(function() {
		KS_InitSearchResults();
		
		//Advanced Search funcitons//
		
		//'In order' is disabled until 'sort results by' is selected
		$('#advSearch_table_keyword').on("change",".advSearch_sortByKeyword", function(){
			loadSymbolOn();
			$('.sortOrder_keyword').removeAttr("disabled");
			loadSymbolOff();
		});
		$('#advSearch_table_other').on("change",".advSearch_sortBy", function(){
			loadSymbolOn();
			$('.sortOrder_other').removeAttr("disabled");
			loadSymbolOff();
		});
		
		/*View all pages without pagination
		$('#content_container').on('click', '.ks_view_all_pages', function(){
			loadSymbolOn();
			viewAllPages();
			loadSymbolOff();
		}); */
		
		//Submission of keyword search
		$('#advSearch_table_keyword').on("click",".ks_asKs_submit", function(){
			loadSymbolOn();
			var fd = new FormData();
			
			fd.append('pid',$('.ks_asKs_pid').val());
			fd.append('sid',$('.ks_asKs_sid').val());
			fd.append('keywords',$('.ks_asKs_keywords').val());
			fd.append('boolean',$('.ks_asKs_boolean').val());
			fd.append('sortBy',$('.advSearch_sortByKeyword').val());
			fd.append('sortOrder',$('.sortOrder_keyword').val());
			
			$.ajaxSetup({ async: true });
			$.ajax({
				url: 'searchResults.php',
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data){
					document.write(data);
					document.close();
					KS_InitSearchResults();
					loadSymbolOff();
				}
			});	
			$.ajaxSetup({ async: true });
		});
		
		//Submission of advanced search
		$('#advSearch_table_other').on("click",".ks_as_submit", function(){
			loadSymbolOn();
			var fd = new FormData();
			var keywords = '';
			
			fd.append('advSearch',true);
			fd.append('boolean','AND');
			fd.append('pid',$('.ks_as_pid').val());
			fd.append('sid',$('.ks_as_sid').val());
			//GATHER THE CONTROLS
			$('.kora_control').each(function(index){
				var cid = $(this).attr('kcid');
				//DateControl
				if($(this).attr('kcclass')=='DateControl'){
					var mon = $(this).children(".kcdc_month:first").val();
					var day = $(this).children(".kcdc_day:first").val();
					var year = $(this).children(".kcdc_year:first").val();
					if(mon!='' | day!='' | year!='')
						keywords += KS_ASKeyConversion_DC(mon,day,year,cid);
				}
				//MultiDateControl
				else if($(this).attr('kcclass')=='MultiDateControl'){
					var dates = [];
					$(this).find(".kcmdc_curritems:first option:selected").each(function() {
						dates.push($(this).val());
					});
					if(dates.length>0){
						keywords += KS_ASKeyConversion_MDC(dates,cid);
					}
				} 
				//ListControl
				else if($(this).attr('kcclass')=='ListControl'){
					var key = $(this).children("select:first").val();
					if(key.length>1)
						keywords += KS_ASKeyConversion(key,cid);
				} 
				//MultiListControl
				else if($(this).attr('kcclass')=='MultiListControl'){
					var values = [];
					$(this).find(".kcmlc_curritems:first option:selected").each(function() {
						values.push($(this).val());
					});
					if(values.length>0){
						keywords += KS_ASKeyConversion_MLC(values,cid);
					}
				} 
				//TextControl
				else if($(this).attr('kcclass')=='TextControl'){
					var key = $(this).children("input:first").val();
					if(key.length>0)
						keywords += KS_ASKeyConversion(key,cid);
				} 
				//MultiTextControl
				else if($(this).attr('kcclass')=='MultiTextControl'){
					var texts = [];
					$(this).find(".kcmtc_curritems:first option:selected").each(function() {
						texts.push($(this).val());
					});
					if(texts.length>0){
						keywords += KS_ASKeyConversion_MTC(texts,cid);
					}
				}
			});
			fd.append('keywords',keywords);
			fd.append('sortBy',$('.advSearch_sortBy').val());
			fd.append('sortOrder',$('.sortOrder_other').val());
			
			$.ajaxSetup({ async: true });
			$.ajax({
				url: 'searchResults.php',
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data){
					document.write(data);
					document.close();
					KS_InitSearchResults();
					loadSymbolOff();
				}
			});
			
			$.ajaxSetup({ async: true });
		});
		
		//Submission of cross project search
		$('.ks_cps_table').on("click",".ks_cps_submit", function(){
			loadSymbolOn();
			var fd = new FormData();
			
			var pids = []; 
			$('.ks_cps_projects :selected').each(function() {
				pids.push(this.value);
			});
			fd.append('keywords',$('.ks_cps_keywords').val());
			fd.append('boolean',$('.ks_cps_boolean').val());
			fd.append('projects',pids);
			
			$.ajaxSetup({ async: true });
			$.ajax({
				url: 'searchResults.php',
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data){
					document.write(data);
					document.close();
					KS_InitSearchResults();
					loadSymbolOff();
				}
			});	
			
			$.ajaxSetup({ async: true });
		});
		
		//disables submit if no projects selected in cross project search
		$('.ks_cps_table').on("change",".ks_cps_projects", function(){
			loadSymbolOn();
			var projs = $('.ks_cps_projects').val();
			if(projs==null){
				$('.ks_cps_submit').attr("disabled", "disabled");
			}else{
				$('.ks_cps_submit').removeAttr("disabled");
			}
			loadSymbolOff();
		});
		
		//Submission of project search
		$('.ks_ps_table').on("click",".ks_ps_submit", function(){
			loadSymbolOn();
			var fd = new FormData();
			
			fd.append('pid',$('.ks_ps_pid').val());
			fd.append('sid',$('.ks_ps_sid').val());
			fd.append('keywords',$('.ks_ps_keywords').val());
			fd.append('boolean',$('.ks_ps_boolean').val());
			
			$.ajaxSetup({ async: true });
			$.ajax({
				url: 'searchResults.php',
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data){
					document.write(data);
					document.close();
					KS_InitSearchResults();
					loadSymbolOff();
				}
			});	
			$.ajaxSetup({ async: true });
			
		});
});

//Preps front end for search results display
function KS_ShowPage(ksdiv, page)
{
	ksdiv.find('.ks_results_page').each(function() {
		// HIDE ALL PAGES NOT EQUAL TO TARGET PAGE
		if ($(this).attr('page') != page)
		{ $(this).hide();	}
		else
		{
			// NOW LOAD IF NECESSARY THE TARGET PAGE RECORDS
			$(this).find('.ks_result_item').each(function() {
				if ($(this).attr('loaded') != 'true')
				{
					var c = $(this);
					var pid = $(this).attr('pid');
					var sid = $(this).attr('sid');
					var rid = $(this).attr('rid');
					console.log('Loading: '+$(this).attr('rid'));
					$.ajaxSetup({ async: false });
					
					$.post($('#kora_globals').attr('baseuri')+"ajax/record.php",{
						action:"viewRecord",
						source:"RecordFunctions",
						pid:pid,
						sid:sid,
						rid:rid},
						function(resp){c.html(resp); c.attr('loaded', 'true'); }, 'html');
					KS_PrintRecordActions(c,pid,sid,rid);
					$.ajaxSetup({ async: true });
				}
			});
			
			// NOW SHOW THE TARGET PAGE
			$(this).show();
		}
		
		ks_currpage = page;
	});
	
	// NOW HANDLE THE NAV LINKS, THIS COULD BE DONE THROUGH JS, BUT A PHP FUNC EXISTS SO AJAX IT...
	var maxpage = ksdiv.find('.ks_results_page').size();
	var adjacentpage = ksdiv.attr('navlinkadj');
	$.ajaxSetup({ async: false });
	
	$.post($('#kora_globals').attr('baseuri')+"ajax/search.php",{
		action:"GetSearchNavLinks",
		source:"SearchFunctions",
		maxpage:maxpage,
		adjacentpage:adjacentpage,
		currpage:page},
		function(resp){ksdiv.find('.ks_results_navlinks').each(function(){$(this).html(resp);});
		}, 'html');
	$.ajaxSetup({ async: true });
}

// WE DO IT LIKE THIS USING THIS FUNCTION AND GLOBAL VARS SO WE DON'T AJAX THE
// SAME PID/SID PERMISSIONS COMBO FOR ALL (THOUSANDS+) RETURN RESULTS FOR A PROJ
function KS_PrintRecordActions(ksobjdiv,pid,sid,rid)
{
	var retval = '';
	if ((ks_projacts[String(pid)]) && (ks_projacts[String(pid)][String(sid)]))
	{
		// NOTHING TO DO
	}
	else
	{ 
		ascheme = { };
		
		$.post($('#kora_globals').attr('baseuri')+"ajax/scheme.php",{action:"GetRecordActions",source:"SchemeFunctions",pid:pid,sid:sid},function(resp){ ascheme[String(sid)] = resp; }, 'html');
		ks_projacts[String(pid)] = ascheme; 
	}
	
	retval = ks_projacts[String(pid)][String(sid)];
	retval = retval.replace(/%s/g,rid);
	ksobjdiv.find('table > tbody').first().prepend("<tr><td colspan='2'>"+retval+"</td></tr>");
	
}

// THIS FUCTION IS TRIGGERED ELSEWHERE BEYOND THE ONLOAD ABOVE, SO MOVED HERE FOR UTILITY
function KS_InitSearchResults()
{
	//************************************************************************
	//                     SEARCH RESULTS HANDLERS
	//************************************************************************
	if ($('.ks_results').length > 0) {
		// TODO: SET THIS TO A SPECIFICALLY REQUESTED PAGE?  RELATED TO TODO 10 LINES DOWN
		ks_currpage = 1;
		
		// HANDLERS FOR THE BREADCRUMB LINKS
		$(".ks_results" ).on( "click",'.ks_results_nav', function() {
			
			var c = $(this);
			var tarpage = parseInt(c.html());
			var tardiv = c.parents('.ks_results').first();
			if (isNaN(tarpage) == true){
				if (c.context['innerText'] == 'Prev'){tarpage = (ks_currpage-1);} //Go to Previous page
				else{tarpage = (ks_currpage+1);}                                  //Go to next page
			}
			KS_ShowPage(tardiv, tarpage);
		});
		
		// FILL THE NUM RESULTS DIV
		var numres = $('.ks_results').find('.ks_result_item').length;
		$('.ks_results_numresults').html(String(numres) + ' results found');
		
		// LOAD THE FIRST PAGE (TODO: OR REQUESTED PAGE?)
		KS_ShowPage($('.ks_results').first(), ks_currpage);
	}	
}

//Functions to handle converting keyword to proper MySQL calls for advanced search
function KS_ASKeyConversion_DC(month,day,year,cid){
	var dateKey = '';
	if(month!=''){dateKey += '%<month>'+month+'</month>%';}
	if(day!=''){dateKey += '%<day>'+day+'</day>%';}
	if(year!=''){dateKey += '%<year>'+year+'</year>%';}
	return "(cid="+cid+" AND value LIKE '"+'%'+dateKey+'%'+"')<ADVSEARCHKEY>";
}

function KS_ASKeyConversion_MDC(dates,cid){
	var mdcKey = '%';
	for(var i=0;i<dates.length;i++){
		mdcKey += (dates[i]+'%');
	}
	return "(cid="+cid+" AND value LIKE '"+'%'+mdcKey+'%'+"')<ADVSEARCHKEY>";
}

function KS_ASKeyConversion_MLC(values,cid){
	var mlcKey = '%';
	var mlcEncodedKey = '%';
	for(var i=0;i<values.length;i++){
		var fd = new FormData();
		fd.append('value', values[i]);
		fd.append('action', 'encodeValue');
		fd.append('source', 'SearchFunctions');
		$.ajax({
			url: 'ajax/search.php',
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(result){
				mlcEncodedKey += ('<value>'+result+'</value>%');
			}
		});	
		mlcKey += ('<value>'+values[i]+'</value>%');
	}
	return "(cid="+cid+" AND (value LIKE '"+mlcKey+"' OR value LIKE '"+mlcEncodedKey+"'))<ADVSEARCHKEY>";
}

function KS_ASKeyConversion_MTC(texts,cid){
	var mtcKey = '%';
	var mtcEncodedKey = '%';
	for(var i=0;i<texts.length;i++){
		var fd = new FormData();
		fd.append('value', values[i]);
		fd.append('action', 'encodeValue');
		fd.append('source', 'SearchFunctions');
		$.ajax({
			url: 'ajax/search.php',
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(result){
				mtcEncodedKey += ('<value>'+result+'</value>%');
			}
		});	
		mtcKey += ('<text>'+texts[i]+'</text>%');
	}
	return "(cid="+cid+" AND (value LIKE '"+mtcKey+"' OR value LIKE '"+mtcEncodedKey+"'))<ADVSEARCHKEY>";
}

function KS_ASKeyConversion(keyword,cid){
	return '(cid='+cid+' AND value LIKE"'+"%"+keyword+"%"+'")<ADVSEARCHKEY>';
}

function viewAllPages(){
	loadSymbolOn();
	$(".ks_results").find('.ks_results_page').each(function() {
		
		$(this).attr('style', '');
		// NOW LOAD IF NECESSARY THE TARGET PAGE RECORDS
		$(this).find('.ks_result_item').each(function() {
			if ($(this).attr('loaded') != 'true')
			{
				var c = $(this);
				var pid = $(this).attr('pid');
				var sid = $(this).attr('sid');
				var rid = $(this).attr('rid');
				$.ajaxSetup({ async: true });
				
				$.post($('#kora_globals').attr('baseuri')+"ajax/record.php",{
					action:"viewRecord",
					source:"RecordFunctions",
					pid:pid,
					sid:sid,
					rid:rid},
					function(resp){ c.html(resp); c.attr('loaded', 'true'); KS_PrintRecordActions(c,pid,sid,rid);}, 'html');
				
				$.ajaxSetup({ async: true });
			}
		});
		
		// NOW SHOW THE TARGET PAGE
		$(this).show();
		
	});
	$(".ks_results_navlinks").each(function () { $(this).hide(); });
	$(".ks_view_all_pages").each(function () { $(this).hide(); });
	loadSymbolOff();
}