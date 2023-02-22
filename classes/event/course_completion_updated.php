<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_ojt\event;

class course_completion_updated extends \core\event\base {

    // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['level'] = self::LEVEL_OTHER;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'mod_ojt';
    }

    public static function get_name() {

        return get_string('course_completion_updated', 'mod_ojt');
    }

    public static function get_legacy_eventname() {
        return 'course_completion_updated';
    }

    public function get_description() {
       
        return "The user with id '{$this->userid}' updated complete for user  with id '{$this->relateduserid}' on course {$this->courseid}";
    }
}
