<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace GreenCape\Extension;

use /** @noinspection PhpDeprecationInspection */
	JArchiveZip;

defined('_JEXEC') or die;

/**
 * ExtensionExport Zipper
 *
 * @since  __DEPLOY_VERSION__
 */
class Zipper
{
	/**
	 * Files to include in the archive
	 *
	 * @var string[][]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private $files = [];

	/**
	 * Add a file to the archive
	 *
	 * @param string $name Name of the file
	 * @param string $data Content of the file
	 * @param int    $time UNIX timestamp of creation time
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addFile($name, $data, $time)
	{
		$this->files[] = [
			'name' => $name,
			'data' => $data,
			'time' => $time,
		];
	}

	/**
	 * Create the archive
	 *
	 * @param string $archive Filename of the archive
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function create($archive)
	{
		/** @noinspection PhpDeprecationInspection No replacement available */
		(new JArchiveZip)->create($archive, $this->files);
	}
}
