<?php
namespace Repositorium;

class History
{
	protected $objectIdentifier = '';
	protected $arrVersions = array();

	public function __construct($objectIdentifier)
	{
		$this->objectIdentifier = $objectIdentifier;
		$this->arrVersions = array();
	}

	public function getObjectIdentifier()
	{
		return $this->objectIdentifier;
	}

	public function addVersion($version, $message, $timestamp)
	{
		if (isset($this->arrVersions[$version])) {
			return false;
		}

		$this->arrVersions[$version] = array(
			'message' => $message,
			'timestamp' => $timestamp
		);

		return true;
	}

	public function removeVersion($version)
	{
		unset($this->arrVersions[$version]);
	}

	public function getVersionMessage($version)
	{
		if (!isset($this->arrVersions[$version])) {
			return false;
		}

		return $this->arrVersions[$version]['message'];
	}

	public function getVersionTimestamp($version)
	{
		if (!isset($this->arrVersions[$version])) {
			return false;
		}

		return $this->arrVersions[$version]['timestamp'];
	}

	public function getFullHistory()
	{
		return $this->arrVersions;
	}
}