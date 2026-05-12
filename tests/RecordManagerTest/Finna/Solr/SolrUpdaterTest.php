<?php

/**
 * Tests for SolrUpdater
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManagerTest\Finna\Solr;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use RecordManager\Base\Database\DatabaseInterface;
use RecordManager\Base\Database\MongoDatabase;
use RecordManager\Base\Enrichment\PluginManager as EnrichmentPluginManager;
use RecordManager\Base\Http\HttpService as HttpService;
use RecordManager\Base\Marc\Marc as MarcMarc;
use RecordManager\Base\Record\Marc\FormatCalculator;
use RecordManager\Base\Record\PluginManager as RecordPluginManager;
use RecordManager\Base\Settings\Ini;
use RecordManager\Base\Utils\Logger;
use RecordManager\Base\Utils\MetadataUtils;
use RecordManager\Base\Utils\WorkerPoolManager;
use RecordManager\Finna\Record\Ead;
use RecordManager\Finna\Record\Marc;
use RecordManager\Finna\Solr\SolrUpdater;
use RecordManager\Finna\Utils\FieldMapper;
use RecordManagerTest\Base\Feature\FixtureTrait;
use RecordManagerTest\Base\Record\CreateSampleRecordTrait;

/**
 * Tests for SolrUpdater
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class SolrUpdaterTest extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;
    use CreateSampleRecordTrait;

    /**
     * Main configuration
     *
     * @var array
     */
    protected $config = [
        'Solr Field Limits' => [
            '__default__' => 1024,
            'fullrecord' => 32766,
            'fulltext' => 0,
            'fulltext_unstemmed' => 0,
            'long_lat' => 0,
            '*_keys_*' => 20,
            'title_sh*' => 30,
            '*sort' => 40,
        ],
    ];

    /**
     * Data source settings
     *
     * @var array
     */
    protected $dataSourceConfig = [
        'test' => [
            'institution' => 'test',
            'format' => 'marc',
            'format_mapping' => [
              'marc_format_basic.map',
              'marc_format_sub.map,regexp',
            ],
        ],
        'tost' => [
            'institution' => 'tost',
            'format' => 'ead',
        ],
    ];

    /**
     * Test merged component
     *
     * @return void
     */
    public function testMergedComponents(): void
    {
        $record = $this->getMockBuilder(Marc::class)->onlyMethods(['createRecord'])->setConstructorArgs([
            [],
            [],
            $this->createMock(Logger::class),
            $this->createMock(MetadataUtils::class),
            fn ($metadata) => new MarcMarc($metadata),
            $this->createMock(FormatCalculator::class),
            $this->createMock(RecordPluginManager::class),
        ])->getMock();

        $record->expects($this->any())->method('createRecord')->willReturnCallback(
            function ($format, $data, $oaiID, $source, $extraData = []) use ($record) {
                $cloneRecord = clone $record;
                $cloneRecord->setData($source, $oaiID, $data, $extraData);
                return $cloneRecord;
            }
        );
        $fixture = $this->getFixture('record/marc4.xml', 'Finna');
        $record->setData('test', 'oaitest', $fixture, []);
        $date = strtotime('2020-10-20 13:01:00');
        $dbRecord = [
            '_id' => 'test123',
            'oai_id' => '',
            'linking_id' => [
              'test12345',
            ],
            'source_id' => 'test',
            'deleted' => false,
            'created' => $date,
            'updated' => $date,
            'date' => $date,
            'format' => 'marc',
            'original_data' => $record->serialize(),
            'normalized_data' => null,
            'host_record_id' => 'test.1',
        ];
        $params = [
            'host_record_id' => [
                '$in' => array_values($dbRecord['linking_id']),
            ],
            'deleted' => false,
            'suppressed' => ['$in' => [null, false]],
            'source_id' => 'test',
        ];
        $records = new ArrayIterator([
            [
                'original_data' => $this->getFixture('record/marc5.xml', 'Finna'),
                'normalized_data' => '',
                '_id' => 'part_1',
                'source_id' => 'test',
                'oai_id' => 'testoai1',
                'format' => 'Marc',
                'date' => '2025-01-01',
            ],
            [
                'original_data' => $this->getFixture('record/marc6.xml', 'Finna'),
                '_id' => 'part_2',
                'normalized_data' => '',
                'source_id' => 'test',
                'oai_id' => 'testoai2',
                'format' => 'Marc',
                'date' => '2025-01-01',
            ],
        ]);
        $database = $this->getDatabase($records, $params);
        $dsOverride = [
            'test' => [
                'mergeMultiLevelParts' => true,
                'componentParts' => 'merge_all',
            ],
        ];
        $solrUpdater = $this->getSolrUpdater(
            dsConfigOverrides: $dsOverride,
            database: $database
        );

        $result = $solrUpdater->processSingleRecord($dbRecord);
        $recordFromResult = $result['records'][0];
        $finalRecord = $record->createRecord(Marc::class, $recordFromResult['fullrecord'], 'testoai', 'test', []);
        $this->assertEquals(2, $result['mergedComponents']);
        $expected = [
            [
                'tag' => '979',
                'i1' => ' ',
                'i2' => '0',
                'subfields' => [
                    [
                        'code' => 'a',
                        'data' => 'part_1',
                    ],
                    [
                        'code' => 'e',
                        'data' => 'Component part title 1',
                    ],
                    [
                        'code' => 'h',
                        'data' => 'fin',
                    ],
                ],
            ],
            [
                'tag' => '979',
                'i1' => ' ',
                'i2' => '1',
                'subfields' => [
                    [
                        'code' => 'a',
                        'data' => 'part_2',
                    ],
                    [
                        'code' => 'e',
                        'data' => 'Component part title 2',
                    ],
                    [
                        'code' => 'h',
                        'data' => 'fin',
                    ],
                ],
            ],
        ];

        $fields = $finalRecord->getRecord()->getFields('979');
        $this->assertEquals($expected, $fields);
    }

    /**
     * Test single record processing data provider
     *
     * @return Generator
     */
    public static function getTestProcessSingleRecordData(): Generator
    {
        $date = strtotime('2020-10-20 13:01:00');
        yield 'Test single marc record with container title field' => [
            [
                '_id' => '123',
                'oai_id' => '',
                'linking_id' => [
                    '010101',
                ],
                'host_record_id' => 'test123',
                'source_id' => 'test',
                'deleted' => false,
                'created' => $date,
                'updated' => $date,
                'date' => $date,
                'format' => 'marc',
                'original_data' => 'record/marc5.xml',
                'normalized_data' => null,
            ],
            [
                'record_format' => 'marc',
                'allfields' => [
                    'Component part title 1',
                    'Test string',
                    'Test parent string',
                ],
                'format' => 'Book/Book',
                'illustrated' => 'Not Illustrated',
                'language' => [
                    'fin',
                ],
                'publishDate' => [
                    '2013',
                ],
                'publishDateRange' => [
                    '[2013-01-01 TO 2013-12-31]',
                ],
                'publishDateSort' => '2013',
                'title_alt' => [
                    'Component part title 1',
                ],
                'title_full' => 'Component part title 1',
                'title_short' => 'Component part title 1',
                'title_sort' => 'component part title 1',
                'title' => 'Component part title 1',
                'main_date_str' => '2013',
                'main_date' => '2013-01-01T00:00:00Z',
                'search_daterange_mv' => [
                    '[2013-01-01 TO 2013-12-31]',
                ],
                'publication_daterange' => '[2013-01-01 TO 2013-12-31]',
                'major_genre_str_mv' => 'nonfiction',
                'source_str_mv' => 'test',
                'datasource_str_mv' => [
                    'test',
                ],
                'format_ext_str_mv' => 'Book/Book',
                'linking_id_str_mv' => [
                    '010101',
                ],
                'id' => '123',
                'institution' => 'test',
                'first_indexed' => '1970-01-01T00:00:00Z',
                'last_indexed' => '1970-01-01T00:00:00Z',
                'catalog_date' => '1970-01-01T00:00:00Z',
                'hierarchy_parent_id' => [
                    'test123',
                ],
                'hierarchy_parent_title' => [
                    'Host record title 1',
                ],
                'container_title' => 'Host record title 1',
                'container_title_str_mv' => [
                    'Host record title 1',
                ],
            ],
        ];

        yield 'Test single ead record with container title field' => [
            [
                '_id' => 'test456',
                'oai_id' => '',
                'linking_id' => [],
                'source_id' => 'tost',
                'deleted' => false,
                'created' => $date,
                'updated' => $date,
                'date' => $date,
                'format' => 'ead',
                'original_data' => 'record/ead.xml',
                'normalized_data' => null,
            ],
            [
                'record_format' => 'ead',
                'allfields' => [
                    'blorf mipmip zaarl frrrp tikka meowzorp flibbin 1977 glarp',
                    'mrrrp-blib-zoink',
                    'zz-ploof-9911',
                    'fiftypurr snorfle meep',
                    'gribble snarrk floomtar wubbadee',
                    'grk',
                    'fnrr',
                    'Kralloo Mipsten',
                    'Snerpaloosi Instiploop',
                    'flarpo 1',
                    'glimfadoo 15 snarr Väinö Talas blorptik Floridana snooflepuff 5+5 min floof cutoff',
                    'blip/intervuu',
                    'Yhdysblat',
                    '11.11111, 22.22222',
                    'Asikblar',
                    '33.33333, 44.44444',
                    'Päijät-Hömp',
                    '55.55555, 66.66666',
                    'Helsinkloof',
                    '77.77777, 88.88888',
                    'Uusimrr',
                    '99.99999, -11.11111',
                    'Clevorland',
                    '-22.22222, 33.33333',
                    'Detroink',
                    '-44.44444, 55.55555',
                    'Montablaa',
                    '-66.66666, 77.77777',
                    'Duluthnoo',
                    '-88.88888, 99.99999',
                    'Fort Loodle',
                    '12.34567, -76.54321',
                    'Lake Worf',
                    'mootlik',
                    'sirtooz',
                    'sirtiloot',
                    'ulkosnarf',
                    'amerisnarf',
                    'floridansnarf',
                    'eläkeblorp',
                    'työbloop',
                    'työnteek',
                    'työväää',
                    'talonrakblip',
                    'järjestööf',
                    'CC BY 4.0',
                    'blorptik snoofle 1977-1978',
                    'snarfloota kokoelma',
                ],
                'author2' => [
                    'Kralloo Mipsten',
                ],
                'description' => 'glimfadoo 15 snarr Väinö Talas blorptik Floridana snooflepuff 5+5 min floof cutoff',
                'format' => 'blip/intervuu',
                'institution' => 'Snerpaloosi Instiploop',
                'language' => ['grk', 'fnrr'],
                'physical' => ['fiftypurr snorfle meep'],
                'title_full' => 'mrrrp-blib-zoink blorf mipmip zaarl frrrp tikka meowzorp flibbin 1977 glarp (9911)',
                'title_short' => 'blorf mipmip zaarl frrrp tikka',
                'title_sort' => 'blorf mipmip zaarl frrrp tikka meowzorp ',
                'title_sub' => 'mrrrp-blib-zoink',
                'title' => 'mrrrp-blib-zoink blorf mipmip zaarl frrrp tikka meowzorp flibbin 1977 glarp (9911)',
                'topic_facet' => [
                    'mootlik','sirtooz','sirtiloot','ulkosnarf','amerisnarf','floridansnarf',
                    'eläkeblorp','työbloop','työnteek','työväää','talonrakblip','järjestööf',
                ],
                'topic' => [
                    'mootlik','sirtooz','sirtiloot','ulkosnarf','amerisnarf','floridansnarf',
                    'eläkeblorp','työbloop','työnteek','työväää','talonrakblip','järjestööf',
                ],
                'location_geo' => 'POINT(-76.54321 12.34567)',
                'center_coords' => '-76.54321 12.34567',
                'geographic_facet' => [
                    'Yhdysblat','Asikblar','Päijät-Hömp','Helsinkloof','Uusimrr','Clevorland',
                    'Detroink','Montablaa','Duluthnoo','Fort Loodle','Lake Worf',
                ],
                'geographic' => [
                    'Yhdysblat','Asikblar','Päijät-Hömp','Helsinkloof','Uusimrr','Clevorland',
                    'Detroink','Montablaa','Duluthnoo','Fort Loodle','Lake Worf',
                ],
                'hierarchytype' => 'Default',
                'hierarchy_top_id' => 'tost.272',
                'hierarchy_top_title' => 'blorptik snoofle 1977-1978',
                'hierarchy_sequence' => '0000020',
                'hierarchy_parent_id' => 'tost.272',
                'hierarchy_parent_title' => 'snarfloota kokoelma',
                'title_in_hierarchy' => 'mrrrp-blib-zoink mrrrp-blib-zoink'
                    . ' blorf mipmip zaarl frrrp tikka meowzorp flibbin 1977 glarp',
                'container_title_str_mv' => ['blorptik snoofle 1977-1978'],
                'unit_daterange' => '[9911-01-01 TO 9911-12-31]',
                'search_daterange_mv' => '[9911-01-01 TO 9911-12-31]',
                'main_date_str' => '9911',
                'main_date' => '9911-01-01T00:00:00Z',
                'hierarchy_sequence_str' => '0000020',
                'source_str_mv' => 'Snerpaloosi Instiploop',
                'datasource_str_mv' => 'tost',
                'online_boolean' => '1',
                'online_str_mv' => 'Snerpaloosi Instiploop',
                'free_online_boolean' => '1',
                'free_online_str_mv' => 'Snerpaloosi Instiploop',
                'identifier' => 'mrrrp-blib-zoink',
                'material' => "\n      \n    ",
                'usage_rights_str_mv' => ['CC BY 4.0'],
                'usage_rights_ext_str_mv' => ['CC BY 4.0'],
                'author_facet' => ['Kralloo Mipsten'],
                'format_ext_str_mv' => ['blip/intervuu'],
                'media_type_str_mv' => ['audio/mpeg'],
                'id' => 'test456',
                'first_indexed' => '1970-01-01T00:00:00Z',
                'last_indexed' => '1970-01-01T00:00:00Z',
                'catalog_date' => '1970-01-01T00:00:00Z',
            ],
        ];
    }

    /**
     * Test single record processing
     *
     * @param array $dbRecord Array presenting a record from database to be processed.
     * @param array $expected Array for expected test results
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestProcessSingleRecordData')]
    public function testProcessSingleRecord(array $dbRecord, array $expected): void
    {
        $dbRecord['original_data'] = $this->getFixture($dbRecord['original_data'], 'Finna');
        $database = $this->getDatabase();
        $solrUpdater = $this->getSolrUpdater(database: $database);
        $result = $solrUpdater->processSingleRecord($dbRecord);
        $testRecord = $result['records'][0];
        // Leave out testing full record but confirm that it exists
        $this->assertTrue(!empty($testRecord['fullrecord']));
        unset($testRecord['fullrecord']);
        $this->assertEquals($expected, $testRecord);
    }

    /**
     * Get database for storing test records found from database.
     *
     * @param ?ArrayIterator<int<0,1>, array> $dbRecords Records found in the database
     * @param ?array                          $params    Params to use for searching records from database
     *
     * @return MockObject
     */
    protected function getDatabase(
        ?ArrayIterator $dbRecords = null,
        ?array $params = null
    ): MockObject&DatabaseInterface {
        $dbRecords ??= new ArrayIterator([
            [
                'original_data' => $this->getFixture('record/marc4.xml', 'Finna'),
                'normalized_data' => '',
                '_id' => 'test123',
                'source_id' => 'test',
                'oai_id' => 'testoai1',
                'format' => 'marc',
                'date' => '2025-01-01',
            ],
        ]);

        $params ??= [
            'host_record_id' => [
                '$in' => ['test12345'],
            ],
            'deleted' => false,
            'suppressed' => ['$in' => [null, false]],
            'source_id' => 'test',
        ];
        $recordMap = [
            [$params, [], $dbRecords[0]],
        ];
        $database = $this->getMockBuilder(MongoDatabase::class)->disableOriginalConstructor()->getMock();
        $database->expects($this->any())->method('findRecord')->willReturnMap($recordMap);
        $database->expects($this->any())->method('findRecords')->willReturn($dbRecords);
        return $database;
    }

    /**
     * Create SolrUpdater
     *
     * @param array                             $dsConfigOverrides Data source config overrides
     * @param array                             $dbRecord          Database record
     * @param MockObject|DatabaseInterface|null $database          Database mock object
     *
     * @return SolrUpdater
     */
    protected function getSolrUpdater(
        array $dsConfigOverrides = [],
        array $dbRecord = [],
        ?DatabaseInterface $database = null
    ): SolrUpdater {
        $dsConfig = array_merge_recursive(
            $this->dataSourceConfig,
            $dsConfigOverrides
        );
        $logger = $this->createMock(Logger::class);
        $metadataUtils = new \RecordManager\Base\Utils\MetadataUtils(
            RECMAN_BASE_PATH,
            [],
            $logger,
        );

        $recordPluginManager = $this->getMockBuilder(\RecordManager\Base\Record\PluginManager::class)
            ->disableOriginalConstructor()->getMock();

        $metaDataUtils = $this->getMockBuilder(MetadataUtils::class)->onlyMethods([])
            ->disableOriginalConstructor()->getMock();

        $marcRecord = $this->getMockBuilder(Marc::class)->onlyMethods(['createRecord'])->setConstructorArgs([
            [],
            [],
            $this->createMock(Logger::class),
            $metaDataUtils,
            fn ($metadata) => new MarcMarc($metadata),
            $this->createMock(FormatCalculator::class),
            $recordPluginManager,
        ])->getMock();
        $marcRecord->expects($this->any())->method('createRecord')->willReturnCallback(
            function ($format, $data, $oaiID, $source, $extraData = []) use ($marcRecord) {
                var_dump(isset($marcRecord));
                $cloned = clone $marcRecord;
                $cloned->setData($source, $oaiID, $data, $extraData);
                return $cloned;
            }
        );
        $eadRecord = $this->getMockBuilder(Ead::class)->onlyMethods([])->setConstructorArgs([
            [],
            [],
            $this->createMock(Logger::class),
            $metaDataUtils,
            fn ($metadata) => new MarcMarc($metadata),
            $this->createMock(FormatCalculator::class),
            $recordPluginManager,
        ])->getMock();

        $recordMap = [
            [
                'marc',
                null,
                clone $marcRecord,
            ],
            [
                'ead',
                null,
                clone $eadRecord,
            ],
        ];

        $recordPluginManager->expects($this->any())->method('get')->willReturnMap($recordMap);

        $fieldMapper = new FieldMapper(
            $this->getFixtureDir('Finna') . 'config/basic',
            [],
            $this->dataSourceConfig
        );
        $solrUpdater = new SolrUpdater(
            $this->config,
            $dsConfig,
            $database,
            $logger,
            $recordPluginManager,
            $this->createMock(EnrichmentPluginManager::class),
            $this->createMock(HttpService::class),
            $this->createMock(Ini::class),
            $fieldMapper,
            $metadataUtils,
            $this->createMock(WorkerPoolManager::class)
        );

        return $solrUpdater;
    }
}
