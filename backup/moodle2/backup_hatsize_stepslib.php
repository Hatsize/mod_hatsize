<?php
// This file is part of the Hatsize Lab Activity Module for Moodle
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     mod_hatsize
 * @copyright   2016 Hatsize Learning {@link http://hatsize.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
* Define the complete url structure for backup, with file and id annotations
*/
class backup_hatsize_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // The URL module stores no user info

        // Define each element separated
        $hatsize = new backup_nested_element('hatsize', array('id'), array(
            'name', 'hsgroup', 'template', 'intro', 'introformat',
            'display', 'displayoptions', 'parameters', 'timemodified'));


        // Build the tree
        // Nothing here for URLs

        // Define sources
        $hatsize->set_source_table('hatsize', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // Module has no id annotations

        // Define file annotations
        $hatsize->annotate_files('mod_hatsize', 'intro', null); // This file area hasn't itemid

        // Return the root element (url), wrapped into standard activity structure
        return $this->prepare_activity_structure($hatsize);

    }
}
