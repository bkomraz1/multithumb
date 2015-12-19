<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.html.html');
jimport('joomla.form.formfield');//import the necessary class definition for formfield


class JFormFieldBlogs extends JFormField
{

	protected $type = 'Blogs';
	
   var $options = array();
   var $menutype = '';
	// $parent is the parent of the children we want to see
	// $level is increased when we go deeper into the tree,
	//        used to display a nice indented tree
	
		protected function getInput()
	{
  // Initialize variables.
  $session = JFactory::getSession();
  // $options = array();

		$db = JFactory::getDBO();


		
		$query = 'SELECT #__menu.id, #__menu.title as title, #__menu.parent_id, #__menu.menutype, menutype as menutitle, link
	            FROM #__menu
				WHERE #__menu.published=1 ORDER BY lft';
		
		
		$result = $db->setQuery( $query );
		$rows = $db->loadObjectList();

		// display each child
		foreach ($rows as $row) {
			if ($row->menutype != $this->menutype) {
				// $this->options[] = JHTML::_('select.optgroup', $row->menutitle ,'id','title');
				$this->options[] = JHTML::_('select.option',  '<OPTGROUP>', $row->menutitle, 'id', 'title' );
				$this->menutype = $row->menutype;
			}
			
			if ( strpos( $row->link, "layout=blog") !== false or
				strpos( $row->link, "view=featured") !== false or
				 strpos( $row->link, "view=frontpage") !== false ) {			
				$this->options[] = JHTML::_('select.option', $row->id, $row->title, 'id', 'title');
			}
			// echo "DEBUG: $parent, $row->id, $row->title<br>";
			// call this function again to display this
			// child's children
			// $this->display_children($row->id, $level+1);
	   }

  $attr = '';

  // Initialize some field attributes.
  $attr .= $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';

  // To avoid user's confusion, readonly="true" should imply disabled="true".
  if ( (string) $this->element['readonly'] == 'true' || (string) $this->element['disabled'] == 'true') {
   $attr .= ' disabled="disabled"';
  }

  $attr .= $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '5';
  $attr .= $this->multiple ? ' multiple="multiple"' : '';

  // Initialize JavaScript field attributes.
  $attr .= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

	// echo "DEBUG:".print_r($this->options)."<br>";
	return JHTML::_('select.genericlist',  $this->options, $this->name, trim($attr), 'id', 'title', $this->value );
	
	// return JHTML::_('select.genericlist',  $this->options, ''.$control_name.'['.$name.'][]',  ' multiple="multiple" size="' . $size . '" class="inputbox"', 'value', 'text', $value, $control_name.$name);
   }
   

}
?>