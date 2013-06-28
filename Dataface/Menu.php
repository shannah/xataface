<?php
class Dataface_Menu {
	private $nextID=0;
	private $items;
	private $urlMap;
	private $root;
	private $triggers = array();
	public $dependencies = array();
	public function __construct($rootPath='/'){
		$this->items = array();
		$this->urlMap = array();
		$this->root = new Dataface_Menu_Item('__root__', $rootPath, null, $this);
		$this->addItem($this->root);
		
	}
	
	public function __destruct(){
		unset($this->root);
		unset($this->items);
		unset($this->urlMap);
	}
	
	public function newMenuItem($label, $url, $parent=null){
		
		// We set $reorganize variable to indicate whether we should
		// attempt to reorganize existing menu items according to 
		// their URL path.  This is advantageous with systems where
		// the menu corresponds to the URL paths.
		$reorganize = false;
		if ( !isset($parent) ){
			// If no parent was specified we must be using the URL to find the parent.
			// so we should reorganize.
			$reorganize=true;
			$p = $this->getItemByURL($url);
			if ( $p['type'] & (Dataface_Menu_URLMap::$CHILD | Dataface_Menu_URLMap::$DESCENDENT) ){
				$parent = $p['menuItem'];
			} else if ( $p['type'] & Dataface_Menu_URLMap::$SELF ){
				$parent = $p['menuItem']->getParent();
			} else {
				$parent = $this->getRoot();
			}
		}
		$item = new Dataface_Menu_Item($label, $url, $parent, $this);
		$parent->addChild($item, $reorganize);
		return $item;
	}
	
	public function getItems(){
		return $this->items;
	}
	
	public function getRoot(){
		return $this->root;
	}
	
	
	
	public function nextID(){
		while ( isset($this->items[$this->nextID]) ) $this->nextID++;
		return $this->nextID;
	}
	
	
	public function addItem(Dataface_Menu_Item $item){
		$menuID = $item->getId();
		if ( !isset($menuID)  ) $item->setId($this->nextID());
		$this->items[$item->getId()] = $item;
		$url = $item->getURL();
		if ( isset($url) ){
			
			$this->registerURL($url, $item);
		}
	}
	
	public function registerURL($url, $menuItem, $type=null){
		$mapper = new Dataface_Menu_URLMap($url, $menuItem, $type);
		$this->urlMap[$url] = $mapper;
	}
	
	public function getItemById($id){
		if ( !isset($this->items[$id]) ) throw new Exception("Attempt to access item that doesn't exists: ".$id);
		return $this->items[$id];
	}
	
	public function getItemByURL($url){
		$parts = explode('/', $url);
		
		$type = null;
		while ( count($parts) > 0 ){
			$curr = implode('/', $parts);
			
			if ( isset($this->urlMap[$curr]) ){
				
				$map = $this->urlMap[$curr];
				$menuID = $map->getMenuID();
				if ( isset($this->items[$menuID]) ){
					
					if ( !isset($type) ) $type = $map->getType();
					else if ( $type == Dataface_Menu_URLMap::$SELF ){
						$type = Dataface_Menu_URLMap::$CHILD;
					} else {
						$type = Dataface_Menu_URLMap::$DESCENDENT;
					}
					return array(
						'menuItem'=>$this->items[$menuID],
						'type'=>$type
					);
				}
				else throw new Exception("Menu with id $menuID could not be found.");
			} else {
				array_pop($parts);
				if ( !isset($type) ) $type = Dataface_Menu_URLMap::$SELF;
				else if ( $type == Dataface_Menu_URLMap::$SELF ){
					$type = Dataface_Menu_URLMap::$CHILD;
				} else {
					$type = Dataface_Menu_URLMap::$DESCENDENT;
				}
			}
		}
		
		return null;
		
	}
	
	
	
