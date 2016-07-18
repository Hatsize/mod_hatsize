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

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/hatsize/lib.php");
require_once("$CFG->dirroot/mod/hatsize/src/autoloader.php");

/**
 * This methods does weak url validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $hatsize
 * @return bool true is seems valid, false if definitely not valid URL
 */
function hatsize_appears_valid_url($hatsize) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $hatsize)) {
        // note: this is not exact validation, we look for severely malformed URLs only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $hatsize);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $hatsize);
    }
}

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $hatsize
 * @return string
 */
function hatsize_fix_submitted_url($hatsize) {
    // note: empty urls are prevented in form validation
    $hatsize = trim($hatsize);

    // remove encoded entities - we want the raw URI here
    $hatsize = html_entity_decode($hatsize, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $hatsize) and !preg_match('|^/|', $hatsize)) {
        // invalid URI, try to fix it by making it normal URL,
        // please note relative urls are not allowed, /xx/yy links are ok
        $hatsize = "http://hatsize.com";
    }

    return $hatsize;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $hatsize
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function hatsize_get_full_url($hatsize, $cm, $course, $config=null) {
  global $USER;
  $md_username = $USER->username;
  $md_email = $USER->email;
  $md_firstname = $USER->firstname;
  $md_lastname = $USER->lastname;
  $parameters = empty($hatsize->parameters) ? array() : unserialize($hatsize->parameters);
  $groupid = $hatsize->hsgroup;
  $templateId = $hatsize->template;
  $duration = $hatsize->duration;


  // Start Hatszie API Call ***********************************
  $config = get_config('hatsize');
  $wsdlUrl = $config->webservices;
  $certkey = $config->apikey;
  $certPath = "apikey.pem";

  if(!$wsdlUrl or !$certkey) {throw new \Exception("Missing security settings in plugin.");  }

  $certfile = fopen($certPath, "w") or die("Unable to open file!");
  fwrite($certfile, $certkey);
  fclose($certfile);
  $hatsize = new \Hatsize\Api($wsdlUrl, $certPath);


  // User Check/Create ***********************************
  $user = $hatsize->getUserByUsername($md_username);
  if(!$user) {
    $user = new \Hatsize\User();
    $user->username = $md_username;
    $user->email = $md_email;
    $user->firstName = $md_firstname;
    $user->lastName = $md_lastname;
    $user->password = mt_rand(1000000000,9999999999);;
    $userId = $hatsize->createUser($user);
    if(!$userId) {throw new \Exception("Unable to create user - Name:" . $md_username); }
  } else {
    $userId = $user->id;
  }


  // Group Assginment ***********************************
  if(!$groupid) {throw new \Exception("Unable to retrieve group");  }
  if(!$hatsize->addUserToGroup($userId, $groupid)) {throw new \Exception("Unable to add user to group - User:" . $userId . " Group:" . $groupid);}


  //  Get SelfPaced Sessions ***********************************
  $criteria = new \Hatsize\SelfPacedSessionCriteria();
  $criteria->includeInProgress = true;
  $criteria->includeUpcoming = true;
  $criteria->userId = $userId;
  $criteria->templateId = $templateId;
  $sessions = $hatsize->getSelfPacedSessions($criteria);

  if(!$sessions) {
    $sessionId = $hatsize->createSelfPacedSessionForUser($userId, $templateId, new \DateTime("+0 hours"), new \DateTime("+".$duration." minutes"), $firstTemplate->locations[0]);
    if(!$sessionId) {
      throw new \Exception("Unable to create lab session for template " . $templateId . "<br>Message: " . $hatsize->getLastErrorMessage() );
    }
    $sessions = $hatsize->getSelfPacedSessions($criteria);
    if(!$sessions) {throw new \Exception("No valid lab session found.");}
  }

  $labUrls = $sessions[0]->urls;
  if(!$labUrls) {throw new \Exception("No valid lab sessions available.");  }

  if (count($labUrls) == '2') {
    $fullurl = $labUrls[1]->url;
  } else {
    $fullurl = $labUrls[0]->url;
  }

  // End Hatsize API Code ***********************************



    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/", 'hatsize_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // add variable url parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('hatsize');
        }
        $paramvalues = hatsize_get_variable_values($hatsize, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawurlencode($parse).'='.rawurlencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullurl, 'teamspeak://') === 0) {
                $fullurl = $fullurl.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullurl, '?') === false) ? '?' : '&';
                $fullurl = $fullurl.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function hatsize_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print url header.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @return void
 */
