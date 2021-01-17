<?php

use ErrorException as ErrorException;

/**
 * ThumbGenerator generate thumbnail from images into working directory
 * 
 * Class offers method to creates image thumbnail, starting from class level folder or given folder.
 * Default settings allow to scan children folder of working directory. Can be disabled.
 * Image thumbnail will retain original angle ( if image has metadata with relavant informations ).
 * Process return logs via proper method or dump whole process into a log file.
 * Resize factor default value is 50%. Values can be changed with proper method, working with same ratio
 * or different ratios. On generated thumbnail a watermark can be added using proper methods and a watermark file
 * 
 * @requires GD library, PHP 7
 * @author Marco Grossi <developer@bigm.it>
 * @license GPL-3.0 License
 * @version 1.0
 */
class ThumbGenerator
{
    // Directories
    protected $workingDir = '';
    protected $thumbnailDir = '';
    protected $searchNested = true;
    // Watermark
    protected $watermark;
    protected $watermarkResource;
    // Image resize percentage
    protected $widthReduction = 50;
    protected $heightReduction = 50;
    // Files processed
    protected $thumbfiles = [];
    protected $cloneFile = [];
    // Logs
    protected $log = [];
    protected $enableDump = false;
    const LOG_DIR = __DIR__.DIRECTORY_SEPARATOR;

    public function __construct()
    {
        $this->workingDir = __DIR__.DIRECTORY_SEPARATOR;
        $this->thumbnailDir = $this->workingDir.'thumbs'.DIRECTORY_SEPARATOR;
    }

    /**
     * Set working directory where images are read as source
     *
     * @param string $workingDir Directory path
     * @return void
     */
    public function setWorkingDir(string $workingDir) : void
    {
        $workingDir = rtrim($workingDir, DIRECTORY_SEPARATOR);
        $this->workingDir = !empty($workingDir) ? $workingDir.DIRECTORY_SEPARATOR : $this->workingDir;
    }

    /**
     * Set thumbnail directory where thumbs are stored
     *
     * @param string $thumbnailDir Directory path
     * @return void
     */
    public function setThumbnailDir(string $thumbnailDir) : void
    {
        $thumbnailDir = rtrim($thumbnailDir, DIRECTORY_SEPARATOR);
        $this->thumbnailDir = !empty($thumbnailDir) ? $thumbnailDir.DIRECTORY_SEPARATOR : $this->thumbnailDir;
    }

    /**
     * Disable search into child directories
     *
     * @return void
     */
    public function disableRecursiveSearch() : void
    {
        $this->searchNested = false;
    }

    /**
     * Return processing log
     *
     * @return array
     */
    public function getLog() : array
    {
        return $this->log;
    }

    /**
     * Create processing log dump
     * Log can grow up easily, use this feature only for debug or for testing
     * 
     * @return void
     */
    public function enableLogDump() : void
    {
        $this->enableDump = true;
    }

    /**
     * Verify permission and directories existence
     *
     * @return void
     */
    public function checkDirectory() : void
    {
        // Verify working directory existence
        if (!is_dir($this->workingDir)) {
            // Try to create if not exists
            @mkdir($this->workingDir, 0755, true);
            if (!is_dir($this->workingDir)) {
                throw new ErrorException('ThumbGenerator cannot create directory '.$this->workingDir);
            }
        }

        // Verify thumbnai directory existence
        if (!is_dir($this->thumbnailDir)) {
            // Try to create if not exists
            @mkdir($this->thumbnailDir, 0755, true);
            if (!is_dir($this->thumbnailDir)) {
                throw new ErrorException('ThumbGenerator cannot create directory '.$this->thumbnailDir);
            }
        }
    }

    /**
     * Set image resize factor, width and height
     *
     * @param integer $width Thumb width reduction percentage
     * @param integer $height Thumb height reduction percentage
     * @return void
     */
    public function setResizeFactor(int $width, int $height = null) : void
    {
        if (!empty($width) || is_numeric($width)) {
            $this->widthReduction = $width;
            $this->heightReduction = empty($height) || !is_numeric($height) ? $width : $height;
        }
    }

    /**
     * Specify file to be used as watermark on image thumbnail
     *
     * @param string $pathToFile Path to file
     * @return void
     */
    public function addWatermarkFile(string $pathToFile) : void
    {
        if (!empty($pathToFile) && file_exists($pathToFile)) {
            $this->watermark = $pathToFile;
            $this->watermarkResource = $this->getResourceFromFile($this->watermark);
        }        
    }

