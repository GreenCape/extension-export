<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace GreenCape\Extension;

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/**
 * ExtensionExport DataMapper
 *
 * @since  1.0.0
 */
class DataMapper
{
	/**
	 * Get extension details from database
	 *
	 * @param int $extensionId
	 *
	 * @return \stdClass
	 * @throws \RuntimeException
	 *
	 * @since 1.0.0
	 */
	public function getExtensionDetails($extensionId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('package_id, element, type, client_id, folder, manifest_cache')
			->from('#__extensions')
			->where("extension_id = $extensionId")
		;
		$db->setQuery($query);

		$attributes    = $db->loadAssoc();
		$manifestCache = json_decode($attributes['manifest_cache'], true);

		return (object) array_merge($attributes, $manifestCache);
	}
}
