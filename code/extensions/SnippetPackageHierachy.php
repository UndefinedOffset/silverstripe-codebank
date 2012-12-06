<?php
class SnippetPackageHierachy extends SnippetHierarchy {
	/**
	 * Returns the children of this DataObject as an XHTML UL. This will be called recursively on each child,
	 * so if they have children they will be displayed as a UL inside a LI.
	 * @param string $attributes Attributes to add to the UL.
	 * @param string|callable $titleEval PHP code to evaluate to start each child - this should include '<li>'
	 * @param string $extraArg Extra arguments that will be passed on to children, for if they overload this function.
	 * @param boolean $limitToMarked Display only marked children.
	 * @param string $childrenMethod The name of the method used to get children from each object
	 * @param boolean $rootCall Set to true for this first call, and then to false for calls inside the recursion. You should not change this.
	 * @param int $minNodeCount
	 * @return string
	 */
    public function getChildrenAsUL($attributes="", $titleEval='"<li>" . $child->Title', $extraArg=null, $limitToMarked=false, $childrenMethod="AllChildrenIncludingDeleted", $numChildrenMethod="numChildren", $rootCall=true, $minNodeCount=30) {
        if($limitToMarked && $rootCall) {
            $this->markingFinished($numChildrenMethod);
        }
    
        if($this->owner->hasMethod($childrenMethod)) {
            $children=$this->owner->$childrenMethod($extraArg);
        } else {
            user_error(sprintf("Can't find the method '%s' on class '%s' for getting tree children",
                    $childrenMethod, get_class($this->owner)), E_USER_ERROR);
        }
    
        if($children) {
            if($attributes) {
                $attributes=" $attributes";
            }
    
            $output="<ul$attributes>\n";
    
            foreach($children as $child) {
                if(!$limitToMarked || $child->isMarked()) {
                    $foundAChild=true;
                    $output .= (is_callable($titleEval)) ? $titleEval($child) : eval("return $titleEval;");
                    $output .= "</li>\n";
                }
            }
    
            $output .= "</ul>\n";
        }
    
        if(isset($foundAChild) && $foundAChild) {
            return $output;
        }
    }
}
?>