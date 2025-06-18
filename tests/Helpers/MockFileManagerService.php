<?php
/**
 * A mock version of FileManagerService for testing that doesn't rely on WordPress functions.
 *
 * @package DebugSuite\Tests\Helpers
 */

namespace DebugSuite\Tests\Helpers;

use DebugSuite\Services\FileManagerService as OriginalFileManagerService;
use DebugSuite\Core\ServiceResult;
use SplFileInfo;
use ReflectionClass;

/**
 * Mock file manager service that overrides problematic methods.
 */
class MockFileManagerService extends OriginalFileManagerService {
    
    /**
     * Override get_file_contents to use the full path when reading file contents.
     *
     * @param string $relative_path Path relative to WordPress root.
     * @return ServiceResult
     */
    public function get_file_contents(string $relative_path): ServiceResult {
        $safe_path = $this->sanitize_path($relative_path);
        
        // Get the base_path from the parent class using reflection
        $reflection = new ReflectionClass(OriginalFileManagerService::class);
        $property = $reflection->getProperty('base_path');
        $property->setAccessible(true);
        $base_path = $property->getValue($this);
        
        $full_path = $base_path . $safe_path;

        // Security and validation checks
        if (!$this->is_path_safe($full_path)) {
            return ServiceResult::failure('Invalid path provided.', 'invalid_path');
        }

        if (!file_exists($full_path)) {
            return ServiceResult::failure('File not found.', 'file_not_found');
        }

        if (!is_file($full_path)) {
            return ServiceResult::failure('Path is not a file.', 'not_a_file');
        }

        if (!is_readable($full_path)) {
            return ServiceResult::failure('File is not readable.', 'file_not_readable');
        }

        // Use full_path instead of safe_path here
        $contents = file_get_contents($full_path);
        $metadata = $this->get_file_metadata($full_path);

        return ServiceResult::success([
            'contents' => $contents,
            'metadata' => $metadata,
            'path' => $relative_path,
        ]);
    }
}
