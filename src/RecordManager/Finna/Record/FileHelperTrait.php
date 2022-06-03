<?php
/**
 * File helper trait
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
namespace RecordManager\Finna\Record;

use League\MimeTypeDetection\ExtensionMimeTypeDetector as MimeTypeDetector;

/**
 * File helper trait
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait FileHelperTrait
{
    /**
     * MIME type detector
     *
     * @var MimeTypeDetector
     */
    protected $mimeTypeDetector;

    /**
     * Found MIME types
     *
     * @var array
     */
    protected $foundMimeTypes = [];

    /**
     * Attribute to type mappings
     *
     * @var array
     */
    protected $attributeToTypeMappings = [
        'THUMBNAIL' => 'image',
        'square' => 'image',
        'small' => 'image',
        'medium' => 'image',
        'large' => 'image',
        'original' => 'image',
        'image_thumb' => 'image',
        'thumb' => 'image',
        'image_large' => 'image',
        'zoomview' => 'image',
        'image_master' => 'image',
        'image_original' => 'image',
        'preview_3D' => 'model',
        'provided_3D' => 'model',
        'preview_audio' => 'audio',
        'preview_video' => 'video',
        'preview_text' => 'application',
        'provided_text' => 'application'
    ];

    /**
     * Return MIME type from the extension of a file
     *
     * @param string $extension to look for
     *
     * @return string found mime type
     */
    public function getMimeTypeFromExtension(string $extension): string
    {
        if (false !== strpos($extension, '/')) {
            return $extension;
        }
        $trimmed = trim($extension, ' .');
        $path = "detect/file.{$trimmed}";
        if (!isset($this->mimeTypeDetector)) {
            throw new \Exception('Mime type detector not set in FileHelperTrait');
        }
        return $this->mimeTypeDetector->detectMimeTypeFromPath($path) ?? '';
    }

    /**
     * Figure file MIME type and extension from url
     *
     * @param string $url Url to check
     *
     * @return array [extension, mimeType]
     */
    protected function getMimeTypeAndExtensionFromUrl(string $url): array
    {
        // Check if url returns a proper mimetype then there is an extension
        if (!isset($this->mimeTypeDetector)) {
            throw new \Exception('Mime type detector not set in FileHelperTrait');
        }

        $extension = '';
        if ($mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($url) ?? ''
        ) {
            $extension = explode('.', $url);
            $extension = end($extension);
        }
        return [$extension, $mimeType];
    }

    /**
     * Get content type from MIME type
     *
     * @param string $mimeType MIME type to check
     *
     * @return string Found type or empty
     */
    protected function getContentTypeFromMimeType(string $mimeType): string
    {
        $exploded = explode('/', $mimeType, 2);
        return !empty($exploded[1]) ? mb_strtolower($exploded[0], 'UTF-8') : '';
    }

    /**
     * Get additional info of a file
     *
     * @param string $url        File url
     * @param string $mimeType   MIME type found from record
     * @param string $identifier Identifier used to identify resources in metadata
     *
     * @return array [type, mimeType, extension]
     */
    public function getAdditionalFileInfo(
        string $url,
        string $mimeType = '',
        string $identifier = ''
    ): array {
        [$extension, $foundMimeType]
            = $this->getMimeTypeAndExtensionFromUrl($url);
        $mimeType = $mimeType ?: $foundMimeType;
        if (!($type = $this->getContentTypeFromMimeType($mimeType)) && $identifier) {
            $type = $this->attributeToTypeMappings[$identifier] ?? '';
        }
        if ($mimeType && !in_array($mimeType, $this->foundMimeTypes)) {
            $this->foundMimeTypes[] = mb_strtolower($mimeType, 'UTF-8');
        }
        return compact('type', 'mimeType', 'extension');
    }
}
