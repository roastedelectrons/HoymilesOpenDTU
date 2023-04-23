<?php

declare(strict_types=1);
	class HoymilesMicroinverter extends IPSModule
	{
		const PREFIX = "HOYMILES";
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//$this->ConnectParent('{40505457-AB2C-057B-C9D7-657EBB53A528}');

			$this->RegisterPropertyString('BaseTopic', 'solar/');
			$this->RegisterPropertyString('Serial', '');
			$this->RegisterPropertyString('Model', 'UNKNOWN');

			$variables = $this->GetVariableList();

			foreach ($variables as $index => $variable)
			{
				$variables[$index]['Name'] = $this->Translate( $variable['Name'] ) ;
			}

			$this->RegisterPropertyString("Variables", json_encode ( $variables) );

			$this->RegisterProfile(2, static::PREFIX.".Wh", "Electricity", "", " Wh", 0, 0, 0, 1);
			$this->RegisterProfile(2, static::PREFIX.".VAr", "Electricity", "", " VAr", 0, 0, 0, 1);
			
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			// For backward compatibility: change parent from MQTT-Server to OpenDTU
			$this->MigrateSplitter();

			$this->ConnectParent('{40505457-AB2C-057B-C9D7-657EBB53A528}');

			//Setze Filter für ReceiveData
			$Serial = $this->ReadPropertyString('Serial');

			$StatusTopic = 'dtu/status';
			$InverterTopic = $Serial;

			$filter = '.*(' . preg_quote($StatusTopic) .'|'. preg_quote($InverterTopic) . ').*';
			$this->SetReceiveDataFilter($filter);
			$this->LogMessage('Filter: '.$filter, KL_MESSAGE);

			// Get Variable list
			$variables = json_decode( $this->ReadPropertyString("Variables"), true);


			// Check for new Variables in case of a module update
			// Get variable list template
			$variableList = $this->GetVariableList();

			if ( count( $variables) != count($variableList) )
			{
				$variables = $this->UpdateVariableList();
			}


			foreach( $variables as $variable)
			{
				$variableProfile = $variable["VariableProfile"];

				$this->MaintainVariable ($variable["Ident"], $this->translate( $variable["Name"] ), $variable["VariableType"], $variableProfile, $variable["Position"], $variable["Active"] );
			
				if ( $variable["Ident"] == "status_limit_relative" || $variable["Ident"] == "status_limit_absolute" || $variable["Ident"] == 'status_producing')
				{
					@$this->EnableAction($variable["Ident"]);
				}
			}
		}

		public function RequestAction($Ident, $Value) {


			switch($Ident) {
				case "status_limit_relative":
					$this->SetLimitRelative( intval( $Value ) );
					$this->SetValue($Ident, $Value);
					break;
				case "status_limit_absolute":
					$this->SetLimitAbsolute( intval( $Value )  );
					$this->SetValue($Ident, $Value);
					break;
				case "status_producing":
					$this->SwitchInverter( boolval( $Value )  );
					$this->SetValue($Ident, $Value);
					break;
				default:
					throw new Exception("Invalid Ident");
			}
			
		}

		public function SetLimitRelative( int $limit )
		{
			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/limit_nonpersistent_relative';

			$this->MQTTSend( $topic, strval($limit) );
		}

		public function SetLimitAbsolute( int $limit )
		{
			// Check maximum power limit
			if ( $maxPower = $this->GetMaxPower() )
			{
				if ($limit > $maxPower)
				{
					throw new Exception("limit exceeds inverter's maxmimum power of ".$maxPower."W");
				}
			}

			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/limit_nonpersistent_absolute';

			$this->MQTTSend( $topic, strval($limit) );
		}

		public function SetLimitPersistentRelative( int $limit )
		{
			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/limit_persistent_relative';

			$this->MQTTSend( $topic, strval($limit) );
		}

		public function SetLimitPersistentAbsolute( int $limit )
		{
			// Check maximum power limit
			if ( $maxPower = $this->GetMaxPower() )
			{
				if ($limit > $maxPower)
				{
					throw new Exception("limit exceeds inverter's maxmimum power of ".$maxPower."W");
				}
			}

			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/limit_persistent_absolute';

			$this->MQTTSend( $topic, strval($limit) );
		}

		public function RestartInverter()
		{
			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/restart';

			$this->MQTTSend( $topic, '1' );
		}

		public function SwitchInverter( bool $status )
		{
			$serial = $this->ReadPropertyString('Serial');

			$topic = $serial.'/cmd/power';
			$status = intval($status);

			$this->MQTTSend( $topic, strval($status) );
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);

			$this->SendDebug("ReceiveData", $JSONString , 0);

			$variables = json_decode( $this->ReadPropertyString("Variables"), true);

			$Serial = $this->ReadPropertyString('Serial');

			$payload= utf8_decode($data->Payload);
			$topic = $data->Topic;
			$inverterTopic = $Serial.'/';


			if ( @$this->GetIDForIdent('dtu_status') ) 
			{

				if ( strpos( $topic, 'dtu/status' ) === 0 && $payload == "offline")
				{
					$this->SetValue( 'dtu_status', false);

					return;
				}	

				$this->SetValue( 'dtu_status', true);
			}


			if ( strpos( $topic, $inverterTopic) === 0)
			{
				$subTopic = str_replace( $inverterTopic, '', $topic);
				$ident = str_replace( '/', '_', $subTopic);

				if ( @$this->GetIDForIdent($ident) ) 
				{
					$this->SetValue( $ident, $payload);
				}
			}

		}


		public function UpdateVariableList()
		{
			// Get current variable list
			$variables = json_decode( $this->ReadPropertyString("Variables"), true);

			// Get variable list  template
			$variableList = $this->GetVariableList();

			// Generate a new Variable List from template
			foreach ($variableList as $index => $newVariable)
			{
				$variableList[$index]['Name'] = $this->Translate( $newVariable['Name'] ) ;
				
				// If variable already existed, keep Active parameter
				$variablesIndex = array_search( $newVariable['Ident'], array_column( $variables, 'Ident') );
				if ($variablesIndex !== false)
				{
					$variableList[$index]['Active']  = $variables[$variablesIndex]['Active'];
				}
			}
			
			IPS_SetProperty( $this->InstanceID, "Variables", json_encode ( $variableList ) );
			IPS_ApplyChanges( $this->InstanceID );	

			return $variableList;
		}

		public function ResetVariableList( )
		{
			$variables = $this->GetVariableList();

			foreach ($variables as $index => $value)
			{
				$variables[$index]['Name'] = $this->Translate( $variables[$index]['Name'] ) ;
			}
	

			IPS_SetProperty( $this->InstanceID, "Variables", json_encode ( $variables ) );
			IPS_ApplyChanges( $this->InstanceID );
		}

		private function MQTTSend(string $Topic, string $Payload)
		{
			$Server['DataID'] = "{6F642E77-958C-6C58-2101-F142FD7836DA}"; // '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}' <- MQTT receive GUID
			$Server['PacketType'] = 3;
			$Server['QualityOfService'] = 0;
			$Server['Retain'] = false;
			$Server['Topic'] = $Topic;
			$Server['Payload'] = $Payload;
			$ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
			$ServerJSON = json_encode($Server);
			$this->SendDebug(__FUNCTION__ . 'MQTT Server', $ServerJSON, 0);
			$resultServer = @$this->SendDataToParent($ServerJSON);
		}

		private function GetMaxPower()
		{
			$power = false;
			$model = $this->ReadPropertyString("Model");

			if ( strpos( $model, 'HM-') === 0 )
			{
				$power = intval( substr( $model, 3) );
			}

			return $power;
		}

		private function GetVariableList()
		{
	
			$file = __DIR__ . "/../libs/variables.json";
			if (is_file($file))
			{
				$data = json_decode(file_get_contents($file), true);
			}
			else
			{
				$data = array();
			}
	
			return $data;
		}

		private function MigrateSplitter()
		{
			$parent = IPS_GetInstance($this->InstanceID)['ConnectionID'];

			if ( $parent != 0 && IPS_GetInstance($parent)['ModuleInfo']['ModuleID'] != '{40505457-AB2C-057B-C9D7-657EBB53A528}')
			{
				$BaseTopic = $this->ReadPropertyString('BaseTopic');

				IPS_DisconnectInstance($this->InstanceID);
				$newParent = 0;
				$dtus = IPS_GetInstanceListByModuleID('{40505457-AB2C-057B-C9D7-657EBB53A528}');
				foreach( $dtus as $dtu)
				{
					if ( IPS_GetProperty( $dtu, 'BaseTopic') == $BaseTopic)
					{
						$newParent = $dtu;
					}
				}
				if ($newParent !== 0)
				{
					IPS_ConnectInstance($this->InstanceID, $newParent);
				}
				else
				{	
					// Create OpenDTU Splitter
					$dtu = IPS_CreateInstance ('{40505457-AB2C-057B-C9D7-657EBB53A528}');
					IPS_SetName($dtu, 'OpenDTU');
					//Connect OpenDTU to MQTT-Server
					IPS_DisconnectInstance($dtu);
					IPS_ConnectInstance($dtu, $parent);
					// Set BaseTopic
					IPS_SetProperty($dtu, 'BaseTopic', $BaseTopic);
					IPS_ApplyChanges($dtu);
					// Connect this instance to OpenDTU
					IPS_ConnectInstance($this->InstanceID, $dtu);
				}
			}
		}

		protected function RegisterProfile($VarTyp, $Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits = 0)
		{
			if (!IPS_VariableProfileExists($Name)) {
				IPS_CreateVariableProfile($Name, $VarTyp);
			} else {
				$profile = IPS_GetVariableProfile($Name);
				if ($profile['ProfileType'] != $VarTyp) {
					throw new \Exception('Variable profile type does not match for profile ' . $Name, E_USER_WARNING);
				}
			}
	
			IPS_SetVariableProfileIcon($Name, $Icon);
			IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
			switch ($VarTyp) {
				case VARIABLETYPE_FLOAT:
					IPS_SetVariableProfileDigits($Name, $Digits);
					// no break
				case VARIABLETYPE_INTEGER:
					IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
					break;
			}
		}
	}