<?php
/**
 * @package     GreenCape Extension Exporter
 * @author      Niels Braczek <nbraczek@bsds.de>
 *
 * @copyright   Copyright (C) 2012 - 2017 BSDS Braczek Software- und DatenSysteme. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace GreenCape\Extension;

use JArchiveZip;
use JFile;
use JFolder;
use JPath;

jimport('joomla.application.module.controlleradmin');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * @package     GreenCape\Extension
 *
 * @since 1.0.0
 */
class Exporter
{
	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $fileBucket;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $filesTargetPath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $mediaSourcePath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $mediaTargetPath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $languageTargetPath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $adminFilesTargetPath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $adminLanguageTargetPath;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $packageName;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $version;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $extension;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $type;

	/**
	 * @var int
	 * @since 1.0.0
	 */
	private $clientId;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	private $pluginGroup;

	/**
	 * @var string The working directory
	 * @since 1.0.0
	 */
	private $exportDirectory;

	/**
	 * @var int
	 * @since 1.0.0
	 */
	private $dirMode = 0775;

	/**
	 * Exporter constructor.
	 *
	 * @param string $exportDirectory The working directory
	 *
	 * @since 1.0.0
	 */
	public function __construct($exportDirectory)
	{
		$this->exportDirectory = $exportDirectory;
	}

	/**
	 * Export an extension
	 *
	 * @param string $extension   The extension
	 * @param string $type        The extension type
	 * @param int    $clientId    The client
	 * @param string $pluginGroup The plugin group
	 *
	 * @return string The package name
	 * @throws \Exception
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 *
	 * @since 1.0.0
	 */
	public function export($extension, $type, $clientId, $pluginGroup)
	{
		$this->extension   = $extension;
		$this->fileBucket  = $extension;
		$this->type        = $type;
		$this->clientId    = $clientId;
		$this->pluginGroup = $pluginGroup;

		$manifest                      = $this->getManifest();
		$this->version                 = (string) $manifest->version;
		$this->filesTargetPath         = $this->getAttribute($manifest, 'files', 'folder', '');
		$this->mediaSourcePath         = $this->getAttribute($manifest, 'media', 'destination', $this->extension);
		$this->mediaTargetPath         = $this->getAttribute($manifest, 'media', 'folder', '');
		$this->languageTargetPath      = $this->getAttribute($manifest, 'languages', 'folder', '');
		$this->adminFilesTargetPath    = '';
		$this->adminLanguageTargetPath = '';

		if ($this->type === 'plugin')
		{
			$this->fileBucket = "plg_{$this->pluginGroup}_{$this->extension}";
		}

		if ($this->type === 'template')
		{
			$this->fileBucket = "tpl_{$this->extension}";
		}

		if (isset($manifest->administration))
		{
			$this->adminFilesTargetPath    = $this->getAttribute($manifest->administration, 'files', 'folder', '');
			$this->adminLanguageTargetPath = $this->getAttribute($manifest->administration, 'languages', 'folder', '');
		}

		$this->packageName = "{$this->fileBucket}-{$this->version}";

		if (JFolder::exists($this->exportDirectory . '/' . $this->fileBucket))
		{
			$this->removeArtifacts();
		}

		$this->copyExtension();

		if (isset($manifest->languages) || (isset($manifest->administration) && isset($manifest->administration->languages)))
		{
			$this->copyLanguages($manifest);
		}

		if (isset($manifest->media))
		{
			$this->copyMedia();
		}

		$this->zip();

		return $this->packageName;
	}

	/**
	 * Remove artifacts
	 *
	 * @since 1.0.0
	 */
	public function removeArtifacts()
	{
		JFolder::delete($this->exportDirectory . '/' . $this->fileBucket);
		JFile::delete($this->exportDirectory . '/' . $this->packageName . '.zip');
	}

	/**
	 * Copy a media directory
	 *
	 * @throws \RuntimeException
	 *
	 * @since 1.0.0
	 */
	private function copyMedia()
	{
		$this->copyDirectory(
			JPATH_SITE . '/media/' . $this->mediaSourcePath,
			$this->exportDirectory . '/' . $this->fileBucket . '/' . $this->mediaTargetPath
		);
	}

