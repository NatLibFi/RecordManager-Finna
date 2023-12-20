<?php

/**
 * Sierra API Harvesting Class
 *
 * PHP version 8
 *
 * Copyright (c) The National Library of Finland 2023.
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

namespace RecordManager\Finna\Harvest;

use function count;
use function in_array;

/**
 * SierraApi Class
 *
 * This class harvests records via the III Sierra REST API using settings from
 * datasources.ini.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class SierraApi extends \RecordManager\Base\Harvest\SierraApi
{
    /**
     * Fields to request from Sierra
     *
     * @var string
     */
    protected $harvestFields = 'id,copies,orders,deleted,locations,fixedFields,varFields';

    /**
     * Convert Sierra record to MARC-in-JSON -style array format
     *
     * @param array $record Sierra BIB record varFields
     *
     * @return array
     */
    protected function convertRecordToMarcArray($record)
    {
        $marc = parent::convertRecordToMarcArray($record);

        if ($record['orders'] ?? null) {
            $marc['fields'][] = [
                '852' => [
                    'ind1' => ' ',
                    'ind2' => ' ',
                    'subfields' => [
                        ['t' => array_sum(array_column($record['orders'], 'copies'))],
                        ['9' => 'orders'],
                    ],
                ],
            ];
        }

        if ($record['copies'] ?? null) {
            $marc['fields'][] = [
                '852' => [
                    'ind1' => ' ',
                    'ind2' => ' ',
                    'subfields' => [
                        ['t' => $record['copies']],
                        ['9' => 'items'],
                    ],
                ],
            ];
        }

        uasort(
            $marc['fields'],
            function ($a, $b) {
                return key($a) <=> key($b);
            }
        );

        return $marc;
    }

    /**
     * Check if the record is deleted.
     *
     * @param array $record Sierra Bib Record
     *
     * @return bool
     */
    protected function isDeleted($record)
    {
        if ($record['deleted']) {
            return true;
        }
        if (isset($record['fixedFields']['31'])) {
            $suppressed = in_array(
                $record['fixedFields']['31']['value'],
                $this->suppressedBibCode3
            );
            if ($suppressed) {
                return true;
            }
        }
        return false;
    }

    /**
     * Report the results of harvesting
     *
     * @return void
     */
    protected function reportResults()
    {
        $this->infoMsg(
            'Harvested ' . $this->changedRecords . ' normal and '
            . $this->deletedRecords . ' deleted records'
        );
    }
}
