<?php
// Class file for the tree navigator
//
// Note : NEVER use person or family id as ids because a same person could
// appear more than once in the tree.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

require_once 'includes/functions/functions_charts.php';

class TreeView {
	var $name;
	var $allPartners;

	/**
	* Treeview Constructor
	* @param string $name the name of the TreeView object's instance
	*/
	function __construct($name='tree') {
		$this->name = $name;
		
		// Read if all partners must be shown or not
		$allPartners = safe_GET('allPartners');
		// if allPartners not specified in url, we try to read the cookie
		if ($allPartners == '') {
			if (isset($_COOKIE['allPartners']))
				$allPartners = $_COOKIE['allPartners'];
			else
				$allPartners = 'true'; // That is now the default value
		}
		$allPartners = ($allPartners == 'true' ? true : false);
    $this->allPartners = $allPartners;
	}

	/**
	* Draw the viewport which creates the draggable/zoomable framework
  * Size is set by the container, as the viewport can scale itself automatically
  * @param string $rootPersonId the id of the root person
  * @param int $generations number of generations to draw
	*/
	public function drawViewport($rootPersonId, $generations, $style) {
		global $GEDCOM, $WT_IMAGES;

    $rootPersonId = check_rootid($rootPersonId);
    $rootPerson = WT_Person::getInstance($rootPersonId);
    if (is_null($rootPerson))
      $rootPerson = new WT_Person('');

    if (WT_SCRIPT_NAME == 'individual.php')
      $path = 'individual.php?pid='.$rootPerson->getXref().'&ged='.$GEDCOM.'&allPartners='.($this->allPartners ? "false" : "true").'#tree';
    else
      $path = 'module.php?mod=tree&mod_action=treeview&rootid='.$rootPerson->getXref().'&allPartners='.($this->allPartners ? "false" : "true");
    $r = '<a name="tv_content"></a><div id="'.$this->name.'_out" dir="ltr" class="tv_out">';
    
    // Read styles (20 maxi) in a hidden list
    $sd = WT_MODULES_DIR.'tree/css/styles/';
    $rs = '<ul id="tvStylesSubmenu">';
    $cs = '<img src="'.$sd.'default/button.gif" alt="d"  onclick="'.$this->name.'Handler.style(\''.$sd.'\', \'default\', this);" title="'.WT_I18N::translate('Style').'" />';
    $rs .= '<li class="tv_button'.($style == '' || $style=='default' ? ' tvPressed' : '').'">'.$cs.'</li>';    
    $nbStyles = 1;
    if (@is_dir($sd) && @is_readable($sd) && ($d=@opendir($sd))) {    	
    	while (($s = readdir($d)) !== false && ($nbStyles < 20)) {
    		if ($s[0] == '.' || $s=='default' || !is_dir($sd.$s))
					continue;
        $sHTML = '<img src="'.$sd.$s.'/button.gif" alt="'.$s[0].'"  onclick="'.$this->name.'Handler.style(\''.$sd.'\', \''.$s.'\', this);" title="'.WT_I18N::translate('Style').'" />';
        if ($s == $style) {
        	$cs = $sHTML;
        	$pressedState = ' tvPressed';
        }
        else
        	$pressedState ='';
        $rs .= '<li class="tv_button'.$pressedState.'">'.$sHTML.'</li>';
        $nbStyles++;
    	}
    }
    $rs .= '</ul>';

		// Add the toolbar
		$r.=
			'<div id="tv_tools"><ul>'.
			// TODO: can we change the toolbar's orientation automatically, when it is dragged to a vertical/horizontal edge?
			'<li id="tvToolsHandler" title="'.WT_I18N::translate('Move the toolbar').'"></li>'.
			'<li id="tvbZoomIn" class="tv_button"><img src="'.$WT_IMAGES['zoomin'].'" alt="'.WT_I18N::translate('Zoom in').'" title="'.WT_I18N::translate('Zoom in').'" /></li>'.
			'<li id="tvbZoomOut" class="tv_button"><img src="'.$WT_IMAGES['zoomout'].'" alt="'.WT_I18N::translate('Zoom out').'" title="'.WT_I18N::translate('Zoom out').'" /></li>'.
			'<li id="tvbNoZoom" class="tv_button"><img src="'.WT_MODULES_DIR.'tree/images/zoom0.png" alt="'.WT_I18N::translate('Reset').'" title="'.WT_I18N::translate('Reset').'" /></li>'.
			'<li id="tvbLeft" class="tv_button"><img src="'.$WT_IMAGES['ldarrow'].'" alt="'.WT_I18N::translate('Align left').'" title="'.WT_I18N::translate('Align left').'" /></li>'.
			'<li id="tvbCenter" class="tv_button"><img src="'.$WT_IMAGES['center'].'" alt="'./* I18N: verb/action */ WT_I18N::translate('Center').'" title="'.WT_I18N::translate('Center').'" /></li>'.
			'<li id="tvbRight" class="tv_button"><img src="'.$WT_IMAGES['rdarrow'].'" alt="'.WT_I18N::translate('Align right').'" title="'.WT_I18N::translate('Align right').'" /></li>'.
			'<li id="tvbDates" class="tv_button tvPressed"><img src="'.WT_MODULES_DIR.'tree/images/dates.png" alt="'.WT_I18N::translate('Show year of birth and death').'" title="'.WT_I18N::translate('Show year of birth and death').'" /></li>'.
			'<li id="tvbCompact" class="tv_button"><img src="'.WT_MODULES_DIR.'tree/images/compact.png" alt="'.WT_I18N::translate('Use compact layout').'" title="'.WT_I18N::translate('Use compact layout').'" /></li>'.
			// TODO: this is temporarily disabled (as it sends a flood of AJAX requests?)
			//'<li id="tvbOpen" class="tv_button"><img src="'.$WT_IMAGES["media"].'" alt="o" title="'.WT_I18N::translate('Show all details').'" /></li>'.
			//'<li id="tvbClose" class="tv_button"><img src="'.$WT_IMAGES["fambook"].'" alt="f" title="'.WT_I18N::translate('Hide all details').'" /></li>'.
			// If the position/order of the style button moves, update TreeViewHandler() in treeview.js
			'<li id="tvStyleButton" class="tv_button">'.$cs.'</li>'.
			'<li id="tvbPrint" class="tv_button"><img src="'.WT_MODULES_DIR.'tree/images/print.png" alt="p" title="'./* I18N: verb/action */ WT_I18N::translate('Print').'" /></li>'.
			'<li class="tv_button'.($this->allPartners ? ' tvPressed' : '').'"><a href="'.$path.'"><img src="'.$WT_IMAGES["sfamily"].'" alt="'.WT_I18N::translate('Show all spouses and ancestors').'" title="'.WT_I18N::translate('Show all spouses and ancestors').'" /></a></li>';
    if (safe_GET('mod_action') != 'treeview') {
			$r.='<li class="tv_button"><a href="module.php?mod=tree&mod_action=treeview&rootid='.$rootPerson->getXref().'#tv_content" title="'. /* I18N: Button label - view this chart in full-screen mode */ WT_I18N::translate('Full screen').'"><img src="'.$WT_IMAGES["fscreen"].'" alt="full screen" /></a></li>';
		}
    // Help, and hidden loading image
		$r.='<li class="tv_button">'.help_link("TV_MODULE", 'tree').'</li>
  <li class="tv_button" id="'.$this->name.'_loading"><img src="images/loading.gif" alt="Loading..." /></li>
</ul>'.$rs;
		$r.='</div><div id="'.$this->name.'_in" class="tv_in">';
    $parent = null;
    $r.=$this->drawPerson($rootPerson, $generations, 0, $parent, '', true);
    $r.='</div></div>'; // Close the tv_in and the tv_out div
		$r.='<script type="text/javascript">var '.$this->name.'Handler = new TreeViewHandler("'.$this->name.'", '.($this->allPartners ? 'true' : 'false').', '.$nbStyles.');</script>';
    return $r;
	}

