<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/** @noinspection PhpUndefinedClassInspection */
/**
 * Extension Manager Manage View
 *
 * @since  1.0.0
 */
class InstallerViewManage extends InstallerViewDefault
{
	/**
	 * @var array
	 * @since 1.0.0
	 */
	protected $items;

	/**
	 * @var Pagination
	 * @since 1.0.0
	 */
	protected $pagination;

	/**
	 * @var Form
	 * @since 1.0.0
	 */
	protected $form;

	/**
	 * @var Registry
	 * @since 1.0.0
	 */
	protected $state;

	/**
	 * Display the view.
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 * @throws Exception
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/helpers/html');

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_installer');

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('manage.publish', 'JTOOLBAR_ENABLE', true);
			JToolbarHelper::unpublish('manage.unpublish', 'JTOOLBAR_DISABLE', true);
			JToolbarHelper::divider();
		}

		JToolbarHelper::custom('manage.refresh', 'refresh', 'refresh', 'JTOOLBAR_REFRESH_CACHE', true);
		JToolbarHelper::divider();

		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('COM_INSTALLER_CONFIRM_UNINSTALL', 'manage.remove', 'JTOOLBAR_UNINSTALL');
			JToolbarHelper::divider();
		}

		JToolbarHelper::custom('extensions.export', 'share', 'share', 'PLG_SYSTEM_EXTENSIONEXPORT_BUTTON_EXPORT');

		JHtmlSidebar::setAction('index.php?option=com_installer&view=manage');

		parent::addToolbar();
		JToolbarHelper::help('JHELP_EXTENSIONS_EXTENSION_MANAGER_MANAGE');
	}
}
