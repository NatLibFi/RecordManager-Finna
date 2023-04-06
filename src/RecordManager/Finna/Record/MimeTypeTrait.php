<?php
/**
 * MIME type handling support trait.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2023.
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

use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

/**
 * MIME type handling support trait.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait MimeTypeTrait
{
    /**
     * Array containing all found mimetypes.
     *
     * @var array
     */
    protected $mimeTypes = [];

    /**
     * Array containing types which can be counted as image/jpeg
     *
     * @var array
     */
    protected $displayImageTypes = [
        'fullres',
        'image_small',
        'image_thumb',
        'image_medium',
        'image_large',
        'large',
        'medium',
        'square',
        'thumb',
        'zoomview',
    ];

    protected $defaultImageMimeType = 'image/jpeg';

    /**
     * Try to detect mimetype from link, format or the type of representation.
     *
     * @param string $link   Link to check
     * @param string $format Format to check i.e jpg or image/jpg
     * @param string $type   Type to check i.e image_large or image_small
     *
     * @return void
     */
    protected function checkLinkMimeType(
        string $link,
        string $format = "",
        string $type = ""
    ): void {
        if (empty($link)) {
            return;
        }
        $mimeType = '';
        if (!empty($format)) {
            $format = trim($format);
            // type/subtype
            $exploded = explode("/", $format);
            // try to find MIME type only from subtype
            if (empty($exploded[1])) {
                $map = new GeneratedExtensionToMimeTypeMap();
                $mimeType = $map->lookupMimeType($exploded[0]);
            } else {
                $mimeType = $format;
            }
        }

        if (!$mimeType) {
            $detector = new ExtensionMimeTypeDetector();
            $mimeType = $detector->detectMimeTypeFromPath(trim($link));
        }
        if (!$mimeType
            && in_array(mb_strtolower($type), $this->displayImageTypes)
            && !in_array($this->defaultImageMimeType, $this->mimeTypes)
        ) {
            $this->mimeTypes[] = $this->defaultImageMimeType;
        }
        if ($mimeType && !in_array($mimeType, $this->mimeTypes)) {
            $this->mimeTypes[] = $mimeType;
        }
    }
}
