<?php
/**
* @version
* @package MosNewsWriter
* @copyright (C) 2009 ongetc.com
* @info ongetc@ongetc.com http://ongetc.com
* @license GNU/GPL http://ongetc.com/gpl.html.
*/
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
$content = "";   // clear previous content
global $mosConfig_shownoauth;
$access = !$mosConfig_shownoauth;

if (!class_exists('MosNewsWriterClass')) {
class MosNewsWriterClass {
	function MosNewsWriterClass($params) {  // constructor
		$this->init($params);
		switch ($this->source) {
		case 'rss':
			$this->rows = $this->getrows_from_rss($this->rssurl);
			break;
		default:
		case 'content':
		case 'category':
			$this->rows = $this->getrows_from_content();
			break;
		}
		$this->numrows = count( $this->rows );
		return;
	}

	function init($params) {
	// ID name Randomizer to avoid conflict in case of multiple modules (module duplication for multiple NewsFlash)
		srand ((double) microtime() * 1000000);
		$this->uniqid = rand( 1, 999999 );
		$this->uniqname = "NFRow".$this->uniqid."_";
		$this->params=$params;
		$this->moduleclass_sfx = $this->params->moduleclass_sfx;
		$this->borders  = $this->params->borders;
		$this->bgcolor  = $this->params->bgcolor;                 // back ground color: #F0F0F0

		$this->source   = strtolower($this->params->source);    // Display mode (marque, flash, TypeWriter, GH)
		$this->rssurl = $this->params->rssurl;

		$this->style   = strtolower($this->params->style);      // Display mode (marque, flash, TypeWriter, GH)
		$this->items    = intval( $this->params->items );       // No of items to display : 0:all
		$this->textlen = intval($this->params->textlen);
		$this->txtcolor = $this->params->txtcolor;

		$this->cid    = $this->params->cid;         // Category or content id to display
		$this->sorting  = $this->params->sorting;   // Display mode (0:sorted,1:random)

		$this->pretext = $this->params->pretext;
		$this->delay = $this->params->delay;        // Flash : Delay : 1000 = 1 seconds 3000 for flash 60 for marquee

		$this->direction    = strtolower($this->params->direction);                // Marquee : Scroll Direction
		$this->height       = intval( $this->params->height );        // Height  : 200px / 0:auto-height
		$this->scrollamount = intval( $this->params->scrollamount );   // Marquee : # of lines (step) per scroll
	}

	function main() {
		$sfx=$this->moduleclass_sfx;
		$tbgcolor="";
		if ($sfx=="" && $this->bgcolor!="") {
			$tbgcolor=" bgcolor=\"".$this->bgcolor."\"";
		}
	//<table class='moduletable$sfx' border=\"$this->borders\" $tbgcolor>
		switch ($this->style) {
		  case 'marquee':
			$output = $this->marquee();
		    break;
		  case 'flash':
			$output = $this->flash();
		    break;
		  case 'gh':
		     $output = $this->gh();
		     break;
		  default:
		  case 'typewriter':
		    $output = $this->typewriter();
		    break;
		} // switch
		echo "<!-- MosNewsWriter -->
<table class='moduletable$sfx' width='100%' border=\"$this->borders\" $tbgcolor>
<tr valign='top'><td>$output</td></tr></table>";
	}

  function mk_typewriter_js_var() {
		if (empty($this->rows)) return;
		$rows=$this->rows;
		$output="";
    $i = 0;
    $output .= "\t\ttheSummaries: [\n";
    foreach ($rows as $row) {
      if ($i > 0) { $output .= ",\n"; }
      $output .= "\t\t\t\t'" . $row["title"] . "'";
      $i++;
    }
    $output .= "\n\t\t\t],\n";

    $i = 0;
    $output .= "\t\t\ttheSiteLinks: [\n";
    foreach ($rows as $row) {
      if ($i > 0) { $output .= ",\n"; }
//      $output .= "\t\t\t\t'" . $row["link"] . "'";
      $output .= "\t\t\t\t'" . $this->mkSefUrl($row["link"]) . "'";      
      $i++;
    }
    $output .= "\n\t\t\t]\n";
    return $output;
  }
  function mkSefUrl($link) {
  	if ($this->source <> "rss") $link = sefRelToAbs($GLOBALS['mosConfig_live_site']."/".$link);
    return $link;
  }
  function mk_flash_js_var() {
		if (empty($this->rows)) return;
		$rows=$this->rows;
		$output="";
    $i = 0;
    foreach ($rows as $row) {
      ($i < 1)
        ? $tattrib='display: visible; visibility: visible;'
        : $tattrib='display: none; visibility: hidden;';
      $output .= '<div id="'.$this->uniqname.$i.'" style="'.$tattrib.'">'
          .$this->mk_link($row).'</div>';
      $i++;
    }
    return $output;
  }
  function mk_marquee_js_var() {
		if (empty($this->rows)) return;
		$rows=$this->rows;
		$output="";
    foreach ($rows as $row) {
				if ($this->source == "rss") {
					$icon1 = $this->getFavicon($this->rssurl);
	//				$output .= "<img src='$icon1' alt=''>";	// not compatible with IE 7
				}
				$output .= $this->mk_link($row).$this->marquee_padding();
    }
    return $output;
  }
  function mk_gh_js_var() {
		if (empty($this->rows)) return;
		$rows=$this->rows;
		$output="";
    $i = 0;
    foreach ($rows as $row) {
			$output .= 'messages['.$i.']="'.$this->mk_link($row).'"'."\n";
      $i++;
    }
    return $output;
  }

	function marquee_padding() {
		if ( (($this->direction == "up") || ($this->direction == "down"))) {
			$br = "<br />";}
		else {
			$br = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		return $br;
	}
	function marquee() {
	return "
	<marquee behavior=scroll loop=-1 height=$this->height direction=$this->direction scrollamount=$this->scrollamount scrolldelay=$this->delay onmouseover=this.stop() onmouseout=this.start();>"
	.$this->mk_marquee_js_var()
	."</marquee>
	";
	}

	function typewriter() {
    $url=sefRelToAbs($GLOBALS['mosConfig_live_site']."/modules/".$this->getModule()."mod_mosnewswriter/typewriter.js");
		echo "<script type='text/javascript' src='".$url."'></script>";
		$moduleclass_sfx=$this->moduleclass_sfx;

		$txtcolor=$this->txtcolor;
		$tickerAnchor='tickerAnchor'.$this->uniqid;
		$tattrib="";
		if ($moduleclass_sfx=="" && $txtcolor!="") {
			$tattrib = " style='font-weight:bold; color:".$txtcolor.";'";
		}
		return "<div class='ticki$moduleclass_sfx'>
			<a id='$tickerAnchor'$tattrib href='http://ongetc.com' target='_top'>MosNewsWriter</a>
			</div>". $this->wrapjs($this->typewriter_js_code($tickerAnchor));
	}

	function flash() {
		return "\n<table onmouseover=\"NFstopScroller".$this->uniqid."();\" onmouseout=\"NFstartScroller".$this->uniqid."();\"><tr><td>"
		. $this->mk_flash_js_var().$this->wrapjs($this->flash_js_code())
		. "</td></tr></table>\n";
	}

	function getTitleAndLink($values) {
	$rArray=array();
	    for ($j=0; $j < count($values); $j++) {
	        $whichtag = $values[$j]["tag"];
	        switch ($whichtag) {	// only want title and link
			case 'title':
				$rArray[$values[$j]["tag"]] = htmlentities($values[$j]["value"],ENT_QUOTES,'utf-8');
				break;
			case 'link':
				$rArray[$values[$j]["tag"]] = $values[$j]["value"];
				break;
			}
	    }
	    return $rArray;
	}

	function getrows_from_rss($feed)  {
		$items    = $this->items;         // No of items to display : 0:all  
		$itemsArray=$tArray=array();
		$curl_handle = curl_init();
		curl_setopt ($curl_handle, CURLOPT_URL, $feed);
		curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl_handle, CURLOPT_CONNECTTIMEOUT, 1);
		$datastring = "";
		$datastring = curl_exec($curl_handle);
		$curl_errors = curl_errno($curl_handle);
		curl_close($curl_handle);
		if ( $curl_errors || !$datastring ) {
			$datastring = "";
		}
	//	$datastring = implode("", file($feed));
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $datastring, $valuesArray, $tagsArray);
		xml_parser_free($parser);
		foreach ($tagsArray as $key=>$val) {
			if ($key == "item") {	// there is only one item array in rss feed
				$itemsArray = $val;
			}
		}
		if (!empty($itemsArray)) {
			for ($i=0; $i < count($itemsArray); $i+=2) {
				$offset = $itemsArray[$i];  // item open
				$size = $itemsArray[$i+1]-$offset;  // item - author complete
				$oneArray = array_slice($valuesArray, $offset, $size);
				$tArray[] = $this->getTitleAndLink($oneArray);	// arrays of title and link
			}
      (empty($items)) 
      ? $return = $tArray
      : $return = array_slice($tArray, 0, $items);  
      return $return;
		} else {
			echo "<pre>Can't locate this RSS feed:<br />";
			echo $feed;
			print_r($datastring);
			echo "</pre>";
			return "";
		}
	}

	function getrows_from_content() {
		global $database;
		global $my, $mosConfig_offset;
		$source    = $this->source;         // No of items to display : 0:all
		$items    = $this->items;         // No of items to display : 0:all
		$cid    = $this->cid;         // Category or Content id to display
		$sorting  = $this->sorting;                 // Display mode (0:sorted,1:random)
		$now = date( "Y-m-d H:i:s", time()+$mosConfig_offset*60*60 );

		if ($source=="content") {
			$contentSource = " AND a.id in (". $cid .") ";
		}
		else {
			$contentSource = " AND catid in (". $cid .") ";
		}

		if ( $items ) { $limit = " LIMIT ". $items; } else { $limit = ""; }

    switch ($sorting) {
    case "ordering" : $sorted = " ORDER BY a.ordering"; break;
    case "modified" : $sorted = " ORDER BY a.modified desc"; break;
    case "created" : $sorted = " ORDER BY a.created desc"; break;    
    default:
    case "random" : $sorted = " ORDER BY rand()"; break;
    }

		$Query = "SELECT a.id, a.title FROM #__content AS a INNER JOIN #__categories AS b ON b.id = a.catid"
		." WHERE a.state = 1 AND a.access <= ". $my->gid
		." AND (a.publish_up = '0000-00-00 00:00:00' OR a.publish_up <= '". $now ."') "
		." AND (a.publish_down = '0000-00-00 00:00:00' OR a.publish_down >= '". $now ."')"
		.$contentSource. $sorted . $limit
		;
		$database->setQuery( $Query );
		$rows = $database->loadObjectList();
		return $this->dbrows2array($rows);
	}


	function mk_aurl($row) {
		global $mainframe, $type;
		$bs=$bc=$gbs="";
		// needed to reduce queries used by getItemid for Content Items
		if (!$this->isJ15()) {
		//	require_once( "$mosConfig_absolute_path/includes/frontend.html.php" );
			require_once( $mainframe->getPath( 'front_html', 'com_content') );
			if ( ( $type == 1 ) || ( $type == 3 ) ) {
				$bs 	= $mainframe->getBlogSectionCount();
				$bc 	= $mainframe->getBlogCategoryCount();
				$gbs 	= $mainframe->getGlobalBlogSectionCount();
			}
		} else {  // >J 1.0
			$document	= &JFactory::getDocument();
			$renderer	= $document->loadRenderer('module');
			$params		= array('style'=>-1);	// raw html code
	//		echo $renderer->render($module, $params);
		}
		$Itemid = $mainframe->getItemid( $row->id, 0, 0, $bs, $bc, $gbs );
		if (!$Itemid) $Itemid=0;
		if ($this->style=="typewriter") {
			$return = "index.php?option=com_content&task=view&id=". $row->id ."&Itemid=". $Itemid;
		}
		else {
			$return = "index.php?option=com_content&amp;task=view&amp;id=". $row->id ."&amp;Itemid=". $Itemid;
		}
		return $return;
	}

	function set_title($row) {
	$textlen=$this->textlen;
		if (strlen($row->title)<= $textlen) { $text = $row->title; } else { $text = substr($row->title,0,$textlen)."..."; }
		return htmlentities($text,ENT_QUOTES,'utf-8'); // not sure about ENT_QUOTES
	}

	function dbrows2array($rows) {
		$rowArray = array();
		$rowsArray = array();
		if ($rows) {
			foreach ($rows as $row) {
				$rowArray["title"] = $this->set_title($row);
				$rowArray["link"] = $this->mk_aurl($row);
				$rowsArray[]=$rowArray;
			}
		}
		return $rowsArray;
	}

	function flash_js_code() {
		$uniqid=$this->uniqid;
		$uniqname=$this->uniqname;

		$thisdelay=intval($this->delay)*30;
		return "
	var NFtimerID".$uniqid." = null;
	var NFscrollerRunning".$uniqid." = false;
	var NFid".$uniqid." = 0;
	var NFtotal".$uniqid." = " .$this->numrows. ";
	var NFblock".$uniqid." = 1;
	var NFdelay".$uniqid." = " .$thisdelay. ";
	var NFname".$uniqid." = '".$uniqname."';

	var ns4=(navigator.appName=='Netscape' && parseInt(navigator.appVersion)==4);
	var ns6=(document.getElementById)? true:false;
	var ie4=(document.all)? true:false;

	function NFshowObject".$uniqid."(obj){
		if (ns6) {
		  obj.style.display='block';
		  obj.style.visibility='visible';
		}
		else if (ie4) obj.visibility='visible';
		else if (ns4) obj.visibility='show';
	}

	function NFhideObject".$uniqid."(obj){
		if (ns6) {
		  obj.style.display='none';
		  obj.style.visibility='hidden';
		}
		else if (ie4)obj.visibility='hidden';
		else if (ns4)obj.visibility='hide';
	}

	function NFnextblock".$uniqid."(id,block,total) {
		if (total % block > 0) { totmax = (Math.floor(total/block)+1)*block; }
		else { totmax = total; }
		id = (id < totmax-block) ? id+block : totmax-(id+block);   // Circular chain : next to last = first
		return id;
	}

	function NFprevblock".$uniqid."(id,block,total) {
		if (total % block > 0) { totmax = (Math.floor(total / block) +1) * block; }
		else { totmax = total; }
		id = (id < block) ? totmax-block+id : id-block;   // Circular chain : previous to first = last
		return id;
	}

	function NFshowBlock".$uniqid."() {
		for (i=NFid".$uniqid."; i < NFid".$uniqid." + NFblock".$uniqid."; i++) {
		p = NFprevblock".$uniqid."(i,NFblock".$uniqid.",NFtotal".$uniqid.");
		if (p < NFtotal".$uniqid.") {
		   NFhideObject".$uniqid."(document.getElementById(NFname".$uniqid." + p));
		}  // Hide Object
		if (i < NFtotal".$uniqid.") {
		   NFshowObject".$uniqid."(document.getElementById(NFname".$uniqid." + i));
		}  // Show Object
		}
		n = NFnextblock".$uniqid."(NFid".$uniqid.",NFblock".$uniqid.",NFtotal".$uniqid.");
		NFid".$uniqid." = n;                                            // Set next object
		NFstartScroller".$uniqid."();
	}

	function NFstopScroller".$uniqid."(){
		if (NFscrollerRunning".$uniqid.") {      // Stop the scroller
		   clearTimeout(NFtimerID".$uniqid.");
		}
		NFscrollerRunning".$uniqid." = false;    // Scroller stopped
	}

	function NFstartScroller".$uniqid."() {
		if (!NFscrollerRunning".$uniqid.") {
		   if (document.getElementById) {   // DOM Compatible ?
			 NFscrollerRunning".$uniqid." = true;      // Scroller started
			 NFtimerID".$uniqid." = setTimeout('NFshowBlock".$uniqid."()', NFdelay".$uniqid.");
		   }
		}
		else { NFtimerID".$uniqid." = setTimeout('NFshowBlock".$uniqid."()', NFdelay".$uniqid.");   }
	}

	function NFloadScroller".$uniqid."() {
		NFstopScroller".$uniqid."();                // Make sure the scroller is stopped
		NFshowBlock".$uniqid."();
	}

	// Start scroller
	// ----------------------------------------
	NFloadScroller".$uniqid."();
	";
	}

	function typewriter_js_code($tickerAnchor) {
	return
	"setTimeout(function() {
		var params = {
			theCharacterTimeout: $this->delay,
			theStoryTimeout: 5000,
			theWidgetOne: '_',
			theWidgetTwo: '-',
			theWidgetNone: '',
			theLeadString: '$this->pretext ',
			theItemCount: '$this->numrows',
			theCurrentStory: -1,
			theCurrentLength: 0,
			theAnchorName: '$tickerAnchor',
	"
	.$this->mk_typewriter_js_var()
	."\t\t};
		co_runTheTicker(params);
	}, 100);";
	}

	function gh() {
	$output ="
	<ilayer id='main' width='100%' height='15' visibility=hide>
	<layer id='first' left=0 top=1 width='100%';>
	<script type='text/javascript'>
	if (document.layers)
		document.write(messages[0])
	</script>
	</layer>
	<layer id='second' left=0 top=0 width='100%' visibility=hide>
	<script type='text/javascript'>
	if (document.layers)
		document.write(messages[dyndetermine=(messages.length==1)? 0 : 1])
	</script>
	</layer>
	</ilayer>
	<script type='text/javascript'>
	if (ie||dom){
	document.writeln('<div id=\"main2\" style=\"position:relative;width:'+scrollerwidth+';height:'+scrollerheight+';overflow:hidden;\">')
	document.writeln('<div style=\"position:absolute;width:'+scrollerwidth+';height:'+scrollerheight+';clip:rect(0 '+scrollerwidth+' '+scrollerheight+' 0);left:0px;top:0px\">')
	document.writeln('<div id=\"first2\" style=\"position:absolute;width:'+scrollerwidth+';left:0px;top:1px;\">')
	document.write(messages[0])
	document.writeln('</div>')
	document.writeln('<div id=\"second2\" style=\"position:absolute;width:'+scrollerwidth+';left:0px;top:0px;visibility:hidden\">')
	document.write(messages[dyndetermine=(messages.length==1)? 0 : 1])
	document.writeln('</div>')
	document.writeln('</div>')
	document.writeln('</div>')
	}
	</script>
	";

	return "<div align='center'>".$this->wrapjs($this->gh_js_code()).$output."</div>";
	}

	function gh_js_code() {
	return "
	var scrollerdelay='3000' //delay between msg scrolls. 3000=3 seconds.
	var scrollerwidth='100%'
	var scrollerheight='15px'
	var scrollerbgcolor=''
	var scrollerbackground=''

	//configure the below variable to change the contents of the scroller
	var messages=new Array()
	"
	.$this->mk_gh_js_var()
	."

	///////Do not edit pass this line///////////////////////
	var ie=document.all
	var dom=document.getElementById

	if (messages.length>2)
		i=2
	else
		i=0

	function move1(whichlayer){
		tlayer=eval(whichlayer)
		if (tlayer.top>0&&tlayer.top<=5){
			tlayer.top=0
			setTimeout('move1(tlayer)',scrollerdelay)
			setTimeout('move2(document.main.document.second)',scrollerdelay)
			return
		}
		if (tlayer.top>=tlayer.document.height*-1){
			tlayer.top-=5
			setTimeout('move1(tlayer)',50)
		}
		else{
			tlayer.top=parseInt(scrollerheight)
			tlayer.document.write(messages[i])
			tlayer.document.close()
			if (i==messages.length-1)
				i=0
			else
				i++
		}
	}

	function move2(whichlayer){
		tlayer2=eval(whichlayer)
		if (tlayer2.top>0&&tlayer2.top<=5){
			tlayer2.top=0
			setTimeout('move2(tlayer2)',scrollerdelay)
			setTimeout('move1(document.main.document.first)',scrollerdelay)
			return
		}
		if (tlayer2.top>=tlayer2.document.height*-1){
			tlayer2.top-=5
			setTimeout('move2(tlayer2)',50)
		}
		else{
			tlayer2.top=parseInt(scrollerheight)
			tlayer2.document.write(messages[i])
			tlayer2.document.close()
			if (i==messages.length-1)
				i=0
			else
				i++
		}
	}

	function move3(whichdiv){
		tdiv=eval(whichdiv)
		if (parseInt(tdiv.style.top)>0&&parseInt(tdiv.style.top)<=5){
			tdiv.style.top=0+'px'
			setTimeout('move3(tdiv)',scrollerdelay)
			setTimeout('move4(second2_obj)',scrollerdelay)
			return
		}
		if (parseInt(tdiv.style.top)>=tdiv.offsetHeight*-1){
			tdiv.style.top=parseInt(tdiv.style.top)-5+'px'
			setTimeout('move3(tdiv)',50)
		}
		else{
			tdiv.style.top=parseInt(scrollerheight)
			tdiv.innerHTML=messages[i]
			if (i==messages.length-1)
				i=0
			else
				i++
		}
	}

	function move4(whichdiv){
		tdiv2=eval(whichdiv)
		if (parseInt(tdiv2.style.top)>0&&parseInt(tdiv2.style.top)<=5){
			tdiv2.style.top=0+'px'
			setTimeout('move4(tdiv2)',scrollerdelay)
			setTimeout('move3(first2_obj)',scrollerdelay)
			return
		}
		if (parseInt(tdiv2.style.top)>=tdiv2.offsetHeight*-1){
			tdiv2.style.top=parseInt(tdiv2.style.top)-5+'px'
			setTimeout('move4(second2_obj)',50)
		}
		else{
			tdiv2.style.top=parseInt(scrollerheight)
			tdiv2.innerHTML=messages[i]
			if (i==messages.length-1)
				i=0
			else
				i++
		}
	}

	function startscroll(){
		if (ie||dom){
			first2_obj=ie? first2 : document.getElementById('first2')
			second2_obj=ie? second2 : document.getElementById('second2')
			move3(first2_obj)
			second2_obj.style.top=scrollerheight
			second2_obj.style.visibility='visible'
		}
		else if (document.layers){
			document.main.visibility='show'
			move1(document.main.document.first)
			document.main.document.second.top=parseInt(scrollerheight)+5
			document.main.document.second.visibility='show'
		}
	}
	window.onload=startscroll
	";
	}

	function getFavicon($url) {
		$parse = parse_url($url);
		$host = $parse['host'];
		$iconUrl="http://".$host."/favicon.ico";
		return $iconUrl;
	}

	function mk_link($row) {
//    $url=sefRelToAbs($GLOBALS['mosConfig_live_site']."/".$row["link"]);
//    return "<a href='".$url."' target='_blank'>&nbsp;<font color=".$this->txtcolor.">".$row["title"]."</font></a>";
//    return "<a href='".$url."'>&nbsp;<font color=".$this->txtcolor.">".$row["title"]."</font></a>";

    ($this->source == "rss") ? $target = " target='_blank'" : $target = "";
    return "<a href='".$this->mkSefUrl($row["link"])."'".$target.">&nbsp;<font color=".$this->txtcolor.">".$row["title"]."</font></a>";
	}

	function isJ15() {
		( (defined('JVERSION')) and
			($this->is1stNewer2nd(substr(JVERSION,0,3),'1.0') ) ) ? $ret=true : $ret=false;
		return $ret;
	}
	function is1stNewer2nd($first,$second) {
	   (version_compare($first,$second)=="1") ? $newer=true : $newer=false;
	   return $newer;
	}

	function debug($vars) {
		echo "<br />vars: <pre>";
		print_r($vars);
		echo "</pre>";
	}

	function wrapjs($intext) {
	return "
	<script type='text/javascript'>
	<!--
	$intext
	//--></script>
	";
	}
	function getModule() {
		($this->isJ15()) ? $ret="mod_mosnewswriter/" : $ret="";
		return $ret;
	}

} // end class
} // if

$MosNW = new MosNewsWriterClass(mosParseParams( $module->params ));
$MosNW->main();
