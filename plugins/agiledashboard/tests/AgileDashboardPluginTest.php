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

require_once dirname(__FILE__) .'/../include/agiledashboardPlugin.class.php';

require_once dirname(__FILE__) .'/../include/AgileDashboard/SearchController.class.php';
Mock::generate('AgileDashboard_SearchController');

class AgileDashboardPluginTest extends UnitTestCase {
    
    public function testProcessShouldRunActionIndexOfAgileDashboardControllerByDefault() {
        $controller = new MockAgileDashboard_SearchController();
        $controller->expectOnce('search');
        
        $plugin = TestHelper::getPartialMock('AgileDashboardPlugin', array('getController'));
        $plugin->setReturnValue('getController', $controller);
        
        $request = new Codendi_Request(array());
        
        $plugin->process($request);
    }
}
?>