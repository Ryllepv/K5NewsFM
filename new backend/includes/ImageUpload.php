<?php
/**
 * Image Upload Utility Class
 * Handles secure image uploads with validation and optimization
 */

class ImageUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $maxWidth;
    private $maxHeight;
    
    public function __construct($uploadDir = 'uploads/', $options = []) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedTypes = $options['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $this->maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
        $this->maxWidth = $options['max_width'] ?? 1920;
        $this->maxHeight = $options['max_height'] ?? 1080;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload and process an image file
     */
    public function upload($file, $prefix = '') {
        $result = [
            'success' => false,
            'path' => '',
            'error' => ''
        ];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->getUploadError($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return $result;
        }
        
        // Validate file type
        if (!$this->validateFileType($file)) {
            $result['error'] = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.';
            return $result;
        }
        
        // Validate file size
        if (!$this->validateFileSize($file)) {
            $result['error'] = 'File too large. Maximum size is ' . $this->formatBytes($this->maxSize) . '.';
            return $result;
        }
        
        // Validate image dimensions
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            $result['error'] = 'Invalid image file.';
            return $result;
        }
        
        // Generate safe filename
        $filename = $this->generateFilename($file['name'], $prefix);
        $targetPath = $this->uploadDir . $filename;
        
        // Resize image if necessary
        if ($imageInfo[0] > $this->maxWidth || $imageInfo[1] > $this->maxHeight) {
            if (!$this->resizeImage($file['tmp_name'], $targetPath, $imageInfo)) {
                $result['error'] = 'Failed to resize image.';
                return $result;
            }
        } else {
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $result['error'] = 'Failed to upload image. Please check directory permissions.';
                return $result;
            }
        }
        
        $result['success'] = true;
        $result['path'] = str_replace('../', '', $this->uploadDir) . $filename;
        
        return $result;
    }
    
    /**
     * Delete an uploaded image
     */
    public function delete($imagePath) {
        if ($imagePath && file_exists('../' . $imagePath)) {
            return unlink('../' . $imagePath);
        }
        return false;
    }
    
    /**
     * Validate file type
     */
    private function validateFileType($file) {
        return in_array($file['type'], $this->allowedTypes);
    }
    
    /**
     * Validate file size
     */
    private function validateFileSize($file) {
        return $file['size'] <= $this->maxSize;
    }
    
    /**
     * Generate safe filename
     */
    private function generateFilename($originalName, $prefix = '') {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $prefix = $prefix ? $prefix . '_' : '';
        return $prefix . uniqid() . '_' . time() . '.' . $extension;
    }
    
    /**
     * Resize image to fit within max dimensions
     */
    private function resizeImage($sourcePath, $targetPath, $imageInfo) {
        list($width, $height, $type) = $imageInfo;
        
        // Calculate new dimensions
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Create image resource from source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Create new image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($resized, $targetPath, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($resized, $targetPath, 6);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($resized, $targetPath);
                break;
            case IMAGETYPE_WEBP:
                $success = imagewebp($resized, $targetPath, 85);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($resized);
        
        return $success;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'File upload was interrupted.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get image info
     */
    public function getImageInfo($imagePath) {
        if (file_exists('../' . $imagePath)) {
            $info = getimagesize('../' . $imagePath);
            return [
                'width' => $info[0],
                'height' => $info[1],
                'type' => $info[2],
                'size' => filesize('../' . $imagePath)
            ];
        }
        return false;
    }
}
?>
