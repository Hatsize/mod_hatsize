<?php
namespace Hatsize;

class User {
	/**
	 *
	 * @var int
	 */
	public $id = '';
	/**
	 *
	 * @var string
	 */
	public $firstName = '';
	/**
	 *
	 * @var string
	 */
	public $lastName = '';
	/**
	 *
	 * @var string
	 */
	public $username = '';
	/**
	 *
	 * @var string
	 */
	public $password = '';
	/**
	 *
	 * @var string
	 */
	public $email = '';
	/**
	 *
	 * @var string
	 */
	public $role = 'User';
	/**
	 *
	 * @var boolean
	 */
	public $disabled = '';
	
	/**
	 *
	 * @var \Hatsize\Address
	 */
	public $address = '';
	
	/**
	 *
	 * @var \Hatsize\UserAttribute[]
	 */
	public $attributes = array();
}
