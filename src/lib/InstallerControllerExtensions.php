<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

use GreenCape\Extension\DataMapper;
use GreenCape\Extension\Exporter;
use GreenCape\Extension\Packager;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Registry\Registry;

/**
 * @package     GreenCape\Extension
 *
 * @since       1.0.0
 */
class InstallerControllerExtensions extends BaseController
{
	/**
	 * Export an extension
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function export()
	{
		$app          = Factory::getApplication();
		$input        = $app->input;
		$extensionIds = (array) $input->get('cid', [], 'array');
		$dataMapper   = new DataMapper();
		$params       = new Registry(JPluginHelper::getPlugin('system', 'extensionexport')->params);
		$exportPath   = JPATH_ROOT . '/' . $params->get('directory', 'images/dist');
		$fileMode     = (int) octdec('0' . $params->get('filemode', '644'));
		$dirMode      = (int) octdec('0' . $params->get('dirmode', '755'));
		$delDirs      = (bool) (int) $params->get('del_dirs', '1');

		$doCreatePackage = count($extensionIds) > 1;
		$packageData     = [];

		foreach ($extensionIds as $extensionId)
		{
			$attributes                = $dataMapper->getExtensionDetails($extensionId);
			$element                   = $attributes->element;
			$type                      = $attributes->type;
			$clientId                  = (int) $attributes->client_id;
			$pluginGroup               = $attributes->folder;
			$packageData[$extensionId] = $attributes;

			try
			{
				$exporter = new Exporter($exportPath, $dirMode, $fileMode);
				$package  = $exporter->export($element, $type, $clientId, $pluginGroup);
				chmod("{$exportPath}/{$package}.zip", $fileMode);
				$packageData[$extensionId]->filename = "$package.zip";

				if ($delDirs)
				{
					$exporter->removeArtifacts();
				}

				$this->issueSuccessMessage($package, $type, $exportPath);
			}
			catch (Throwable $exception)
			{
				$doCreatePackage = false;

				$this->issueFailureMessage($element, $type, $exception);
			}
		}

		if ($doCreatePackage)
		{
			$element = 'exported';

			try
			{
				$packager = new Packager();

				$filters = $app->input->get('filter', [], 'array');
				if (!empty($filters['search']))
				{
					$element = trim(preg_replace('~\W+~', '_', $filters['search']), '_');
					$packager->setPackageName($element);
				}

				$package = $packager->export($packageData, $exportPath);
				chmod("{$exportPath}/{$package}.zip", $fileMode);

				$this->issueSuccessMessage($package, 'package', $exportPath);
			}
			catch (Throwable $exception)
			{
				$this->issueFailureMessage($element, 'package', $exception);
			}
		}

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=manage', false));
	}

	/**
	 * @param string $package
	 * @param string $type
	 * @param string $exportPath
	 *
	 * @since __DEPLOY_VERSION__
	 * @throws Exception
	 */
	private function issueSuccessMessage($package, $type, $exportPath)
	{
		$app = Factory::getApplication();
		$app->enqueueMessage(
			Text::sprintf(
				'PLG_SYSTEM_EXTENSIONEXPORT_MESSAGE_EXPORT_SUCCESS',
				$type,
				$package,
				str_replace(JPATH_ROOT, '', $exportPath) . "/{$package}.zip"
			)
		);
	}

	/**
	 * @param string    $element
	 * @param string    $type
	 * @param Throwable $exception
	 *
	 * @since __DEPLOY_VERSION__
	 * @throws Exception
	 */
	private function issueFailureMessage($element, $type, $exception)
	{
		$app = Factory::getApplication();
		$app->enqueueMessage(
			Text::sprintf(
				'PLG_SYSTEM_EXTENSIONEXPORT_MESSAGE_EXPORT_FAILURE',
				$type,
				$element,
				$exception->getMessage()
			),
			'error'
		);
	}
}
