# GreenCape Extension Export

This is a **system plugin** for Joomla **3.8+** that extends Joomla's Extension Manager with extension export functionality.

## Installation

Download the latest release (see [Release tab](https://github.com/GreenCape/extension-export/releases)) and install it like any other plugin.
Navigate to *Extensions/Plugins*, and search for `extension export`.
Enable the plugin.

## Configuration

When you click on the plugin's name in the view mentioned before, you can edit the settings.

### Directory

Specify the directory, where the exported packages will be stored for later use.
It defaults to `images/dist`, so the packages can easily be managed (renamed, deleted) using the Media Manager.

### Delete Directories

This switch controls the deletion of the temporary directories after bundling the extension files.
* `Yes` - delete the directories (default). Use this setting if you do not need the uncompressed packages. They can only be deleted if you have file system access (FTP or SSH).
* `No` - keep the directories. Use this setting (in your development environment) to simply copy the packages into your development area without unpacking them first.

### File Mode

The File Mode is the UNIX permissions for files, defaulting to `0644`.

### Dir Mode

The Dir Mode is the UNIX permissions for directories, defaulting to `0755`.
This field is only available, if `Delete Directories` is set to `no`.

## Usage

Navigate to *Extensions/Manage/Manage*.
In the toolbar, a new button has appeared.

![The new "Export" button in the Manage Extensions view](docs/screenshot-button.png)

Select the extension that you want to export.
Currently, components, modules, plugins and templates are supported.
Click on the *Export* button.
If everything goes right, you'll see a success message with a download link.

![The success message and the download link](docs/screenshot-download.png)

