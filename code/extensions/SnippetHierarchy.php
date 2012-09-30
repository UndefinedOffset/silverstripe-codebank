<?php
class SnippetHierarchy extends Hierarchy {
    public static function get_extra_config($class, $extension, $args) {}

	/**
	 * Validate the owner object - check for existence of infinite loops.
	 */
	public function validate(ValidationResult $validationResult) {
		if (!$this->owner->ID) return; // The object is new, won't be looping.
		if (!$this->owner->LanguageID) return; // The object has no parent, won't be looping.
		if (!$this->owner->isChanged('LanguageID')) return; // The parent has not changed, skip the check for performance reasons.

		// Walk the hierarchy upwards until we reach the top, or until we reach the originating node again.
		$node = $this->owner;
		while($node) {
			if ($node->LanguageID==$this->owner->ID) {
				// Hierarchy is looping.
				$validationResult->error(
					_t(
						'Hierarchy.InfiniteLoopNotAllowed',
						'Infinite loop found within the "{type}" hierarchy. Please change the parent to resolve this',
						'First argument is the class that makes up the hierarchy.',
						array('type' => $this->owner->class)
					),
					'INFINITE_LOOP'
				);
				break;
			}
			$node = $node->LanguageID ? $node->Language() : null;
		}

		// At this point the $validationResult contains the response.
	}
    
	/**
	 * Return an array of this page and its ancestors, ordered item -> root.
	 * @return array
	 */
	public function parentStack() {
		$p = $this->owner;
		
		while($p) {
			$stack[] = $p;
			$p = $p->LanguageID ? $p->Language() : null;
		}
		
		return $stack;
	}
	
	/**
	 * Get the parent of this class.
	 * @return DataObject
	 */
	public function getParent($filter = '') {
		return $this->owner->Language();
	}
	
	/**
	 * Return all the parents of this class in a set ordered from the lowest to highest parent.
	 *
	 * @return SS_List
	 */
	public function getAncestors() {
		$ancestors = new ArrayList();
		$object    = $this->owner;
		
		while($object = $object->Language()) {
			$ancestors->push($object);
		}
		
		return $ancestors;
	}
    
    /**
	 * Get the next node in the tree of the type. If there is no instance of the className descended from this node,
	 * then search the parents.
	 * @param string $className Class name of the node to find.
	 * @param string|int $root ID/ClassName of the node to limit the search to
	 * @param DataObject afterNode Used for recursive calls to this function
	 * @return DataObject
	 */
	public function naturalNext( $className = null, $root = 0, $afterNode = null ) {
		// If this node is not the node we are searching from, then we can possibly return this
		// node as a solution
		if($afterNode && $afterNode->ID != $this->owner->ID) {
			if(!$className || ($className && $this->owner->class == $className)) {
				return $this->owner;
			}
		}
			
		$nextNode = null;
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		
		$children = DataObject::get(ClassInfo::baseDataClass($this->owner->class), "\"$baseClass\".\"LanguageID\"={$this->owner->ID}" . ( ( $afterNode ) ? " AND \"Sort\" > " . sprintf( '%d', $afterNode->Sort ) : "" ), '"Sort" ASC');
		
		// Try all the siblings of this node after the given node
		/*if( $siblings = DataObject::get( ClassInfo::baseDataClass($this->owner->class), "\"LanguageID\"={$this->owner->LanguageID}" . ( $afterNode ) ? "\"Sort\" > {$afterNode->Sort}" : "" , '\"Sort\" ASC' ) )
			$searchNodes->merge( $siblings );*/
		
		if($children) {
			foreach($children as $node) {
				if($nextNode = $node->naturalNext($className, $node->ID, $this->owner)) {
					break;
				}
			}
			
			if($nextNode) {
				return $nextNode;
			}
		}
		
		// if this is not an instance of the root class or has the root id, search the parent
		if(!(is_numeric($root) && $root == $this->owner->ID || $root == $this->owner->class) && ($parent = $this->owner->Language())) {
			return $parent->naturalNext( $className, $root, $this->owner );
		}
		
		return null;
	}
	
