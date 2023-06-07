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
     * Generated extension to Mime type mapper
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
    protected $defaultImageMimeType = 'image/jpeg';

    /**
     * Initialize MimeTypeTrait.
     *
     * @param array $config RecordManager config
     *
     * @return void
     */
    protected function initMimeTypeTrait(array $config): void
    {
        $this->extensionMapper = new GeneratedExtensionToMimeTypeMap();
        if (!empty($config['MimeType']['excluded_file_extensions'])) {
            $this->excludedFileExtensions
                = $config['MimeType']['excluded_file_extensions'];
        }
    }

    /**
     * Try to get mimetype from link, format or the type of representation.
     *
     * @param string $link   Link to check
     * @param string $format Format to check i.e jpg or image/jpg
     * @param string $type   Type to check i.e image_large or image_small
     *
     * @return string Found mimetype or an empty string.
     */
    protected function getLinkMimeType(
        string $link,
        string $format = "",
        string $type = ""
    ): string {
        $link = trim($link);
        if (empty($link)) {
            return '';
        }
        $mimeType = '';
        if (!empty($format)) {
            $format = trim($format);
            // type/subtype
            $exploded = explode("/", $format);
            // try to find MIME type only from subtype
            if (!empty($exploded[1])) {
                // This can be returned instantly as it is a full mimetype
                return $format;
            } else {
                $mimeType
                    = $this->extensionMapper->lookupMimeType($exploded[0]);
            }
        }
        if (!$mimeType) {
            $parsedURL = parse_url($link);
            if (!empty($parsedURL['path'])) {
                $ext = pathinfo($parsedURL['path'], PATHINFO_EXTENSION);
                if ($ext && !in_array($ext, $this->excludedFileExtensions)) {
                    $mimeType
                        = $this->extensionMapper->lookupMimeType($ext);
                }
            }
        }
        if (
            !$mimeType
            && in_array(mb_strtolower($type), $this->displayImageTypes)
        ) {
            $mimeType = $this->defaultImageMimeType;
        }
        return $mimeType ?: '';
    }
}
