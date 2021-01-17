# ThumbGenerator
![version](https://img.shields.io/badge/stable-1.0-blue) ![license](https://img.shields.io/badge/license-GPLv3-brightgreen)

Requires: PHP 7.0, GD-Library
Stable tag: 1.0  
License: GPLv3

Create your images thumbnail, easily and with no effort. You need a folder, an environment with PHP, two line of code and your images to shrink. That's it

## Description
ThumbGenerator allows to create image thumbnail, easily and with no effort. Image thumbnail are fetched from class level folder or given folder. Default settings allow to scan children folder of working directory. Nested reading can be disabled. Image thumbnail will retain original angle ( if  metadata image has relavant informations ). Process return logs within class method or via simple log dump. Resize factor default value is 50%, can be customized. Generated image thumbnail can get watermark. This software is 

Main features:

* create image thumbnail from working directory, reaching each children folder, with a single command
* customise resize ratio, working directory and thumbnail destination folder, add watermark
* read processing log within the class object or from a log file
* in case of clone image, process will rename it with increasing index

### Installation & Requirements
* PHP 7.0 and GD-Library
* no installation required
* include or require the class in your project
 
### Sample
```
// Class init
$thumbGenerator = new ThumbGenerator();
// *optional* - Set custom working directory, images contained in this folder will be processed
$thumbGenerator->setWorkingDir(<PATH TO WORKING DIR>);
// *optional* - Set image thumbnail destination folder
$thumbGenerator->setThumbnailDir(<PATH TO THUMB DESTINATION FOLDER>);
// *optional* - Will process only images within the working directory; childern folders are ignored
$thumbGenerator->disableRecursiveSearch();
// Return processing log
$thumbGenerator->getLog();
// *optional* - Processing will be dumped into log file
$thumbGenerator->enableLogDump();
// *optional* - Will check and eventually create working directory and image thumbnail destination directory
$thumbGenerator->checkDirectory();
// *optional* - Specify widget and height of generated image thumbnail
$thumbGenerator->setResizeFactor(<WIDTH>, <HEIGHT>);
// *optional* - Specify path to file to be used as watermark on image thumbnail
$thumbGenerator->addWatermarkFile(<PATH TO FILE>);
// Start image thumbnail processing
$thumbGenerator->createThumbsFromDirectory();
// *optional* - Create image thumbnail for specified file
$thumbGenerator->createThumbnail(<PATH TO FILE>, <GENERATED IMAGE THUMBNAIL NAME>);
```

###### Changelog
###### 1.0
* Release 1.0

#### Disclaimer
ThumbGenerator is provided with the "AS IT IS" formula. This software and his creator are not responsible for any damage/abuse arising the use of this class. Images generated with this tool are still property of their original own creator if protecteed by licenses/trademark/copyright. Software and creator are not resposible for creation and/or distribution of these images and cannot be legally responsible for any breaking law that involves these generated images and/or the original images used as source.