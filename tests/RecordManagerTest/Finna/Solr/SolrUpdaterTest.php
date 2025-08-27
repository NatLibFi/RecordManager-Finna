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
                '$in' => array_values((array)$dbRecord['linking_id']),
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
        $recordMap = [
            [$params, [], $records[0]],
        ];
        $dsOverride = [
            'test' => [
                'mergeMultiLevelParts' => true,
                'componentParts' => 'merge_all',
            ],
        ];
        $database = $this->getMockBuilder(MongoDatabase::class)->disableOriginalConstructor()->getMock();
        $database->expects($this->any())->method('findRecord')->willReturnMap($recordMap);
        $database->expects($this->any())->method('findRecords')->willReturn($records);
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
     * Create SolrUpdater
     *
     * @param array              $dsConfigOverrides Data source config overrides
     * @param ?DatabaseInterface $database          Database mock object
     *
     * @return SolrUpdater
     */
    protected function getSolrUpdater(
        array $dsConfigOverrides = [],
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

        $record = $this->getMockBuilder(Marc::class)->onlyMethods(['createRecord'])->setConstructorArgs([
            [],
            [],
            $this->createMock(Logger::class),
            $metaDataUtils,
            fn ($metadata) => new MarcMarc($metadata),
            $this->createMock(FormatCalculator::class),
            $recordPluginManager,
        ])->getMock();

        $record->expects($this->any())->method('createRecord')->willReturnCallback(
            function ($format, $data, $oaiID, $source, $extraData = []) use ($record) {
                $cloneRecord = clone $record;
                $cloneRecord->setData($source, $oaiID, $data, $extraData);
                return $cloneRecord;
            }
        );

        $recordPluginManager->expects($this->any())->method('get')->willReturnCallback(function () use ($record) {
            return clone $record;
        });
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
