<?php
/**
 * @version		$Id: dcfunctions.php 1.- 11/02/16 mouser@donationcoder.com $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2016 by mouser@donationcoder.com. All rights reserved.
 * @license		TBD
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */
 
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
// based on code from https://docs.joomla.org/J3.x:Creating_a_content_plugin



/*
	Display a list of child menu items on current page
	See https://dj-extensions.com/dj-selectmenu
	See https://stackoverflow.com/questions/4972811/getting-menu-parameters-from-joomla
	See https://forum.joomla.org/viewtopic.php?t=221117
	See good: https://stackoverflow.com/questions/30101065/joomla-how-to-display-sub-menu-items
	
	ATTN: TODO
	1. allow params to change options like which menu to show or how to format
	2. test image support
	3. use a custom html-compatible description for each menu item, INSTEAD of using meta description
		(see https://stackoverflow.com/questions/25832262/joomla-3-3-adding-custom-fields-to-all-menu-items-via-plugin-params-not-save
		http://docs.joomla.org/Adding_custom_fields_to_core_components_using_a_plugin)
	4. add option to show recursive child contents
	5. cache menus
	6. give proper credit to dj-selectmenu
	7. use a helper function to parse params rather than regex (see do_dcmenulist_build_parentitem)


	HOW TO USE:
	{dcmenulist} <-- show all submenu children of current page as a list
	{dcmenulist_desc} <-- show current page custom html description
	{dcmenulist_full} <-- same as {dcmenulist_desc} followed by {dcmenulist}
*/


class PlgContentDcmenulist extends JPlugin {
	
	public function onContentPrepare($context, &$article, &$params, $page = 0) {

		// Quick efficient check to see if we see our tag anywhere, if not we can stop right now.
		if (strpos($article->text, '{dcmenulist') === false) {
			return true;
		}
		
		// content is article text
		$content = $article->text;


		// note that because we are trying to grab this text from a wysiwyg
		$regex1 = '/{dcmenulist([^\s}]*)\s*([^}]*)}/s';

		// Don't run this plugin when the content is being indexed?
		if ($context == 'com_finder.indexer') {
			// in sample code you will see a simple "return true" here, but we don't want that we want to CLEAR the {dcf}...{/dcf} content so it doesn't get indexed.
			$content = preg_replace($regex1, '', $content);
			$article->text = $content;
			// now return with no returnval to say we changed it
			return;
		}

		// do replacements
		$content = preg_replace_callback($regex1, 'preg_callback_dcmenulist_rep', $content);
	
	// replace output
	$article->text = $content;
	
	// return void if no error
	}





	// to add custom menuitem field for html description
	// see form directory and https://docs.joomla.org/Editor_form_field_type
    function onContentPrepareForm($form, $data) {

        $app = JFactory::getApplication();
        $option = $app->input->get('option');
        $view = $app->input->get('view');

				// corrected to handle menumanager ck stuff
        switch($option) {

                case 'com_menus':
                case 'com_menumanagerck':
                {
                    if ($app->isAdmin() && ($view == 'item' || $view == 'itemedition')) {
                    //if ($app->isAdmin()) {
                            JForm::addFormPath(__DIR__ . '/forms');
                            $form->loadFile('menuitem_form_extradescription', false);
                    }
                    return true;
                }

        }
        return true;

    }  




}



function preg_callback_dcmenulist_rep(array $matches) {
	$param_type = $matches[1];
	$param_options = $matches[2];
	//Echo 'params: '; print_r($matches); echo '<br/>';
	//
	require_once('dcmenulist_helpers.php');
	//
	$parentitem = null;
	if ($param_type=='_desc') {
		do_dcmenulist_build_parentitem($param_options,$parentitem);
		$repstr = do_dcmenulist_build_html_parentdescription($param_options, $parentitem);
	}
	else if ($param_type=='_full') {
		$listitems = do_dcmenulist_build_listitems($param_options, $parentitem);
		$repstr = do_dcmenulist_build_html($param_options, $parentitem, $listitems, true, 'full');
	}
	else if ($param_type=='_brief') {
		$listitems = do_dcmenulist_build_listitems($param_options, $parentitem);
		$repstr = do_dcmenulist_build_html($param_options, $parentitem, $listitems, false, 'brief');
	}
	else if ($param_type=='') {
		$listitems = do_dcmenulist_build_listitems($param_options, $parentitem);
		$repstr = do_dcmenulist_build_html($param_options, $parentitem, $listitems, false, 'full');
	}
	else {
		$repstr = 'Dcmenulist plugin text not understood: ' . $param_type . '<br/>';
	}
	//
	return $repstr;
}
	

