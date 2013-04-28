<?php

/**
 * Post model
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_Model_Post extends XFCP_WHM_Core_Model_Post
{

	/**
	 * Checks the 'join' key of the incoming array for the presence of the FETCH_x bitfields in this class
	 * and returns SQL snippets to join the specified tables if required
	 *
	 * @param array $fetchOptions containing a 'join' integer key build from this class's FETCH_x bitfields
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys. Example: selectFields = ', user.*, foo.title'; joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
	 */
	public function preparePostJoinOptions(array $fetchOptions)
	{
		$postFetchOptions = parent::preparePostJoinOptions($fetchOptions);
		$dwCoreFields = WHM_Core_Application::getInstance()->get(
			WHM_Core_Application::DW_FIELDS,
			'XenForo_DataWriter_DiscussionMessage_Post'
		);

		unset($dwCoreFields['xf_post']);
		if ($dwCoreFields && is_array($dwCoreFields))
		{
			foreach ($dwCoreFields as $table => $fields)
			{
				unset($fields['post_id']);
				if ($fields && is_array($fields))
				{
					$postFetchOptions['selectFields'] .= ',
					' . $table . '.' . implode(', ' . $table . '.', $fields);

					$postFetchOptions['joinTables'] .= '
					LEFT JOIN ' . $table . ' AS ' . $table . ' ON
							(post.post_id = ' . $table . '.post_id)';
				}
			}
		}

		return $postFetchOptions;
	}
}