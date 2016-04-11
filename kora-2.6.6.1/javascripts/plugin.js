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
$('.plugin_submit').click(function(){
	
	var id = this.id;
	id = id.toString();
	
	var fd = new FormData();
	fd.append('action', 'activation');
	fd.append('id', id);

	var baseURI = $('#kora_globals').attr('baseURI');
	
	$.ajax({
		url: baseURI + 'ajax/plugin.php',
		data: fd,
		processData: false,
		contentType: false,
		type: 'POST',
		dataType: 'json',
		success: function(resp) { 
			var i='#'+resp;
			location.reload();
		},
		error: function(resp) {
			var i='#'+resp;
			location.reload();
		}
	});
	
	return true;
});

//When hitting the edit button, need an ajax call to call upon the colorbox and the stuff inside
//Then I think I need another ajax call (seperate from the edit button) to handle the edit schema.

//Code for the description colorbox
$(function() {
	$('.table').on('click', '.edit_plugin', function(){

		//Get the id from the table
		var id = this.id;
		id = id.toString();
		
		//Colorbox set up
		$.ajaxSetup({ async: false });
		$.colorbox({href:'ajax/plugin.php',data:{action:'Editplugin',id: id}});
		$.ajaxSetup({ async: true });

		//Once you hit the Edit Plugin button
		$("#project_editPlugin_form").on('click', '.project_editPlugin_submit', function() {

			//Crete a new formdata.
			var fd = new FormData();
			var check_error = 0;
			fd.append('action', 'submitDescription');

			//Get id and description
			var id = this.id;
			id = id.toString();
			var description = $('.project_editPlugin_desc').val();

			fd.append('id', id);
			fd.append('description', description);

			console.log(fd);
			
			$.ajax({
				url: 'ajax/plugin.php',
				data: fd,
				processData: false,
				contentType: false,
				async: false,
				type: 'POST',
				success: function(resp) { 
					if (resp == ""){ check_error = 1; }
					$("#cbox_error").append(resp);
					$.colorbox.resize();
				}
			});
			if ( check_error == 1){ $.colorbox.close(); } //If no error, close cbox
			location.reload();
		});

	});

});