	public function getItemPath($item, $type=null){
		if ( !isset($item) ) return array();
		if ( !isset($type) ) $type = Dataface_Menu_URLMap::$SELF;
		$path = array($item, $type);
		while (($curr = $item->getParent() ) !== null ){
			array_unshift($path, $curr);
			unset($item);
			$item = $curr;
			unset($curr);
		}
		return $path;
		
	}
	
	
	public function buildMenu($url){
		
		$menu = array();
		$item = $this->getItemByURL($url);
		$pageTitle = (
			$item['type'] == Dataface_Menu_URLMap::$SELF
		) ? $item['menuItem']->getLabel() : ucwords(str_replace(array('-','_','+'), array(' ',' ',' '), basename($url)));
		
		$path = $this->getItemPath($item['menuItem'], $item['type']);
		
		$this->getRoot()->buildMenu($path, 0, $pageTitle, $menu);
		return $menu;
	}
	
	public function toJSON(){
	
		$out = array(
			'items' => array(),
			'nextID' => $this->nextID,
			'urlMap'=>array(),
			'root' => $this->getRoot()->getId()
		);
		
		foreach ($this->items as $key=>$val){
			$out['items'][$key] = $val->toJSON(false);
		}
		
		foreach ($this->urlMap as $key=>$val ){
			$out['urlMap'][$key] = $val->toJSON(false);
		}
		return json_encode($out);
	}
	
	public function registerTrigger($name, $callback){
		$this->triggers[$name][] = $callback;
	}
	
	public function fireTrigger($name){
		while ( isset($this->triggers[$name]) and count($this->triggers[$name])>0 ){
			$callback = array_shift($this->triggers[$name]);
			if ( is_array($callback) ){
				$obj =& $callback[0];
				$method = $callback[1];
				$obj->$method();
				
			} else {
				$callback();
			}
		}
	}
	
	public function loadJSON($in){
		$in = json_decode($in);
		$this->items = array();
		$this->urlMap = array();
		$this->nextID = $in->nextID;
		
		foreach ( $in->items as $key=>$val ){
			$this->items[$val->menuID] = Dataface_Menu_Item::fromJSON($val, $this, false);
		}
		
		
		foreach ($in->urlMap as $key=>$val ){
			$this->urlMap[$key] = Dataface_Menu_URLMap::fromJSON($val, $this, false);
		}
		
		$this->root = $this->getItemById($in->root);
		
		$this->fireTrigger('afterLoadJSON');
		
	
	}
	
	
	public function toHtml($url, $id='main_nav'){
		$m = $this->buildMenu($url);
		array_shift($m);  //get rid of root node.
		$out = array();
		$level = 0;
		foreach ($m as $i){
			if ( $i['level'] > $level ){
				while ( $level < $i['level'] ){
					if ( $level>0 ) $out[] = '<li>';
					$out[] = '<ul '.($level==0?'id="'.df_escape($id).'"':'').'>';
					$level++;
				}
			}
			
			
			
			else if ( $i['level'] < $level ){
				while ( $level > $i['level'] ){
					$out[] = '</ul>';
					$level--;
				}
			}
			$class = array('menu-level-'.$i['level']);
			if ( $i['selected'] ) $class[] = 'menu-selected';
			if ( $i['breadCrumb'] ) $class[] = 'menu-breadCrumb current';
			if ( $i['parent'] ) $class[] = 'menu-parent';
			$out[] = '<li class="'.implode(' ',$class).'"><a href="'.df_escape($i['url']).'">'.df_escape($i['label']).'</a></li>';
			
		}
		while ( $level > 0 ){
			$out[] = '</ul>';
			$level--;
			if ( $level > 0 ) $out[] = '</li>';
		}
		
		return implode("\n", $out);
	}
	
	
}


class Dataface_Menu_URLMap {
	private $url;
	private $menuID;
	public static $CHILD=1;
	public static $DESCENDENT=2;
	public static $SELF=4;
	private $type;
	
