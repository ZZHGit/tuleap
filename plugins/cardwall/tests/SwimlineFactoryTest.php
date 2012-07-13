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

require_once dirname(__FILE__).'/../include/SwimlineFactory.class.php';
require_once dirname(__FILE__).'/../../tracker/tests/builders/anArtifact.php';

class Cardwall_SwimLineFactoryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->factory    = new Cardwall_SwimlineFactory();
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsAndNoPresenters() {
        $columns    = array();
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsButSomePresenters() {
        $columns    = array();
        $presenters = array(mock('Cardwall_CardInCellPresenter'));
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterPresentersIfThereAreColumnsButNoPresenters() {
        $columns    = array(mock('Cardwall_Column'));
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $expected   = array(
                          array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itAsksTheColumnIfItGoesInThere() {
        $artifact1 = anArtifact()->withId(1)->build();
        $artifact2 = anArtifact()->withId(2)->build();
        $columns   = array(stub('Cardwall_Column')->isInColumn($artifact1)->returns(true),
                           stub('Cardwall_Column')->isInColumn($artifact2)->returns(true));
        $cardincell_presenter1 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact1);
        $cardincell_presenter2 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact2);
        
        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter1, $cardincell_presenter2));
        $expected  = array(
                        array('cardincell_presenters' => array($cardincell_presenter1)),
                        array('cardincell_presenters' => array($cardincell_presenter2)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itIgnoresPresentersIfThereIsNoMatchingColumn() {
        $artifact = anArtifact()->build();
        $columns  = array(stub('Cardwall_Column')->isInColumn($artifact)->returns(false));
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);

        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter));
        $expected  = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
}

?>