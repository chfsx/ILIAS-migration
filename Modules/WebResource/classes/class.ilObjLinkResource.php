<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once "./classes/class.ilObject.php";

/** @defgroup ModulesWebResource Modules/WebResource
 */

/**
* Class ilObjLinkResource
* 
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
*
* @ingroup ModulesWebResource
*/
class ilObjLinkResource extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjLinkResource($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "lnkr";
		$this->ilObject($a_id,$a_call_by_reference);
	}

	/**
	* create object
	*/
	function create()
	{
		parent::create();
		$this->createMetaData();
	}

	/**
	* update object
	*/
	function update()
	{
		$this->updateMetaData();
		parent::update();
	}

	/**
	* copy all entries of your object.
	*
	* @access	public
	* @param	integer	ref_id of parent object
	* @return	integer	new ref id
	*/
	function ilClone($a_parent_ref)
	{		
		global $rbacadmin;

		// always call parent clone function first!!
		$new_ref_id = parent::ilClone($a_parent_ref);
		
		// get object instance of cloned object
		//$newObj =& $this->ilias->obj_factory->getInstanceByRefId($new_ref_id);

		// create a local role folder & default roles
		//$roles = $newObj->initDefaultRoles();

		// ...finally assign role to creator of object
		//$rbacadmin->assignUser($roles[0], $newObj->getOwner(), "n");		

		// always destroy objects in clone method because clone() is recursive and creates instances for each object in subtree!
		//unset($newObj);

		// ... and finally always return new reference ID!!
		return $new_ref_id;
	}

	/**
	* delete object and all related data
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}

		// delete items
		include_once './Modules/WebResource/classes/class.ilLinkResourceItems.php';
		ilLinkResourceItems::_deleteAll($this->getId());


		// Delete notify entries
		include_once './classes/class.ilLinkCheckNotify.php';
		ilLinkCheckNotify::_deleteObject($this->getId());

		// delete meta data
		$this->deleteMetaData();

		return true;
	}

	function initLinkResourceItemsObject()
	{
		include_once './Modules/WebResource/classes/class.ilLinkResourceItems.php';

		$this->items_obj =& new ilLinkResourceItems($this->getId());

		return true;
	}
	
	/**
	 * Clone
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function cloneObject($a_target_id)
	{
	 	$new_obj = parent::cloneObject($a_target_id);
	 	$this->cloneMetaData($new_obj);
	 	
	 	// object created now copy other settings
	 	include_once('Modules/WebResource/classes/class.ilLinkResourceItems.php');
	 	$links = new ilLinkResourceItems($this->getId());
	 	$links->cloneItems($new_obj->getId());
	 	
	 	return $new_obj;
	}

	// PRIVATE


} // END class.ilObjLinkResource
?>
