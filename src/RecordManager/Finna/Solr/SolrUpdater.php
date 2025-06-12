<?php

/**
 * SolrUpdater Class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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

namespace RecordManager\Finna\Solr;

use RecordManager\Base\Record\AbstractRecord;

use function is_callable;

/**
 * SolrUpdater Class
 *
 * This is a class for updating the Solr index.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class SolrUpdater extends \RecordManager\Base\Solr\SolrUpdater
{
    /**
     * Add extra fields from settings etc. and map the values
     *
     * @param array          $data           Field array
     * @param mixed          $record         Database record
     * @param AbstractRecord $metadataRecord Metadata record
     * @param string         $source         Source ID
     * @param array          $settings       Settings
     *
     * @return void
     */
    protected function augmentAndProcessFields(
        array &$data,
        $record,
        AbstractRecord $metadataRecord,
        string $source,
        array $settings
    ): void {
        parent::augmentAndProcessFields($data, $record, $metadataRecord, $source, $settings);
        if (!isset($data['catalog_date'])) {
            $field = $settings['catalogDateField'] ?? 'first_indexed';
            if ($date = $data[$field] ?? null) {
                $data['catalog_date'] = $date;
            }
        }
    }

    /**
     * Merge component parts to record
     *
     * @param AbstractRecord $metadataRecord Record to merge component parts to
     * @param array          $record         Database record
     * @param \Traversable   $components     Component parts to merge
     * @param string         $source         Source ID
     *
     * @return int Amount of merged component parts
     */
    protected function mergeComponentParts(
        AbstractRecord $metadataRecord,
        array &$record,
        \Traversable $components,
        string $source
    ): int {
        if (!is_callable([$metadataRecord, 'mergeComponentPartsExtended'])) {
            return 0;
        }
        $changeDate = null;
        $mergedComponents = $metadataRecord->mergeComponentPartsExtended(
            $components,
            $changeDate,
            function (&$data) use ($source) {
                $format = $data['format'] ?? null;
                if (!$format) {
                    return;
                }
                if ($results = $this->fieldMapper->mapFormat($source, [$format])) {
                    $data['format'] = reset($results);
                }
            }
        );

        // Use latest date as the host record date
        // @phpstan-ignore-next-line
        if (null !== $changeDate && $changeDate > $record['date']) {
            $record['date'] = $changeDate;
        }
        return $mergedComponents;
    }
}
