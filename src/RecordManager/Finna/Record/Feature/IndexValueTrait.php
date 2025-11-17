<?php

/**
 * Trait which exposes methods to keep index values consistent.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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

namespace RecordManager\Finna\Record\Feature;

use function in_array;

/**
 * Trait which exposes methods to keep index values consistent.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait IndexValueTrait
{
    /**
     * Function specific mappings used in createArray functions.
     *
     * @var array
     */
    protected array $allowedOnlineURLMediaTypes = [];

    /**
     * Create value for online_urls_str_mv index field.
     *
     * @param string $url            Online URL.
     * @param string $mediaType      Media type associated to the URL.
     * @param string $text           Text or description for the url.
     * @param string $source         URL source. Default is this->source.
     * @param bool   $mediaTypeCheck Compare the given media type to values found in
     *                               recordFormatSpecificMappings[online_urls_str_mv].
     *                               Default is false.
     *
     * @return array
     */
    public function createOnlineURLEntry(
        string $url,
        string $mediaType = '',
        string $text = '',
        string $source = '',
        bool $mediaTypeCheck = false
    ): array {
        // Require at least one dot surrounded by valid characters or a familiar scheme
        // If media type is required to be checked, try to find the value from functionSpecificMappings.
        if (
            !preg_match('/[A-Za-z0-9]\.[A-Za-z0-9]/', $url)
            && !preg_match('/^https?:\/\//', $url)
            || ($mediaTypeCheck && !in_array($mediaType, $this->allowedOnlineURLMediaTypes))
        ) {
            return [];
        }
        $source = $source ?: $this->source;
        return array_map('trim', compact('url', 'mediaType', 'text', 'source'));
    }

    /**
     * Create online_urls_str_mv index field value containing json_encoded URLs.
     *
     * @param array $urls URLs.
     *
     * @return array<int, string>
     */
    public function createOnlineURLsArray(array $urls): array
    {
        return array_map(
            fn ($url) => json_encode($url),
            $urls
        );
    }

    /**
     * Get media_type_str_mv values array
     *
     * @param array $urls URLs containing mediaType key.
     *
     * @return array
     */
    public function createMediaTypeArray(array $urls): array
    {
        return array_values(
            array_filter(
                array_unique(
                    array_column($urls, 'mediaType')
                )
            )
        );
    }

    /**
     * Create author_facet values array. Removes any extra whitespaces.
     *
     * @param array $solrArray Solr array.
     *
     * @return array
     */
    public function createAuthorFacetArray(array $solrArray): array
    {
        $authors = $solrArray['author'] ?? [];
        $authors2 = $solrArray['author2'] ?? [];
        $authorCorporate = $solrArray['author_corporate'] ?? [];
        $authorsCombined = array_values(
            array_unique(
                array_filter(
                    [
                        ...(array)$authors,
                        ...(array)$authors2,
                        ...(array)$authorCorporate,
                    ]
                )
            )
        );
        return array_map(
            fn ($s) => preg_replace('/\s+/', ' ', $s),
            $authorsCombined
        );
    }

    /**
     * Initialize index value trait.
     *
     * @param array $config Main config.
     *
     * @return void
     */
    protected function initIndexValueTrait(array $config): void
    {
        if ($allowedMediaTypes = $config['OnlineURLs']['allowed_media_types'] ?? []) {
            $this->allowedOnlineURLMediaTypes = $allowedMediaTypes;
        }
    }
}
