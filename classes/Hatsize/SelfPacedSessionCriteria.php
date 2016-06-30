<?php
namespace Hatsize;

class SelfPacedSessionCriteria {
	/**
	 *
	 * @var boolean
	 */
	public $includeUpcoming = false;
	/**
	 *
	 * @var boolean
	 */
	public $includeInProgress = false;
	/**
	 *
	 * @var boolean
	 */
	public $includeCompleted = false;
	/**
	 *
	 * @var boolean
	 */
	public $includeCanceled = false;
	/**
	 *
	 * @var int
	 */
	public $organizationId = '';
	/**
	 *
	 * @var int
	 */
	public $userId = '';
	/**
	 *
	 * @var int
	 */
	public $templateId = '';
	/**
	 *
	 * @var string
	 */
	public $startTime = '';
	/**
	 *
	 * @var string
	 */
	public $endTime = '';
}
