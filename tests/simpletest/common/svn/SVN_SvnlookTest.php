<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_Svnlook_getDirectoryListingTest extends TuleapTestCase {

    private $svn_prefix;
    private $svnrepo;
    private $project;
    private $svnlook;

    // Use of construct/destruct to save time (avoid destruction and recreate of svn repo each time)
    public function __construct() {
        parent::__construct();
        $this->svn_prefix = dirname(__FILE__).'/_fixtures';
        $project_name = 'svnrepo';
        $this->project = stub('Project')->getSVNRootPath()->returns($this->svn_prefix . '/' . $project_name);
        $this->svnrepo = $this->svn_prefix . '/' . $project_name;
        exec("svnadmin create $this->svnrepo");
        exec("svn mkdir --username donald_duck --parents -m 'this is 1.0' file://$this->svnrepo/tags/1.0");
        exec("svn mkdir --parents -m 'that is 2.0' file://$this->svnrepo/tags/2.0");
    }

    public function __destruct() {
        exec("/bin/rm -rf $this->svnrepo");
    }

    public function setUp() {
        parent::setUp();
        $this->svnlook = new SVN_Svnlook();
    }

    public function itGetADirectoryContents() {
        $tags = $this->svnlook->getDirectoryListing($this->project, '/tags');
        $tags = array_values($tags);
        $tags = sort($tags);
        $this->assertEqual($tags, array('1.0', '2.0'));
    }

    public function itGetsHistoryOfAPath() {
        $this->assertEqual(
            $this->svnlook->getPathLastHistory($this->project, '/tags'),
            array(
                'REVISION   PATH',
                '--------   ----',
                '       2   /tags',
            )
        );
    }

    public function itGetsTheLogForARevision() {
        $expected_message = 'this is 1.0';
        $log = $this->svnlook->getInfo($this->project, 1);
        $this->assertCount($log, 4);
        $this->assertEqual($log[0], 'donald_duck');
        $this->assertEqual($log[2], strlen($expected_message));
        $this->assertEqual($log[3], $expected_message);

        // Date
        $str_date = substr($log[1], 0, strpos($log[1], '('));
        $log_timestamp = strtotime($str_date);

        // Same year-month-day
        $this->assertEqual(date('Y-m-d'), date('Y-m-d', $log_timestamp));
    }
}
?>
