<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

/**
* @author Michael Jansen <mjansen@databay.de>
* @version $Id$
*
* @ingroup ServicesMail
*/
class ilMailingList
{
	private $mail_id = 0;
	private $user_id = 0;
	private $title = '';
	private $description = '';
	private $createdate = '0000-00-00 00:00:00';
	private $changedate = '0000-00-00 00:00:00';
	
	private $user = null;	
	private $db = null;
	
	public function __construct(ilObjUser $user, $id = 0)
	{
		global $ilDB;

		$this->db = $ilDB;
		$this->user = $user;
		
		$this->mail_id = $id;
		$this->user_id = $this->user->getId();
		
	
		$this->read();
	}
	
	public function insert()
	{
		$statement = $this->db->manipulateF('
			INSERT INTO addressbook_mlist 
			SET ml_id = %s, 
				user_id = %s, 
				title = %s, 
				description = %s, 
				createdate = %s, 
				changedate = %s',
			array(	'integer',
					'integer',
					'text', 
					'text', 
					'timestamp',
					'timestamp'),
			array(	'', 
					$this->getUserId(), 
					$this->getTitle(), 
					$this->getDescription(), 
					$this->getCreatedate(), 
					''
		));
		
		$this->mail_id = $this->db->getLastInsertId();
		
		return true;
	}
	
	public function update()
	{
		if ($this->mail_id && $this->user_id)
		{
			$statement = $this->db->manipulateF('
				UPDATE addressbook_mlist
				SET title = %s,
					description = %s,
					changedate =  %s
				WHERE 1
				AND ml_id =  %s
				AND user_id =  %s',
				array(	'text',
						'text',
						'timestamp',
						'integer',
						'integer'
				),
				array(	$this->getTitle(),
							$this->getDescription(),
							$this->getChangedate(),
							$this->getId(),
							$this->getUserId()
			));
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function delete()
	{
		if ($this->mail_id && $this->user_id)
		{
			$this->deassignAllEntries();

			$statement = $this->db->manipulateF('
				DELETE FROM addressbook_mlist
				WHERE 1 
				AND ml_id = %s
				AND user_id = %s',
				array('integer', 'integer'),
				array($this->getId(), $this->getUserId()));
			
			return true;
		}
		else
		{
			return false;
		}		
	}
	
	private function read()
	{		
	
	if ($this->getId() && $this->getUserId())
		{
			$res = $this->db->queryf('
				SELECT * FROM addressbook_mlist 
				WHERE 1 
				AND ml_id = %s
				AND user_id =%s',
				array('integer', 'integer'),
				array($this->getId(), $this->getUserId())); 
	
			$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	
			if (is_object($row))
			{
				$this->setId($row->ml_id);
				$this->setUserId($row->user_id);
				$this->setTitle($row->title);
				$this->setDescription($row->description);
				$this->setCreatedate($row->createdate);
				$this->setChangedate($row->changedae);		
			}
		}		
		
		
		return true;
	}
	
	public function getAssignedEntries()
	{
		$res = $this->db->queryf('
			SELECT * FROM addressbook_mlist_ass 
			INNER JOIN addressbook ON addressbook.addr_id = addressbook_mlist_ass.addr_id 
			WHERE 1
			AND ml_id = %s',
			array('integer'),
			array($this->getId()));
		
		$entries = array();
		
		$counter = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{			
			$entries[$counter] = array('a_id' => $row->a_id, 
									   'addr_id' => $row->addr_id,
									   'login' => $row->login,
									   'email' => $row->email,
									   'firstname' => $row->firstname,
									   'lastname' => $row->lastname									   
									   );
			
			++$counter;
		}
		
		return $entries;
	}
	
	public function assignAddressbookEntry($addr_id = 0)	
	{
		$statement = $this->db->manipulateF('
			INSERT INTO addressbook_mlist_ass 
			SET a_id = %s, 
				ml_id = %s, 
				addr_id = %s',
			array('integer', 'integer', 'integer'),
			array('', $this->getId(), $addr_id));
		
		return true;
	}
	
	public function deassignAddressbookEntry($a_id = 0)	
	{
	
		$statement = $this->db->manipulateF('	
		DELETE FROM addressbook_mlist_ass 
				WHERE 1 
				AND a_id = %s',
				array('integer'),
				array($a_id));
		
		return true;
	}
	
	public function deassignAllEntries()	
	{
		$statement = $this->db->manipulateF('	
		DELETE FROM addressbook_mlist_ass 
				WHERE 1 
				AND ml_id = %s',
				array('integer'),
				array($this->getId()));
			
		return true;
	}
	
	public function setId($a_mail_id = 0)
	{
		$this->mail_id = $a_mail_id;
	}
	public function getId()
	{
		return $this->mail_id;
	}
	public function setUserId($a_user_id = 0)
	{
		$this->user_id = $a_user_id;
	}
	public function getUserId()
	{
		return $this->user_id;
	}
	public function setTitle($a_title = '')
	{
		$this->title = $a_title;
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function setDescription($a_description = '')
	{
		$this->description = $a_description;
	}
	public function getDescription()
	{
		return $this->description;
	}
	public function setCreatedate($_createdate = '0000-00-00 00:00:00')
	{
		$this->createdate = $_createdate;
	}
	public function getCreatedate()
	{
		return $this->createdate;
	}
	public function setChangedate($a_changedate = '0000-00-00 00:00:00')
	{
		$this->changedate = $a_changedate;
	}
	public function getChangedate()
	{
		return $this->changedate;
	}
}
?>