	public function __construct($url, $menu, $type=null){
		if ( is_int($menu) ) $this->menuID = $menu;
		else if ( is_a($menu, 'Dataface_Menu_Item') ){
			$this->menuID = $menu->getId();
		} else {
			throw Exception("2nd parameter of URLMap constructor takes either an integer ID or a Dataface_Menu_Item class but received a ".get_class($menu)." object instead.");
			
		}
		$this->url = $url;
		if ( !isset($type) ) $type= self::$SELF;
		$this->type = $type;
	}
	
	public function toJSON($serialize=true){
		$out = array(
			'url'=>$this->url,
			'menuID'=>$this->menuID,
			'type'=>$this->type
		);
		if ( $serialize ) $out = json_encode($out);
		return $out;
	}
	
	public static function fromJSON($in, $menu, $serialized=true){
		if ( $serialized ) $in = json_decode($data);
		$map = new Dataface_Menu_URLMap($in->url, $in->menuID, $in->type);
		return $map;
	}
	
	public function toArray(){
		$out = array(
			'url'=>$this->url,
			'menuID'=>$this->menuID,
			'type'=>$this->type
		);
		return $out;
	}
	
	public function getMenuID(){
		return $this->menuID;
	}
	
	public function getURL(){
		return $this->url;
	}
	
	public function getType(){
		return $this->type;
	}
	
	
	
	
}

class Dataface_Menu_Item {
	private $menu;
	private $url;
	private $label;
	private $menuID;
	private $parent;
	private $children;
	private $order=0;
	private $sorted = false;
	
	public static $SHOW_CHILDREN_WHEN_SELECTED=1;
	public static $SHOW_CHILDREN_WHEN_PARENT=2;
	public static $SHOW_CHILDREN_WHEN_ANCESTOR=4;
	public static $SHOW_CHILDREN_ALWAYS=8;
	
	private $showChildrenSetting;
	
	private $_loadChildIDs = array();
	private $_loadParent = null;
	
	public function __construct($label, $url, Dataface_Menu_Item $parent=null, Dataface_Menu $menu=null){
		$this->children = array();
		$this->label = $label;
		$this->parent = $parent;
		$this->url = $url;
		$this->menu = $menu;
		if ( !isset($this->menu) ) $this->menu = $this->parent->menu;
		$this->showChildrenSetting = 1;
	}
	
	public function __destruct(){
		unset($this->parent);
		unset($this->children);
		unset($this->menu);
	}
	
	public function toJSON($serialize=true){
		$out = array(
			'url'=>$this->url,
			'label'=>$this->label,
			'menuID'=>$this->menuID,
			'parent'=>null,
			'children'=>array(),
			'order'=>$this->order,
			'showChildrenSetting'=>$this->showChildrenSetting
		);
		if ( isset($this->parent) ) $out['parent'] = $this->parent->getId();
		foreach ($this->getChildren() as $key=>$val){
			$out['children'][$key] = $val->getId();
		}
		if ( $serialize ) $out =  json_encode($out);
		return $out;
	}
	
	public static function fromJSON($in, $menu, $serialized=true){
		if ( $serialized ) $in = json_decode($in);
		
		$item = new Dataface_Menu_Item($in->label, $in->url, null, $menu);
		$item->menuID = $in->menuID;
		$item->setOrder($in->order);
		$item->setShowChildrenSetting($in->showChildrenSetting);
		$item->setLoadData(array(
			'childIDs' => $in->children,
			'parent'=>$in->parent
		));
		$menu->registerTrigger('afterLoadJSON', array(&$item, 'afterFromJSON'));
		return $item;	
	}
	
	
	public function setLoadData($params=array()){
		if ( isset($params['childIDs']) ) $this->_loadChildIDs = $params['childIDs'];
		if ( isset($params['parent']) ) $this->_loadParent = $params['parent'];
		return $this;
	}
	public function afterFromJSON(){
		if ( isset($this->_loadParent) ){
			$this->parent = $this->menu->getItemById($this->_loadParent);
		}
		foreach ( $this->_loadChildIDs as $childID ){
			$this->children[] = $this->menu->getItemById($childID);
		}
		return $this;
		
	}
	
