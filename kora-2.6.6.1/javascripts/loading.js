function loadSymbolOn(){
	$(".kora_loading_img").each(function(){
		$(this).attr("style", "border:0px");
	});
}

function loadSymbolOff(){
	$(".kora_loading_img").each(function(){
		$(this).attr("style", "border:0px;visibility:hidden");
	});
}
