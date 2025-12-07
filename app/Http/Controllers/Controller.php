<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Normalize request data from camelCase to snake_case.
     * Only converts keys that don't already have a snake_case version.
     * If both camelCase and snake_case versions exist, prefers snake_case.
     * 
     * @param array $data
     * @return array
     */
    protected function normalizeRequestData(array $data): array
    {
        $normalized = [];
        $processedKeys = [];
        
        foreach ($data as $key => $value) {
            // If key is already in snake_case, use it as-is
            if (str_contains($key, '_') || ctype_lower($key)) {
                $normalized[$key] = $value;
                $processedKeys[$key] = true;
            } else {
                // Convert camelCase to snake_case
                $snakeKey = Str::snake($key);
                
                // Only process if we haven't already processed the snake_case version
                if (!isset($processedKeys[$snakeKey])) {
                    // If snake_case version exists in original data, prefer it
                    if (isset($data[$snakeKey])) {
                        $normalized[$snakeKey] = $data[$snakeKey];
                    } else {
                        // Otherwise, use the converted camelCase value
                        $normalized[$snakeKey] = $value;
                    }
                    $processedKeys[$snakeKey] = true;
                }
                // If snake_case version was already processed, skip this camelCase key
            }
        }
        
        return $normalized;
    }
}