	public function addChild(Dataface_Menu_Item $menuItem, $reorganize=false){
		// Now we should check our siblings to see if any of them should
		// be our children.  This may happen because when we add
		// menu items if the immediate parent isn't part of the menu, then
		// the menu item will be added as a child to the nearest ancestor.
		if ( $reorganize ){
			foreach ( $this->children as $key=>$sibling ){
				if ( stristr( $sibling->getURL(), $menuItem->getURL() ) == $sibling->getURL() ){
					unset($this->children[$key]);
					$menuItem->addChild($sibling);
				}
			}
		}
		
		
		$menuItem->parent = $this;
		$this->children[] = $menuItem;
		$this->sorted = false;
		
		// Now we add to the main menu and assign menuID if not set already.
		if ( !isset($menuItem->menuID) ){
			$menuItem->menuID = $this->menu->nextID();
			$this->menu->addItem($menuItem);
		}
		
		
		
	}
	
	
	
	public function setOrder($order){
		$oldOrder = $this->order;
		$this->order = $order;
		if ( $order != $oldOrder and isset($this->parent) ){
			$this->parent->sorted = false;
		}
		return $this;
	}
	
	
	public function buildMenu($path, $level, $pageTitle, &$menu){
		if ( !$this->sorted ){
			$this->_sort();
		}
		//echo "[$level :".count($path)." Building menu for {$this->getLabel()} : {$this->showChildrenSetting}]";
		/*if ( $level == count($path)-1 and !is_object($path[$level])){
			echo "bitch";
			// We are simply at the point where we decide
			// whether we are an ancestor, a decendent, or something else
			//This next bit basically checks to see if we should display
			// the children.  We display the children if:
			// 1. The url points directly to the menu item, and 
			//		a. The menu item is set to display children when selected.
			//		or
			//		b. The menu item is set to display children always
			// or
			// 2. The url points to the last menu item as its parent and
			//		a. The menu item is set to display children when it is the parent of the selected page.
			//		b. The menu item is set to display children when it is the ancestor of the selected page.
			//		c. The menu item is set to display children always.
			//
			// or
			// 3. The url points to the last menu item as its ancestor and
			//		a. The menu item is set to display children when it is an ancestor of the selected page.
			//		b. The menu item is set to display children always.
			//
			
			if (
					(
						$path[$level] == Dataface_Menu_URLMap::$SELF and ( 
				 			$this->showChildrenSetting & (
				 				self::$SHOW_CHILDREN_WHEN_SELECTED |
				  				self::$SHOW_CHILDREN_ALWAYS
				  			)
						)
					) or (
						$path[$level] == Dataface_Menu_URLMap::$CHILD and (
							$this->showChildrenSetting & (
								self::$SHOW_CHILDREN_WHEN_PARENT |
								self::$SHOW_CHILDREN_WHEN_ANCESTOR |
								self::$SHOW_CHILDREN_ALWAYS
							)
						)
					) or (
						$path[$level] == Dataface_Menu_URLMap::$DESCENDENT and (
							$this->showChildrenSetting & (
								self::$SHOW_CHILDREN_WHEN_ANCESTOR |
								self::$SHOW_CHILDREN_ALWAYS
							)
						)
					)
				){
				
					
				foreach ($this->getChildren() as $child){
					$menu[] = $child->selfToMenuStruct(array('level'=>$level));
					if ( $child->showChildrenSetting & self::$SHOW_CHILDREN_ALWAYS ){
						$child->buildMenu($path, $level+1, $pageTitle, $menu);
					}
				}
				
				
				// We don't do anything here
				
			} 
			
			
			
		} else */if ( $level < count($path)-1 ){
			//echo "there";
			// Find out if we are showing children.
			$breadCrumb = ( $path[$level]->menuID == $this->menuID );
			$parent = ( 
				$breadCrumb and ( 
					(
						$level == count($path)-2 and (
							$path[$level+1] == Dataface_Menu_URLMap::$CHILD
						)
					) or (
						$level == count($path)-3 and (
							$path[$level+2] == Dataface_Menu_URLMap::$SELF
						)
					)
				)
			);
			$selected = ( $breadCrumb and ($level == count($path)-2) and $path[$level+1] == Dataface_Menu_URLMap::$SELF );
			$showChildren = (
				(
					$selected and (
						$this->showChildrenSetting & (
							self::$SHOW_CHILDREN_ALWAYS |
							self::$SHOW_CHILDREN_WHEN_SELECTED
						)
					)
				) or (
					$parent and (
						$this->showChildrenSetting & (
							self::$SHOW_CHILDREN_ALWAYS |
							self::$SHOW_CHILDREN_WHEN_PARENT |
							self::$SHOW_CHILDREN_WHEN_ANCESTOR
						)
					)
				) or (
					($breadCrumb and !$parent and !$selected) and (
						$this->showChildrenSetting & (
							self::$SHOW_CHILDREN_ALWAYS |
							self::$SHOW_CHILDREN_WHEN_ANCESTOR
						)
					)
				)
			);
			//echo "[Show children $showChildren]";
			
			
			$menu[] = $this->selfToMenuStruct(array(
				'selected'=>$selected,
				'parent'=>$parent,
				'breadCrumb'=>$breadCrumb,
				'level'=>$level
			));
			
			if ( $level == count($path) - 2 and $path[$level+1] != Dataface_Menu_URLMap::$SELF and $path[$level]->getId()==$this->getId() ){
				$item = new Dataface_Menu_Item($pageTitle, '#', $this);
				$menu[] = $item->selfToMenuStruct(array(
					'selected'=>true,
					'level'=>$level+1
				));
			}
			foreach ($this->getChildren() as $child){
				if ( $showChildren or $path[$level+1]->menuID == $child->menuID ){
					
					$child->buildMenu($path, $level+1, $pageTitle, $menu);
				} else {
					
				}
				unset($child);
			}
			
			
		} else {
			// We are beyond the throws of the path... we're just finishing up.
			//echo "Here";
			$menu[] = $this->selfToMenuStruct(array('level'=>$level));
			if ( $this->showChildrenSetting & self::$SHOW_CHILDREN_ALWAYS ){
				foreach ($this->children as $child ){
					$child->buildMenu($path, $level+1, $pageTitle, $menu);
				}
			}
		
		
		}
		
		return $this;
		
	}
	
	
	
	
	public function selfToMenuStruct($params=array()){
		$defaults = array(
			'menuID'=>$this->menuID,
			'url'=>$this->url,
			'label'=>$this->label,
			'selected'=>false,
			'parent'=>false,
			'breadCrumb'=>false,
			'level'=>0
		);
		
		return array_merge($defaults, $params);
	}	
	
	private function _sort(){
		uasort($this->children, array(&$this, '_cmp'));
		$this->sorted = true;
		return $this;
	}
	
	private function _cmp($a, $b){
		if ( $a->order == $b->order ) return 0;
		else return ($a->order<$b->order)?-1:1;
	}
	
	
	
	public function getId(){ return $this->menuID;}
	public function getLabel(){ return $this->label;}
	public function getURL(){ return $this->url;}
	public function getParent(){ return $this->parent;}
	public function setId($id){ $this->menuID = $id;}
	public function setShowChildrenSetting($setting){
		$this->showChildrenSetting = $setting;
		return $this;
	}
	public function getShowChildrenSetting(){ return $this->showChildrenSetting;}
	
	public function getChildren(){
		if ( !$this->sorted ) $this->_sort();
		return $this->children;
	}
	
	
}