  /**
  * Return a JSON structure to a JSON request
  * @param string $list list of JSON requests
  */
  public function getPersons($list) {
    $list = explode(';', $list);
    $r = array();
    foreach($list as $jsonRequest) {
      $firstLetter = substr($jsonRequest, 0, 1);
      $jsonRequest = substr($jsonRequest, 1);
      switch($firstLetter) {
        case 'c':
        	$fidlist = explode(',', $jsonRequest);
        	$flist = array();
        	foreach($fidlist as $fid)
        		$flist[] = WT_Family::getInstance($fid);
        	$r[] = $this->drawChildren($flist, 1, true);
          break;
        case 'p':
          $params = explode('@', $jsonRequest);
          $fid = $params[0];
          $order = $params[1];
          $f = WT_Family::getInstance($fid);
          $p = $f->getHusband();
          $r[] = $this->drawPerson($f->getHusband(), 0, 1, $f, $order);
          break;
      }
    }
    return json_encode($r);
  }

  /**
  * Get the details for a person and their life partner(s)
  * @param string $pid the person id to return the details for
  */
  public function getDetails($pid) {

    $person = WT_Person::getInstance($pid);

    $r = $this->getPersonDetails($person, $person, null);
    foreach ($person->getSpouseFamilies() as $family) {
      if (!empty($family)) {
        $partner = $family->getSpouse($person);
        if (!empty($partner))
          $r .= $this->getPersonDetails($person, $partner, $family);
      }
    }
    return $r;
  }

