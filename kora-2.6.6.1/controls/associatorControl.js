
$(function() {
		var ajaxhandler = 'ajax/control.php';
		
		//************************************************************************
		//                     INGESTION FORM HANDLERS
		//************************************************************************
		if ($('.ingestionForm').length > 0) {
			$(".ingestionForm" ).find('.kora_control').each(function() {
					var kcdiv = $(this);
					if ($(this).attr('kctype') == 'Record Associator')
					{
						$(this).find('.kcac_removeitem').click(function() {
								kcdiv.find('.kcac_curritems').find('option:selected').each(function() {
										$(this).remove();
								});
						});
						
						$(this).find('.kcac_assrec:button').click(function() {
								var kid =  kcdiv.find('.kcac_assrec:text').val();
								kcdiv.find('.kcac_curritems').append('<option>'+kid+'</option>');
						});
						
						$(this).find('.kcac_moveitemup').click(function() {
								kcdiv.find('.kcac_curritems > option:selected').each(function() {
										$(this).insertBefore($(this).prev());
								});
						});
						
						$(this).find('.kcac_moveitemdown').click(function() {
								kcdiv.find('.kcac_curritems > option:selected').each(function() {
										$(this).insertAfter($(this).next());
								});
						});
						
						$(this).find('.kcac_viewitem').click(function() {
								kcdiv.find('.kcac_curritems > option:selected').each(function() {
										console.log('add: ' + $(this).val());
										$.colorbox({href:'ajax/record.php?source=AssociatorControl&action=viewRecord&showall=true&rid='+$(this).val()});
								});
						});
						//Base URI used for compatibility with public ingestion
						$(this).find('.kcac_findrec:button').click(function() {
								$.ajaxSetup({ async: false });
								$.colorbox({href:$('#kora_globals').attr('baseuri')+'ajax/control.php?source=AssociatorControl&action=assocSearch&pid='+kcdiv.attr('kpid')+'&sid='+kcdiv.attr('ksid')+'&cid='+kcdiv.attr('kcid')+'&keywords='+escape(kcdiv.find('.kcac_findrec:text').val()),onComplete: function(){$(this).colorbox.resize({width:"50%"});}});
								$.ajaxSetup({ async: true });
								KS_InitSearchResults();
						});
						
						$(this).find('.kcac_addnew').click(function(e) {
								e.preventDefault();
								$.colorbox({href:'ajax/control.php?action=assocIngest&pid='+kcdiv.attr('kpid')+'&sid='+kcdiv.attr('ksid')});
						});
						
					}
			});
		}
		
		// THESE WILL POP UP IN COLORBOX OVERLAY NOT IN INGESTION FORM
		$("#colorbox" ).on( "click",'.assoc_search_link', function() {
				// SMALL POTENTIAL FOR BUG HERE IF SHOWING MULTIPLE INGESTION FORMS FOR MULTIPLE PROJECTS WITH OVERLAPPING CID NUMBERS BOTH OF
				// OF WHICH WOULD HAVE TO BE ASSOCIATOR CONTROLS....
				var cid = $("#colorbox .assoc_search_results" ).first().attr('kcid');
				var kcdiv = $(".ingestionForm" ).find('.kora_control[kcid='+cid+']');
				kcdiv.find('.kcac_assrec:text').val($(this).attr('krid'));
				kcdiv.find('.kcac_curritems').append('<option>'+kcdiv.find('.kcac_assrec:text').val()+'</option>');
				$.colorbox.close();
		});
		
		$("#colorbox" ).on( "click",'.assoc_search_navlinks a', function(e) {
				e.preventDefault();
				console.log($(this).attr('href'));
				$.colorbox({open:true,href:$(this).attr('href')});
		});		
		
		// LOAD A TARGET ASS RECORD IF THE USER CLICKS TO VIEW
		$("#colorbox").on( "click",'.kcac_assview', function() {
			var recinfo = $(this).parent('.kcac_assresult_item');
			if (recinfo.attr('loaded') != 'true')
			{
				var pid = recinfo.attr('pid');
				var sid = recinfo.attr('sid');
				var rid = recinfo.attr('rid');
				console.log('Loading: '+rid);
				$.ajaxSetup({ async: false });
				
				$.post("ajax/record.php",{action:"viewRecord",source:"RecordFunctions",pid:pid,sid:sid,rid:rid},function(resp){recinfo.append(resp); recinfo.attr('loaded', 'true'); }, 'html');
				$.ajaxSetup({ async: true });
			}
			// IF WE'RE ALREADY LOADED, FUTURE CLICKS WILL TOGGLE THE VIEW OF THIS TABLE IN/OUT
			else if (recinfo.find('.kr_view').first().is(':visible'))
				{ recinfo.find('.kr_view').first().hide(); }
			else
				{ recinfo.find('.kr_view').first().show(); }
			
			$.colorbox.resize();
		});
		
		// A USER HAS CLICKED ON AN 'ASSOCIATE THIS RECORD' LINK
		$("#colorbox").on( "click",'.kcac_assthis', function() {
			var asskid = $(this).attr('kcac_assval');
			var ctrlcid = $(this).parents('.assoc_search_results').first().attr('kcid');
			
			// NOW WE SEARCH THE ENTIRE DOC FOR THE ORIGINAL CONTROL WHICH GENERATED THIS SEARCH
			// SMALL BUG POTENTIAL HERE IF SHOWING MULTIPLE INGEST FORMS ON A SINGLE PAGE SOMEHOW...
			// KORA CURRENTLY NOT CODED TO REALLY HANDLE MULTIPLE INPUT FORMS FROM DIFF PROJS ON SAME PAGE ANYWAY
			$.colorbox.close();
			$('.ctrlEdit .kora_control[kcid="'+ctrlcid+'"] .kcac_curritems').append('<option>'+asskid+'</option>');
			
		});
		
		


		//************************************************************************
		//                     OPTION FORM HANDLERS
		//************************************************************************
		$("#colorbox").on( "click",".kcacopts_defassrec:button", function() {
				var val = $('#colorbox .kcacopts_defassrec:text').val();
				$('#colorbox .kcacopts_defcurritems').append('<option>'+val+'</option>');
				KCAC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcacopts_defremoveitem", function() {
				$('#colorbox .kcacopts_defcurritems > option:selected').each(function() {
						$(this).remove();
				});
				KCAC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcacopts_defmoveitemup", function() {
				$('#colorbox .kcacopts_defcurritems > option:selected').each(function() {
						$(this).insertBefore($(this).prev());
				});
				KCAC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcacopts_defmoveitemdown", function() {
				$('#colorbox .kcacopts_defcurritems > option:selected').each(function() {
						$(this).insertAfter($(this).next());
				});
				KCAC_SaveDefaultValue();
		});
		
		$("#colorbox").on( "click",".kcacopts_cpallowed", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var schemes = [];
				$('#colorbox .kcacopts_cpallowed:checked').each(function () {
						schemes.push($(this).attr('sid'));
				});
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updateAllowedSchemes',source:'AssociatorControl',pid:pid,sid:sid,cid:cid,schemes:schemes}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });						
		});
		
		$("#colorbox").on( "change",".kcacopts_cppreview", function() {
				var pid = $('#colorbox .kora_control_opts').attr('pid');
				var sid = $('#kora_globals').attr('sid');
				var cid = $('#colorbox .kora_control_opts').attr('cid');
				var prevcid = $(this).attr('sid');
				var prevval = $(this).val();
				$.ajaxSetup({ async: false });
				
				$.post(ajaxhandler, {action:'updatePreviewControl',source:'AssociatorControl',pid:pid,sid:sid,cid:cid,prevcid:prevcid,prevval:prevval}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
				PrintControlOpts(pid,sid,cid);
				$.ajaxSetup({ async: true });						
		});
		
});

