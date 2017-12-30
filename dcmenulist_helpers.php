<?php


// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


//---------------------------------------------------------------------------
function do_dcmenulist_build_parentitem($param_options, &$parentitem) {

	$menu = JFactory::getApplication()->getMenu();

	// get specific menuid? using regex
	$matches = array();
	if (preg_match('/menuid\s*=\s*([1-9]+)/',$param_options, $matches)) {
		$menuid = intval($matches[1]);
		$parentitem = $menu->getItem($menuid);
	}
	else {
		// which menu item is active?
		// ATTN: we might want to let them override with a param later
		$parentitem = $menu->getActive();
	}
	dcmenulist_fillItemDat($parentitem);
}



function do_dcmenulist_build_html_parentdescription($params, $parentitem) {
	$rethtml = '';
	if (!empty($parentitem->description)) {
		$rethtml .= '<div class="dcmenulistheader">' . "\n";
		$rethtml .= $parentitem->description;
		$rethtml .= '</div>' . "\n";
	}
	return $rethtml;
}








function do_dcmenulist_build_listitems($params, &$parentitem) {
	// build list of menu items under current page
	do_dcmenulist_build_parentitem($params, $parentitem);
	// get children menu items
	$menu = JFactory::getApplication()->getMenu();
	$childs = $menu->getItems('parent_id', $parentitem->id);
	// return them
	return $childs;
}




function do_dcmenulist_build_html($params, $parentitem, $listitems, $flag_showtopdesc, $option_displaymode) {
	// generate html to display list

	if (empty($listitems))
		return '';

	$rethtml = '';	
	// header
	if ($flag_showtopdesc) {
		$rethtml .= do_dcmenulist_build_html_parentdescription($params, $parentitem);
	}

	// first process entire list, and detect whether there are any images
	$flag_hasimages = false;
	foreach ($listitems as $item) {
		dcmenulist_fillItemDat($item);
		if (!empty($item->menu_image)) {
			$flag_hasimages = true;
		}
	}

	$ulclass = 'dcmenulist';
	if ($flag_hasimages) {
		$ulclass .= ' dcmenulist_wimages';
	}


	// brief mode stuff
	if ($option_displaymode=='brief') {
		$ulclass .= ' dcmenulist_brief';
		// cleanup titles
		dcmenulist_briefifytitles($listitems);
	}

	$rethtml .= '<ul class="' . $ulclass . '">' . "\n";



	
	// now walk and display
	foreach ($listitems as $item) {
		if (!empty($item->flink)) {
			if (!empty($item->menu_image)) {
				$rethtml .= '<li class="dcmenulist_image">';
			} else {
				$rethtml .= '<li>';
			}

			$rethtml .= '<a href="' . $item->flink . '">';
			if (!empty($item->menu_image)) {
				if ($option_displaymode=='brief') {
					$rethtml .= '<img src="' . $item->menu_image . '" width="16" alt="bullet"/>';
					} else {
					$rethtml .= '<img src="' . $item->menu_image . '" alt="link thumbnail"/>';
					}
			}
			//
			$rethtml .= '<span class="dcmenulisti_title">' . $item->title . '</span>' . '</a>';
			if ($option_displaymode!='brief')
				{				
				if (!empty($item->description)) {
					if (strpos($item->description,"\n")!==false) {
						//$rethtml .= '<br/>';
					} else {
						//$rethtml .= ' - ';
					}
					$rethtml .= '<div class="dcmenulisti_body">' . $item->description . '</div>';
				}
			}
			$rethtml .= '</li>' . "\n";
		}
	}
	
	// end
	$rethtml .= '</ul>' . "\n";
	
	return $rethtml;
}
//---------------------------------------------------------------------------



//---------------------------------------------------------------------------
function dcmenulist_briefifytitles(&$listitems) {
	$allpos = -1;
	$allprefixstr = '';
	foreach ($listitems as $item) {
		$pos = strpos($item->title,':');
		if ($pos === false) {
			return;
		}
		if ($allpos === -1) {
			$allpos = $pos;
			$allprefixstr = substr($item->title, 0,$pos);
		}
		else {
			if ($pos!=$allpos || substr($item->title, 0,$pos)!=$allprefixstr) {
				return;
			}
		}
		// loop continues
	}
	// ok if we dropped down here they all have the same "SOMETHING:" prefix, so we remove it
	foreach ($listitems as $item) {
		$item->title = trim(substr($item->title,$allpos+1));
	}
}
//---------------------------------------------------------------------------


//---------------------------------------------------------------------------
function dcmenulist_fillItemDat(&$item) {
// from mod_djselectmenu

	$item->active = false;
	$item->flink = $item->link;
	
	// based on code in joomla/modules/mod_menu/helper.php

	// Reverted back for CMS version 2.5.6
	switch ($item->type)
	{
		case 'separator':
			// No further action needed.
			continue;

		case 'url':
			if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
				// If this is an internal Joomla link, ensure the Itemid is set.
				$item->flink = $item->link.'&Itemid='.$item->id;
			}
			break;

		case 'alias':
			// If this is an alias use the item id stored in the parameters to make the link.
			$item->flink = 'index.php?Itemid='.$item->params->get('aliasoptions');
			break;

		default:
			$router = JSite::getRouter();
			if ($router->getMode() == JROUTER_MODE_SEF) {
				$item->flink = 'index.php?Itemid='.$item->id;
			}
			else {
				$item->flink .= '&Itemid='.$item->id;
			}
			break;
	}

	if (strcasecmp(substr($item->flink, 0, 4), 'http') && (strpos($item->flink, 'index.php?') !== false)) {
		$item->flink = JRoute::_($item->flink, true, $item->params->get('secure'));
	}
	else {
		$item->flink = JRoute::_($item->flink);
	}

	$item->title = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
	//$item->anchor_css   = htmlspecialchars($item->params->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
	//$item->anchor_title = htmlspecialchars($item->params->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);


	// image
	// first try our custom dcmenulist image
	$image = $item->params->get('dcml_image', '');
	if (empty($image)) {
		// fall back on menu item image
		$image = $item->params->get('menu_image', '');
	}
	$item->menu_image = $image;
	//$item->menu_image = $item->params->get('menu_image', '') ? htmlspecialchars($item->params->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';



	// description
	$item->description = $item->params->get('dcml_desc', '');
	//$item->description = htmlspecialchars($item->params->get('dcml_desc', ''), ENT_COMPAT, 'UTF-8', false);
	if (empty($item->description)) {
		$item->description = htmlspecialchars($item->params->get('menu-meta_description', ''), ENT_COMPAT, 'UTF-8', false);
	}

	$item->showself = $item->params->get('dcml_showself', 0);
	
}
//---------------------------------------------------------------------------




