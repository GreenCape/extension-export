<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

/**
 * ExtensionExport Plugin.
 *
 * @since  1.0.0
 */
class PlgSystemExtensionExport extends CMSPlugin
{
	/** @noinspection GenericObjectTypeUsageInspection */
	/**
	 * Constructor.
	 *
	 * @param   object $subject The object to observe.
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 * @throws \RuntimeException
	 */
	public function __construct($subject, $config)
	{
		$this->autoloadLanguage = true;

		parent::__construct($subject, $config);

		JLoader::register(
			'InstallerControllerExtensions',
			__DIR__ . '/lib/InstallerControllerExtensions.php'
		);
		JLoader::register(
			'InstallerViewDefault',
			JPATH_ADMINISTRATOR . '/components/com_installer/views/default/view.php'
		);
		JLoader::register(
			'InstallerViewManage',
			__DIR__ . '/lib/InstallerViewManage.php'
		);
		JLoader::register(
			'ContentHelper',
			JPATH_ADMINISTRATOR . '/components/com_content/helpers/content.php'
		);
		JLoader::registerNamespace(
			'GreenCape\\Extension\\',
			__DIR__ . '/lib',
			false,
			false,
			'psr4'
		);
	}
}