    /**
     * Add watermark to file
     *
     * @param string $pathToFile Path to file
     * @param integer $marginRight Watermark right point
     * @param integer $marginBottom Watermark bottom point
     * @return void
     */
    private function addWatermark(string $pathToFile, int $marginRight = null, int $marginBottom = null) : void
    {
        if (!empty($this->watermark)) {
            // Load the stamp and the photo to apply the watermark to
            $image = $this->getResourceFromFile($pathToFile);
            if ($image && $this->watermarkResource) {
                $fileProps = @getimagesize($pathToFile);

                // Images dimensions
                $imageWidth = imagesx($image);
                $imageHeight = imagesy($image);
                $watermarkWidth = imagesx($this->watermarkResource);
                $watermarkHeight = imagesy($this->watermarkResource);
                //Watermark positioning
                $marginRight = $marginRight ? $marginRight : $imageWidth/2 - $watermarkWidth/2;
                $marginBottom = $marginBottom ? $marginBottom : $imageHeight/2 - $watermarkHeight/2;

                // Apply watermark to resource
                imagecopy(
                    $image, 
                    $this->watermarkResource, 
                    $imageWidth - $watermarkWidth - $marginRight, 
                    $imageHeight - $watermarkHeight - $marginBottom, 
                    0, 
                    0, 
                    imagesx($this->watermarkResource), 
                    imagesy($this->watermarkResource)
                );
                // Save file
                $this->createImageFileFromResource($fileProps, $image, $pathToFile);
            }
        }
    }

    /**
     * Create image resource from file
     *
     * @param string $pathToFile Path to file
     * @return resource|null
     */
    private function getResourceFromFile(string $pathToFile)
    {
        if (!file_exists($pathToFile)) {
            return null;
        }
        $fileProps = @getimagesize($pathToFile);
        if (!$fileProps || empty($fileProps[2])) {
            return null;
        }
        $imageType = $fileProps[2];
        switch($imageType) {
            case ( $imageType == IMAGETYPE_JPEG ) :
                return imagecreatefromjpeg($pathToFile);  
                break;
            case ( $imageType == IMAGETYPE_GIF ) :
                return imagecreatefromgif($pathToFile);
                break;
            case ( $imageType == IMAGETYPE_PNG ) :
                return imagecreatefrompng($pathToFile); 
                break;
            case ( $imageType == IMAGETYPE_BMP ) :
                return imagecreatefrombmp($pathToFile);
                break;
            default: return null;
        }
    }

    /**
     * Create image file from resource
     *
     * @param array $fileProps Array of image properties
     * @param resource $imageResource Image resource
     * @param string $pathToFile Path to file
     * @return bool
     */
    private function createImageFileFromResource(array $fileProps, $imageResource, string $pathToFile) : bool
    {
        if (!is_resource($imageResource)) {
            return false;
        }
        $processed = true;
        $imageType = $fileProps[2];
        switch($imageType) {
            case ( $imageType == IMAGETYPE_JPEG ) :
                imagejpeg($imageResource, $pathToFile);
                break;
            case ( $imageType == IMAGETYPE_GIF ) :
                imagegif($imageResource, $pathToFile);
                break;
            case ( $imageType == IMAGETYPE_PNG ) :
                imagepng($imageResource, $pathToFile);
                break;
            case ( $imageType == IMAGETYPE_BMP ) :
                imagebmp($imageResource, $pathToFile);
                break;
            default: 
                $processed = false;
                break;
        }

        return $processed;
    }

    /**
     * Create thumbs from images inside the working directory
     *
     * @return void
     */
    public function createThumbsFromDirectory() : bool
    {
        // Verify directories
        $this->checkDirectory();
        $this->thumbfiles = scandir($this->thumbnailDir);
        // When reaching child folder we need to skip thumb folder
        if ($this->workingDir == $this->thumbnailDir) {
            return false;
        }
        // Collect files inside working directory
        $files = scandir($this->workingDir);
        foreach ($files as $file) {
            if (is_dir($this->workingDir.$file)) {
                if (
                    $this->isCurrentFolder($this->workingDir.$file) || 
                    $this->isParentFolder($this->workingDir.$file)
                ) {
                    continue;
                }
                // Process data from children folder
                if ($this->searchNested) {
                    $this->processNestedDirectory($this->workingDir.$file);
                    continue;
                }

            }

            // File does not exists, skip
            if (!file_exists($this->workingDir.$file)) {
                continue;
            }

            // Process image as thumbnail
            $this->createThumbnail($this->workingDir.$file, $file);
        }
        if ($this->enableDump) {
            // Create log dump
            error_log(implode("\n", $this->log)."\n", 3, self::LOG_DIR.'thumbGenerator_'.date('Y-m-d-Hi').'.log');
        }

        return true;
    }

    /**
     * Create thumbnail given file
     *
     * @param string $pathToFile Path to file
     * @param string $fileName Filename
     * @return boolean
     */
    public function createThumbnail(string $pathToFile, string $fileName) : bool
    {
        $processed = false;
        // Get file properties
        $fileProps = @getimagesize($pathToFile);
        // If cannot fetch it or type is missing, log and skip
        if (!$fileProps || empty($fileProps[2])) {
            $this->log[] = date('Y-m-d-H:i:s').': Not an image '.$pathToFile;
            return $processed;
        }
        $thumbName = $this->thumbnailDir.$fileName;
        if (file_exists($thumbName)) {
            $thumbName = $this->renameDestinationFile($fileName, $this->thumbnailDir.$fileName);
        }
        // Process images based on type
        $processed = $this->processImageFile($thumbName, $pathToFile, $fileProps);

        if (!$processed) {
            $this->log[] = date('Y-m-d-H:i:s').': Not a valid image format '.$fileName;
        }

        return $processed;
    }

