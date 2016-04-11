<?php 
// THIS FILE IS OF TYPE .PHP BECAUSE IT HAS TO MAKE PHP GETTEXT CALLS TO SET THESE
// VARS CORRECTLY, THEN THIS header FUNCTION IS CALLED TO PROPER IDENTIFY THE MIME-TYPE
// TO THE BROWSER
header('Content-Type: application/javascript; charset=UTF-8'); 

?>
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

/** Prefix kgt_ is for KORA GetText **/
var kgt_reallydelete   = '<?php echo gettext('Really delete user?');?>';
var kgt_pwdontmatch    = '<?php echo gettext('Passwords do not match.  Please Try Again.');?>';
var kgt_pwchanged      = '<?php echo gettext('Password has successfully been changed!');?>';
var kgt_reallydelproj  = '<?php echo gettext('Are you sure you want to delete the following project(s)? This takes effect immediately and cannot be undone.');?>';
var kgt_reallydelpuser = '<?php echo gettext('Really delete from project?');?>';
var kgt_reallydelpgrp  = '<?php echo gettext('Really delete group?');?>';
var kgt_reallydeltok   = '<?php echo gettext('Really delete token?');?>';
var kgt_reallydelscheme = '<?php echo gettext('Really delete scheme?');?>';
var kgt_reallydelcoll  = '<?php echo gettext('Really delete collection?  Any data and Dublin Core associations to controls in this collection will be lost, including any data pending approval.');?>';
var kgt_reallydelctrl  = '<?php echo gettext('Really delete control?  Any data and Dublin Core associations will be lost, including any data pending approval.');?>';
var kgt_reallydelfile  = '<?php echo gettext('Are you sure you want to delete this file?  This takes effect immediately and cannot be undone.'); ?>';
var kgt_reallydelschemeassoc = '<?php echo gettext('Are you sure you want to remove this permission?'); ?>';
var kgt_jan = '<?php echo gettext('January'); ?>';
var kgt_feb = '<?php echo gettext('February'); ?>';
var kgt_mar = '<?php echo gettext('March'); ?>';
var kgt_apr = '<?php echo gettext('April'); ?>';
var kgt_may = '<?php echo gettext('May'); ?>';
var kgt_jun = '<?php echo gettext('June'); ?>';
var kgt_jul = '<?php echo gettext('July'); ?>';
var kgt_aug = '<?php echo gettext('August'); ?>';
var kgt_sep = '<?php echo gettext('September'); ?>';
var kgt_oct = '<?php echo gettext('October'); ?>';
var kgt_nov = '<?php echo gettext('November'); ?>';
var kgt_dec = '<?php echo gettext('December'); ?>';
var kgt_baddateformat = '<?php echo gettext('Bad Date Format Option'); ?>';
var kgt_resetpasstooshort = '<?php echo gettext('Password must be at least 8 characters.'); ?>';
var kgt_resetpassnomatch = '<?php echo gettext('Passwords do not match.'); ?>';
var kgt_useregexpreset = '<?php echo gettext('Really select preset?  This will delete any existing RegEx and cannot be undone!')?>';
var kgt_changectlname = '<?php echo gettext('Are you sure you wish to change the name, this can affect API programming?')?>';
var kgt_pi_approving_data = '<?php echo gettext('Approving Data');?>';
var kgt_pi_confirm_denying_data = '<?php echo gettext('Are you sure you want to deny this record? The data will be deleted immediately and cannot be undone.');?>';
var kgt_pi_denying_data = '<?php echo gettext('Deleting Data'); ?>';
var kgt_pi_confim_denying_all_data = '<?php echo gettext('Are you sure you want to deny all records? All data will be deleted immediately and cannot be undone.'); ?>';
var kgt_pi_denying_all_data = '<?php echo gettext('Deleting All Records. Please Wait.'); ?>';
var kgt_exportgeneratingfile = '<?php echo gettext('Generating Zip File, Please Wait...');?>';
var kgt_schemeusepreset = '<?php echo gettext('Are you sure you want to load this preset?  It will overwrite currently entered data.');?>';


