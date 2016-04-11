function addDublinCore(pid,sid) {
	if($('#controlmap').val()!=null){
		$.ajaxSetup({ async: false });
	    $.post('includes/dublinFunctions.php',{action:'addDublinCore',source:'DublinFunctions',dctype:$('#dcfield').val(),
	        cid:$('#controlmap').val(),pid:pid,sid:sid },function(resp){$(".dc_ajax").html(resp);}, 'html');
	    $.ajaxSetup({ async: true });
	}
    return;
}
function removeDublinCore(pid,sid,varcid,vardctype) {
    var answer = confirm("Remove DC field?  Records currently in KORA will require a recalculation of DC data.");
    if(answer) {
        alert("Run the Dublin Core Data Update function when you are done editing the controls associated with Dublin Core for this scheme");
        $.ajaxSetup({ async: false });
        $.post('includes/dublinFunctions.php',{ action:'removeDublinCore',source:'DublinFunctions',cid:varcid,
            dctype:vardctype,pid:pid,sid:sid },function(resp){$(".dc_ajax").html(resp);}, 'html');
        $.ajaxSetup({ async: true });
    }
    return;
}
$(function() {
	var pid = $('#kora_globals').attr('pid');
	var sid = $('#kora_globals').attr('sid');
	
	$.ajaxSetup({ async: false });
	$.post('includes/dublinFunctions.php',{action:'loadDublinCore',source:'DublinFunctions',pid:pid,sid:sid },function(resp){$(".dc_ajax").html(resp);}, 'html');
	$.ajaxSetup({ async: true });
	
	$('.dc_ajax').on('click','.mdc_add', function() {
		addDublinCore(pid,sid);
	});
	
	$('.dc_ajax').on('click','.mdc_rem', function() {
		var cid = $('.mdc_rem').attr('cid');
		var dctype = $('.mdc_rem').attr('dctype');
		
		removeDublinCore(pid,sid,cid,dctype);
	});
});