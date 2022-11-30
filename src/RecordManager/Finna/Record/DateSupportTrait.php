<?php
/**
 * Date handling support trait.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
namespace RecordManager\Finna\Record;

/**
 * Date handling support trait.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
trait DateSupportTrait
{
    /**
     * Convert a date range to a Solr date range string,
     * e.g. [1970-01-01 TO 1981-01-01]
     *
     * @param array|null $range Start and end date
     *
     * @return string|null Start and end date in Solr format
     * @throws \Exception
     */
    public function dateRangeToStr($range)
    {
        if (!$range) {
            return null;
        }
        $oldTZ = date_default_timezone_get();
        try {
            date_default_timezone_set('UTC');
            $start = date('Y-m-d', strtotime($range[0]));
            $end = date('Y-m-d', strtotime($range[1]));
        } catch (\Exception $e) {
            date_default_timezone_set($oldTZ);
            throw $e;
        }
        date_default_timezone_set($oldTZ);

        return $start === $end ? $start : "[$start TO $end]";
    }

    /**
     * Get years from a string, try exploding with separators [/-–].
     *
     * @param string $dateString String to check for years.
     *
     * @return array [startYear, endYear] or empty array if not found.
     */
    protected function getYearsFromString(string $dateString): array
    {
        $getYears = function (array $dates) {
            $result = [];
            // Reset array indexes just in case
            $dates = array_values($dates);

            for ($i = 0; $i < count($dates); $i++) {
                $exploded = explode('-', $dates[$i]);
                if (!isset($exploded[0])) {
                    continue;
                }
                // First array element = "" equals negative year
                $isNegative = $exploded[0] === "";
                if ($isNegative) {
                    array_shift($exploded);
                }
                $firstElement = reset($exploded);

                // The year should be the first element in the array.
                // Also check that it is purely numeric and contains 4 digits
                if (strlen($firstElement) === 4 && is_numeric($firstElement)) {
                    if ($isNegative) {
                        $firstElement = "-{$firstElement}";
                    }
                    switch ($i) {
                    case 0:
                        $result['startYear'] = $firstElement;
                        break;
                    case 1:
                        $result['endYear'] = $firstElement;
                        break;
                    }
                }
            }
            if (empty($result)) {
                return [];
            }
            if (!isset($result['startYear']) && isset($result['endYear'])) {
                $result['startYear'] = $result['endYear'];
            }
            if (!isset($result['endYear']) && isset($result['startYear'])) {
                $result['endYear'] = $result['startYear'];
            }
            // Verify that the startYear is smaller than the endYear.
            // If not, then assign startYear as endYear
            if (strcmp($result['startYear'], $result['endYear']) === 1) {
                $result['endYear'] = $result['startYear'];
            }
            return $result;
        };
        $split = explode('/', $dateString);
        if (count($split) === 2) {
            return $getYears($split);
        }
        $split = explode('-', $dateString);
        if (count($split) === 2 && $split[0] !== "") {
            return $getYears($split);
        }
        // Try the U-2013 just in case
        $split = explode('–', $dateString);
        if (count($split) === 2) {
            return $getYears($split);
        }
        // Splitting did not yield results. Try to get a year from the string itself.
        return $getYears([$dateString]);
    }
}