 /**
  * Get full resolution medias
  * @param string $medias the list of medias to return
  */
  public function getMedias($medias) {
  	$medias = explode(';', $medias);
  	$nb = count($medias);
  	$r = array();
  	for ($i=0; $i<$nb; $i++) {
  		$mid = $medias[$i];
  		$m = WT_Media::getInstance($mid);
  		$r[] = $m->getServerFilename();
  	}
  	return json_encode($r);
  }

  
  /**
  * Return the details for a person
  * @param Person $person the person to return the details for
  */
  private function getPersonDetails($personGroup, $person, $family) {
		global $WT_IMAGES;

    $r = '<div class="tv'.$person->getSex().' tv_person_expanded">';
    $r .= $this->getThumbnail($personGroup, $person);
    $r .= '<a class="tv_link" href="'.$person->getHtmlUrl().'">'.$person->getFullName().'</a> <a href="module.php?mod=tree&mod_action=treeview&allPartners='.($this->allPartners ? 'true' : 'false').'&rootid='.$person->getXref().'" title="'.WT_I18N::translate('Interactive tree of %s', htmlspecialchars(strip_tags($person->getFullName()))).'"><img src="'.$WT_IMAGES['tree'].'" class="tv_link tv_treelink" /></a>';
    $r .= '<br /><b>'.WT_Gedcom_Tag::getAbbreviation('BIRT').'</b> '.$person->getBirthDate()->Display().' '.$person->getBirthPlace();
    if ($family) {
      $r .= '<br /><b>'.WT_Gedcom_Tag::getAbbreviation('MARR').'</b> '.$family->getMarriageDate()->Display().' <a href="'.$family->getHtmlUrl().'"><img src="'.$WT_IMAGES['button_family'].'" class="tv_link tv_treelink" title="'.htmlspecialchars(strip_tags($family->getFullName())).'" /></a>'.$family->getMarriagePlace();
    }
    if ($person->isDead())
      $r .= '<br /><b>'.WT_Gedcom_Tag::getAbbreviation('DEAT').'</b> '.$person->getDeathDate()->Display().' '.$person->getDeathPlace();
    $r.= '</div>';
    return $r;
  }

  /**
  * Draw the children for some families
  * @param Array $familyList array of families to draw the children for
  * @param int $gen number of generations to draw
  * @param boolean $ajax setted to true for an ajax call
  */
  private function drawChildren($familyList, $gen=1, $ajax=false) {
  	$r ='';
  	$flWithChildren = array();
  	$f2load = array();
  	$tc = 0;
  	foreach($familyList as $f) {
  		if (empty($f))
  			continue;
  		$nbcf = $f->getNumberOfChildren();
  		if ($nbcf > 0) {
  			$flWithChildren[] = $f;
  			$f2load[] = $f->getXref();
  			$tc += $nbcf;
  		}
  	}
  	if ($tc) {
  		$f2load = implode(',', $f2load);
  		if (!$ajax)
  			$r .= '<td align="right"'.($gen == 0 ? ' abbr="c'.$f2load.'"' : '').'>';
  		$nbc = 0;
  		foreach($flWithChildren as $f) {
  			foreach ($f->getChildren() as $child) {
  				$nbc++;
	        if ($tc == 1)
            $co = 'c'; // unique
          elseif ($nbc == 1)
            $co = 't'; // first
          elseif($nbc == $tc)
            $co = 'b'; //last
          else
            $co = 'h';
          $fam = null;
          $r .= $this->drawPerson($child, $gen-1, -1, $fam, $co);
  			}
  		}
  		if (!$ajax)
  			$r .= '</td>'.$this->drawHorizontalLine();
  	}
    return $r;
  }
  