	/**
	 * Copy a plugin directory
	 *
	 * @throws \RuntimeException
	 *
	 * @since 1.0.0
	 */
	private function copyPlugin()
	{
		$this->copyDirectory(
			JPATH_SITE . '/plugins/' . $this->pluginGroup . '/' . $this->extension,
			$this->exportDirectory . '/' . $this->fileBucket
		);
	}

	/**
	 * Copy a module directory
	 *
	 * @throws \RuntimeException
	 *
	 * @since 1.0.0
	 */
	private function copyModule()
	{
		$this->copyDirectory(
			($this->clientId === 0 ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/modules/' . $this->extension,
			$this->exportDirectory . '/' . $this->fileBucket
		);
	}

	/**
	 * Copy a template directory
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function copyTemplate()
	{
		$this->copyDirectory(
			($this->clientId === 0 ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/templates/' . $this->extension,
			$this->exportDirectory . '/' . $this->fileBucket
		);
	}

	/**
	 * Copy a component
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function copyComponent()
	{
		$this->copyComponentAdministrator();
		$this->copyComponentSite();

		$baseName   = substr($this->extension, 4);
		$sourcePath = $this->exportDirectory . '/' . $this->fileBucket . '/' . $this->adminFilesTargetPath . '/' . $baseName . '.xml';
		if (!JFile::exists($sourcePath))
		{
			$sourcePath = $this->exportDirectory . '/' . $this->fileBucket . '/' . $this->filesTargetPath . '/' . $baseName . '.xml';
		}
		$targetPath = $this->exportDirectory . '/' . $this->fileBucket . '/' . $baseName . '.xml';
		JFile::move($sourcePath, $targetPath);
	}

	/**
	 * Copy an extension
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function copyExtension()
	{
		switch ($this->type)
		{
			case 'component':
				$this->copyComponent();
				break;

			case 'module':
				$this->copyModule();
				break;

			case 'template':
				$this->copyTemplate();
				break;

			case 'plugin':
				$this->copyPlugin();
				break;

			default:
				throw new \RuntimeException('Unknown extension type ' . $this->type);
		}
	}

	/**
	 * Create a ZIP archive
	 *
	 * @since 1.0.0
	 * @throws \UnexpectedValueException
	 */
	private function zip()
	{
		$originalDirectory = getcwd();
		$workingDirectory  = $this->exportDirectory . '/' . $this->fileBucket;
		chdir($workingDirectory);

		$data = [];
		foreach (JFolder::files('.', '', true, true) as $file)
		{
			$data[] = [
				'name' => str_replace('./', '', $file),
				'data' => file_get_contents($file),
				'time' => filemtime($file),
			];
		}

		/** @noinspection PhpDeprecationInspection Replacement does not yet exist */
		$zip = new JArchiveZip;
		$zip->create(JPath::clean("$this->exportDirectory/$this->packageName.zip"), $data);

		chdir($originalDirectory);
	}

	/**
	 * Copy language files
	 *
	 * @param string            $language   The language code
	 * @param string            $sourcePath The source path
	 * @param string            $targetPath The target path
	 * @param \SimpleXMLElement $manifest
	 *
	 * @since 1.0.0
	 */
	private function copyLanguageFiles($language, $sourcePath, $targetPath, $manifest)
	{
		foreach (['ini', 'sys.ini'] as $ext)
		{
			$languageFile = "$language.$this->fileBucket.$ext";

			if (!JFile::exists("$sourcePath/$languageFile"))
			{
				continue;
			}

			$targetName = "$language/$languageFile";
			foreach ($manifest->language as $file)
			{
				if (basename($file) === $languageFile)
				{
					$targetName = $file;
					break;
				}
			}

			if (!empty($targetName))
			{
				JFolder::create(dirname("$targetPath/$targetName"), $this->dirMode);
				JFile::copy("$sourcePath/$languageFile", "$targetPath/$targetName");
			}
		}
	}

	/**
	 * Copy a language directory
	 *
	 * @param string            $sourcePath The source path
	 * @param string            $targetPath The target path
	 * @param \SimpleXMLElement $manifest
	 *
	 * @since 1.0.0
	 */
	private function copyLanguageDirectory($sourcePath, $targetPath, $manifest)
	{
		if (!JFolder::exists($sourcePath))
		{
			return;
		}

		foreach (JFolder::listFolderTree($sourcePath, $filter = '') as $folder)
		{
			$this->copyLanguageFiles($folder['name'], $folder['fullname'], $targetPath, $manifest);
		}
	}

	/**
	 * Copy language files if not in extension directory
	 *
	 * @param \SimpleXMLElement $manifest
	 *
	 * @since 1.0.0
	 */
	private function copyLanguages($manifest)
	{
		$locations = [
			'site'    => [
				'source'   => JPATH_SITE . '/language',
				'target'   => $this->fileBucket . '/' . $this->languageTargetPath,
				'manifest' => $manifest->languages ?: null,
			],
			'admin'   => [
				'source'   => JPATH_ADMINISTRATOR . '/language',
				'target'   => $this->fileBucket . '/' . $this->adminLanguageTargetPath,
				'manifest' => null,
			],
			'plugins' => [
				'source'   => JPATH_ADMINISTRATOR . '/language',
				'target'   => $this->fileBucket . '/' . $this->languageTargetPath,
				'manifest' => null,
			],
		];

		if (isset($manifest->administration))
		{
			$locations['admin']['manifest'] = $manifest->administration->languages ?: null;
		}

		if ($this->type === 'plugin')
		{
			$locations['plugins']['manifest'] = $manifest->languages ?: null;
		}

		foreach ($locations as $location)
		{
			if ($location['manifest'] === null)
			{
				continue;
			}
			$this->copyLanguageDirectory(
				$location['source'], $this->exportDirectory . '/' . $location['target'], $location['manifest']
			);
		}
	}

	/**
	 * Copy site parts of a component
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function copyComponentSite()
	{
		$sourcePath = JPATH_SITE . '/components/' . $this->extension;
		$targetPath = $this->exportDirectory . '/' . $this->fileBucket . '/' . $this->filesTargetPath;

		$this->copyDirectory($sourcePath, $targetPath);
	}

	/**
	 * Copy admin parts of a component
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function copyComponentAdministrator()
	{
		$sourcePath = JPATH_ADMINISTRATOR . '/components/' . $this->extension;
		$targetPath = $this->exportDirectory . '/' . $this->fileBucket . '/' . $this->adminFilesTargetPath;

		$this->copyDirectory($sourcePath, $targetPath);
	}

	/**
	 * @param $sourcePath
	 * @param $targetPath
	 *
	 * @throws \RuntimeException
	 *
	 * @since 1.0.0
	 */
	private function copyDirectory($sourcePath, $targetPath)
	{
		if (!JFolder::exists($sourcePath))
		{
			return;
		}

		JFolder::create($targetPath, $this->dirMode);
		JFolder::copy($sourcePath, $targetPath, '', true);
	}

	/**
	 * @return \SimpleXMLElement
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function getManifest()
	{
		$path = $this->getManifestPath();

		return simplexml_load_string(file_get_contents($path));
	}

	/**
	 * @return string
	 *
	 * @since 1.0.0
	 * @throws \RuntimeException
	 */
	private function getManifestPath()
	{
		$clientPath   = $this->clientId === 0 ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$manifestName = substr($this->extension, 4) . '.xml';
		$manifestPath = [
			'component' => $clientPath . '/components/' . $this->extension . '/' . $manifestName,
			'module'    => $clientPath . '/modules/' . $this->extension . '/mod_' . $manifestName,
			'plugin'    => JPATH_SITE . '/plugins/' . $this->pluginGroup . '/' . $this->extension . '/' . $this->extension . '.xml',
			'template'  => $clientPath . '/templates/' . $this->extension . '/templateDetails.xml',
		];

		if (!isset($manifestPath[$this->type]))
		{
			throw new \RuntimeException("Extensions of type '{$this->type}' are not supported.");
		}

		if (!JFile::exists($manifestPath[$this->type]))
		{
			throw new \RuntimeException("No manifest found for {$this->type} {$this->extension} (expected {$manifestPath[$this->type]})");
		}

		return $manifestPath[$this->type];
	}

	/**
	 * @param \SimpleXMLElement $element
	 * @param string            $tag
	 * @param string            $attribute
	 * @param string            $default
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function getAttribute($element, $tag, $attribute, $default)
	{
		if (!isset($element->{$tag}))
		{
			return (string) $default;
		}

		return (string) $element->{$tag}->attributes()->{$attribute} ?: $default;
	}
}