//Saves the default value for a RAC
function KCAC_SaveDefaultValue()
{
	console.log('start KCAC_SaveDefaultValue');
	var ajaxhandler = 'ajax/control.php';
	
	var pid = $('#colorbox .kora_control_opts').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	var cid = $('#colorbox .kora_control_opts').attr('cid');
	var defvals = [];
	$('#colorbox .kcacopts_defcurritems option').each(function(i) {
			defvals.push(this.value);
			console.log('adding: '+this.value);
	});
	
	$.ajaxSetup({ async: false });
	
	$.post(ajaxhandler, {action:'saveDefault',source:'AssociatorControl',pid:pid,sid:sid,cid:cid,values:defvals}, function(resp){$("#ajaxstatus").html(resp);}, 'html');
	PrintControlOpts(pid,sid,cid);
	$.ajaxSetup({ async: true });
	console.log('end KCAC_SaveDefaultValue');
}

//Performs a speacial validation method on RAC
function KCAC_Validate(kcdiv)
{
	var datadom = kcdiv.find('.kcac_curritems').first();
	
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
				///For RAC, maybe we do validation elsewhere
				if(data==''){
					kcdiv.attr('kcvalid','valid');
				}else{
					kcdiv.attr('kcvalid','invalid');
				}
				
			}
	});		
	
}