  /**
  * Draw a person in the tree
  * @param Person $person The Person object to draw the box for
  * @param int $gen The number of generations up or down to print
  * @param int $state Whether we are going up or down the tree, -1 for descendents +1 for ancestors
  * @param Family $pfamily
  * @param string $order first (1), last(2), unique(0), or empty. Required for drawing lines between boxes
  *
  * Notes : "spouse" means explicitely married partners. Thus, the word "partner"
  * (for "life partner") here fits much better than "spouse" or "mate"
  * to translate properly the modern french meaning of "conjoint"
  */
  private function drawPerson($person, $gen, $state=0, $pfamily, $order, $isRoot=false) {
    global $TEXT_DIRECTION;

    if ($gen < 0 || empty($person))
      return;

    if (!empty($pfamily))
      $partner = $pfamily->getSpouse($person);
    else {
      $partner = $person->getCurrentSpouse();
      $fams = $person->getSpouseFamilies();
      $pfamily = end($fams);
    }

    if ($isRoot)
    	$r = '<table id="tvTreeBorder" class="tv_tree"><tbody><tr><td id="tv_tree_topleft"></td><td id="tv_tree_top"><div>'.
    		WT_I18N::translate('Interactive tree of %s',$person->getFullName()).
    		'</div></td><td id="tv_tree_topright"></td></tr><tr><td id="tv_tree_left"></td><td>';
    else $r = '';
    /* height 1% : this hack enable the div auto-dimensionning in td for FF & Chrome */
    $r .= '<table class="tv_tree"'.($isRoot ? ' id="tv_tree"' : '').' style="height: 1%"><tbody><tr>';
    
    if ($state<=0) {
    	// draw children
			$r.=$this->drawChildren($person->getSpouseFamilies(), $gen);
    } else {
    	// draw the parent's lines
      $r .= $this->drawVerticalLine($order).$this->drawHorizontalLine();
		}

    /* draw the person. Do NOT add person or family id as an id, since a same person could appear more than once in the tree !!!   */
    // Fixing the width for td to the box initial width when the person is the root person fix a rare bug that happen when a person without child and without known parents is the root person : an unwanted white rectangle appear at the right of the person's boxes, otherwise.
    $r .= '<td'.($isRoot ? ' style="width:1px"' : '').'><div class="tv_box'.($isRoot ? ' rootPerson' : '').'" dir="'.$TEXT_DIRECTION.'" style="text-align: '.($TEXT_DIRECTION=="rtl" ? "right":"left").'; direction: '.$TEXT_DIRECTION.'" abbr="'.$person->getXref().'" onclick="'.$this->name.'Handler.expandBox(this, event);">';
    $r .= $this->drawPersonName($person);
    $fop = Array(); // $fop is fathers of partners
    if (!is_null($partner)) {
      $sfams = $person->getSpouseFamilies();      
      foreach ($sfams as $famid=>$family) {
        $p = $family->getSpouse($person);
        if (!empty($p)) {
          if (($p->equals($partner)) || $this->allPartners) {
            $pf = $p->getPrimaryChildFamily();
            if (!empty($pf))
          		$fop[] = Array($pf->getHusband(), $pf);
            $r .= $this->drawPersonName($p);
            if (!$this->allPartners)
              break; // we can stop here the foreach loop
           }
        }
      }
    }
    $r .= '</div></td>';

    $fatherFamily = $person->getPrimaryChildFamily();
    if (!empty($fatherFamily))
    	$father = $fatherFamily->getHusband();
    if (!empty($father) || count($fop) || ($state < 0))
      $r .= $this->drawHorizontalLine();

    /* draw the parents */
    if ($state >= 0 && (!empty($father) || count($fop))) {
      $unique = (empty($father) || count($fop) == 0);
      $r .= '<td align="left"><table class="tv_tree"><tbody>';
      if (!empty($father)) {
        $u = ($unique ? 'c' : 't');
        $r .= '<tr><td '.($gen == 0 ? ' abbr="p'.$fatherFamily->getXref().'@'.$u.'"' : '').'>';
        $r .= $this->drawPerson($father, $gen-1, 1, $fatherFamily, $u);
        $r .= '</td></tr>';
      }
      if (count($fop)) {
        $n = 0;
        $nb = count($fop);
        foreach($fop as $p) {
          $n++;
          $u = ($unique ? 'c' : ($n == $nb || empty($p[1]) || !$this->allPartners ? 'b' : 'h'));
          $r .= '<tr><td '.($gen == 0 ? ' abbr="p'.$p[1]->getXref().'@'.$u.'"' : '').'>'.$this->drawPerson($p[0], $gen-1, 1, $p[1], $u).'</td></tr>';
        }
      }
      $r .= '</tbody></table></td>';
    }
    if ($state < 0) {
      $r .= $this->drawVerticalLine($order);
    }
    $r .= '</tr></tbody></table>';
	  if ($isRoot)
  	 	$r .= '</td><td id="tv_tree_right"></td></tr><tr><td id="tv_tree_bottomleft"></td><td id="tv_tree_bottom"></td><td id="tv_tree_bottomright"></td></tr></tbody></table>';
  return $r;
  }

