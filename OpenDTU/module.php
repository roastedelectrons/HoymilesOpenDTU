<?php

declare(strict_types=1);
	class OpenDTU extends IPSModule
	{
		const PREFIX = "OPENDTU";
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

			$this->RegisterPropertyString('BaseTopic', 'solar/');
			$this->RegisterPropertyString('Username', 'admin');
			$this->RegisterPropertyString('Password', 'openDTU42');
			$this->RegisterPropertyBoolean('Reconnect', false);
			$this->RegisterPropertyString('Variables', '[]');

			$this->RegisterAttributeString('IP', '');
			
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

			$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

			//Setze Filter für ReceiveData
			$BaseTopic =rtrim( $this->ReadPropertyString('BaseTopic') , '/');

			//$DeviceTopic = $BaseTopic.'/dtu';

			$filter = '.*(' . preg_quote($BaseTopic) . ').*';
			$this->SetReceiveDataFilter($filter);
			$this->LogMessage('Filter: '.$filter, KL_MESSAGE);

			// Get Variable list
			$variables = $this->GetVariablesConfiguration();

			foreach( $variables as $variable)
			{
				$variableProfile = $variable["VariableProfile"];

				$this->MaintainVariable ($variable["Ident"], $this->translate( $variable["Name"] ), $variable["VariableType"], $variableProfile, $variable["Position"], $variable["Active"] );
			
			}

			if ($this->ReadPropertyBoolean('Reconnect') && $this->GetStatus() == 200)
			{
				$this->ReconnectMQTT();
			}
		}

		public function RequestAction($Ident, $Value) {


			switch($Ident) {
				default:
					throw new Exception("Invalid Ident");
			}
			
		}

		public function ReconnectMQTT()
		{
			$url = '/api/mqtt/config';
			$data = [];
			$method = 'GET';
			$this->LogMessage("Reconnect ...", KL_MESSAGE);
			$result = $this->httpRequest( $url, $data, $method);

			if ($result != false)
			{
				$url = '/api/mqtt/config';
				$data = $result;
				$method = 'POST';	

				$result = $this->httpRequest( $url, $data, $method);

				if ($result !== false)
				{
					$this->LogMessage("Reconnect successful", KL_MESSAGE);
					return true;
				}
			}

			return false;
		}

		public function Reboot()
		{
			$data['reboot'] = true;

			$result = $this->httpRequest( '/api/maintenance/reboot', $data, 'POST');

			if ($result != false)
			{
				return true;
			}

			return false;
		}



		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);

			$this->SendDebug("ReceiveData", $JSONString , 0);

			$variables = json_decode( $this->ReadPropertyString("Variables"), true);

			//UTF-8 Fix for Symcon 6.3
			if (IPS_GetKernelDate() > 1670886000) {
				$data->Payload = utf8_decode($data->Payload);
			}

			$topic = $data->Topic;
			$payload = $data->Payload;


			$baseTopic = $this->GetBaseTopic();

			$deviceTopic = $baseTopic.'dtu/';


			if ( strpos( $topic, $deviceTopic) === 0)
			{
				$subTopic = str_replace( $deviceTopic, '', $topic);
				$ident = str_replace( '/', '_', $subTopic);

				if ( $ident == 'status')
				{
					if ($payload == "offline")
					{
						$payload = false;
						$this->SetStatus(200);
					}
					else
					{
						$payload = true;

						if ($this->GetStatus() != 102)
						{
							$this->SetStatus(102);
						}
					}
				}
				elseif ( @$this->GetIDForIdent('status') && $this->GetValue('status') === false )
				{
					$this->SetValue( 'status', true);
				}

				if ( $ident == 'ip')
				{
					$this->WriteAttributeString('IP', $payload);
				}

				if ( @$this->GetIDForIdent($ident) ) 
				{
					$this->SetValue( $ident, $payload);
				}
			}

			// Change DataID to Microinverter RX
			$data->DataID = "{69CDBA93-CAE6-A38E-A3F1-0D779C1A1047}";
			$data->Topic = substr($data->Topic, strlen($baseTopic));
			$data->Payload = utf8_encode( $data->Payload);
			$JSONString = json_encode($data);
			$this->SendDataToChildren($JSONString);
			$this->SendDebug("SendDataToChildren", $JSONString , 0);

		}

		public function ForwardData($JSONString)
		{
			$data = json_decode($JSONString);

			// Change DataID to MQTT RX
			$data->DataID = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
			$data->Topic = $this->GetBaseTopic().$data->Topic;

			$JSONString = json_encode($data);

			$this->SendDebug("SendDataToParent", $JSONString, 0);

			// Forward to MQTT-Server
			$result = $this->SendDataToParent($JSONString);			
		}

		public function GetConfigurationForm()
		{
			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

			// Set variables configuration
			$variablesIndex = array_search( 'Variables', array_column( $form['elements'], 'name') );
			if ( $variablesIndex !== false)
			{
				$form['elements'][$variablesIndex]['values'] = $this->GetVariablesConfiguration();
			}

			return json_encode($form);
		}

		public function GetVariablesConfiguration()
		{
			// Get variables configuration
			$variablesConfiguration = json_decode( $this->ReadPropertyString("Variables"), true);

			// Get variables list template
			$variableList = $this->GetVariablesList();

			// Generate a new Variable List from template
			foreach ($variableList as $index => $newVariable)
			{
				$variableList[$index]['Name'] = $this->Translate( $newVariable['Name'] ) ;
				
				// If configuration for variable exists, keep Active parameter
				$variablesIndex = array_search( $newVariable['Ident'], array_column( $variablesConfiguration, 'Ident') );
				if ($variablesIndex !== false)
				{
					$variableList[$index]['Active']  = $variablesConfiguration[$variablesIndex]['Active'];
				}
			}
			
			return $variableList;
		}

		private function MQTTSend(string $Topic, string $Payload)
		{
			$Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
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

		private function httpRequest( string $url, array $data = [], string $method = 'GET')
		{
			$ip = $this->ReadAttributeString('IP');

			if (!filter_var($ip, FILTER_VALIDATE_IP) )
			{
				die('Error: Invalid ip address');
			}

			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);    
        	curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
			curl_setopt($ch, CURLOPT_URL, $ip.$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			if ($method == 'POST' && $data != [] )
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".json_encode( $data ) );
			}

			curl_setopt($ch, CURLOPT_USERPWD, $this->ReadPropertyString('Username') . ':' . $this->ReadPropertyString('Password') );
		
			$headers = array();
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				die('Error:' . curl_error($ch));
			}
		
			$statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			curl_close($ch);

			if ($statusCode == 401)
			{
				die('Error: Invalid username or password!');
			}

			if ($statusCode != 200)
			{
				die('Error: Http Response Code ('.$statusCode.')');
			}

			$result = json_decode( $result, true);

			if ($result === false)
			{
				die('Error: Invalid json response');
			}

			if (isset($result['type'])  && $result['type'] != 'success')
			{
				die($result['type'].': '.$result['message']);
			}

			return $result;
		}

		private function GetBaseTopic()
		{
			return rtrim( $this->ReadPropertyString('BaseTopic') , '/').'/';
		}

		private function GetVariablesList()
		{
	
			$file = __DIR__ . "/../libs/variables_opendtu.json";
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