	/**
	 * Mark a segment of the tree, by calling mark().
	 * The method performs a breadth-first traversal until the number of nodes is more than minCount.
	 * This is used to get a limited number of tree nodes to show in the CMS initially.
	 *
	 * This method returns the number of nodes marked.  After this method is called other methods
	 * can check isExpanded() and isMarked() on individual nodes.
	 *
	 * @param int $minNodeCount The minimum amount of nodes to mark.
	 * @return int The actual number of nodes marked.
	 */
	public function markPartialTree($minNodeCount = 30, $context = null, $childrenMethod="AllChildrenIncludingDeleted", $numChildrenMethod="numChildren") {
		if(!is_numeric($minNodeCount)) $minNodeCount = 30;

		$this->markedNodes=array($this->owner->ClassName.'_'.$this->owner->ID=>$this->owner);
		$this->owner->markUnexpanded();

		// foreach can't handle an ever-growing $nodes list
		while(list($id, $node)=each($this->markedNodes)) {
			$this->markChildren($node, $context, $childrenMethod, $numChildrenMethod);
			if($minNodeCount && sizeof($this->markedNodes)>=$minNodeCount) {
				break;
			}
		}
		
		return sizeof($this->markedNodes);
	}
	
	/**
	 * Mark all children of the given node that match the marking filter.
	 * @param DataObject $node Parent node.
	 */
	public function markChildren($node, $context=null, $childrenMethod='AllChildrenIncludingDeleted', $numChildrenMethod='numChildren') {
		if($node->hasMethod($childrenMethod)) {
			$children = $node->$childrenMethod($context);
		}else {
			user_error(sprintf("Can't find the method '%s' on class '%s' for getting tree children", $childrenMethod, get_class($node)), E_USER_ERROR);
		}
		
		$node->markExpanded();
		if($children) {
			foreach($children as $child) {
				if(!$this->markingFilter || $this->markingFilterMatches($child)) {
					if($child->$numChildrenMethod()) {
						$child->markUnexpanded();
					}else {
						$child->markExpanded();
					}
					
					$this->markedNodes[$child->ClassName.'_'.$child->ID]=$child;
				}
			}
		}
	}
	
	/**
	 * Mark the children of the DataObject with the given ID.
	 * @param int $id ID of parent node.
	 * @param boolean $open If this is true, mark the parent node as opened.
	 */
	public function markById($id, $open=false, $className=null) {
	    if(isset($this->markedNodes[$className.'_'.$id])) {
			$this->markChildren($this->markedNodes[$className.'_'.$id]);
			if($open) {
				$this->markedNodes[$className.'_'.$id]->markOpened();
			}
			
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Expose the given object in the tree, by marking this page and all it ancestors.
	 * @param DataObject $childObj
	 */
	public function markToExpose($childObj) {
		if(is_object($childObj)){
			$stack = array_reverse($childObj->parentStack());
			foreach($stack as $stackItem) {
				$this->markById($stackItem->ID, true, $stackItem->ClassName);
			}
		}
	}
	
	/**
	 * Return the number of direct children.
	 * By default, values are cached after the first invocation.
	 * Can be augumented by {@link augmentNumChildrenCountQuery()}.
	 *
	 * @param Boolean $cache
	 * @return int
	 */
	public function numChildren($cache = true) {
		// Build the cache for this class if it doesn't exist.
		if(!$cache || !is_numeric($this->_cache_numChildren)) {
		    if($this->owner instanceof SnippetLanguage) {
		        $this->_cache_numChildren=(int)$this->owner->Snippets()->Count();
		    }else {
		        $this->_cache_numChildren=0;
		    }
		}

		// If theres no value in the cache, it just means that it doesn't have any children.
		return $this->_cache_numChildren;
	}
}
?>