	/**
  * Draw a person name preceded by sex icon, with parents as tooltip
  * @param WT_Person $p a person
  */
  private function drawPersonName($p) {
  	if ($this->allPartners) {
    	$f = $p->getPrimaryChildFamily();
    	if ($f) {
				switch ($p->getSex()) {
				case 'M':
					$title=' title="'.htmlspecialchars(strip_tags(/* I18N: e.g. "Son of [father name & mother name]" */ WT_I18N::translate('Son of %s', $f->getFullName()))).'"';
					break;
				case 'F':
					$title=' title="'.htmlspecialchars(strip_tags(/* I18N: e.g. "Daughter of [father name & mother name]" */ WT_I18N::translate('Daughter of %s', $f->getFullName()))).'"';
					break;
				case 'U':
					$title=' title="'.htmlspecialchars(strip_tags(/* I18N: e.g. "Child of [father name & mother name]" */ WT_I18N::translate('Child of %s', $f->getFullName()))).'"';
					break;
				}
			} else {
				$title='';
			}
  	}
  	else
  		$title = '';
		$sex = $p->getSex();
  	switch($sex) {
  		case 'M':
  			$sexSymbol = WT_UTF8_MALE;
  			break;
  		case 'F':
  			$sexSymbol = WT_UTF8_FEMALE;
  			break;
  		default:
  			$sexSymbol = WT_UTF8_NO_SEX;
  			break;
  	}
  	$r = '<div class="tv'.$sex.'"'.$title.'><span class="dates">'.$p->getLifeSpan().'</span><a href="'.$p->getHtmlUrl().'"><span class="tvSexSymbol tv'.$sex.' tv_link">'.$sexSymbol.'</span></a>&nbsp;'.$p->getFullName().'</div>';
  	return $r;
  }

  /**
  * Get the thumbnail image for the given person
  *
  * @param Person $person
  * @return string
  */
	private function getThumbnail($personGroup, $person) {
		global $MULTI_MEDIA, $SHOW_HIGHLIGHT_IMAGES;

		$thumbnail="";
		if ($MULTI_MEDIA && $SHOW_HIGHLIGHT_IMAGES) {
			$object=$person->findHighlightedMedia();
			$img_title=PrintReady(htmlspecialchars($person->getFullName()));
			if (!empty($object)) {
				$mediaobject=WT_Media::getInstance($object['mid']);
				$thumbnail=$mediaobject->displayMedia(array('display_type'=>'treeview','img_title'=>$img_title,'clearbox'=>'tvlb'.$personGroup->getXref()));
			} else {
				$thumbnail=display_silhouette(array('sex'=>$person->getSex(),'display_type'=>'treeview','img_title'=>$img_title)); // may return ''
			}
		}

		return $thumbnail;
	}

  /**
  * Draw a vertical line
  * @param string order $order A parameter that set how to draw this line
  * with auto-redimensionning capabilities
  * WARNING : some tricky hacks are required in CSS to ensure cross-browser compliance
  * some browsers shows an image, which imply a size limit in height,
  * and some other browsers (ex: firefox) shows a <div> tag, which have no size limit in height
  * Therefore, Firefox is a good choice to print very big trees.
  */
  private function drawVerticalLine($order) {
    $r = '<td class="tv_vline tv_vline_'.$order.'"><div class="tv_vline tv_vline_'.$order.'"></div></td>';
    return $r;
  }

  /**
  * Draw an horizontal line
  */
  private function drawHorizontalLine() {
    $r = '<td class="tv_hline"><div class="tv_hline"></div></td>';
    return $r;
  }

}
