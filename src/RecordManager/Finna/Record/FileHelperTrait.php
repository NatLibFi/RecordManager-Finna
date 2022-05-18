<?php
/**
 * File helper trait.
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

/**
 * File helper trait.
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
     * Mime type detector
     *
     * @var League\MimeTypeDetection\ExtensionMimeTypeDetector
     */
    protected $mimetypeDetector;

    /**
     * Return mimetype given only the extension of a file.
     *
     * @param string $extension to look for.
     *
     * @return string found mimetype
     */
    protected function getMimeTypeWithExtension(string $extension): string
    {
        $trimmed = trim($extension, ' .');
        $path = "detect/file.{$trimmed}";
        return $this->mimetypeDetector->detectMimeTypeFromPath($path) ?? '';
    }

    /**
     * Figure file extension from given url.
     *
     * @param string $url Url to check
     *
     * @return string found extension or empty
     */
    protected function getURLExtension(string $url): string
    {
        if (preg_match(
            '/^http(s)?:\/\/.*\.([a-zA-Z0-9]{3,4})$/',
            $url,
            $match
        )
        ) {
            return $match[2];
        }
        return '';
    }

    /**
     * Get type from the mime or empty.
     *
     * @param string $mimeType Mime type to check.
     *
     * @return string Found type or empty.
     */
    protected function getTypeFromMime(string $mimeType): string
    {
        $exploded = explode('/', $mimeType, 2);
        return !empty($exploded[1]) ? $exploded[0] : '';
    }

    /**
     * Get additional data for a file
     *
     * @param string $url      File url
     * @param string $mimeType Mime type found from record
     *
     * @return array [type, mimeType, extension]
     */
    public function getAdditionalFileData(string $url, string $mimeType = ''): array
    {
        $extension = $this->getURLExtension($url);
        if (!$mimeType && $extension) {
            $mimeType = $this->getMimeTypeWithExtension($extension);
        }
        $type = $this->getTypeFromMime($mimeType);
        return compact('type', 'mimeType', 'extension');
    }
}
