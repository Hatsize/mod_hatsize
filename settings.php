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

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_POPUP,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_POPUP,
                                   RESOURCELIB_DISPLAY_EMBED,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('hatsize/framesize',
        get_string('framesize', 'hatsize'), get_string('configframesize', 'hatsize'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('hatsize/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'hatsize'), 1));
    $settings->add(new admin_setting_configcheckbox('hatsize/rolesinparams',
        get_string('rolesinparams', 'hatsize'), get_string('configrolesinparams', 'hatsize'), false));
    $settings->add(new admin_setting_configmultiselect('hatsize/displayoptions',
        get_string('displayoptions', 'hatsize'), get_string('configdisplayoptions', 'hatsize'),
        $defaultdisplayoptions, $displayoptions));
    $settings->add(new admin_setting_configtext('hatsize/webservices', get_string('configwebservices', 'hatsize'),
        get_string('configwebservicesinfo', 'hatsize'), ''));
    $settings->add(new admin_setting_configtextarea('hatsize/apikey', get_string('configapikey', 'hatsize'),
        get_string('configapikeyinfo', 'hatsize'), ''));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('urlmodeditdefaults', get_string('modeditdefaults', 'hatsize'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('hatsize/printintro',
        get_string('printintro', 'hatsize'), get_string('printintroexplain', 'hatsize'), false));
    $settings->add(new admin_setting_configselect('hatsize/display',
        get_string('displayselect', 'hatsize'), get_string('displayselectexplain', 'hatsize'), RESOURCELIB_DISPLAY_EMBED, $displayoptions));
    $settings->add(new admin_setting_configtext('hatsize/popupwidth',
        get_string('popupwidth', 'hatsize'), get_string('popupwidthexplain', 'hatsize'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('hatsize/popupheight',
        get_string('popupheight', 'hatsize'), get_string('popupheightexplain', 'hatsize'), 480, PARAM_INT, 7));
}
