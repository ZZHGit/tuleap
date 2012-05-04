<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'SemanticTitleReportField.class.php';
require_once 'Query.class.php';
require_once 'SemanticStatusReportField.class.php';
require_once 'SemanticValueFactory.class.php';
require_once 'CriteriaBuilder.class.php';
require_once 'AbstractViewBuilder.class.php';

class Tracker_CrossSearch_ViewBuilder extends Tracker_CrossSearch_AbstractViewBuilder {

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    

    public function __construct(Tracker_FormElementFactory $form_element_factory, TrackerFactory $tracker_factory, Tracker_CrossSearch_Search $search, Tracker_CrossSearch_CriteriaBuilder $criteria_builder) {
        parent::__construct($form_element_factory, $search, $criteria_builder);
        $this->tracker_factory = $tracker_factory;
    }
    
    /**
     * @return Tracker_CrossSearch_SearchView 
     */
    public function buildView(User $user, Project $project, Tracker_CrossSearch_Query $cross_search_query) {
        $report       = $this->getReport($user);
        $service      = $this->getService($project);
        $criteria     = $this->getCriteria($user, $project, $report, $cross_search_query);
        $trackers     = $this->tracker_factory->getTrackersByGroupIdUserCanView($project->getGroupId(), $user);
        $tracker_ids  = $this->getTrackersIds($trackers);
        $artifacts    = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query);
        $content_view = new Tracker_CrossSearch_SearchContentView($report, $criteria, $artifacts, Tracker_ArtifactFactory::instance(), $this->form_element_factory);
        return new Tracker_CrossSearch_SearchView($project, $service, $criteria, $trackers, $content_view);
    }
    
    public function getTrackersIds(array $trackers) {
        return array_map(array($this, 'getTrackerId'), $trackers);
    }
    
    private function getTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
}
?>