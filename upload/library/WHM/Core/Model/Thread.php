<?php

/**
 * Thread model
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_Model_Thread extends XFCP_WHM_Core_Model_Thread
{
	/**
	 * Standard approach to caching prepareThread callbacks.
	 *
	 * @var array
	 */
	protected $_callbacksCache = array();

	public function prepareThreadFetchOptions(array $fetchOptions)
	{
		$threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);
		$dwCoreFields = WHM_Core_Application::getInstance()->get(
			WHM_Core_Application::DW_FIELDS,
			'XenForo_DataWriter_Discussion_Thread'
		);

		unset($dwCoreFields['xf_thread']);
		if ($dwCoreFields && is_array($dwCoreFields))
		{
			foreach($dwCoreFields as $table => $fields)
			{
				unset($fields['thread_id']);
				if ($fields && is_array($fields))
				{
					$threadFetchOptions['selectFields'] .= ',
					' . $table . '.' . implode(', ' . $table . '.', $fields);

					$threadFetchOptions['joinTables'] .= '
					LEFT JOIN ' . $table . ' AS ' . $table . ' ON
							(thread.thread_id = ' . $table . '.thread_id)';
				}
			}
		}

		return $threadFetchOptions;
	}
}