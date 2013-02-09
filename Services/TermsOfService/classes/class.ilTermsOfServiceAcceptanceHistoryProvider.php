<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/TermsOfService/classes/class.ilTermsOfServiceTableDatabaseDataProvider.php';

/**
 * @author  Michael Jansen <mjansen@databay.de>
 * @version $Id$
 */
class ilTermsOfServiceAcceptanceHistoryProvider extends ilTermsOfServiceTableDatabaseDataProvider
{
	/**
	 * @return string
	 */
	protected function getSelectPart()
	{
		$fields = array(
			'ud.usr_id',
			'ud.login',
			'ud.firstname',
			'ud.lastname',
			'tos_acceptance_track.ts',
			'tos_versions.path',
			'tos_versions.text',
			'tos_versions.lng'
		);

		return implode(', ', $fields);
	}

	/**
	 * @return string
	 */
	protected function getFromPart()
	{
		$joins = array(
			'INNER JOIN tos_acceptance_track ON tos_acceptance_track.usr_id = ud.usr_id',
			'INNER JOIN tos_versions ON tos_versions.id = tos_acceptance_track.tosv_id',
		);
		
		return 'usr_data ud ' . implode(' ', $joins);
	}

	/**
	 * @param array $filter
	 * @return string
	 */
	protected function getWherePart(array $filter)
	{
		$where = array();

		if(isset($filter['query']) && strlen($filter['query']))
		{
			$where[] = '(' . implode(' OR ', array(
				$this->db->like('ud.login', 'text', '%'.$filter['query'].'%'),
				$this->db->like('ud.firstname', 'text', '%'.$filter['query'].'%'),
				$this->db->like('ud.lastname', 'text', '%'.$filter['query'].'%'),
				$this->db->like('ud.email', 'text', '%'.$filter['query'].'%')
			)) . ')';
		}

		if(isset($filter['lng']) && strlen($filter['lng']))
		{
			$where[] = 'tos_versions.lng = ' . $this->db->quote($filter['lng'], 'text');
		}

		if(isset($filter['period']) && is_array($filter['period']))
		{
			$where[] = '(' . implode(' AND ', array(
				'tos_acceptance_track.ts >= ' . $this->db->quote($filter['period']['start'], 'integer'),
				'tos_acceptance_track.ts <= ' . $this->db->quote($filter['period']['end'], 'integer')
			)) . ')';
		}

		return implode(' AND ', $where);
	}

	/**
	 * @return string
	 */
	protected function getGroupByPart()
	{
		return '';
	}

	/**
	 * @param array $filter
	 * @return mixed
	 */
	protected function getHavingPart(array $filter)
	{
		return '';
	}

	/**
	 * @param array $params
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected function getOrderByPart(array $params)
	{
		if(isset($params['order_field']))
		{
			if(!is_string($params['order_field']))
			{
				throw new InvalidArgumentException('Please provide a valid order field.');
			}
			
			if(!in_array($params['order_field'], array('lng', 'login', 'firstname', 'lastname', 'path', 'ts')))
			{
				throw new InvalidArgumentException('Please provide a valid order field.');
			}
			
			if($params['order_field'] == 'ts')
			{
				$params['order_field'] = 'tos_acceptance_track.ts';
			}

			if(!isset($params['order_direction']))
			{
				$params['order_direction'] = 'ASC';
			}
			else if(!in_array(strtolower($params['order_direction']), array('asc', 'desc')))
			{
				throw new InvalidArgumentException('Please provide a valid order direction.');
			}

			return $params['order_field'] . ' ' . $params['order_direction'];
		}
		
		return '';
	}
}
