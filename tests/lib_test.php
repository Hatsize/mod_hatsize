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
 */

defined('MOODLE_INTERNAL') || die();

class mod_hatsize_lib_testcase extends basic_testcase {

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/hatsize/locallib.php');
    }

    /**
     * Tests the hatsize_appears_valid_url function
     * @return void
     */
    public function test_hatsize_appears_valid_url() {
        $this->assertTrue(hatsize_appears_valid_url('http://example'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.exa-mple2.com'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com/~nobody/index.html'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com#hmm'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com/#hmm'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com/žlutý koní?ek/lala.txt'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com/žlutý koní?ek/lala.txt#hmmmm'));
        $this->assertTrue(hatsize_appears_valid_url('http://www.example.com/index.php?xx=yy&zz=aa'));
        $this->assertTrue(hatsize_appears_valid_url('https://user:password@www.example.com/žlutý koní?ek/lala.txt'));
        $this->assertTrue(hatsize_appears_valid_url('ftp://user:password@www.example.com/žlutý koní?ek/lala.txt'));

        $this->assertFalse(hatsize_appears_valid_url('http:example.com'));
        $this->assertFalse(hatsize_appears_valid_url('http:/example.com'));
        $this->assertFalse(hatsize_appears_valid_url('http://'));
        $this->assertFalse(hatsize_appears_valid_url('http://www.exa mple.com'));
        $this->assertFalse(hatsize_appears_valid_url('http://www.examplé.com'));
        $this->assertFalse(hatsize_appears_valid_url('http://@www.example.com'));
        $this->assertFalse(hatsize_appears_valid_url('http://user:@www.example.com'));

        $this->assertTrue(hatsize_appears_valid_url('lalala://@:@/'));
    }
}