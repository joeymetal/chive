<?php

class UserSettingsManager
{

	private $configPath;
	private $host, $user;
	private $defaultSettings = array();
	private $userSettings = array();

	public function __construct($host, $user)
	{

		$this->host = $host;
		$this->user = $user;

		// Get config path
		$this->configPath = Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'user-config' . DIRECTORY_SEPARATOR;

		// Load settings
		$this->loadSettings();

	}

	public function get($name, $scope = null)
	{
		$id = $this->getSettingId($name, $scope);

		if(isset($this->userSettings[$id]))
		{
			return $this->userSettings[$id];
		}
		elseif(isset($this->defaultSettings[$id]))
		{
			return $this->defaultSettings[$id];
		}
		else
		{
			throw new CException(Yii::t('yii','The setting {setting} does not exist.',
				array('{setting}'=>$id)));
		}
	}

	public function set($name, $value, $scope = null)
	{
		$id = $this->getSettingId($name, $scope);
		if(isset($this->defaultSettings[$id]))
		{
			$this->userSettings[$id] = $value;
		}
		else
		{
			throw new CException(Yii::t('yii','The setting {setting} does not exist.',
				array('{setting}'=>$id)));
		}
	}

	private function loadSettings()
	{
		// Load settings
		$this->defaultSettings = $this->loadSettingsFile($this->configPath . 'default.xml');
		if(is_file($this->configPath . $this->host . '.' . $this->user . '.xml'))
		{
			$this->userSettings = $this->loadSettingsFile($this->configPath . $this->host . '.' . $this->user . '.xml');
		}
	}

	private function loadSettingsFile($filename)
	{
		$defaultXml = new SimpleXMLElement(file_get_contents($filename));
		$settings = array();
		foreach($defaultXml->children() AS $setting)
		{
			$name = $setting->getName();
			$value = (string)$setting;
			$scope = (isset($setting['scope']) ? $setting['scope'] : null);

			$id = $this->getSettingId($name, $scope);

			$settings[$id] = $value;
		}
		return $settings;
	}

	public function saveSettings()
	{
		if(count($this->userSettings) > 0)
		{
			$xml = new SimpleXmlElement('<settings host="' . $this->host . '" user="' . $this->user . '" />');
			foreach($this->userSettings AS $key => $value)
			{
				list($name, $scope) = $this->getSettingNameScope($key);
				$settingXml = $xml->addChild($name, $value);
				if($scope)
				{
					$settingXml['scope'] = $scope;
				}
			}
		}
		elseif(is_file($this->configPath . $this->host . '.' . $this->user . '.xml'))
		{
			unlink($this->configPath . $this->host . '.' . $this->user . '.xml');
		}
		$xml->asXML($this->configPath . $this->host . '.' . $this->user . '.xml');
	}

	private function getSettingId($name, $scope)
	{
		return $name . ($scope ? '|' . $scope : '');
	}

	private function getSettingNameScope($id)
	{
		$return = explode('|', $id);
		if(is_array($return))
		{
			return $return;
		}
		else
		{
			return array($return, null);
		}
	}

}