function hatsize_print_header($hatsize, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$hatsize->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($hatsize);
    echo $OUTPUT->header();
}

/**
 * Print url heading.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function hatsize_print_heading($hatsize, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($hatsize->name), 2);
}

/**
 * Print url introduction.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function hatsize_print_intro($hatsize, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($hatsize->displayoptions) ? array() : unserialize($hatsize->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($hatsize->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'urlintro');
            echo format_module_intro('hatsize', $hatsize, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display url frames.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function hatsize_display_frame($hatsize, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        hatsize_print_header($hatsize, $cm, $course);
        hatsize_print_heading($hatsize, $cm, $course);
        hatsize_print_intro($hatsize, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('hatsize');
        $context = context_module::instance($cm->id);
        $exteurl = hatsize_get_full_url($hatsize, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/hatsize/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($hatsize->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','hatsize'));
        $contentframetitle = s(format_string($hatsize->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print url info and link.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function hatsize_print_workaround($hatsize, $cm, $course) {
    global $OUTPUT;

    hatsize_print_header($hatsize, $cm, $course);
    hatsize_print_heading($hatsize, $cm, $course, true);
    hatsize_print_intro($hatsize, $cm, $course, true);

    $fullurl = hatsize_get_full_url($hatsize, $cm, $course);

    $display = hatsize_get_final_display_type($hatsize);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($hatsize->displayoptions) ? array() : unserialize($hatsize->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="urlworkaround">';
    print_string('clicktoopen', 'hatsize', "<a href=\"$fullurl\" $extra>$fullurl</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded url file.
 * @param object $hatsize
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function hatsize_display_embed($hatsize, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = 'url'; // resourcelib_guess_url_mimetype($hatsize->externalurl);
    $fullurl  = hatsize_get_full_url($hatsize, $cm, $course);
    $title    = $hatsize->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'hatsize', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = ''; // resourcelib_get_extension($hatsize->externalurl);

    $mediarenderer = $PAGE->get_renderer('core', 'media');
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediarenderer->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediarenderer->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }

    hatsize_print_header($hatsize, $cm, $course);
    hatsize_print_heading($hatsize, $cm, $course);

    echo $code;

    hatsize_print_intro($hatsize, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $hatsize
 * @return int display type constant
 */
function hatsize_get_final_display_type($hatsize) {
    global $CFG;

    if ($hatsize->display != RESOURCELIB_DISPLAY_AUTO) {
        return $hatsize->display;
    }

    // detect links to local moodle pages

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = 'url'; //resourcelib_guess_url_mimetype($hatsize->externalurl);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to URL
 * @param object $config url module config options
 * @return array array describing opt groups
 */
function hatsize_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'hatsize'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'hatsize')] = array(
        'urlinstance'     => 'id',
        'urlcmid'         => 'cmid',
        'urlname'         => get_string('name'),
        'urlidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverurl'       => get_string('serverurl', 'hatsize'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userurl'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to URL
 * @param object $hatsize module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function hatsize_get_variable_values($hatsize, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverurl'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'urlinstance'     => $hatsize->id,
        'urlcmid'         => $cm->id,
        'urlname'         => format_string($hatsize->name),
        'urlidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $values['usertimezone']    = get_user_timezone_offset();
        $values['userurl']         = $USER->url;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    // if (!empty($config->secretphrase)) {
    //    $values['encryptedcode'] = hatsize_get_encrypted_parameter($hatsize, $config);
    //}

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $hatsize
 * @param object $config
 * @return string
 */
function hatsize_get_encrypted_parameter($hatsize, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($hatsize, $config);
        }
    }
    return md5(getremoteaddr().$config->webservices);
}

/**
 * Optimised mimetype detection from general URL
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function hatsize_guess_icon($fullurl, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullurl, '/') < 3 or substr($fullurl, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullurl, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
