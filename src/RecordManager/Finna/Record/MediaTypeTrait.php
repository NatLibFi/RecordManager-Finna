<?php

/**
 * Media type handling support trait.
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

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

/**
 * Media type handling support trait.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait MediaTypeTrait
{
    /**
     * Generated extension to media type mapper
     *
     * @var GeneratedExtensionToMimeTypeMap
     */
    protected $extensionMapper;

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

    /**
     * These values are not to be checked from a link, as they
     * can return an invalid mime type (e.g. an address ending in .php is probably
     * executed server-side and could return anything)
     *
     * @var array
     */
    protected $excludedFileExtensions = [
        'php',
        'pl',
        'phtml',
        'pht',
        'asp',
        'rb',
        'py',
        'js',
        'phtml',
        'aspx',
        'asmx',
        'ashx',
        'swf',
    ];

    /**
     * As default, displayable and loadable images are converted into jpeg format.
     *
     * @var string
     */
    protected $defaultImageMediaType = 'image/jpeg';

    /**
     * Initialize MediaTypeTrait.
     *
     * @param array $config RecordManager config
     *
     * @return void
     */
    protected function initMediaTypeTrait(array $config): void
    {
        $this->extensionMapper = new GeneratedExtensionToMimeTypeMap();
        if (!empty($config['MediaType']['excluded_file_extensions'])) {
            $this->excludedFileExtensions
                = $config['MediaType']['excluded_file_extensions'];
        }
    }

    /**
     * Try to get media type from link, format or the type of representation.
     *
     * @param string $link   Link to check
     * @param string $format Format to check i.e jpg or image/jpg
     * @param string $type   Type to check i.e image_large or image_small
     *
     * @return string Found media type or an empty string.
     */
    protected function getLinkMediaType(
        string $link,
        string $format = "",
        string $type = ""
    ): string {
        $link = trim($link);
        if (empty($link)) {
            return '';
        }
        $mediaType = '';
        if (!empty($format)) {
            $format = trim($format);
            // type/subtype
            $exploded = explode("/", $format);
            // try to find media type only from subtype
            if (!empty($exploded[1])) {
                // This can be returned instantly as it is a full media type
                return $format;
            } else {
                $mediaType
                    = $this->extensionMapper->lookupMimeType($exploded[0]);
            }
        }
        if (!$mediaType) {
            $parsedURL = parse_url($link);
            if (!empty($parsedURL['path'])) {
                $ext = pathinfo($parsedURL['path'], PATHINFO_EXTENSION);
                if ($ext && !in_array($ext, $this->excludedFileExtensions)) {
                    $mediaType
                        = $this->extensionMapper->lookupMimeType($ext);
                }
            }
        }
        if (
            !$mediaType
            && in_array(mb_strtolower($type), $this->displayImageTypes)
        ) {
            $mediaType = $this->defaultImageMediaType;
        }
        return $mediaType ?: '';
    }
}
