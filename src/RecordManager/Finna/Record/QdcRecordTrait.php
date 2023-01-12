<?php
/**
 * Qdc record trait.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2019-2020.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
namespace RecordManager\Finna\Record;

use RecordManager\Base\Database\DatabaseInterface as Database;

/**
 * Qdc record trait.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait QdcRecordTrait
{
    use DateSupportTrait;

    /**
     * Rights statements indicating open access
     *
     * Matched with fnmatch, so * or ? can be used.
     *
     * @var array
     */
    protected $openAccessRights = [
        'openAccess',
        'info:eu-repo/semantics/openAccess',
    ];

    /**
     * Rights statements indicating restricted access
     *
     * Matched with fnmatch, so * or ? can be used.
     *
     * @var array
     */
    protected $restrictedAccessRights = [
        'closedAccess',
        'info:eu-repo/semantics/restrictedAccess',
        'restrictedAccess',
        '*Restricted access*',
        '*Rajattu käyttöoikeus*',
        '*Tillgången är begränsad*',
    ];

    /**
     * Return fields to be indexed in Solr
     *
     * @param Database $db Database connection. Omit to avoid database lookups for
     *                     related records.
     *
     * @return array<string, string|array<int, string>>
     */
    public function toSolrArray(Database $db = null)
    {
        $data = parent::toSolrArray($db);

        if (isset($data['publishDate'])) {
            $data['main_date_str']
                = $this->metadataUtils->extractYear($data['publishDate']);
            $data['main_date'] = $this->validateDate(
                $this->getPublicationYear() . '-01-01T00:00:00Z'
            );
        }

        if ($ranges = $this->getPublicationDateRanges()) {
            $data['publication_daterange'] = $this->dateRangeToStr(reset($ranges));
            foreach ($ranges as $range) {
                $stringDate = $this->dateRangeToStr($range);
                $data['search_daterange_mv'][] = $stringDate;
            }
        }

        foreach ($this->getRelationUrls() as $url) {
            $link = [
                'url' => $url,
                'text' => '',
                'source' => $this->source
            ];
            $data['online_urls_str_mv'][] = json_encode($link);
        }

        foreach ($this->doc->file as $file) {
            $url = (string)$file->attributes()->href
                ? trim((string)$file->attributes()->href)
                : trim((string)$file);
            $link = [
                'url' => $url,
                'text' => trim((string)$file->attributes()->name),
                'source' => $this->source
            ];
            $data['online_urls_str_mv'][] = json_encode($link);
            if (strcasecmp($file->attributes()->bundle, 'THUMBNAIL') == 0
                && !isset($data['thumbnail'])
            ) {
                $data['thumbnail'] = $url;
            }
        }

        if ($this->isOnline()) {
            // This may get overridden below...
            $data['online_boolean'] = '1';
            $data['online_str_mv'] = $this->source;
            if ($this->isFreeOnline()) {
                $data['free_online_boolean'] = '1';
                $data['free_online_str_mv'] = $this->source;
            }
        }

        foreach ($this->doc->coverage as $coverage) {
            $attrs = $coverage->attributes();
            if ($attrs->type == 'geocoding') {
                $match = preg_match(
                    '/([\d\.]+)\s*,\s*([\d\.]+)/',
                    trim((string)$coverage),
                    $matches
                );
                if ($match) {
                    if ($attrs->format == 'lon,lat') {
                        $lon = $matches[1];
                        $lat = $matches[2];
                    } else {
                        $lat = $matches[1];
                        $lon = $matches[2];
                    }
                    $data['location_geo'][] = "POINT($lon $lat)";
                }
            }
        }
        if (!empty($data['location_geo'])) {
            $data['center_coords']
                = $this->metadataUtils->getCenterCoordinates($data['location_geo']);
        }

        // Usage rights
        if ($rights = $this->getUsageRights()) {
            $data['usage_rights_str_mv'] = $rights;
            $data['usage_rights_ext_str_mv'] = $rights;
        }

        $data['source_str_mv'] = $this->source;
        $data['datasource_str_mv'] = $this->source;

        // phpcs:ignore
        /** @psalm-var list<string> */
        $a = (array)($data['author'] ?? []);
        // phpcs:ignore
        /** @psalm-var list<string> */
        $a2 = (array)($data['author2'] ?? []);
        // phpcs:ignore
        /** @psalm-var list<string> */
        $ac = (array)($data['author_corporate'] ?? []);
        $data['author_facet'] = [...$a, ...$a2, ...$ac];

        $data['format_ext_str_mv'] = $data['format'];

        return $data;
    }

    /**
     * Check if the needle is found in the haystack using fnmatch for comparison
     *
     * @param string $needle   String to look for
     * @param array  $haystack Values to compare with
     *
     * @return bool
     */
    protected function inArrayFnMatch(string $needle, array $haystack): bool
    {
        // Check for an excessively long string that would cause a warning with
        // fnmatch:
        if (strlen($needle) > 1024) {
            return false;
        }
        foreach ($haystack as $pattern) {
            if (fnmatch($pattern, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get URLs from the relation field
     *
     * @return array
     */
    protected function getRelationUrls(): array
    {
        $result = [];
        foreach ($this->doc->relation as $relation) {
            $url = trim((string)$relation);
            // Ignore too long fields. Require at least one dot surrounded by valid
            // characters or a familiar scheme
            if (strlen($url) > 4096
                || (!preg_match('/^[A-Za-z0-9]\.[A-Za-z0-9]$/', $url)
                && !preg_match('/^https?:\/\//', $url))
            ) {
                continue;
            }
            $result[] = $url;
        }
        return $result;
    }

    /**
     * Get secondary authors
     *
     * @return array
     */
    protected function getSecondaryAuthors()
    {
        return array_merge(
            parent::getSecondaryAuthors(),
            $this->getValues('author')
        );
    }

    /**
     * Return publication year/date ranges
     *
     * @return array
     */
    protected function getPublicationDateRanges(): array
    {
        $result = [];
        foreach ([$this->doc->date, $this->doc->issued] as $arr) {
            foreach ($arr as $date) {
                $years = $this->getYearsFromString($date);
                if (isset($years['startYear'])) {
                    $result[] = [
                        $years['startYear'] . '-01-01T00:00:00Z',
                        $years['endYear'] . '-12-31T23:59:59Z'
                    ];
                }
            }
        }
        return array_unique($result, SORT_REGULAR);
    }

    /**
     * Return usage rights if any
     *
     * @return array ['restricted'] or more specific id's if defined for the record
     */
    protected function getUsageRights()
    {
        if (!isset($this->doc->rights)) {
            return ['restricted'];
        }
        $result = [];
        // Try to find useful rights, fall back to the first entry if not found
        $firstRights = '';
        foreach ($this->doc->rights as $rights) {
            if ('' === $firstRights) {
                $firstRights = (string)$rights;
            }
            if ($rights->attributes()->lang) {
                // Language string, hope for something better
                continue;
            }
            $type = (string)$rights->attributes()->type;
            if ('' !== $type && 'url' !== $type) {
                continue;
            }
            $rights = trim((string)$rights);
            $result[] = $rights;
        }
        if (!$result && $firstRights) {
            $result[] = $firstRights;
        }
        $result = array_map(
            function ($s) {
                // Convert lowercase CC rights to uppercase
                if (strncmp($s, 'cc', 2) === 0) {
                    $s = mb_strtoupper($s, 'UTF-8');
                }
                return $s;
            },
            $result
        );
        return $result;
    }

    /**
     * Return URLs associated with object
     *
     * @return array
     */
    protected function getUrls()
    {
        $urls = parent::getUrls();

        if ($this->doc->permaddress) {
            $urls[] = trim((string)$this->doc->permaddress[0]);
        }

        foreach ($this->getValues('identifier') as $identifier) {
            $res = preg_match(
                '/^(URN:NBN:fi:|URN:ISBN:978-?951|URN:ISBN:951)/i',
                $identifier
            );
            if ($res) {
                if (!empty($urls)) {
                    // Check that the identifier is not already listed
                    foreach ($urls as $url) {
                        if (stristr($url, $identifier) !== false) {
                            continue 2;
                        }
                    }
                }
                $urls[] = "http://urn.fi/$identifier";
            }
        }

        return $urls;
    }

    /**
     * Check if the record is available online
     *
     * @return bool
     */
    protected function isOnline(): bool
    {
        if (null !== ($online = $this->getDriverParam('online', null))) {
            return boolval($online);
        }
        // Note: Make sure not to use `empty()` for the file check since the element
        // will be empty.
        if (!empty($this->getRelationUrls()) || $this->doc->file) {
            return true;
        }
        return false;
    }

    /**
     * Check if the record is freely available online
     *
     * @return bool
     */
    protected function isFreeOnline(): bool
    {
        if (null !== ($free = $this->getDriverParam('freeOnline', null))) {
            return boolval($free);
        }

        // Check rights for open access or restricted access indicators:
        foreach ($this->doc->rights as $rights) {
            $rights = trim((string)$rights);
            if ($this->inArrayFnMatch($rights, $this->openAccessRights)) {
                return true;
            }
            if ($this->inArrayFnMatch($rights, $this->restrictedAccessRights)) {
                return false;
            }
        }

        return $this->getDriverParam('freeOnlineDefault', true);
    }
}
