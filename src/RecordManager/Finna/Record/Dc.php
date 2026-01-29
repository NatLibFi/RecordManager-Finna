<?php

/**
 * Dc record class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2012-2018.
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
use RecordManager\Base\Http\HttpService;
use RecordManager\Base\Utils\Logger;
use RecordManager\Base\Utils\MetadataUtils;

use function array_slice;

/**
 * Dc record class
 *
 * This is a class for processing Dublin Core records.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class Dc extends \RecordManager\Base\Record\Dc
{
    use DateSupportTrait;
    use Feature\MediaTypeTrait;
    use Feature\IndexValueTrait;

    /**
     * Constructor
     *
     * @param array         $config           Main configuration
     * @param array         $dataSourceConfig Data source settings
     * @param Logger        $logger           Logger
     * @param MetadataUtils $metadataUtils    Metadata utilities
     * @param HttpService   $httpService      HTTP service
     * @param ?Database     $db               Database
     */
    public function __construct(
        $config,
        $dataSourceConfig,
        Logger $logger,
        MetadataUtils $metadataUtils,
        HttpService $httpService,
        ?Database $db = null
    ) {
        parent::__construct($config, $dataSourceConfig, $logger, $metadataUtils, $httpService, $db);
        $this->initMediaTypeTrait($config);
        $this->initIndexValueTrait($config);
    }

    /**
     * Return fields to be indexed in Solr
     *
     * @param ?Database $db Database connection. Omit to avoid database lookups for related records.
     *
     * @return array<string, mixed>
     */
    public function toSolrArray(?Database $db = null)
    {
        $data = parent::toSolrArray($db);

        if (null !== ($publishDate = $data['publishDate'][0] ?? null)) {
            $year = $this->metadataUtils->extractYear($publishDate);
            $data['main_date_str'] = $year;
            $data['main_date'] = $this->validateDate($year . '-01-01T00:00:00Z');
        }

        if ($range = $this->getPublicationDateRange()) {
            $data['search_daterange_mv'][] = $data['publication_daterange'] = $this->dateRangeToStr($range);
        }

        // language, take only first
        $data['language'] = array_slice($data['language'], 0, 1);

        $data['source_str_mv'] = $this->source;
        $data['datasource_str_mv'] = $this->source;

        $data['author_facet'] = $this->createAuthorFacetArray($data);

        $data['format_ext_str_mv'] = $data['format'];

        return $data;
    }

    /**
     * Return publication year/date range
     *
     * @return array|null
     */
    protected function getPublicationDateRange()
    {
        $year = $this->getPublicationYear();
        if ($year) {
            $startDate = "$year-01-01T00:00:00Z";
            $endDate = "$year-12-31T23:59:59Z";
            return [$startDate, $endDate];
        }
        return null;
    }
}
