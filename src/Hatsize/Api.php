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

namespace Hatsize;

class Api {
  private $client;
  private $errors = array();

    /**
     * @param  string $wsdlUrl WSDL URL
     * @param  string $cert certificate path
     * @return self
     * @throws Exception with invalid certificate path argument
     */
  public function __construct($wsdlUrl, $cert) {
    if (!is_readable($cert)) {
      throw new \Exception('Invalid HTTPS client certificate path.');
    }

    $options = array(
      'trace' => true,
      'local_cert' => $cert
    );

    $this->client = new \SoapClient($wsdlUrl, $options);
  }

  /**
   * @param \Hatsize\User $user
   * @return userId
   */
  public function createUser(\Hatsize\User $user) {
    $result = $this->callApi(__FUNCTION__, $user);

    if(!$result) {
      return false;
    }

    return $result->userId;
  }

  /**
   * @param \Hatsize\User $user
   * @return userId
   */
  public function updateUserById(\Hatsize\User $user) {
    $result = $this->callApi(__FUNCTION__, $user);

    if(!$result) {
      return false;
    }

    return $result->userId;
  }

  /**
   * @param string $username
   * @return \Hatsize\User
   */
  public function getUserByUsername($username) {
    $result = $this->callApi(__FUNCTION__, array("username" => $username));

    if(!$result) {
      return false;
    }

    return $this->getUser($result->users->item);
  }

  /**
   * @return \Hatsize\Group[]
   */
  public function getGroups() {
    $result = $this->callApi(__FUNCTION__);

    if(!$result) {
      return false;
    }

    $groups = array();

    self::formatArray($result->groups);

    foreach ($result->groups as $group) {
      $groups[] = $this->getGroup($group);
    }

    return $groups;
  }

  /**
   * @return \Hatsize\Group
   */
  public function getGroupByName($name) {
    $result = $this->callApi(__FUNCTION__, array("name" => $name));

    if(!$result) {
      return false;
    }

    return $this->getGroup($result->groups->item);
  }

  /**
   * @return \Hatsize\SelfPacedSession[]
   */
  public function getSelfPacedSessions(\Hatsize\SelfPacedSessionCriteria $criteria) {
    $result = $this->callApi(__FUNCTION__, array("criteria" => $criteria));

    if(!$result) {
      return false;
    }

    $sessions = array();

    self::formatArray($result->sessions);

    foreach ($result->sessions as $sessionData) {
      $sessions[] = $this->getSelfPacedSession($sessionData);
    }

    return $sessions;
  }

  /**
   * @param int $userId
   * @param int $groupId
   * @return boolean
   */
  public function addUserToGroup($userId, $groupId) {
    $result = $this->callApi(__FUNCTION__, array("userId" => $userId, "groupId" => $groupId));

    if(!$result) {
      $errorout = getLastErrorMessage();
      echo "Cannot Creae session in api - " . $errorout;
      return false;
    }

    return true;
  }

  /**
   * @param int $userId
   * @param int $groupId
   * @return boolean
   */
  public function removeUserFromGroup($userId, $groupId) {
    $result = $this->callApi(__FUNCTION__, array("userId" => $userId, "groupId" => $groupId));

    if(!$result) {
      return false;
    }

    return true;
  }

  /**
   * @param int $userId
   * @return \Hatsize\SelfPacedTemplate[]
   */
  public function getSelfPacedTemplatesForUser($userId) {
    $result = $this->callApi(__FUNCTION__, array("userId" => $userId));

    if(!$result) {
      return false;
    }

    $templates = array();

    self::formatArray($result->templates);

    foreach ($result->templates as $templateData) {
      $templates[] = $this->getSelfPacedTemplate($templateData);
    }

    return $templates;
  }

  /**
   * @param int $userId
   * @param int $templateId
   * @param \DateTime $startTime
   * @param \DateTime $endTime
   * @param string $location
   * @return eventId
   */
  public function createSelfPacedSessionForUser($userId, $templateId, \DateTime $startTime, \DateTime $endTime, $location) {
    $attributes = array(
      "userId" => $userId,
      "templateId" => $templateId,
      "session" => array(
        "startTime" => $startTime->format(\DateTime::ATOM),
        "endTime" => $endTime->format(\DateTime::ATOM)
      ),
      "location" => $location
    );

    $result = $this->callApi(__FUNCTION__, $attributes);

    if(!$result) {
      return false;
    }

    return $result->eventId;
  }

  /**
   * @return errors
   */
  public function getErrors() {
    return $this->errors;
  }

  public function getLastErrorMessage() {
    if($this->errors) {
      return $this->errors[0]->message;
    }
  }

  private function callApi($apiName, $arguments = array()) {
    $this->errors = array();

    try {
      $result = $this->client->__soapCall($apiName, array($arguments));
    } catch (\Exception $e) {
      // $this->errors[] = array("message" => $e->getMessage(), "code" => $e->getCode());
      $this->errors[] = (object)array("message" => $e->getMessage(), "code" => $e->getCode());
      return false;
    }

    $result = reset($result);

    if(!$result->success) {
      self::formatArray($result->errors);
      $this->errors = $result->errors;
      return false;
    }

    return $result;
  }

  /**
   * @param \stdClass $result
   * @return \Hatsize\User
   */
  private function getUser($result) {
    /* @var $user \Hatsize\User */
    $user = self::cast(new \Hatsize\User(), $result);

    $user->address = self::cast(new \Hatsize\Address(), $user->address);

    $attributes = array();

    self::formatArray($user->attributes);

    foreach($user->attributes as $attribute) {
      $attributes[] = self::cast(new \Hatsize\UserAttribute(), $attribute);
    }

    $user->attributes = $attributes;
    return $user;
  }

  /**
   * @param \stdClass $result
   * @return \Hatsize\Group
   */
  private function getGroup($result) {
    return self::cast(new \Hatsize\Group(), $result);
  }

  /**
   * @param \stdClass $result
   * @return \Hatsize\SelfPacedSession
   */
  private function getSelfPacedSession($result) {
    /* @var $session \Hatsize\SelfPacedSession */
    $session = self::cast(new \Hatsize\SelfPacedSession(), $result);

    $urls = array();

    self::formatArray($session->urls);

    foreach($session->urls as $url) {
      $urls[] = self::cast(new \Hatsize\LabUrl(), $url);
    }

    $session->urls = $urls;
    return $session;
  }

  /**
   * @param \stdClass $result
   * @return \Hatsize\SelfPacedTemplate
   */
  private function getSelfPacedTemplate($data) {
    self::formatArray($data->locations);
    return self::cast(new \Hatsize\SelfPacedTemplate(), $data);
  }

  private static function formatArray(&$input) {
    if(!isset($input->item)) {
      $input = array();
    }
    else if(!is_array($input->item)) {
      $input = array($input->item);
    }
    else {
      $input = $input->item;
    }
  }

  private static function cast($destination, \stdClass $source) {
    $sourceReflection = new \ReflectionObject($source);
    $sourceProperties = $sourceReflection->getProperties();
    foreach ($sourceProperties as $sourceProperty) {
      $name = $sourceProperty->getName();
      $destination->{$name} = $source->$name;
    }
    return $destination;
  }
}
