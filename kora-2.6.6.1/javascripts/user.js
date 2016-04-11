function ReloadUserUpdatePage(response){
	var baseURI = $('#kora_globals').attr('baseURI');
	window.location.replace(baseURI+'accountSettings.php?submit="'+response+'"');
}

$(function() {
	$('.global_error').appendTo('#global_error');
	$('.login_form').find('input[type="text"]').focus();
	
	var ajaxhandler = 'ajax/user.php';
	
	//handles login
	$(".kora_login_form").on( "click",".kora_login_submit", function() {
		loadSymbolOn();
		var user = $('.kora_login_user').val();
		var pass = $('.kora_login_pass').val();
		
		var fd = new FormData();
		fd.append('action','userLogin');
		fd.append('username',user);
		fd.append('password',pass);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				if(data=="success")
					window.location="index.php";
				else{
					$('#global_error').text(data);
					$('#global_error').attr('style','color:red');
				}
				
			}
		});
		loadSymbolOff();
	});
	
	//handles login via enter key, when focused on password
	$('.kora_login_pass').keypress(function(e) {
		loadSymbolOn();
		if(e.which == 13) {
			var user = $('.kora_login_user').val();
			var pass = $('.kora_login_pass').val();
			
			var fd = new FormData();
			fd.append('action','userLogin');
			fd.append('username',user);
			fd.append('password',pass);
			
			
			$.ajax({
				url: ajaxhandler,
				data: fd,
				processData: false,
				contentType: false,
				type: 'POST',
				success: function(data){
					if(data=="success")
						window.location="index.php";
					else{
						$('#global_error').text(data);
						$('#global_error').attr('style','color:red');
					}
					
				}
			});
		}
		loadSymbolOff();
	});
	
	//handles going to new user page
	$(".kora_login_form").on( "click",".kora_login_new", function() {
		loadSymbolOn();
		window.location = "accountRegister.php";
	});
	
	//handles going to activation page
	$(".kora_login_form").on( "click",".kora_login_activate", function() {
		loadSymbolOn();
		window.location = "accountActivate.php";
	});
	
	//handles account activation submission
	$(".kora_activate_form").on( "click",".kora_activate_submit", function() {
		loadSymbolOn();
		var user = $('.kora_activate_user').val();
		var token = $('.kora_activate_token').val();
		
		var fd = new FormData();
		fd.append('action','activateAccount');
		fd.append('username',user);
		fd.append('token',token);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				$('#global_error').html(data);
				$('#global_error').css('color', 'red');
				
			}
		});
		loadSymbolOff();
	});
	
	//handles account registration submission
	$(".kora_reg_form").on( "click",".kora_reg_submit", function() {
		loadSymbolOn();
	
		var user = $('.kora_reg_user').val();
		var pw1 = $('.kora_reg_pw1').val();
		var pw2 = $('.kora_reg_pw2').val();
		var email = $('.kora_reg_email').val();
		var name = $('.kora_reg_name').val();
		var org = $('.kora_reg_org').val();
		var lang = $('.kora_reg_lang').val();
		var resp = $('#recaptcha_response_field').val(); 
		var chal = $('#recaptcha_challenge_field').val();
		
		var fd = new FormData();
		fd.append('action','registerAccount');
		fd.append('username',user);
		fd.append('password1',pw1);
		fd.append('password2',pw2);
		fd.append('email',email);
		fd.append('realname',name);
		fd.append('organization',org);
		fd.append('language',lang);
		fd.append('recaptcha_response_field',resp);
		fd.append('recaptcha_challenge_field',chal);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				$('#global_error').html(data);
				$('#global_error').css('color', 'red');
				
			}
		});
		loadSymbolOff();
	});
	
	//handles password recovery submission
	$(".kora_recoverPassword_form").on( "click",".kora_recoverPassword_submit", function() {
		loadSymbolOn();
		var user = $('.kora_recoverPassword_user').val();
		
		var fd = new FormData();
		fd.append('action','recoverPassword');
		fd.append('username',user);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				$('#global_error').text(data);
				$('#global_error').css('color', 'red');
				
			}
		});
		loadSymbolOff();
	});
	
	//handles username recovery submission
	$(".kora_recoverUser_form").on( "click",".kora_recoverUser_submit", function() {
		loadSymbolOn();
		var email = $('.kora_recoverUser_email').val();
		
		var fd = new FormData();
		fd.append('action','recoverUser');
		fd.append('email',email);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				$('#global_error').text(data);
				$('#global_error').css('color', 'red');
				
			}
		});
		loadSymbolOff();
	});
	
	//handles password reset submission
	$(".kora_resetPass_form").on( "click",".kora_resetPass_submit", function() {
		loadSymbolOn();
		var user = $('.kora_resetPass_user').val();
		var token = $('.kora_resetPass_token').val();
		var pw1 = $('.kora_resetPass_pw1').val();
		var pw2 = $('.kora_resetPass_pw2').val();
		
		var fd = new FormData();
		fd.append('action','resetPass');
		fd.append('username',user);
		fd.append('token',token);
		fd.append('password1',pw1);
		fd.append('password2',pw2);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				$('#global_error').html(data);
				$('#global_error').css('color', 'red');
				
			}
		});
		loadSymbolOff();
	});

	///this handles rename of a record preset
	$(".account_userUpdate").on( "click",".account_submitUpdate", function() {
		loadSymbolOn();
		var email = $('.account_email').val();
		var name = $('.account_realName').val();
		var org = $('.account_organization').val();
		var pw1 = $('.account_password1').val();
		var pw2 = $('.account_password2').val();
		var lang = $('.account_language').val();
		
		var fd = new FormData();
		fd.append('action','updateUserInfo');
		fd.append('email',email);
		fd.append('name',name);
		fd.append('org',org);
		fd.append('pw1',pw1);
		fd.append('pw2',pw2);
		fd.append('lang',lang);
		
		
		$.ajax({
			url: ajaxhandler,
			data: fd,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				ReloadUserUpdatePage(data);
				
			}
		});
	});
	
	$('.registration_form').find('input[name="username"]').change(function () {
		loadSymbolOn();
		$.post('ajax/admin.php',{ action:"checkUsername",source:'UserFunctions', uname:$('input#username').val() },function(resp){$("#unamecheck").html(resp);}, 'html');
		loadSymbolOff();
	});

	//Not sure if this is best place, but will do for now.
	$('.language').on( "change",'.kora_language_select', function() {
		loadSymbolOn();
		var lan = $(this).val();
		var currUrl = document.URL.split("?")[0];
		window.location.href = currUrl+'?lang='+lan;
	});
});