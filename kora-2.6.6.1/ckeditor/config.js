/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';



	// these are the default toolbar configurations.

//	config.toolbar_Full =
//	[
//		['Source','-','Save','NewPage','Preview','-','Templates'],
//		['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
//		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
//		['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
//		'/',
//		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
//		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
//		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
//		['BidiLtr', 'BidiRtl'],
//		['Link','Unlink','Anchor'],
//		['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
//		'/',
//		['Styles','Format','Font','FontSize'],
//		['TextColor','BGColor'],
//		['Maximize', 'ShowBlocks','-','About']
//	];
//
//	config.toolbar_Basic =
//	[
//		['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink','-','About']
//	];

	config.toolbar = 'Partial';

	config.toolbar_Partial =
	[
		
		['Source','Maximize'],
		['Cut','Copy','Paste','PasteText','PasteFromWord'],
		['SelectAll','RemoveFormat','-','SpellChecker', 'Scayt','-','Undo','Redo'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['Link','Unlink','Anchor'],
		['Find','Replace']
	];

	// use <br> tags instead of <p> tags when pressing enter.
	config.enterMode = CKEDITOR.ENTER_BR;
	
};

