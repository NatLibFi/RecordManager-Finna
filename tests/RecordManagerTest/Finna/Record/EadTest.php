<?php

/**
 * Finna EAD Record Driver Test Class
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManagerTest\Finna\Record;

use RecordManager\Finna\Record\Ead;

/**
 * Finna EAD Record Driver Test Class
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class EadTest extends \RecordManagerTest\Base\Record\RecordTestBase
{
    /**
     * Test EAD record handling
     *
     * @return void
     */
    public function testEad1()
    {
        $record = $this->createRecord(
            Ead::class,
            'ead1.xml',
            [
                '__unit_test_no_source__' => [
                    'driverParams' => [
                        'addIdToHierarchyTitle=false',
                    ],
                ],
            ],
            'Finna',
        );
        $fields = $record->toSolrArray();
        unset($fields['fullrecord']);

        $expected = [
            'allfields' => [
                'Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä.',
                'USA_1287',
                '1940',
                '172 vedosta',
                'Siirtolaisuusinstituutin arkisto. Hämeenkatu 13, 20500 Turku',
                'fin',
                'eng',
                'Haapaniemi, Fred',
                '1912-1966',
                'Siirtolaisuusinstituutti',
                'Sivu 1',
                'Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä kesäkuussa 1940.',
                'Image/Photo',
                'Yhdysvallat',
                '40.71278, -74.00611',
                'New York (kaupunki)',
                'muuttoliike',
                'siirtolaisuus',
                'siirtolaiset',
                'ulkosuomalaiset',
                'amerikansuomalaiset',
                'tapahtumat',
                'maailmannäyttelyt',
                'CC BY 4.0',
                'Haapaniemi, Fred (arkisto)',
                'Haapaniemi, Fred (arkisto)',
            ],
            'author' => [],
            'author2' => [
                'Haapaniemi, Fred',
            ],
            'author_facet' => [
                'Haapaniemi, Fred',
            ],
            'author_corporate' => [],
            'author_sort' => '',
            'center_coords' => '-74.00611 40.71278',
            'ctrlnum' => [],
            'datasource_str_mv' => '__unit_test_no_source__',
            'description' => 'Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä kesäkuussa 1940.',
            'format' => 'Image/Photo',
            'format_ext_str_mv' => [
                'Image/Photo',
                'Image',
            ],
            'free_online_boolean' => 1,
            'free_online_str_mv' => 'Siirtolaisuusinstituutti',
            'geographic' => [
                'Yhdysvallat',
                'New York (kaupunki)',
            ],
            'geographic_facet' => [
                'Yhdysvallat',
                'New York (kaupunki)',
            ],
            'hierarchy_parent_id' => '239',
            'hierarchy_parent_title' => 'Haapaniemi, Fred (arkisto)',
            'hierarchy_sequence' => '0000003',
            'hierarchy_sequence_str' => '0000003',
            'hierarchy_top_id' => '239',
            'hierarchy_top_title' => 'Haapaniemi, Fred (arkisto)',
            'hierarchytype' => 'Default',
            'identifier' => 'USA_1287',
            'institution' => 'Siirtolaisuusinstituutti',
            'isbn' => [],
            'issn' => [],
            'language' => [
                'fin',
                'eng',
            ],
            'location_geo' => 'POINT(-74.00611 40.71278)',
            'main_date' => '1940-01-01T00:00:00Z',
            'main_date_str' => '1940',
            'material' => '',
            'media_type_str_mv' => [
                0 => 'image/jpeg',
            ],
            'online_boolean' => 1,
            'online_str_mv' => 'Siirtolaisuusinstituutti',
            'physical' => [
                '172 vedosta',
            ],
            'publishDate' => [],
            'publishDateRange' => [],
            'publishDateSort' => '',
            'record_format' => 'ead',
            'search_daterange_mv' => '[1940-01-01 TO 1940-12-31]',
            'series' => '',
            'source_str_mv' => 'Siirtolaisuusinstituutti',
            'thumbnail' => '',
            'title' => 'USA_1287 Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä. (1940)',
            'title_full' => 'USA_1287 Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä. (1940)',
            'title_short' => 'Suomi päivä "Finland Day" New Yorkin maailmannäyttelyssä. (1940)',
            'title_sort' => 'suomi päivä finland day new yorkin maailmannäyttelyssä (1940)',
            'title_sub' => 'USA_1287',
            'topic' => [
                'muuttoliike',
                'siirtolaisuus',
                'siirtolaiset',
                'ulkosuomalaiset',
                'amerikansuomalaiset',
                'tapahtumat',
                'maailmannäyttelyt',
            ],
            'topic_facet' => [
                'muuttoliike',
                'siirtolaisuus',
                'siirtolaiset',
                'ulkosuomalaiset',
                'amerikansuomalaiset',
                'tapahtumat',
                'maailmannäyttelyt',
            ],
            'unit_daterange' => '[1940-01-01 TO 1940-12-31]',
            'usage_rights_str_mv' => [
                0 => 'CC BY 4.0',
            ],
            'usage_rights_ext_str_mv' => [
                0 => 'CC BY 4.0',
            ],
          ];

        $this->compareArray($expected, $fields, 'toSolrArray');
    }
}
