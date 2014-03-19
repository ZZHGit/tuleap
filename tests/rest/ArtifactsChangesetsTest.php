<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group ArtifactsChangesets
 */
class ArtifactsChangesetsTest extends RestBase {

    private $artifact_resource;

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    public function __construct() {
        parent::__construct();
        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            new Guzzle\Http\Client($this->base_url),
            $this->rest_request,
            TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            TestDataBuilder::TEST_USER_1_NAME
        );
        $this->createData();
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testOptionsArtifactId() {
        $response = $this->getResponse($this->client->options($this->artifact_resource['uri'].'/changesets'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());

        $this->assertTrue($response->hasHeader('X-PAGINATION-LIMIT'));
        $this->assertTrue($response->hasHeader('X-PAGINATION-OFFSET'));
        $this->assertTrue($response->hasHeader('X-PAGINATION-LIMIT-MAX'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testGetChangesetsHasPagination() {
        $response = $this->getResponse($this->client->get($this->artifact_resource['uri'].'/changesets?offset=2&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $changesets = $response->json();
        $this->assertCount(1, $changesets);
        $this->assertEquals("Awesome changes", $changesets[0]['last_comment']['body']);

        $pagination_offset = $response->getHeader('X-PAGINATION-OFFSET')->normalize()->toArray();
        $this->assertEquals($pagination_offset[0], 2);

        $pagination_size = $response->getHeader('X-PAGINATION-SIZE')->normalize()->toArray();
        $this->assertEquals($pagination_size[0], 3);
    }

    private function getResponse($request) {
        return $this->getResponseByName(
            TestDataBuilder::TEST_USER_1_NAME,
            $request
        );
    }

    private function createData() {
        $tracker = $this->tracker_test_helper->getTrackerRest('task');
        $this->artifact_resource = $tracker->createArtifact(array(
            $tracker->getSubmitTextValue('Summary', 'A task to do'),
            $tracker->getSubmitListValue('Status', 'To be done')
        ));
        $tracker->addCommentToArtifact($this->artifact_resource, "I do some changes");
        $tracker->addCommentToArtifact($this->artifact_resource, "Awesome changes");
    }
}