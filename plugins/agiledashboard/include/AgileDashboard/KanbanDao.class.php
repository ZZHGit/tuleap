<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_KanbanDao extends DataAccessObject {

    public function create($kanban_name, $tracker_kanban) {
        $tracker_kanban = $this->da->escapeInt($tracker_kanban);
        $kanban_name    = $this->da->quoteSmart($kanban_name);

        $sql = "INSERT INTO plugin_agiledashboard_kanban_configuration (tracker_id, name)
                VALUES ($tracker_kanban, $kanban_name)";

        return $this->update($sql);
    }

    public function getKanbanByTrackerId($tracker_kanban) {
        $tracker_kanban = $this->da->escapeInt($tracker_kanban);

        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE kanban_config.tracker_id = $tracker_kanban";

        return $this->retrieve($sql);
    }

    public function getTrackersWithKanbanUsage($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT tracker.id, tracker.name, kanban_config.name AS used
                FROM tracker
                    LEFT JOIN plugin_agiledashboard_kanban_configuration AS kanban_config
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE tracker.group_id = $project_id
                ORDER BY tracker.name ASC";

        return $this->retrieve($sql);
    }

    public function getKanbansForProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE tracker.group_id = $project_id
                ORDER BY kanban_config.name ASC";

        return $this->retrieve($sql);
    }
}