    /**
     * Process image file based on type
     *
     * @param string $thumbName Thumbnail name
     * @param string $pathToFile Path to file
     * @param array $fileProps Array of image props
     * @return boolean
     */
    private function processImageFile(string $thumbName, string $pathToFile, array $fileProps) : bool
    {
        $resource = $this->getResourceFromFile($pathToFile);
        $imageResource = $this->resizeImage($pathToFile,$resource,$fileProps[0],$fileProps[1]);
        $processed = $this->createImageFileFromResource($fileProps, $imageResource, $thumbName);
        if (is_resource($imageResource)) {
            imagedestroy($imageResource);
        }

        if ($processed && $this->watermark) {
            $this->addWatermark($thumbName);
        }

        return $processed;
    }

    /**
     * Resize given image based on resize ratio
     *
     * @param string $sourceFile Path to file
     * @param resource $resourceId Resource
     * @param integer $width Original width
     * @param integer $height Original height
     * @return resource
     */
    private function resizeImage(string $sourceFile, $resourceId, int $width, int $height)
    {
        // If is not resource stop process
        if (!is_resource($resourceId)) {
            return null;
        }
        // Calculate thumb ratio
        $targetWidth = $width*($this->widthReduction/100);
        $targetHeight = $height*($this->heightReduction/100);
        $imageResource = @imagecreatetruecolor($targetWidth,$targetHeight);
        if (!$imageResource) {
            return null;
        }
        imagecopyresampled($imageResource,$resourceId,0,0,0,0,$targetWidth,$targetHeight, $width,$height);
        // Get image details data
        $exif = @exif_read_data($sourceFile);
        $degree = false;
        if (!empty($exif['Orientation'])) {
            // Calculate rotation based on image data
            switch ($exif['Orientation']) {
                case 3: $degree = 180; break;
                case 6: $degree = 270; break;
                case 8: $degree = 90; break;
                default: break;
            }
        }
        // Rotate image if needed
    	$imageResource = $degree ? imagerotate($imageResource,$degree,0) : $imageResource;
        return $imageResource;
    }

    /**
     * Rename file with same existing name
     *
     * @param string $fileName File name
     * @param string $pathToFile Path to file
     * @return string
     */
    private function renameDestinationFile(string $fileName, string $pathToFile) : string
    {
        // Search for extension dot position
        $typeSplit = strrpos($pathToFile, '.');
        // Remove extension from string
        $extension = substr($pathToFile, $typeSplit, strlen($pathToFile));
        $pathToFile = substr($pathToFile, 0, $typeSplit);
        // Detect actual incremental value
        if (empty($this->cloneFile[$fileName])) {
            $this->cloneFile[$fileName] = $this->calculateClones($fileName);
        }
        $this->cloneFile[$fileName]++;
        // Add incremental value to name and add back extension;
        $pathToFile .= "({$this->cloneFile[$fileName]})".$extension;

        return $pathToFile;
    }

    /**
     * Calculate clones to rename thumbs
     *
     * @param string $fileName
     * @return integer
     */
    private function calculateClones(string $fileName) : int
    {
        $totalCount = -1;
        // Search for extension dot position
        $typeSplit = strrpos($fileName, '.');
        $rawName = substr($fileName, 0, $typeSplit);
        foreach ($this->thumbfiles as $file) {
            $pattern = "/(".$rawName."|".$rawName."\([0-9]*\))\.[a-z]{3,4}$/";
            if (preg_match($pattern, $file)) {
                $totalCount++;
            }
        }

        return $totalCount;
    }

    /**
     * Perform thumbnail creation from child folder
     *
     * @param string $nestedFolder Path to nested folder
     * @return void
     */
    private function processNestedDirectory(string $nestedFolder) : void
    {
        $pattern = "/^".addcslashes($this->workingDir,'/')."/";
        if (preg_match($pattern, $nestedFolder)) {
            $this->parentFolder[] = $this->workingDir;
            $this->workingDir = $nestedFolder.DIRECTORY_SEPARATOR;
            $this->createThumbsFromDirectory();
            $this->workingDir = array_pop($this->parentFolder);
        }
    }

    /**
     * Detect if given folder is current folder (.)
     *
     * @param string $folder
     * @return boolean
     */
    private function isCurrentFolder(string $folder) : bool
    {
        return preg_match("/\/\.\/{0,1}$/", $folder);
    }

    /**
     * Detect if given folder is parent folder (..)
     *
     * @param string $folder
     * @return boolean
     */
    private function isParentFolder(string $folder) : bool
    {
        return preg_match("/\/\.\.\/{0,1}$/", $folder);
    }
}
