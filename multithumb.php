<?php
#error_reporting(E_ALL);
/**
 * @version $Id: multithumb.php, v 2.0 alpha 3 for Joomla 1.5 2008/8/27 15:08:21 marlar Exp $
 * @package Joomla
 * @copyright (C) 2007-2008 Martin Larsen; with modifications from Erich N. Pekarek and René-C. Kerner
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

//import library dependencies
jimport('joomla.event.plugin');
jimport('joomla.document.document');

require_once JPATH_SITE.'/components/com_content/helpers/route.php';

// BK-Thumb plugin implementation
class plgContentMultithumb extends JPlugin {

	function plgContentMultithumb ( &$subject, $config ) {
	// public function __construct(& $subject, $config) {
		// Iniatialize parent class
		parent::__construct($subject, $config);
		
		// $this->init_all();
		$this->cont_num = 0;
                // echo "DEBUG0: $this->cont_num<br/>";


	}
	
	function init_all($context) {
		
		// Current version for infornation message
		$this->botmtversion = 'Multithumb 3.7.2';

		// Don't initaialize anymore if plugin is disabled
		$this->published	= JPluginHelper::isEnabled('content','multithumb');
		if (!$this->published) {
			return;
		}
		
		$this->_live_site = JURI::base( true );
		
        $this->jversion = new JVersion();
		if ( version_compare($this->jversion->getShortVersion(), '1.6.0', '>=') ) {
			$this->loadLanguage();
		}
		
		// Initialize paramters
		$plugin = JPluginHelper::getPlugin( 'content', 'multithumb' );
		if ( version_compare($this->jversion->getShortVersion(), '1.6.0', '>=') ) {
			$plugin = JPluginHelper::getPlugin( 'content', 'multithumb' );
			$params = new JRegistry();
			if ( version_compare($this->jversion->getShortVersion(), '3.0.0', '>=') ) {
				$params->loadString($plugin->params);
			} else {
				$params->loadJSON($plugin->params);
			}
			$this->init_params($params, $context);
		} else {
			$this->init_params(new JParameter( $plugin->params ), $context);
		}
		
		
 		if ( $this->_params->get('highslide_headers') == 2 ) {
			$this->botAddMultiThumbHeader('highslide');
		}
 		if ( $this->_params->get('lightbox_headers') == 2 ) {
			$this->botAddMultiThumbHeader('lightbox');
		}
 		if ( $this->_params->get('slimbox_headers') == 2 ) {
			$this->botAddMultiThumbHeader('slimbox');
		}
		if ( $this->_params->get('prettyphoto_headers') == 2 ) {
			$this->botAddMultiThumbHeader('prettyPhoto');
		}

		if ( $this->_params->get('shadowbox_headers') == 2 ) {
			$this->botAddMultiThumbHeader('shadowbox');
		}
		
		if ( $this->_params->get('jquery_headers') == 2 ) {
			$this->botAddMultiThumbHeader('jquery');
		}	

		if ( $this->_params->get('iload_headers') == 2 ) {
			$this->botAddMultiThumbHeader('iLoad');
		}

			
	}

	function set_blog_params($context) {
	
		if ($this->is_blog) {
	
			$item_id = JRequest::getInt('Itemid');
			// <param name="blog_mode" type="list"
			// <option value="link">Link to article</option>
			// <option value="popup">Popup</option>
			// <option value="thumb">Thumbnails only</option>
			// <option value="disable">Disable</option>
			// $item_id = JRequest::getInt('Itemid');
	
			$blog_ids = $this->_params->get('blog_ids');
			if ($blog_ids) {
				if ( !is_array($blog_ids) ) {
					$blog_ids = (array)$blog_ids;
				}
			} else {
				$blog_ids = (array)null;
			}
				
			if ( /* preg_match ( "/^com_content./", $context ) && */ 
				( ( $this->_params->get('enable_blogs') == 0 ) ||
					( $this->_params->get('enable_blogs') == 2 && /* $blog_ids && */ !in_array($item_id, $blog_ids) ) )) {
	
				$this->_params->set('enable_thumbs', 0);
				$this->_params->set('blog_mode', 'disable');
	
				$this->_params->set('blog_mode', 'thumb');
	
				$this->_params->set('popup_type', 'nothumb' );
			} else {
				$this->_params->set('enable_thumbs', 1);
				if( ( $this->_params->get('blog_mode')=='thumb' || $this->_params->get('blog_mode')=='link' ) ) {
					$this->_params->set('popup_type', 'none');
				}
			}
				
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('blog_size', "200x150" ));
			$this->_params->set('thumb_width', $thumb_width);
			$this->_params->set('thumb_height', $thumb_height);
	
			$this->_params->set('thumbclass', $this->_params->get('thumbclass_blog') );
			$this->_params->set('css_blog',   $this->_params->get('css_blog') );
				
			// 
			if ( $this->_params->get('caption') ==1 ) {
				$this->_params->set('caption', 0);
			}
				
			$this->_params->set('thumb_size_first', '');
	
		} else {
			$this->_params->set('blog_mode', 'disable');
	
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('thumb_size', "150x100"));
			$this->_params->set('thumb_width', $thumb_width);
			$this->_params->set('thumb_height', $thumb_height);
				
			if ( $this->_params->get('caption') == 3 ) {
				$this->_params->set('caption', 0);
			}
		}
		// echo "DEBUG_:".$this->_params->get('blog_mode') ."<br/>";
	
	}

	function is_article_by_url()
	{
		$IS_ARTICLE_RULE=$this->_params->get('IS_ARTICLE_RULE', "option=com_content&view=article,option=com_flexicontent&view=items");
	
		$IS_ARTICLE_RULE=str_replace ("\n", "", $IS_ARTICLE_RULE);
		$IS_ARTICLE_RULE=str_replace ("\r", "", $IS_ARTICLE_RULE);
		$IS_ARTICLE_RULE=str_replace (" ", "", $IS_ARTICLE_RULE);
		$P1 = explode(",", $IS_ARTICLE_RULE);
		$this->is_article_rule = "(";
		foreach ($P1 as $val1) {
			$P2 = explode("&", $val1);
			$this->is_article_rule .= "(";
			foreach ($P2 as $val2) {
				list($cmd, $val) = explode("=", $val2);
				$this->is_article_rule .= "( JRequest::getCmd('$cmd') == '$val' ) AND ";
			}
			$this->is_article_rule .= "TRUE ) OR ";
		}
		$this->is_article_rule .= "FALSE )";
	
		eval( "\$is_article = $this->is_article_rule;" ) ;
	
		return $is_article;
	}
	
	function is_blog_by_url() {
		
		$IS_BLOG_RULE=$this->_params->get('IS_BLOG_RULE', "option=com_content&view=featured,option=com_content&layout=blog");
		$IS_BLOG_RULE=str_replace ("\n", "", $IS_BLOG_RULE);
		$IS_BLOG_RULE=str_replace ("\r", "", $IS_BLOG_RULE);
		$IS_BLOG_RULE=str_replace (" ", "", $IS_BLOG_RULE);		
		$P1 = explode(",", $IS_BLOG_RULE);
		$is_blog_rule = "(";
		foreach ($P1 as $val1) {
			$P2 = explode("&", $val1);
			$is_blog_rule .= "(";
			foreach ($P2 as $val2) {
				list($cmd, $val) = explode("=", $val2);
				$is_blog_rule .= "( JRequest::getCmd('$cmd') == '$val' ) AND ";
			}
			$is_blog_rule .= "TRUE ) OR ";
		}
		$is_blog_rule .= "FALSE )";
		
		eval( "\$is_blog = $is_blog_rule;" ) ;

		return $is_blog;
	}
	
		// Initializes plugin parameters
	function init_params ($params, $context) {
		$this->_params = $params;
		$this->_params->def('thumb_size_type', 0);
		$this->_params->def('shadowbox_headers', 1);
		$this->_params->def('highslide_headers', 1);
		$this->_params->def('lightbox_headers', 1);
		$this->_params->def('slimbox_headers', 1);
		$this->_params->def('prettyphoto_headers', 1);
		
		$this->_params->def('memory_limit', 'default');
		$this->_params->def('time_limit', '');

		// $this->_params->def('ignore_cats', '');
		$this->_params->def('only_classes', '');		
		$this->_params->def('only_tagged', 0);
		$this->_params->def('exclude_tagged', 0);
		$this->_params->def('enable_thumbs', 1);
		
		// $this->_params->def('thumb_width',150);
		// $this->_params->def('thumb_height',100);
		$this->_params->def('popup_type', 'slimbox');
		$this->_params->def('thumb_proportions','bestfit');
		$this->_params->def('thumb_bg', '#FFFFFF');
		$this->_params->def('border_size', '2px');
		$this->_params->def('border_color', '#000000');
		$this->_params->def('border_style', 'none');
		$this->_params->def('thumbclass', 'multithumb');
		
		$this->_params->def('resize',0);
		$this->_params->def('full_width',800);
		$this->_params->def('full_height',600);
		$this->_params->def('image_proportions','bestfit');
		$this->_params->def('image_bg', '#000000');
		
		$this->_params->def('blog_mode', 'link');
		$this->_params->def('enable_blogs', 1);
		$this->_params->def('max_thumbnails', 0);
		$this->_params->def('num_cols', 3);
		$this->_params->def('allow_img_toolbar',0);
		$this->_params->def('scramble', 'off');
		$this->_params->def('quality', 80);
		$this->_params->def('watermark_type', 0);
		$this->_params->def('watermark', 0);
		if ( !$this->_params->get('watermark_type') ) {
			$this->_params->set('watermark', 0);
		}
		$this->_params->def('watermark_file', '');
		if ( !$this->_params->get('watermark_file') ) {
			$this->_params->set('watermark', 0);
		}
		
		if(!$this->_params->get('watermark')) { // No watermark
			$this->_params->set('watermark_file', '');
		}
		// if(strpos($this->_params->get('watermark_file'), '://')) { // It's a url
			// $this->_params->set( 'watermark_file', 
					// str_replace( "/", DS, 
						// str_replace($this->_live_site, JPATH_SITE, 
							// $this->_params->get('watermark_file'))) );
		// }
		$this->_params->def('watermark_left', '');
		$this->_params->def('watermark_top', '');
		
		$this->watermark_cats = $this->_params->get('watermark_cats');
		if ($this->watermark_cats) {
			if ( !is_array($this->watermark_cats) ) {
				$this->watermark_cats = (array)$this->watermark_cats;
			}
		} else {
			$this->watermark_cats = (array)null;
		}
		
		$this->_params->def('transparency_type', 'alpha');
		$this->_params->set('transparent_color', hexdec($this->_params->get('transparent_color', '#000000')) );
		$this->_params->def('transparency', '25');
		
		$this->_params->def('error_msg', 'text');
 		$this->_params->def('css', ".multithumb {
    margin: 5px; 
    float: left; 
 }");

	$this->_params->def('caption_css', ".mtCapStyle figcaption {  
   caption-side: bottom; 
   font-weight: bold;   
   color: black;    
   background-color: #ddd;    
   text-align:center;    
}");

	$this->_params->def('gallery_css', ".mtGallery {
   margin: 5px;
   align: center;
   float: none;
}");


		if ( $this->_params->get('by_context' ) ) {
			$this->is_blog = $this->is_blog_by_context($context);
			$this->is_article = $this->is_article_by_context($context);
		} else {
			$this->is_article = $this->is_article_by_url();
			$this->is_blog = $this->is_blog_by_url();
		}
		
		$this->set_blog_params($context);
		
		// $this->_params->set('thumb_width', $thumb_width);
		// $this->_params->set('thumb_height', $thumb_height);
		
		$this->_params->def('caption_type', 'title');
    	switch ($this->_params->get('caption_type')) {
    		case "alt":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',0);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',0);
    			break;
			case "iptc_caption":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',0);
				$this->_params->set('caption_type_alt',0);
				break;
    		case "alt_or_title":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title_or_alt":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',2);
    			break;
    		case "iptc_caption_or_alt_or_title":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',3);
				$this->_params->set('caption_type_alt',2);
    			break;
    		case "iptc_caption_or_title_or_alt":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',3);
    			break;
    		case "alt_or_title_or_iptc_caption":
				$this->_params->set('caption_type_iptc',3);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title_or_alt_or_iptc_caption":
				$this->_params->set('caption_type_iptc',3);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',2);
    			break;
    	}

		$this->regex = '#<img[^>]*src=(["\'])([^"\']*)\1[^>]*>';
		$this->regex .= '|{multithumb([^}]*)}#is';
		
		$this->is_gallery = false;	
		

		// Preserve parameters set

		$this->_paramsDef = clone($this->_params);
		
	}
	
	function parse_size($size, $dimention = "" ) {
		$pieces = explode("x", $size);
		if ( isset($pieces[0]) && !isset($pieces[1]) ) {
			$height = $width = $pieces[0];
		} elseif ( isset($pieces[0]) && isset($pieces[1]) ) {
			$width = $pieces[0];
			$height = $pieces[1];
		} else {
			$width = $height = 0;
		}
		
		if ($dimention == "width") {
			return $width;
		} elseif ($dimention == "height") {
			return $height;
		} else {
			return array($width, $height);
		}
	}
	
	function is_blog_by_context($context) {
		
		$is_blog = false;
		
		$BLOG_CONTEXT=$this->_params->get('blog_context', "com_content.featured,com_content.category,mod_dn.featured,com_tags.tag" );
		$BLOG_CONTEXT=str_replace ("\n", "", $BLOG_CONTEXT);
		$BLOG_CONTEXT=str_replace ("\r", "", $BLOG_CONTEXT);
		$BLOG_CONTEXT=str_replace (" ", "", $BLOG_CONTEXT);
		$patterns = explode(",", $BLOG_CONTEXT);
		foreach ($patterns as $pattern) {
			if ( preg_match("/".$pattern."/", $context ) ) {
				$is_blog = true;
				break;
			}
		}
		
		return $is_blog;
		
	}
	
	function is_article_by_context($context) {
		
		$is_article = false;
		
		$ARTICLE_CONTEXT=$this->_params->get('article_context', "com_content.article,mod_custom.content,mod_articles_category.content,com_tag.tag" );
		$ARTICLE_CONTEXT=str_replace ("\n", "", $ARTICLE_CONTEXT);
		$ARTICLE_CONTEXT=str_replace ("\r", "", $ARTICLE_CONTEXT);
		$ARTICLE_CONTEXT=str_replace (" ", "", $ARTICLE_CONTEXT);
		$patterns = explode(",", $ARTICLE_CONTEXT);
		foreach ($patterns as $pattern) {
			if ( preg_match("/".$pattern."/", $context ) ) {
				$is_article = true;
				break;
			}
		}
		
		return $is_article;
		
	}

	function image_intro(&$images_json, $image_type, $params ) {

		$images = json_decode($images_json);
		// echo "DEBUG5:".$images_json."<br>";
		

		$image_intro = "";
		if (isset($images->{"image_${image_type}"}) && !empty($images->{"image_${image_type}"})) {
			$imgfloat = (empty($images->{"float_${image_type}"}) ? ( isset($params) ? $params->get("float_${image_type}") : "" ) : $images->{"float_${image_type}"});
			$image_intro .= "<div class=\"img-intro-$imgfloat\"><img ";
			if ($images->{"image_${image_type}_caption"}) {
				$image_intro .=  'class="caption"'.' title="' . $images->{"image_${image_type}_caption"} . '"';
			}
			$image_intro .= 'src="'.$images->{"image_${image_type}"}.'" alt="'.$images->{"image_${image_type}_alt"}.'"/></div>';
		}
		
		// echo "DEBUG4:".${image_type}."<br>";
		// unset( $images->{"image_${image_type}"} );
		if (isset($images->{"image_${image_type}"}) ) {
			$images->{"image_${image_type}"} = "";
		}
		
		$images_json = json_encode($images);
		
		return $image_intro;
		
	}
	
	public function onContentPrepare($context, &$article, &$params, $limitstart = 0) {
		
		// echo "DEBUG:$context<br/>";
		$this->init_all($context);
	
		if ( isset($this->_params) && $this->_params->get('context_debug' ) ) {
			echo "Multithumb onContentPrepare: context: $context, is_blog: $this->is_blog, is_article: $this->is_article,  <br/>";
		}
		
		// com_content blogs should be processed by onContentBeforeDisplay priour J3
		$jversion = new JVersion();
		if ( version_compare($jversion->getShortVersion(), '2.5.10', '<') && 
				$this->is_blog && 
				preg_match ( "/^com_content./", $context ) ) {
			return;
		}
	
		if ( ( $this->is_blog || $this->is_article ) ) {
			$this->_onPrepareContent ( $context, $article, $params, $limitstart);
		}
	}
	
	public function onContentBeforeDisplay($context, &$article, &$params, $limitstart = 0) {
		
		$jversion = new JVersion();
		if ( version_compare($jversion->getShortVersion(), '2.5.10', '>=') ) {
				return;
		}

		// return;
		
		if ( isset($this->_params) && $this->_params->get('context_debug' ) ) {
			echo "Multithumb onContentBeforeDisplay: context:$context, is_blog:$this->is_blog, is_article:$this->is_article<br/>";
		}
		
/*		if ( $this->_params->get('by_context' ) ) {
			return;
		}
		*/

		
		if ( isset($this->is_blog) and $this->is_blog && preg_match ( "/^com_content./", $context ) ) {
				
			// $text_save = @$article->text;
		// $article->text = $article->introtext;
			$this->_onPrepareContent ( $context, $article, $params, $limitstart);
		// $article->introtext = $article->text ;
		
		// $article->text = $text_save;
		}
	}
	
	// Process one article
	function _onPrepareContent ( $context, &$row, &$params, $page=0 ) {
		
		
		
		
		
	// Figure out the name of the text field
	
	/* if ( isset($row->core_body) ) {
		$text_field_name = 'core_body';
		}
	else*/ if ( isset($row->text) ) {
			$text_field_name = 'text';
			}
		elseif ( isset($row->fulltext) ) {
			$text_field_name = 'fulltext';
			}
		elseif ( isset($row->introtext) ) {
			$text_field_name = 'introtext';
			}
		else {
			// Unrecognized
			return false;
		}	
		
		// If plugin disabled or row parameters is not post remove plugin tags and stop processing
		if ( !$this->published /*|| !$params */) {
			$row->$text_field_name = preg_replace('#{(no)?multithumb([^}]*)}#i', '', $row->$text_field_name);

			return true;
		}
		
		static $rowid = 0;
		
		if ( isset( $row->id ) ) {
			$this->rowid = $row->id;
		} else {
			$this->rowid = ++$rowid;
		}
		
		if ( $this->rowid ) {
			$this->cont_num++;
                        // echo "DEBUG2: $this->cont_num<br/>";

		}
		
		

		// 
		if(	($this->_params->get('exclude_tagged' ) && preg_match('/{nomultithumb}/is', $row->$text_field_name)) ||
			($this->_params->get('only_tagged' )    && preg_match('/{multithumb([^}]*)}/is', $row->$text_field_name )==0 )) {
			$this->_params->set('blog_mode', 'thumb'); // TBD ???
			$row->$text_field_name = preg_replace('/{(no)?multithumb([^}]*)}/i', '', $row->$text_field_name);
			return true;
		}
		
		
		if ( /*preg_match ( "/^com_content./", $context ) && */ $this->is_article && $this->_params->get('enable_thumbs') < 4) {
			if ( preg_match('/{multithumb}/is', $row->$text_field_name)==0 ) {
				if ($this->_params->get('enable_thumbs') ) {
					// <param name="enable_thumbs" type="list" default="1" label="Thumbnails for articles"
					// <option value="1">Enable for allcategories</option>
					// <option value="2">Enable for following categories only</option>
					// <option value="3">Enable for all except following categories</option>
					// <option value="0">Disable</option>

					$only_cats = $this->_params->get('only_cats');
					if ($only_cats) {
						if ( !is_array($only_cats) ) {
							$only_cats = (array)$only_cats;
						}
					} else {
						$only_cats = (array)null;
					}

					if ( ( $this->_params->get('enable_thumbs')==0 ) ||
						 ( $this->_params->get('enable_thumbs')==2  && !in_array($row->catid, $only_cats) ) ||
						 ( $this->_params->get('enable_thumbs')==3  && in_array($row->catid, $only_cats) ) 
					   ) {
						$row->$text_field_name = preg_replace('/{(no)?multithumb([^}]*)}/i', '', $row->$text_field_name);
						
						$this->_params->set('popup_type', 'nothumb');				
					}
			} else { 
				$this->_params->set('popup_type', 'nothumb');	
			}
		}
	}

	
		
		// Cleanup NOmultithumb if it is ignored
		if (preg_match('/{nomultithumb}/is', $row->$text_field_name)!==false) { // BK
			$row->$text_field_name = preg_replace('/{nomultithumb}/i', '', $row->$text_field_name);
		}
		
		// PROCESS ROW
		
		$this->mt_thumbnail_count = array();
		$this->mt_gallery_count = 0;
		

		if ( isset ( $row->catid  ) and $this->_params->get('blog_mode')=='link' ) {
		
				if (!isset($row->params)) {
					$this->botMtLinkText = JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
				} elseif ($row->alternative_readmore) {
					$this->botMtLinkText = $row->alternative_readmore;
					if ($row->params->get('show_readmore_title', 0) != 0) {
						$this->botMtLinkText .= JHTML::_('string.truncate', $row->title, $row->params->get('readmore_limit'));
					}
				} elseif ($row->params->get('show_readmore_title')) {
					$this->botMtLinkText = JText::_('COM_CONTENT_READ_MORE').
						JHTML::_('string.truncate', $row->title, $row->params->get('readmore_limit'));
				} else {
					$this->botMtLinkText = JText::sprintf('COM_CONTENT_READ_MORE_TITLE');	
				}

				// article link 
				$this->botMtLinkOn = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catid )); // BK
			// } 
		}
			
		// echo "DEBUG".$this->_params->get('watermark_type')."<br/>";
		$this->watermark = $this->_params->get('watermark');
		if ( ! ( ( $this->_params->get('watermark_type')==1 ) or 
                 ( $this->_params->get('watermark_type')==2  && isset ( $row->catid ) &&  in_array($row->catid, $this->watermark_cats ) ) ||
			     ( $this->_params->get('watermark_type')==3  && isset ( $row->catid ) && !in_array($row->catid, $this->watermark_cats ) ) 
		      )) {
			// $this->_params->set('watermark', 0);
			$this->watermark = 0;
		}
		
		
		// if ( $this->watermark > 0 ) {
		//	$this->watermark = 1;
		// }
			
		// initialize error message
		$this->multithumb_msg = '';
		
		$this->imgnum = 0;

		if (isset($row->images) ) {
			$img_intro = $this->image_intro( $row->images, ( $this->is_article ? 'fulltext' : 'intro'), $params );
		} else {
			$img_intro = $this->image_intro( $row->core_images, ( $this->is_article ? 'fulltext' : 'intro'), $params );
		}
		
						
		// echo "DEBUG3:".print_r($row, true) ."<br/>";
		// PROCESS IMAGES OR INLINE PARAMETERS IN THE TEXT
		$row->$text_field_name = $img_intro . $row->$text_field_name;
		$row->$text_field_name = preg_replace_callback($this->regex, array($this,'bot_mt_replace_handler'), $row->$text_field_name);
			
		// echo "DEBUG31:".$text_field_name ."<br/>";
		
		// Print error messages
		if($this->multithumb_msg)
		switch($this->_params->get('error_msg')) {
			case 'popup':
				$row->$text_field_name .= "<script type='text/javascript' language='javascript'>alert('Multithumb found errors on this page:\\n\\n".$this->multithumb_msg."')</script>";
				break;
			case 'text':
				$this->multithumb_msg = str_replace('\\n', "\n", $this->multithumb_msg);
				$row->$text_field_name = "<div style='border:2px solid black; padding: 10px; background-color: white; font-weight: bold; color: red;'>Multithumb found errors on this page:<br /><br />\n\n".$this->multithumb_msg."</div>" . $row->$text_field_name;
		}

		// Update headers
		$this->botAddMultiThumbHeader('style');

		return true;
	}
	
	// Is called for each image or inline parameters in the text
    function bot_mt_replace_handler(&$matches) {
	  
		// echo "DEBUG5:".$this->_params->get('blog_mode') ."<br/>";
		
		//
    	// inline parameters processing
    	//
    	if(strtolower(substr($matches[0], 0, 11))=='{multithumb') {
    		// Just for remding: '|{multithumb([^}]*)}#is';
    		$this->inline_parms($matches[3]);

    		// go to the next match
    		return '';

    	}

    	// it's a normal image
    	return $this->image_replacer($matches);
    }


	// Process images in the text
    function image_replacer(&$matches) {
		
		// echo "DEBUG4:".$this->_params->get('blog_mode') ."<br/>";
		
    	// Current image tag
    	$imgraw = $imgrawOrg = $matches[0];

    	// Original path of current image
    	$imgloc = rawurldecode($matches[2]);

		// Captions parameters
    	$this->caption_type = $this->_params->get('caption_type');
    	 
    	$style=$alt=$title=$align=$class=$onclick=$img=$hspace=$vspace=$border=$inline_height=$inline_width='';

		// class
    	if(preg_match('#class=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$class = $temp[2];
			$imgraw = preg_replace('#class=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		
		// style
    	if(preg_match('#style=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$style = $temp[2];
			$imgraw = preg_replace('#style=(["\'])(.*?)\\1#i', "", $imgraw);
    	}

	if ( $style and !preg_match('/.*;\s*$/i', $style, $temp) ) {
		$style = $style.";";	
	}

		// border
		$border = '';

    	if (preg_match('#\bborder\b:\s*(.*?);#i', $style, $temp)) {
			$border = $temp[1];
			$style = preg_replace('#\bborder\b:\s*(.*?);#i', "", $style);
    	} elseif(preg_match('#\bborder\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$border = $temp[2]."px solid black";
			$imgraw = preg_replace('#\bborder\b=(["\'])(.*?)\\1#i', "", $imgraw);
	}


    	// align
    	if(preg_match('#align=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$align = $temp[2];
			$imgraw = preg_replace('#align=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		
    	// alt 
    	if(preg_match('#alt=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$alt = $temp[2];
			$imgraw = preg_replace('#alt=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		$orgAlt=$alt;
		
		// title
    	if(preg_match('#title=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$title = $temp[2];
			$imgraw = preg_replace('#title=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		$orgTitle = $title;
		
    	// Process alt parameter of image that is used for multithumb instructions as prefix separated by ":"
    	$this->set_popup_type($title, $alt);

		// Ignore images where mt_ignore specified in the alt text
		if ( $this->popup_type=='ignore' ) {
			return  preg_replace('/mt_ignore\s*:\s*/', '', $imgrawOrg);
		}


		// hspace
		if (preg_match('#hspace=(["\'])(.*?)\\1#i', $imgraw, $temp) ) {
			$hspace = $temp[2];
			$imgraw = preg_replace('#hspace=(["\'])(.*?)\\1#i', "", $imgraw);
		}

		// vspace
		if (preg_match('#vspace=(["\'])(.*?)\\1#i', $imgraw, $temp) ) {
			$vspace = $temp[2];
			$imgraw = preg_replace('#vspace=(["\'])(.*?)\\1#i', "", $imgraw);
		}

		// height
		if(preg_match('#\bheight\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$inline_height = $temp[2];
			$imgraw = preg_replace('#\bheight\b=(["\'])(.*?)\\1#i', "", $imgraw);
    	} elseif (preg_match('#(^|;)\s*\bheight\b:\s*([0-9]*)px;#i', $style, $temp)) {
			$inline_height = $temp[2];
			$style = preg_replace('#(^|;)\s*\bheight\b:\s*[^;]*;#i', '\1', $style);
		}
		// 

		// width
		if(preg_match('#\bwidth\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$inline_width = $temp[2];
			$imgraw = preg_replace('#\bwidth\b=(["\'])(.*?)\\1#i', "", $imgraw);
    	} elseif (preg_match('#(^|;)\s*\bwidth\b:\s*([0-9]*)px;#i', $style, $temp)) {
			$inline_width = $temp[2];
			$style = preg_replace('#(^|;)\s*\bwidth\b:\s*[^;]*;#i', '\1', $style);
		}
		
    	$this->set_sys_limits();

    	// Change image path from relative to full
    	$this->fix_path($imgloc, $imgurl);

    	// It's a gallery
    	if($this->popup_type=='gallery'){
    		$res = $this->gallery($imgloc, $imgurl, $alt, $title, "center", $class  );
			if ( $res === false ) {
				return $imgrawOrg;
			} else {
				return $res;
			}
    	}
		
		$this->imgnum++;
		
		if( ( ( $this->is_article and /* preg_match('/{multithumb([^}]*)}/is', $row->text)==0 and */ $this->_params->get('enable_thumbs') == 4 ) and  
			( !$this->_params->get('only_classes') or !preg_match  ( '/\b'.$this->_params->get('only_classes').'\b/', $class ) ) ) 
			or
			( ( $this->is_blog and $this->_params->get('enable_blogs') == 4 ) and  
			( !$this->_params->get('only_classes_blog') or !preg_match  ( '/\b'.$this->_params->get('only_classes_blog').'\b/', $class ) ) ) ) {
			
			$this->popup_type = 'nothumb';
		}
		
		// Resize or watermark full image
		$imgurlOrg = $imgurl;
			
		// Full image size
    	$full_width  = $this->_params->get('full_width');
    	$full_height = $this->_params->get('full_height');

    	if( !$this->_params->get('resize')) {
    		$full_width = $full_height = 0;
    	}
		
		$dummy = "";
    	// Resize image and/or set watermark
		
    	$imgtemp = self::botmt_thumbnail($imgurl, $full_width, $full_height, $this->_params->get('image_proportions'), hexdec($this->_params->get('image_bg')), (int)($this->watermark >= 1), 'images', 0 , /* $size, */ $this->_params->get('img_type', ""), 0, $dummy, $this );

    	// If image resized or watermarked use it instead of the original one
    	if($imgtemp) {
    		$imgurl = $imgtemp;
    		$real_width = $full_width;
    		$real_height = $full_height;
			preg_match('/(.*src=["\'])[^"\']+(["\'].*)/i', $imgraw, $parts);
			$imgraw = $parts[1].$imgurl.$parts[2];
    	} else {
    		$real_width = $full_width;
    		$real_height = $full_height;
		}

		// Ignore too small images or images with unknown size
		list ($min_img_width, $min_img_height) = $this->parse_size($this->_params->get('min_img_size', '20x20'));		
		if( !( ( $real_width==0 || $real_height==0 ||
				 ( $min_img_width  && ( $min_img_width  < $real_width ) ) ||
				 ( $min_img_height && ( $min_img_height < $real_height ) ) )  ) ) {
			return $imgrawOrg;  // just show the full image
		}			
			
		$imgraw = preg_replace('/(src=["\'])[^"\']+(["\'])/i', '', $imgraw);
		$imgraw = preg_replace('/^<img/', '', $imgraw);
		$imgraw = preg_replace('#/*>$#', '', $imgraw);
		
		$thumb_width = $this->_params->get('thumb_width');
		$thumb_height = $this->_params->get('thumb_height');

		if ( $this->_params->get('thumb_size_first') && $this->imgnum <= $this->_params->get('leading_num', 1) ) {
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('thumb_size_first'));
		}

		if ( $this->is_blog ) {
                        // echo "DEBUG: $this->cont_num<br/>";
			if ( $this->_params->get('blog_size_leading') && 
				$this->cont_num && 
				($this->cont_num <= $this->_params->get('blog_leading_num', 1) ) ) {
				list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('blog_size_leading'));
			}
		}

		if ( $this->is_gallery && $this->_params->get('gallery_thumb_size') ) {
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('gallery_thumb_size'));
		}

		if ( (( $this->is_article and $this->_params->get('use_image_size')) or 
		      ($this->is_blog and $this->_params->get('use_image_size_blog')) ) and ($inline_width or $inline_height ) ) {
			$thumb_width  = 0;
			$thumb_height = 0;
			if ($inline_width) {
				$thumb_width = $inline_width;
			}
			if ( $inline_height ) {
				$thumb_height = $inline_height;
			}
		}
		
    	if( !( ( $real_width==0 || $real_height==0 ||
				 ($thumb_width  && ($thumb_width  < $real_width) ) ||
				 ($thumb_height && ($thumb_height < $real_height) )  )  ) ) {
				
			   
				if ($this->_params->get('force_popup') ) {
					$thumb_width = $real_width;
					$thumb_height = $real_height;
				} else {
					$this->popup_type = 'none';
				}
    	} 

				$iptc_caption = '';		
		{
    		// Process the varius popup methods

			$this->caption = '';
    		// Create thumb
			if ( $this->popup_type == 'nothumb' ) {
				$thumb_file = $imgurl;
				
				if ( $inline_width or $inline_height ) {
					$thumb_size = '';
					
					if ($inline_width) {
						$thumb_width = $inline_width;
						$thumb_size .= ' width="'. $thumb_width .'"';
					}
					if ( $inline_height ) {
						$thumb_height = $inline_height;
						$thumb_size .= ' height="'. $thumb_height .'"';
					}
				} elseif ( $real_width and $real_height ) {
					$thumb_width = $real_width;
					$thumb_height = $real_height;
					$thumb_size = ' width="'. $thumb_width .'" height="'. $thumb_height .'"';
				} else {
					$thumb_size = '';
				}

			} else {

				$zoomin = ( $this->_params->get('magnify_type') == 2 or $this->_params->get('magnify_type') == 3 ) && 
					( $this->popup_type != "nothumb" ) && 
					( $this->popup_type != "none" ) && 
					( $this->popup_type != "expando" );
				


				
				$temp_file = self::botmt_thumbnail($imgurlOrg, $thumb_width, $thumb_height, $this->_params->get('thumb_proportions'), hexdec($this->_params->get('thumb_bg')), (int)($this->watermark == 2), 'thumbs', $this->popup_type == "expando" , /* $size, */ $this->_params->get('img_type', ""), $zoomin, $iptc_caption, $this );


				if ( $temp_file ) {
					$thumb_file = $temp_file;
					$thumb_size = ' width="'. $thumb_width .'" height="'. $thumb_height .'"';
					// return true;
				} else {
					$thumb_file = $imgurl;
					$thumb_size = '';
					if ( $thumb_width ) {
						$thumb_size .= ' width="'. $thumb_width .'" ';
					}
					if ( $thumb_height ) {
						$thumb_size .= ' height="'. $thumb_height .'" ';
					}
				}
			}

			// Define caption text
			$this->caption = $this->set_caption($alt, $title, $iptc_caption, 
				substr(basename($imgurl), 0, strlen(basename($imgurl))-4) );
			
			// Include java scripts
    		$this->botAddMultiThumbHeader($this->popup_type);

			//
			// Build popup tag
			//

			if ( $this->_params->get('magnify_type') == 1 or $this->_params->get('magnify_type') == 3 ) {
				$cursor_style = 'style="cursor: url(\''.$this->_live_site.'/plugins/content/multithumb/magnify.cur\'), auto;"';
			} else {
				$cursor_style = "";
			}

			switch ( $this->_params->get('group_images', 0) ) {
				case 1:
// 					if ( $this->is_gallery ) {
//						$rel = $alt;
//					}
 					$rel = $this->rowid;
					break;
				case 2:
					$rel = 'page';
					break;
				case 3:
 					$rel = $this->rowid."_".$alt;
					break;
				case 0:
				default:
					static $i = 0;
					$rel = $i++;
			}

			if ( $this->is_gallery and $this->_params->get('group_images_gal')) {
				$rel = $alt;
			}
			
			// Start popup link
    		switch($this->popup_type) {
    			case 'normal': // Normal popup
    				/* $imgtemp  */ $img = "<a target=\"_blank\" href=\"$imgurl\" onclick=\"this.target='_self';this.href='javascript:void(0)';thumbWindow( '".JURI::base(false)."' , '$imgurl','$alt',$real_width,$real_height,0,".$this->_params->get('allow_img_toolbar').");\" ".$cursor_style." >";
    				break;
				case 'iLoad': 
					if ( $this->_params->get('group_images', 0) or $this->is_gallery) {
						$rel = $this->_params->get('iload_splitSign', '|').$rel;
					} else {
						$rel = '';
					}
					$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.$rel.'" title="'.$this->caption.'" '.$cursor_style.' >';
					break;
				case 'thumbnail':
					$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.'" title="'.$this->caption.'" '.$cursor_style.' >';
					break;
				case 'modal':
					$img = '<a target="_blank" href="'.$imgurl.'" class="'.$this->popup_type.'" title="'.$this->caption.'" '.$cursor_style.' >';
					break;
	    		case 'lightbox': // Lightbox
    			case 'slimbox': // Slimbox
					if ( $this->_params->get('group_images', 0) or $this->is_gallery ) {
						$img = '<a target="_blank" href="'.$imgurl.'" rel="lightbox['.$rel.']" title="'.$this->caption.'" '.$cursor_style.' >';
					} else {
						$img = '<a target="_blank" href="'.$imgurl.'" rel="lightbox" title="'.$this->caption.'" '.$cursor_style.' >';
					}
    				break;
    			// case 'highslide': // 
    			case 'shadowbox': 
    			case 'prettyPhoto': 
					if ( $this->_params->get('group_images', 0) or $this->is_gallery ) {
						$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.'['.$rel.']" title="'.$this->caption.'" '.$cursor_style.' >';
					} else {
						$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.'" title="'.$this->caption.'" '.$cursor_style.' >';
					}
    				break;
					
    			case 'highslide': // 
					if ( $this->_params->get('group_images', 0)  or $this->is_gallery ) {
						$img = '<a target="_blank" href="'.$imgurl.'" onclick="return hs.expand(this, { slideshowGroup: \''.$rel.'\'})" title="'.$this->caption.'" '.$cursor_style.' >';
					} else {
						$img = '<a target="_blank" href="'.$imgurl.'" onclick="return hs.expand(this)" title="'.$this->caption.'" '.$cursor_style.' >';
					}
					
					
					

					
					$class .= " highslide";
					break;
					
    			case 'greybox': // Greybox
    				$img = '<a target="_blank" href="'.$imgurl.'" rel="gb_imageset['.$rel.']" title="'.$this->caption.'" '.$cursor_style.' >';
    				break;
    			case 'expansion': // Thumbnail expansion
    				$thumb_size = ''; // No size attr for thumbnail expansion!
    				$img = '<a href="javascript:void(0);">';
    				$onclick = "onclick=\"return multithumber_expand(this)\"";
    				$onclick .= "lowsrc=\"$imgurl\" ".$cursor_style." ";
    				break;
    			case 'expando': // Thumbnail expansion
    				$thumb_file = '';
    				$class .= " expando";
    				break;
    			// case 'thickbox': // Thickbox
    				// /* $imgtemp  */ $img = '<a target="_blank" href="'.$imgurl.'" rel="'.$alt.'" alt="'.$this->caption.'" title="'.$this->caption.'" class="thickbox">';
    				// if($this->_params->get('max_thumbnails')) {
    					// if(!isset($this->mt_thumbnail_count[$alt])) {
    						// $this->mt_thumbnail_count[$alt] = 0;
    					// }
    					// $this->mt_thumbnail_count[$alt]+=1;
    					// if($this->mt_thumbnail_count[$alt]>$this->_params->get('max_thumbnails')) {
							// $call_counter--;
    						// return /* $imgtemp  */ $img."</a>\n";
    					// }
    				// }
    				// break;
				case 'widgetkit':
					if ( $this->_params->get('group_images', 0) or $this->is_gallery ) {
				    	$img = '<a target="_blank" href="'.$imgurl.'" data-lightbox="group:'.$rel.'" title="'.$this->caption.'" '.$cursor_style.'>'; 
					} else {
				    	$img = '<a target="_blank" href="'.$imgurl.'" data-lightbox="on" title="'.$this->caption.'" '.$cursor_style.'>'; 
					}
					break;
				case 'nothumb':
    			case 'none': // No popup, just thumbnail
    			default:
    				/* $imgtemp  */ $img = '';
			}
			
    		switch($this->popup_type) {
    			case 'iLoad': // Normal popup
     			case 'lightbox': // Lightbox
    			case 'shadowbox': 
    			case 'prettyPhoto':
				case 'greybox':				
				case 'widgetkit':
					if ( $this->is_gallery and $this->_params->get('group_images_gal') and $this->_params->get('max_thumbnails_gal') ) {
						if(!isset($this->mt_thumbnail_count[$rel])) {
							$this->mt_thumbnail_count[$rel] = 0;
						}
						$this->mt_thumbnail_count[$rel]+=1;
						if($this->mt_thumbnail_count[$rel]>$this->_params->get('max_thumbnails_gal')) {
							if ( $this->_params->get('more_images', 0) and
									$this->mt_thumbnail_count[$rel]-1 == $this->_params->get('max_thumbnails_gal') ) {
								$img .= "<span class=\"more_images\">".$this->_params->get('more_images_text', JText::_("MORE_IMAGES_TITLE"))."</span>";
							}
							return /* $imgtemp  */ $img."</a>";
						}
					} elseif( $this->_params->get('max_thumbnails') && $this->_params->get('group_images') ) {
						if(!isset($this->mt_thumbnail_count[$rel])) {
    						$this->mt_thumbnail_count[$rel] = 0;
    					} 
    					$this->mt_thumbnail_count[$rel]+=1;
    					if($this->mt_thumbnail_count[$rel]>$this->_params->get('max_thumbnails')) {
							if ( $this->_params->get('more_images', 0) and 
								 $this->mt_thumbnail_count[$rel]-1 == $this->_params->get('max_thumbnails') ) {
								$img .= "<span class=\"more_images\">".$this->_params->get('more_images_text', JText::_("MORE_IMAGES_TITLE"))."</span>";
							}
    						return /* $imgtemp  */ $img."</a>";
    					}
    				}
					break;
			}

			//Img class
			if ( $this->popup_type != 'nothumb' /* and 
					$this->popup_type != 'none' */ ) {
				if ( $this->is_gallery ) {
					$class .= " ".$this->_params->get('gallery_class');
				} else {
					$class .= " ".$this->_params->get('thumbclass');
				}
				
			}
			

			
			if($thumb_file) { // If thumb generated show it
				$img .= '<img src="'.$thumb_file.'" '.$imgraw." ";
			} else { // If thumb is not generated row image
				$img .= '<img src="'.$imgurl.    '" '.$imgraw." ";
			}
			
			
			$img .= $thumb_size.'  '.$onclick.' ';
			

			
			// Border 
			$bordercss = '';
			
			if( $this->_params->get('border_style') == 'none' ) {
				if ( $border ) {
					$bordercss = 'border: '.$border.';';
					$style .= $bordercss;
				}
			} elseif( $this->_params->get('border_style')!='remove' ) {
					$border=$this->_params->get('border_size').' '.$this->_params->get('border_style').' '.$this->_params->get('border_color');
					$bordercss = 'border: '.$border.';';
					
					$style .= $bordercss;
			}
			
			// }
			
			// margin
/* 			if (!$margin && ($hspace || $vspace) ) {
				$margin = "margin:";
				if ($vspace) {
					$margin .= $vspace."px ";
				} else {
					$margin .= "0px ";
				}
				
				if ($hspace) {
					$margin .= $hspace."px ;";
				} else {
					$margin .= "0px ;";
				}
			} */
			
			if ( $style ) {
				$style='style="'.$style.'"';
			}
			
			if ( $align ) {
				$align='align="'.$align.'"';
			}
			
			if ( $hspace ) {
				$hspace='hspace="'.$hspace.'"';
			}
			
			if ( $vspace ) {
				$vspace='vspace="'.$vspace.'"';
			}
			
			if ( !$alt ) {
				$alt=$title;
			}
		
			if ( !$alt ) {
				$alt=basename($thumb_file);
			}
						
			if ( $alt ) {
				$alt='alt="'.$alt.'"';
			}
			

			if ( $title ) {
				$title='title="'.$title.'"';
			}
		
			if ( $border != "" ) {
				$border='border="'.$border.'"';
			}
		

			$img .= " $alt $title ";

			$class = trim($class);
			if ( $this->_params->get('caption') && $this->caption) {
				$class .= " ".$this->_params->get('caption_class');
			}
			
			$imgclass = '';
			if( $class ) {
				$imgclass = 'class="'.$class.'" ';
			}

			// if no caption image will have border propertiese
			if ( !($this->_params->get('caption') && $this->caption) ) {
				$img .= " $imgclass $style $align $hspace $vspace ";
			}

			// finishing image
			$img .= "/>";
			
			// finishing popup link
			switch($this->popup_type) {
				case 'expando':
				case 'nothumb';
    			case 'none': // No popup, just thumbnail
					break;
				default:
					$img .= '</a>';
			}

			// caption processing
			if ( $this->_params->get('caption') &&  $this->caption) {

				// W/A 
				$style = preg_replace('#display\s*:\s*block\s*;#i', '', $style);

				$caption_style = ' style=" ';

				$caption_style .= "width:".$thumb_width."px; height:0px;"; 

				$caption_style .= '"';
				
				
				$img = '<figure '.$imgclass.' '.$style.' '.$align.' '.$hspace.' '.$vspace.'>'.
					'<figcaption>'.$this->caption.'</figcaption>'.
					$img.
					'</figure>';
				

			}

		}

		// article link for blog
		if( $this->_params->get('blog_mode')=='link' ) {
			$img = $this->bot_mt_makeFullArticleLink($img, $orgAlt, $orgTitle );
		}

		return $img;
	}
	
	
	function create_watermark($sourcefile_id ,  $watermarkfile, 
								$horiz_position, $horiz_shift, 
								$vert_position, $vert_shift, 
								$transparency_type = 'alpha', $transcolor = false, $transparency = 100 ) {
		static $disable_wm_ext_warning, $disable_wm_load_warning, $disable_alpha_warning;
		
		
		if($transparency_type == 'alpha') {
			$transcolor = FALSE;
		} 

		//Get the resource ids of the pictures
		$fileType = strtolower(substr($watermarkfile, strlen($watermarkfile)-3));
		switch($fileType) {
			case 'png':
				$watermarkfile_id = @imagecreatefrompng($watermarkfile);
				break;
			case 'gif':
				$watermarkfile_id = @imagecreatefromgif($watermarkfile);
				break;
			case 'jpg':
				$watermarkfile_id = @imagecreatefromjpeg($watermarkfile);
				break;
			default:
				$watermarkfile = basename($watermarkfile);
				if(!$disable_wm_ext_warning) $this->multithumb_msg .= "You cannot use a .$fileType file ($watermarkfile) as a watermark<br />\\n";
				$disable_wm_ext_warning = true;
				return false;
		}
		if(!$watermarkfile_id) {
			if(!$disable_wm_load_warning) $this->multithumb_msg .= "There was a problem loading the watermark $watermarkfile<br />\\n";
			$disable_wm_load_warning = true;
			return false;
		}

		@imageAlphaBlending($watermarkfile_id, false);
		$result = @imageSaveAlpha($watermarkfile_id, true);
		if(!$result) {
			if(!$disable_alpha_warning) $this->multithumb_msg .= "Watermark problem: your server does not support alpha blending (requires GD 2.0.1+)<br />\\n";
			$disable_alpha_warning = true;
			imagedestroy($watermarkfile_id);
			return false;
		}

		//Get the sizes of both pix
		$sourcefile_width=imageSX($sourcefile_id);
		$sourcefile_height=imageSY($sourcefile_id);
		$watermarkfile_width=imageSX($watermarkfile_id);
		$watermarkfile_height=imageSY($watermarkfile_id);

		switch ($horiz_position) {
		case 'center':
			$dest_x = ( $sourcefile_width / 2 ) - ( $watermarkfile_width / 2 );
			break;
		case 'left':
			$dest_x = $horiz_shift;
			break;
		case 'right':
			$dest_x = $sourcefile_width - $watermarkfile_width + $horiz_shift;
			break;
		}

		switch ($vert_position) {
		case 'middle':
			$dest_y = ( $sourcefile_height / 2 ) - ( $watermarkfile_height / 2 );
			break;
		case 'top':
			$dest_y = $vert_shift;
			break;
		case 'bottom':
			$dest_y = $sourcefile_height - $watermarkfile_height + $vert_shift;
			break;
		}
			
		// if a gif, we have to upsample it to a truecolor image
		if($fileType == 'gif') {
			// create an empty truecolor container
			$tempimage = imagecreatetruecolor($sourcefile_width, $sourcefile_height);

			// copy the 8-bit gif into the truecolor image
			imagecopy($tempimage, $sourcefile_id, 0, 0, 0, 0, $sourcefile_width, $sourcefile_height);

			// copy the source_id int
			$sourcefile_id = $tempimage;
		}

		if($transcolor!==false) {
			imagecolortransparent($watermarkfile_id, $transcolor); // use transparent color
			imagecopymerge($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height, $transparency);
		} else {
			imagecopy($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height); // True alphablend
		}

		imagedestroy($watermarkfile_id);
			
	}
	
	static function calc_size($origw, $origh, &$width, &$height, &$proportion, &$newwidth, &$newheight, &$dst_x, &$dst_y, &$src_x, &$src_y, &$src_w, &$src_h) {
		
		if(!$width ) {
			$width = $origw;
			// $newwidth = $width;
		}

		if(!$height ) {
			$height = $origh;
			// $newheight = $height;
		}

		$dst_x = $dst_y = $src_x = $src_y = 0;

		if (  $proportion == 'stretch' ) {
			$src_w = $origw;
			$src_h = $origh;
			$newwidth = $width;
			$newheight = $height;
			return;
		}
				
		if ( $height > $origh ) {
			$newheight = $origh;
			$height = $origh;
		} else {
			$newheight = $height;
		}
		
		if ( $width > $origw ) {
			$newwidth = $origw;
			$width = $origw;
		} else {
			$newwidth = $width;
		}
		
		switch($proportion) {
			case 'fill':
			case 'transparent':
				$xscale=$origw/$width;
				$yscale=$origh/$height;

				// Recalculate new size with default ratio
				if ($yscale<$xscale){
					$newheight =  round($origh/$origw*$width);
					$dst_y = round(($height - $newheight)/2);
				} else {
					$newwidth = round($origw/$origh*$height);
					$dst_x = round(($width - $newwidth)/2);

				}

				$src_w = $origw;
				$src_h = $origh;
				break;

			case 'crop':

				$ratio_orig = $origw/$origh;
				$ratio = $width/$height;
				if ( $ratio > $ratio_orig) {
					$newheight = round($width/$ratio_orig);
					$newwidth = $width;
				} else {
					$newwidth = round($height*$ratio_orig);
					$newheight = $height;
				}
					
				$src_x = ($newwidth-$width)/2;
				$src_y = ($newheight-$height)/2;
				$src_w = $origw;
				$src_h = $origh;				
				break;
				
 			case 'only_cut':
				// }
				$src_x = round(($origw-$newwidth)/2);
				$src_y = round(($origh-$newheight)/2);
				$src_w = $newwidth;
				$src_h = $newheight;
				
				break; 
				
			case 'bestfit':
				$xscale=$origw/$width;
				$yscale=$origh/$height;

				// Recalculate new size with default ratio
				if ($yscale < $xscale){
					$newheight = $height = round($width / ($origw / $origh));
				}
				else {
					$newwidth = $width = round($height * ($origw / $origh));
				}
				$src_w = $origw;
				$src_h = $origh;	
				
				break;
			}
	}
	
	static function getremoteimage($url, &$me = null ) {

	    $headers = array(
	    "Range: bytes=0-32768"
	    );

        $url = str_replace(' ', '%20', $url);
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    // curl_setopt($curl, CURLOPT_HEADER, 0);
		
		curl_setopt($curl, CURLINFO_CONTENT_TYPE, 1);	
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
	    $data = curl_exec($curl);
		// echo "DEBUG:".print_r(curl_getinfo($curl), true );
		// $mime = curl_getinfo($curl)['content_type'];
		$mime = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
		curl_close($curl);
			
		$im = imagecreatefromstring($data);
		if ( isset( $me ) && !$im ) {
			$me->multithumb_msg .= "There was a problem loading image $url<br/>\\n";
			return false;
		}
			
		$origw = imagesx($im);
		$origh = imagesy($im);
		
	    return array($origw, $origh, 'mime' => $mime, 'im' => $im );	

	}
	
	static function getlocalimage($filename, &$info, &$me = null)
	{
				
		$size = getimagesize($filename, $info);
  	
		if(!$size) {
			if ( isset( $me ) ) {				
				$me->multithumb_msg .= "There was a problem loading image '$filename'<br/>\\n";
			}
			return false;
		}	
		
		switch(strtolower($size['mime'])) {
			case 'image/png':
				$imagecreatefrom = "imagecreatefrompng";
				break;
			case 'image/gif':
				$imagecreatefrom = "imagecreatefromgif";
				break;
			case 'image/jpeg':
				$imagecreatefrom = "imagecreatefromjpeg";
				break;
			case 'image/svg':
				return false;
			default:
				if ( isset( $me ) ) {
					$me->multithumb_msg .= "Unsupported image type $filename ".$size['mime']."<br />\\n";
				}
				return false;
		}

		
		if ( !function_exists ( $imagecreatefrom ) ) {
			if ( isset( $me ) ) {
				$me->multithumb_msg .= "Failed to process $filename. Function $imagecreatefrom doesn't exist.<br />\\n";
			}
			return false;
		}
	   
	    $src_img = @$imagecreatefrom($filename);
		
		if ( !$src_img && isset( $me ) ) {
			$me->multithumb_msg .= "There was a problem to process image $filename ".$size['mime']."<br />\\n";
		}
		
		$size['im'] = $src_img;
				
	    return $size;	
	}
	
	
	public static function botmt_thumbnail($filename, &$width, &$height, $proportion='bestfit', $bgcolor = 0xFFFFFF, 
							$watermark = 0, $dest_folder = 'thumbs', $size_only = 0, /* $size = 0, */ $img_type = "", $zoomin = 0, 
							&$iptc_caption = NULL, plgContentMultithumb &$me = NULL ) {


		static $disablegifwarning, $disablepngwarning, $disablejpgwarning, $disablepermissionwarning;
		$ext = pathinfo($filename , PATHINFO_EXTENSION ); // BK
		// echo "DEBUG_:".$me->_params->get('blog_mode') ."<br/>";
		
		
		$prefix = '';
		$prefix = substr($proportion,0,1) . "_".$width."_".$height."_".$bgcolor."_".$watermark.(int)$zoomin."_";
		
		if ( $img_type ) {
			$thumb_ext = $img_type;
		} else {
			$thumb_ext = $ext;
		}
		
		$alt_filename = '';
		if($dest_folder=='thumbs') {
			$alt_filename = substr($filename, 0, -(strlen($ext)+1)) . '.thumb.' . $ext;
			if(file_exists($alt_filename)) {
				$filename = $alt_filename;
			}
		}
		$dest_folder='thumbs';
		
		$thumb_file = $prefix . str_replace(array( JPATH_ROOT, ':', '/', '\\', '?', '&', '%20', ' '),  '_' ,substr($filename, 0, -(strlen($ext)+1)).'.'.$thumb_ext); // BK
		if ( isset( $me ) ) {
			switch($me->_params->get('scramble')) {
				case 'md5': $thumb_file = md5($thumb_file) . '.' . $thumb_ext; break;
				case 'crc32': $thumb_file = sprintf("%u", crc32($thumb_file)) . '.' . $thumb_ext;
			}
		}
		
		$thumbname = JPATH_CACHE. "/multithumb_$dest_folder/$thumb_file"; // BK
		if (file_exists($thumbname)) {
			
			if ( isset( $me ) and ( $me->_params->get('caption_type_iptc') or $me->_params->get('caption_type_gallery_iptc') ) ) {
				$iptc_caption = file_get_contents($thumbname.".iptc_caption.txt");
			}

			if ( !filesize($thumbname) ) {
				list($width,$height) = explode( " " , file_get_contents($thumbname.".size") );
				return $filename;
			}
			
			$size = @getimagesize($thumbname);
			if($size) {
				$width = $size[0];
				$height = $size[1];
			}

			
			
			if (is_link(JPATH_BASE."/images/multithumb_".$dest_folder)) { 
				return JURI::base(false)."images/multithumb_$dest_folder/" . basename($thumbname); // BK
			} else {
				return JURI::base(false)."cache/multithumb_$dest_folder/" . basename($thumbname); // BK
			}
		}

		$info = NULL;
			
			
		if(!(strpos($filename, 'http:') === false)) {
			$size = self::getremoteimage($filename, $me);
		} else {
			$size = self::getlocalimage($filename, $info, $me);
		}
			
  		if(!$size) {
			return false;
		}
		
		$origw = $size[0];
		$origh = $size[1];		
			
		// echo $origw." x ".$origh." ({$filename})<br/>";

		{
	
			self::calc_size($origw, $origh, $width, $height, $proportion, $newwidth, $newheight, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $me);

			if ( $size_only ) { // TODO. Avoid access to thumb for $size_only
				return true;
			}
						
		 	$src_img = $size['im'];
		
			if (!$src_img) {
				return false;
			}
			
			$dst_img = ImageCreateTrueColor($width, $height);
			
			
			imagefill( $dst_img, 0,0, $bgcolor);
			if ( $proportion == 'transparent' ) {
				imagecolortransparent($dst_img, $bgcolor);
			}
			
			imagecopyresampled($dst_img,$src_img, $dst_x, $dst_y, $src_x, $src_y, $newwidth, $newheight, $src_w, $src_h);

			// watermark image
			if(isset( $me ) && $watermark) {
				$me->create_watermark($dst_img, $me->_params->get('watermark_file'), 
					$me->_params->get('watermark_horiz_type', 'center'), $me->_params->get('watermark_left', 0), 
					$me->_params->get('watermark_vert_type', 'middle'),  $me->_params->get('watermark_top', 0), 
					$me->_params->get('transparency_type'), $me->_params->get('transparent_color'), $me->_params->get('transparency'));

			}
			

			if ( isset( $me ) && $zoomin ) {
			
				switch ($me->_params->get('zoomin_position') ) {
					// <option value="0">MAGNIFYING_CENTER</option>
					case 0;
						$zoomin_position_horiz = 'center';
						$zoomin_position_vert = 'middle';
						break;
					// <option value="1">MAGNIFYING_LEFT_TOP</option>
					case 1;
						$zoomin_position_horiz = 'left';
						$zoomin_position_vert = 'top';
						break;					// <option value="2">MAGNIFYING_RIGHT_TOP</option>
					case 2;
						$zoomin_position_horiz = 'right';
						$zoomin_position_vert = 'top';
						break;					// <option value="3">MAGNIFYING_RIGHT_BOTTOM</option>
					case 3;
						$zoomin_position_horiz = 'right';
						$zoomin_position_vert = 'bottom';
						break;					// <option value="4">MAGNIFYING_LEFT_BOTTOM</option>
					case 4;
						$zoomin_position_horiz = 'left';
						$zoomin_position_vert = 'bottom';
						break;				}
			
				$me->create_watermark($dst_img, $me->_params->get('zoomin_file', 'plugins/content/multithumb/zoomin.png'), 
					$zoomin_position_horiz, 0,
					$zoomin_position_vert, 0
					);
			}
			
		// Make sure the folder exists
			
			
			switch(strtolower($thumb_ext)) {
				case 'png':
					$imagefunction = "imagepng";
					break;
				case 'gif':
					$imagefunction = "imagegif";
					break;
				default:
					$imagefunction = "imagejpeg";
			}
			



			if($imagefunction=='imagejpeg') {
				$result = @$imagefunction($dst_img, $thumbname, isset($me) ? $me->_params->get('quality') : 80 );
			} else {
				$result = @$imagefunction($dst_img, $thumbname);
			}


			if(!$result) {
				
				// BK This code proceses the case when   storage is not created yet.
				// It means that first time a first image will not be creted.
				// If the folder doesn't exist try to create it
				$dir = JPATH_CACHE."/multithumb_".$dest_folder;
				if (!is_dir($dir)) {
				
					// error_log("Multithumb cache creation. If you see this message on screen set Display Errors to Off");
					// Make sure the index file is there
					$indexFile      = $dir . '/index.html';
					$mkdir_rc = mkdir($dir) && file_put_contents($indexFile, '<html><body bgcolor="#FFFFFF"></body></html>');
					
				    if (!is_link(JPATH_BASE."/images/multithumb_".$dest_folder)) { 
						symlink("../cache/multithumb_".$dest_folder, JPATH_BASE."/images/multithumb_".$dest_folder);
					}
						
					if( !$mkdir_rc && !$disablepermissionwarning) {
						isset( $me ) && $me->multithumb_msg .= "Could not create image storage: ".$dir."<br />\\n"; // BK
					}
						
					if($imagefunction=='imagejpeg') {
						$result = $imagefunction($dst_img, $thumbname, $me ? $me->_params->get('quality') : 80 );
					} else {
						$result = $imagefunction($dst_img, $thumbname);
					}
						
				}
				
				if ( !$result ) {
					if(!$disablepermissionwarning) {
						isset( $me ) && $me->multithumb_msg .= "Could not create image:\\n$thumbname.\\nCheck if you have write permissions in ".JPATH_CACHE."/multihumb_$dest_folder/<br />\\n"; // BK
					}
					$disablepermissionwarning = true;
				}
			
			} else {
				imagedestroy($dst_img);
			}
			imagedestroy($src_img);
			
			$iptc_caption = '';
			if ( $info && isset($info["APP13"])) { 
				$iptc = iptcparse($info["APP13"]); 
				if (is_array($iptc)) { 
					$iptc_caption = @utf8_encode($iptc["2#120"][0]); 
				}
			}	
			if ( isset( $me ) and ( $me->_params->get('caption_type_iptc') or $me->_params->get('caption_type_gallery_iptc') )) {
				file_put_contents($thumbname.".iptc_caption.txt", $iptc_caption);
			}			

		}
		
		if( ($width!=0 and $origw<=$width ) and ( $height<>0 and $origh<=$height)) { // BK
			$width = $origw;
			$height = $origh;
			
			if ( !$watermark ) {
				file_put_contents($thumbname, '');
				file_put_contents($thumbname.".size", "$origw $origh");
				return false; 
			}
		}
		
		if (is_link(JPATH_BASE."/images/multithumb_".$dest_folder)) { 
			return JURI::base(false)."images/multithumb_$dest_folder/" . basename($thumbname); // BK
		} else {
			return JURI::base(false)."cache/multithumb_$dest_folder/" . basename($thumbname); // BK
		}
    }
	
    function botAddMultiThumbHeader($headertype) {
    	// global $cur_template;
    	$document 	= JFactory::getDocument();
    	// static $libs;


		static $headers;

    	if($headertype=='style' && !isset($headers['style'])) {
    		$headers['style'] = 1;
    		$document->addStyleDeclaration( "/* ".$this->botmtversion." */\n" . str_replace(array('<br />', '\[', '\]', '&nbsp;'), array("\n", '{', '}', ' '), 
										$this->_params->get('css', '')."\n".
										$this->_params->get('css_blog', '')."\n".
										$this->_params->get('gallery_css', '')."\n".
										$this->_params->get('caption_css', '')));
    	}

    	$header = '';

    	switch($headertype) {
			case 'jquery':
                                // Add JavaScript Frameworks   
								$jversion = new JVersion();   
                                if ( version_compare($jversion->getShortVersion(), '3.0.0', '>=') ){
                                    JHtml::_('jquery.framework');
                                } else if ( $this->_params->get('jquery_headers', 1) && !isset($headers[$headertype]) ) {
    					$headers[$headertype]=1;			
					// jquery core, and no conflict directly after
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/jquery/jquery-'.$this->_params->get('jquery_version', '1.6.1').'.min.js' );
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/jquery/jquery.no.conflict.js' );
				}
				break;
			case 'highslide':
				// if ( $this->_params->get('highslide_headers', 1) && !isset($headers[$headertype]) ) {
    				// $headers[$headertype]=1;			
					// $document->addScript( $this->_live_site.'/plugins/content/multithumb/highslide/highslide.js?full=true' );
    				
					// $document->addScriptDeclaration( "hs.graphicsDir = '".$this->_live_site."/plugins/content/multithumb/highslide/graphics/';" );
					// $document->addScriptDeclaration( "hs.align = '".$this->_params->get('hs_align', 'center')."';" );
				// }

				if ( $this->_params->get('highslide_headers', 1) && !isset($headers[$headertype]) ) {
					$headers[$headertype] = 1;
					$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/highslide/highslide.css', "text/css", "screen" );
				$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";

var tt = " function config_highslide() { hs.graphicsDir=\"'.JURI::base( false ).'plugins/content/multithumb/highslide/graphics/\"\n\
hs.align = \"'.$this->_params->get('hs_align', 'center').'\"\n\
hs.allowMultipleInstances = '.$this->_params->get('hs_allowMultipleInstances', 'false').'\n\
}";
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
head.appendChild(script2);  

var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script = document.createElement("script");  
script.type = "text/javascript";
script.onload = config_highslide;
script.src = "'.JURI::base( false ).'plugins/content/multithumb/highslide/highslide.js?full=true";  
head.appendChild(script);  
};' );	
}			

				
				break;    		
			case 'lightbox':
				$this->botAddMultiThumbHeader('jquery');
    			if ( $this->_params->get('lightbox_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/lightbox/css/lightbox.css', "text/css", "screen" );
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/lightbox/js/lightbox.min.js' );
    			}
    			break;
			case 'slimbox':
				$this->botAddMultiThumbHeader('jquery');
    			if ( $this->_params->get('slimbox_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/slimbox/css/slimbox.css', "text/css", "screen" );
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/slimbox/js/slimbox2.js' );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "jQuery(document).ready(function(){ jQuery(\"a[rel^=\'lightbox\']\").slimbox({/* Put custom options here */  /* BEGIN */ loop: '.$this->_params->get('slimbox_loop', '0').' , overlayOpacity: '.$this->_params->get('slimbox_overlayOpacity', '0.8').',	overlayFadeDuration: '.$this->_params->get('slimbox_overlayFadeDuration', '400').',resizeDuration: '.$this->_params->get('slimbox_resizeDuration', '400').', initialWidth: '.$this->_params->get('slimbox_initialWidth','250').', initialHeight: '.$this->_params->get('slimbox_initialHeight', '250').' , imageFadeDuration: '.$this->_params->get('slimbox_imageFadeDuration', '400').' , captionAnimationDuration: '.$this->_params->get('slimbox_captionAnimationDuration', '400').' , closeKeys: '.$this->_params->get('slimbox_closeKeys', '[27, 88, 67]').' , previousKeys: '.$this->_params->get('slimbox_previousKeys', '[37, 80]').' , nextKeys: '.$this->_params->get('slimbox_nextKeys', '[39, 78]').' , counterText: \"'.$this->_params->get('slimbox_counterText', 'Image {x} of {y}').'\" /* END */ }, null, function(el) {			return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));		}); });"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
};' );
    			}
    			break;
     		case 'iLoad':
				if ( $this->_params->get('iload_headers', 1) && !isset($headers[$headertype]) ) {
					$headers[$headertype] = 1;
				$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("body"); 				
var body = b[b.length-1] ;  

script2 = document.createElement("script");   
script2.type = "text/javascript";   
var tt = " function config_iLoad() {L.path=\"'.JURI::base( false ).'plugins/content/multithumb/iLoad/\"\n\
L.showInfo='.$this->_params->get('iload_info', 'true').'\n\
L.showName='.$this->_params->get('iload_showName', 'true').'\n\
L.showDesc='.$this->_params->get('iload_showDesc', 'true').'\n\
L.showSet='.$this->_params->get('iload_showSet', 'true').'\n\
L.fileInfoText=\"'.$this->_params->get('iload_fileInfoText', 'File format <b> \[F\] <b> size<b> \[W\] x \[H\] </b> pixels').'\"\n\
L.imageSetText='.$this->_params->get('iload_imageSetText','[\'<b>[N] </b> from <b> [T] </b>\', \'in the group [S]\']').'\n\
L.fontCss=\"'.$this->_params->get('iload_fontCss', 'font:11pxTahoma,Arial,Helvetica,sans-serif;color:#aaa;').'\"\n\
L.imageDescCss=\"'.$this->_params->get('iload_imageDescCss', 'display: block;').'\"\n\
L.imageNameCss=\"'.$this->_params->get('iload_imageNameCss', 'display: block; font-weight: 700; color: # 999;').'\"\n\
L.imageSetCss=\"'.$this->_params->get('iload_imageSetCss', 'display: block;').'\"\n\
L.imageInfoCss=\"'.$this->_params->get('iload_imageInfoCss', 'display: block;').'\"\n\
L.zIndex=\"'.$this->_params->get('iload_zIndex', '9999').'\"\n\
L.splitSign=\"'.$this->_params->get('iload_splitSign', '|').'\"\n\
L.bigButtonsDisabledOpacity='.$this->_params->get('iload_bigButtonsDisabledOpacity', '30').'\n\
L.bigButtonsPassiveOpacity='.$this->_params->get('iload_bigButtonsPassiveOpacity', '100').'\n\
L.bigButtonsActiveOpacity='.$this->_params->get('iload_bigButtonsActiveOpacity', '70').'\n\
L.minButtonsPassiveOpacity='.$this->_params->get('iload_minButtonsPassiveOpacity', '50').'\n\
L.minButtonsActiveOpacity='.$this->_params->get('iload_minButtonsActiveOpacity', '100').'\n\
L.overlayAppearTime='.$this->_params->get('iload_overlayAppearTime', '200').'\n\
L.overlayDisappearTime='.$this->_params->get('iload_overlayDisappearTime', '200').'\n\
L.containerAppearTime='.$this->_params->get('iload_containerAppearTime', '300').'\n\
L.containerDisappearTime='.$this->_params->get('iload_containerDisappearTime', '300').'\n\
L.containerResizeTime='.$this->_params->get('iload_containerResizeTime', '300').'\n\
L.contentAppearTime='.$this->_params->get('iload_contentAppearTime', '350').'\n\
L.contentDisappearTime='.$this->_params->get('iload_contentDisappearTime', '200').'\n\
L.loaderAppearTime='.$this->_params->get('iload_loaderAppearTime', '200').'\n\
L.loaderDisappearTime='.$this->_params->get('iload_loaderDisappearTime', '200').'\n\
L.containerCenterTime='.$this->_params->get('iload_containerCenterTime', '300').'\n\
L.panelAppearTime='.$this->_params->get('iload_panelAppearTime', '300').'\n\
L.panelDisappearTime='.$this->_params->get('iload_panelDisappearTime', '300').'\n\
L.arrowsTime='.$this->_params->get('iload_arrowsTime', '230').'\n\
L.paddingFromScreenEdge='.$this->_params->get('iload_paddingFromScreenEdge', '35').'\n\
L.contentPadding='.$this->_params->get('iload_contentPadding', '0').'\n\
L.cornersSize='.$this->_params->get('iload_cornersSize', '18').'\n\
L.overlayOpacity='.$this->_params->get('iload_overlayOpacity', '95').'\n\
L.overlayBackground=\"'.$this->_params->get('iload_overlayBackground', '#000000').'\"\n\
L.containerColor=\"'.$this->_params->get('iload_containerColor', '#ffffff').'\"\n\
L.panelType='.$this->_params->get('iload_panelType', '1').'\n\
L.hidePanelWhenScale='.$this->_params->get('iload_hidePanelWhenScale', 'true').'\n\
L.forceCloseButton='.$this->_params->get('iload_forceCloseButton', 'true').'\n\
L.arrows='.$this->_params->get('iload_arrows', 'true').'\n\
L.imageNav='.$this->_params->get('iload_imageNav', 'true').'\n\
L.showSize='.$this->_params->get('iload_showSize', 'true').'\n\
L.forceFullSize='.$this->_params->get('iload_forceFullSize', 'false').'\n\
L.keyboard='.$this->_params->get('iload_keyboard', 'true').'\n\
L.dragAndDrop='.$this->_params->get('iload_dragAndDrop', 'true').'\n\
L.preloadNeighbours='.$this->_params->get('iload_preloadNeighbours', 'true').'\n\
L.slideshowTime='.$this->_params->get('iload_slideshowTime', '3000').'\n\
L.slideshowRound='.$this->_params->get('iload_slideshowRound', 'true').'\n\
L.slideshowClose='.$this->_params->get('iload_slideshowClose', 'false').'\n\
L.tips=['.$this->_params->get('iload_tips', '\'Previous\', \'Next\', \'Close\', \'Slideshow\', \'Pause\', \'Original\',\'Fit to window\'').']\n\
L.errorWidth='.$this->_params->get('iload_errorWidth', '240').'\n\
L.errorName='.$this->_params->get('iload_errorName', 'Error.').'\n\
L.closeOnClickWhenSingle='.$this->_params->get('iload_closeOnClickWhenSingle', 'true').'\n\
L.errorDescCss=\"'.$this->_params->get('iload_errorDescCss', 'display: block; padding-bottom: 4px;').'\"\n\
L.errorNameCss=\"'.$this->_params->get('iload_errorNameCss', 'display: block; font-weight: 700; color: # 999; padding-bottom: 4px;').'\"\n\
L.errorText=\"'.$this->_params->get('iload_errorText', 'Could not load image. Perhaps the address specified is not valid or the server is temporarily unavailable.').'\"\n\
}";
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  

script = document.createElement("script");  
script.type = "text/javascript";
script.onload=config_iLoad;
script.src = "'.JURI::base( false ).'/plugins/content/multithumb/iLoad/iLoad.js";  
body.appendChild(script);  
};' );

				}
				break;
    		case 'prettyPhoto':
    			$this->botAddMultiThumbHeader('jquery', 1);
    			if ( $this->_params->get('prettyphoto_headers') && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/prettyPhoto/css/prettyPhoto.css', "text/css", "screen" );
    				$document->addScript( $this->_live_site.'/plugins/content/multithumb/prettyPhoto/js/jquery.prettyPhoto.js' );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("body"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "jQuery(document).ready(function(){ jQuery(\"a[rel^=\'prettyPhoto\']\").prettyPhoto({\n\
animation_speed: \''.$this->_params->get('prettyphoto_animationSpeed', 'normal').'\',  \n\
opacity: '.$this->_params->get('prettyphoto_opacity', '0.80').', 	\n\
show_title: '.$this->_params->get('prettyphoto_showTitle', 'true').',  \n\
allow_resize: '.$this->_params->get('prettyphoto_allowresize', 'true').'  ,			\n\
default_width: 500,			\n\
default_height: 344	,			\n\
counter_separator_label: \''.$this->_params->get('prettyphoto_counter_separator_label', '/').'\', 			\n\
theme: \''.$this->_params->get('prettyphoto_theme', 'light_rounded').'\', 			\n\
opacity: '.$this->_params->get('prettyphoto_opacity', '0.80').', 	\n\
horizontal_padding: '.$this->_params->get('prettyphoto_horizontal_padding', '20').', 	\n\
wmode: \'opaque\',		\n\
autoplay: true, 			\n\
modal: '.$this->_params->get('prettyphoto_modal', 'false').', 	\n\
deeplinking: true, \n\
slideshow:  '.$this->_params->get('prettyphoto_slideshow', 'false').', 	\n\
autoplay_slideshow: '.$this->_params->get('prettyphoto_autoplay_slideshow', 'false').', 	\n\
overlay_gallery: '.$this->_params->get('prettyphoto_overlay_gallery', 'false').', 	\n\
keyboard_shortcuts: true, \n\
social_tools: false, \n\
}); \n\
});"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
};' );

/*

overlay_gallery: '.$this->_params->get('prettyphoto_overlay_gallery', 'false').', 	\n\

*/
    			}
    			break;
    		case 'shadowbox':
    			if ( $this->_params->get('shadowbox_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addScript( $this->_live_site.'/plugins/content/multithumb/shadowbox/shadowbox.js' );
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/shadowbox/shadowbox.css', "text/css", "screen" );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "Shadowbox.init( {  animate:	           '.$this->_params->get('shadowbox_animate', '1').' ,animateFade:           '.$this->_params->get('shadowbox_animateFade', '1').' ,animSequence:        \"'.$this->_params->get('shadowbox_animSequence', 'sync').'\"  ,autoplayMovies:	       '.$this->_params->get('shadowbox_autoplayMovies', '1').'  ,continuous:	           '.$this->_params->get('shadowbox_continuous', '0').'  ,counterLimit:	      '.$this->_params->get('shadowbox_counterLimit', '10').' ,counterType:	      \"'.$this->_params->get('shadowbox_counterType', 'default').'\"    ,displayCounter:	       '.$this->_params->get('shadowbox_displayCounter', '1').'  ,displayNav:	          '.$this->_params->get('shadowbox_displayNav', '1').' ,enableKeys:	           '.$this->_params->get('shadowbox_enableKeys', '1').'  ,fadeDuration:          '.$this->_params->get('shadowbox_fadeDuration', '0.35').' ,flashVersion:	      \"'.$this->_params->get('shadowbox_flashVersion', '9.0.0').'\"  ,handleOversize:	      \"'.$this->_params->get('shadowbox_handleOversize', 'resize').'\"  ,handleUnsupported:	 \"'.$this->_params->get('shadowbox_handleUnsupported', 'link').'\"  ,initialHeight:	       '.$this->_params->get('shadowbox_initialHeight','160').' ,initialWidth:	       '.$this->_params->get('shadowbox_initialWidth', '320').' ,modal:	               '.$this->_params->get('shadowbox_modal', '0').'  ,overlayColor:	      \"'.$this->_params->get('shadowbox_overlayColor','#000').'\"  ,overlayOpacity:	       '.$this->_params->get('shadowbox_overlayOpacity', '0.5').'  ,resizeDuration:	       '.$this->_params->get('shadowbox_resizeDuration', '0.35').'  ,showOverlay:	      '.$this->_params->get('shadowbox_showOverlay', '1').' ,showMovieControls:	   '.$this->_params->get('shadowbox_showMovieControls', '1').' ,slideshowDelay:	      '.$this->_params->get('shadowbox_slideshowDelay', '0').' ,viewportPadding:	   '.$this->_params->get('shadowbox_viewportPadding', '20').' ,flashVars: {'.$this->_params->get('shadowbox_flashVars','').'}    } );"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
};' );

/*
	, flashParams: {'.$this->_params->get('shadowbox_flashParams','bgcolor:#000000').'}    
*/
    			}
    			break;
			case 'thumbnail':
				if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/thumbnailviewer/thumbnailviewer.js' );
					
				$document->addScriptDeclaration( 'window.onload=function(){
var h = document.getElementsByTagName("head"); 
var head = h[h.length-1] ;  				
script2 = document.createElement("script");   
script2.type = "text/javascript";   
var tt = "/* Image Thumbnail Viewer Script- © Dynamic Drive (www.dynamicdrive.com) * This notice must stay intact for legal use. * Visit http://www.dynamicdrive.com/ for full source code */ \n\
thumbnailviewer.definefooter=\'<div class=\"footerbar\">CLOSE x</div>\' \n\
thumbnailviewer.enableAnimation=true ; \n thumbnailviewer.enableTitle=true;  \n\
thumbnailviewer.defineLoading=\"<img src='.$this->_live_site.'/plugins/content/multithumb/thumbnailviewer/loading.gif /> Loading Image...\"\n\
";
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
head.appendChild(script2);  
};' );
					
	//		
 
 		
					
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/thumbnailviewer/thumbnailviewer.css', "text/css", "screen" );
    			}
				break;
				
    		case 'modal':
				if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
					JHTML::_('behavior.modal');
				}
				break;
				
    		case 'greybox':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    
				
$document->addScriptDeclaration( 'document.write(\'<scr\'+\'ipt type="text/javascript">var GB_ROOT_DIR = "'.$this->_live_site.'/plugins/content/multithumb/greybox/";</scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS.js"></scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS_fx.js"></scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_scripts.js"></scr\'+\'ipt>\');
document.write(\'<link rel="stylesheet" href="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_styles.css" type="text/css" media="screen"  />\'); ');
					
    		}
				
   			break;


    		case 'normal':
    		case 'expansion':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
   					$document->addScript( $this->_live_site.'/plugins/content/multithumb/multithumb.js' );
    			}
    			break;
    		case 'expando':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
   					$document->addScript( $this->_live_site.'/plugins/content/multithumb/expando.js' );
    			}
    			break;
    	}
		
    }

    function bot_mt_makeFullArticleLink($img, $altOrg, $titleOrg) {

		$botMtLinkText = $this->botMtLinkText;

		if ($this->_params->get('blog_img_txt')==1) {
			if ($altOrg!="") {
				$botMtLinkText = JText::_('COM_CONTENT_READ_MORE').$altOrg;
			}
		} elseif ($this->_params->get('blog_img_txt')==2) {					
			if ($titleOrg!="") {
				$botMtLinkText = JText::_('COM_CONTENT_READ_MORE').$titleOrg;
			}
		}
		
		if ( $botMtLinkText ) {
			
			$img = preg_replace(array('/((?:title|alt)=(["\']))(.*?)(\\2)/', '/<img[^>]*>/'), array("$1".$botMtLinkText."$4", "<a href=\"".$this->botMtLinkOn."\" title=\"".$botMtLinkText."\">$0</a>"), $img);
		}
    	return $img;
    }


    // processes inline parameters
    function inline_parms(&$parms) {

	
		 
    	if(isset($parms)) { // Now handle manual param settings ...
    		$botMtParamStr = str_replace(array('<br />', '&nbsp'), '', $parms);
    		// parse parameters
    		if(preg_match_all('/\bdefault\b|[^=\s]+ *=[^=]*(?:\s+|$)(?!=)/is', $botMtParamStr, $botParams)) {
    			foreach($botParams[0] as $param) {
				
						
					$param = trim(trim($param, ';'));
    				// restore default value of all parameters
    				if($param == 'default') {
						$this->_params = clone($this->_paramsDef);
    				} else {
    					// set specific parameter
    					$param = explode('=', $param);
    					$varname = trim($param[0]);
    					$value = trim($param[1]);
    					// restore default value of specific parameter
    					// if(strtolower($value)=='default') {
    						// $value = $this->_paramsDef->get( $varname );
    					// }

    					// update only exist paramters
    					if( null != $this->_params->get( $varname, null )) {
							$this->_params->set( $varname,  $value );
    					}
    				}
    			}
    		}
    	}

    }

    function set_sys_limits () {

    	// set system parametrs once per session
    	if(is_numeric($this->_params->get('time_limit'))) {
    		set_time_limit($this->_params->get('time_limit'));
    		// avoid next execution
    		// @TODO Change to static variable
    		$this->_params->set('time_limit', '');
    		$this->_paramsDef->set('time_limit', '');
    	}

    	if($this->_params->get('memory_limit') != 'default') {
    		ini_set("memory_limit", $this->_params->get('memory_limit'));
    		$this->_params->set('memory_limit', 'default');
    		$this->_paramsDef->set('memory_limit', 'default' );
    	}

    }

    function set_caption($alt, $title, $iptc_caption, $filename) {
		$values = array( $this->_params->get('caption_type_iptc') 	=> $iptc_caption,
						$this->_params->get('caption_type_filename') 		=> $filename,
						$this->_params->get('caption_type_title') 			=> $title,
						$this->_params->get('caption_type_alt') 			=> $alt );
				
		ksort($values);
		$caption = '';
		foreach ($values as $key =>  $val) {
		    if ( $key and $val ) {
				$caption = $val;
				break;
			}
		}
		

    	return $caption;
    }


    //
    // Process resized or/and watermarked images
    //
 /*   function resize_image(&$imgraw, &$imgurl, &$real_width, &$real_height)
    {

		// Full image size
    	$full_width  = $this->_params->get('full_width');
    	$full_height = $this->_params->get('full_height');

    	if( !$this->_params->get('resize')) {
    		$full_width = $full_height = 0;
    	}

    	// Resize image and/or set watermark
    	$imgtemp = $this->botmt_thumbnail($imgurl, $full_width, $full_height, $this->_params->get('image_proportions'), hexdec($this->_params->get('image_bg')), (int)($this->_watermark >= 1), 'images', 0 , $this->_params->get('img_type', "") );

    	// If image resized or watermarked use it instead of the original one
    	if($imgtemp) {
    		$imgurl = $imgtemp;
    		$real_width = $full_width;
    		$real_height = $full_height;
			preg_match('/(.*src=["\'])[^"\']+(["\'].*)/i', $imgraw, $parts);
			$imgraw = $parts[1].$imgurl.$parts[2];
    	} else {
    		$real_width = $full_width;
    		$real_height = $full_height;
		}

    } */
	
	function gallery($imgloc, $imgurl, $alt, $title, $align, $class  ) {
    	// It should be list of files

		static $mt_gallery_count = 0;
		
		if ( $mt_gallery_count ) {
			return false;
		}
		
		$mt_gallery_count++;
		
		if(!@fopen($imgloc, 'r')) {
    		// can't open file. Ignore it.
    		return false;
    	}
		
  		$old_caption_type_iptc     = $this->_params->get('caption_type_iptc');
		$old_caption_type_filename = $this->_params->get('caption_type_filename');
		$old_caption_type_title    = $this->_params->get('caption_type_title');
		$old_caption_type_alt      = $this->_params->get('caption_type_alt');  

		$this->_params->set('caption_type_iptc', $this->_params->get('caption_type_gallery_iptc'));
		$this->_params->set('caption_type_filename', $this->_params->get('caption_type_gallery_filename'));
		$this->_params->set('caption_type_title', $this->_params->get('caption_type_gallery_title'));
		$this->_params->set('caption_type_alt', $this->_params->get('caption_type_gallery_alt'));  		
		
    	$pathinfo = pathinfo($imgloc);
    	$filepatt = "$pathinfo[dirname]/{*.gif,*.jpg,*.png,*.GIF,*.JPG,*.PNG}";


		$style = $align ? ' align="'.$align.'" ' : '';
    	$gallery = '<table class="'.$this->_params->get('gallery_class').'" >' . "\n";

    	$n = 0; $lblinks = '';
	
		if(file_exists("$imgloc.txt")) {
			$imglist = file_get_contents("$imgloc.txt");
			preg_match_all('/(\S+\.(?:jpg|png|gif))\s(.*)/i', $imglist, $files, PREG_SET_ORDER);
			$dir = dirname($imgurl);
		} else {
			$files = glob($filepatt, GLOB_BRACE);
			sort($files);
			$imgpos = array_search($imgloc, $files);
			$files = array_merge(array_slice($files, $imgpos), array_slice($files, 0, $imgpos));
			$dir = dirname($imgurl);
		}
    	 
//     	if($alt=='mt_gallery') {
    		$this->mt_gallery_count++;
			
    		if ( !$alt ) {
				$alt = $title;
			}
			
    		if ( !$alt ) {
				$alt = "gallery".$this->mt_gallery_count;
			}
			
			
//    	}
		
		$mt_gallery_more = 0;
		
    	foreach($files as $file) {
    		if(is_array($file)) {
    			$fn = $dir.'/'.$file[1];
    			$title = str_replace("'", '&#39;', $file[2]);
    		} else {
				$fn = $dir.'/'.basename($file);
			}
			$this->is_gallery = true;
    		$galimg = preg_replace_callback($this->regex, 
									        array( &$this,'image_replacer'), 
											'<img '.$style.' class="'.$class.'" alt="'.$alt.'" title="'.$title.'" src="'.$fn.'" />' . "\n");
			$this->is_gallery = false;
    		if(!(strpos($galimg, '<img') === false)){

    			if($n % $this->_params->get('num_cols') == 0) { 
					$gallery .= '<tr class="'.$this->_params->get('gallery_class').'" >';
				}
    			$gallery .= '<td class="'.$this->_params->get('gallery_class').'" valign="bottom" nowrap="nowrap" '.$style.'>'.$galimg.'</td>
';
    			$n++;
    			if($n % $this->_params->get('num_cols') == 0) {
					$gallery .= "</tr>\n";
				}
    		} else if(substr($galimg,0,2)=='<a') {
				{
					$lblinks .= $galimg;
				}
    		}
    	}

    	$gallery .= str_repeat('<td>&nbsp;</td>', $this->_params->get('num_cols')-1 - ($n-1) % $this->_params->get('num_cols')) . "</tr>";
		$gallery .= "</table>\n";
		

		
		$this->_params->set('caption_type_iptc', $old_caption_type_iptc);
		$this->_params->set('caption_type_filename', $old_caption_type_filename);
		$this->_params->set('caption_type_title', $old_caption_type_title);
		$this->_params->set('caption_type_alt', $old_caption_type_alt);  		
		
		
		$mt_gallery_count--;
    	return $gallery . $lblinks;

    }

	// Change image path from relative to full
    function fix_path(&$imgloc, &$imgurl) {
		$imgloc = urldecode($imgloc);
    	if(!(false === strpos($imgloc, '://'))) { // It's a url
			// 
			$pos = strpos($imgloc, JURI::base( false ));
			if ( $pos !== false && $pos == 0 ) { // It's internal full url
				$imgloc = substr($imgloc, strlen (  JURI::base( false ) ) );
				$imgurl = $imgloc;
				// $imgloc = JPATH_SITE.DS.str_replace( "/", DS, $imgloc );
			} else { // external url
				$imgurl = $imgloc;
			}
			
    	} else { // it's a relative path
    		if (substr($imgloc, 0, 1) == "/") {
    			// It's full path
				$imgloc = substr_replace( $imgloc, "", 0, strlen(JURI::base( true ))+1 );
				// $imgloc = /*JURI::base( true ).*/ $imgloc;
				$imgurl = $imgloc;
    			// $imgloc = JPATH_SITE.str_replace( "/", DS, $imgloc );
    		} else {
    			$imgurl = /* JURI::base( true ).'/'.*/ $imgloc;
    			$imgloc = JPATH_SITE."/".$imgloc;
    		}
    	}
    }

    function set_popup_type( &$title, &$alt ) {

    	$this->popup_type = $this->_params->get('popup_type');

    	// Process alt parameter of image that is used for multithumb instructions as prefix separated by ":"
    	$temp = explode(':', $alt, 2);
    	// Parametrs that may be specified in alt field
    	$popupmethods = array(	'none'		=>'mt_none',
								'normal'	=>'mt_popup', 
								'thumbnail'	=>'mt_thumbnail', 
								'lightbox'	=>'mt_lightbox', 
								'highslide'	=>'mt_highslide', 
								'prettyPhoto' =>'mt_prettyPhoto', 
								'shadowbox'	=>'mt_shadowbox', 
								'expansion'	=>'mt_expand', 
								'expando'	=>'mt_expando', 
								'gallery'	=>'mt_gallery', 
								'ignore'	=>'mt_ignore', 
								'nothumb'	=>'mt_nothumb', 
								'greybox'	=>'mt_greybox', 
								'slimbox'	=>'mt_slimbox', 
								'widgetkit'	=>'mt_widgetkit', 
								'thickbox'	=>'mt_slimbox');
    	// Search for any expected instruction
    	$new_popup_style = array_search(strtolower($temp[0]), $popupmethods);
		
		
		
    	// instruction found
    	if($new_popup_style!==false) {
    		// change popup type
    		$this->popup_type = $new_popup_style;

    		// Remove instruction from alt pararmeter of the image
    		$alt = preg_replace('/^(mt_none|mt_popup|mt_thumbnail|mt_lightbox|mt_expand|mt_gallery|mt_ignore|mt_nothumb|mt_greybox|mt_highslide|mt_slimbox|mt_prettyPhoto|mt_shadowbox|mt_thickbox|mt_widgetkit):?/i', '', $alt);

    	}

    	/* if ( $this->popup_type == "lightbox"  ) {
    		$this->popup_type = "slimbox";
    	}*/
		
		if ( $this->popup_type == "thickbox" ) {
    		$this->popup_type = "shadowbox";
		}
    }

} // Class End
?>
