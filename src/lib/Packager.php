<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace GreenCape\Extension;

use JPath;

defined('_JEXEC') or die;

/**
 * ExtensionExport Packager
 *
 * @since  __DEPLOY_VERSION__
 */
class Packager
{
	/**
	 * Data for use in the package manifest
	 *
	 * @var string[]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $data = [
		'name'         => '',
		'author'       => '',
		'creationDate' => '',
		'packagename'  => 'exported',
		'version'      => '0.0.0',
		'url'          => '',
		'packager'     => 'GreenCape Extension Exporter',
		'packagerurl'  => 'https://github.com/GreenCape/extension-export',
		'copyright'    => '',
		'description'  => 'This package was exported using GreenCape Extension Exporter.',
	];

	/**
	 * Data for the files section of the package manifest
	 *
	 * @var string[][]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private $files = [];

	/**
	 * Create the package
	 *
	 * @param array  $packageData
	 * @param string $exportPath
	 *
	 * @return string
	 * @throws \UnexpectedValueException
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function export($packageData, $exportPath)
	{
		$authors    = [];
		$copyrights = [];
		$version    = '0.0.0';

		$zipper = new Zipper();

		foreach ($packageData as $data)
		{
			$authors[]    = $data->author;
			$copyrights[] = $data->copyright;
			$version      = version_compare($version, $data->version, '<') ? $data->version : $version;
			$this->addFile($data->filename, $data->type, $data->element, $data->folder);
			$zipper->addFile(
				$data->filename,
				file_get_contents($exportPath . '/' . $data->filename),
				filemtime($exportPath . '/' . $data->filename)
			);
		}

		$this->data['name']      = ucfirst($this->data['packagename']) . ' Extension Package';
		$this->data['author']    = $this->combineAuthors($authors);
		$this->data['copyright'] = $this->combineCopyrights($copyrights);
		$this->data['version']   = $version;

		$element = "pkg_{$this->data['packagename']}";

		$zipper->addFile(
			"{$element}.xml",
			$this->nodeToString($this->getManifest()),
			time()
		);

		$zipper->create(JPath::clean("{$exportPath}/{$element}-{$version}.zip"));

		return "$element-$version";
	}

	/**
	 * Set the package name
	 *
	 * @param string $packageName
	 *
	 * @return Packager
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setPackageName($packageName)
	{
		$this->data['packagename'] = $packageName;

		return $this;
	}

	/**
	 * Combine author names from the included extensions
	 *
	 * The unique authors are combined forming a comma separated list.
	 *
	 * @param array $authors
	 *
	 * @return string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function combineAuthors($authors)
	{
		return implode(', ', array_unique($authors));
	}

	/**
	 * Combine copyright notices from the included extensions
	 *
	 * The substrings 'Copyright (C)' and 'All rights reserved.' are removed from all entries, if present.
	 * The remaining unique entries are combined forming a comma separated list.
	 * The substrings are added again, if they were present in at least one extension.
	 *
	 * @param string[] $copyrights
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private function combineCopyrights($copyrights)
	{
		$foundCopyright = false;
		$foundReserved  = false;
		foreach ($copyrights as $key => $copyright)
		{
			if (preg_match('~^\s*(Copy\w+\s*)?(\(C\)\s*)?~i', $copyright, $match))
			{
				$copyright      = str_replace($match[0], '', $copyright);
				$foundCopyright = trim($match[0] > '');
			}
			if (preg_match('~\s*\.?\s*All rights reserved\W*$~', $copyright, $match))
			{
				$copyright     = str_replace($match[0], '', $copyright);
				$foundReserved = true;
			}
			$copyrights[$key] = $copyright;
		}

		return ($foundCopyright ? 'Copyright (C) ' : '') . trim(implode(', ',
				array_unique($copyrights))) . ($foundReserved ? '. All rights reserved.' : '');
	}

	/**
	 * Create the manifest from the collected data
	 *
	 * @return \SimpleXMLElement
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private function getManifest()
	{
		$this->data['creationDate'] = date('Y-m-d');

		$manifest = simplexml_load_string('<extension version="3.3.0" type="package" method="upgrade"></extension>');
		foreach ($this->data as $tag => $content)
		{
			if (empty($content))
			{
				continue;
			}

			$manifest->addChild($tag, $content);
		}

		$files = $manifest->addChild('files');

		foreach ($this->files as $filename => $attributes)
		{
			$node = $files->addChild('file', $filename);

			foreach ($attributes as $attribute => $value)
			{
				if (empty($value))
				{
					continue;
				}

				$node->addAttribute($attribute, $value);
			}
		}

		return $manifest;
	}

	/**
	 * Add a file to the manifest
	 *
	 * @param string $filename
	 * @param string $type
	 * @param string $id
	 * @param string $group
	 *
	 * @return Packager
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	private function addFile($filename, $type, $id, $group = null)
	{
		$this->files[$filename] = compact('type', 'id', 'group');

		return $this;
	}

	/**
	 * Convert a SimpleXMLElement to a pretty printed XML string
	 *
	 * @param \SimpleXMLElement $node
	 *
	 * @return string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function nodeToString($node)
	{
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->appendChild($dom->importNode(dom_import_simplexml($node), true));
		$dom->formatOutput = true;

		return $dom->saveXML();
	}
}
