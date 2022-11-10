<?php

declare(strict_types=1);
	class HoymilesOpenDTUConfigurator extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
			$this->SetBuffer("Devices", "[]");
			$this->SetBuffer("Topics", "[]");
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

			$this->AddReceiveDataFilter('/dtu/hostname');
			$this->AddReceiveDataFilter('/dtu/ip');
			$this->AddReceiveDataFilter('/device/hwpartnumber');
		}

		public function ReceiveData($JSONString)
		{

			$devices = json_decode( $this->GetBuffer("Devices"), true);
			$topics = array_column( $devices, 'topic');

			$data = json_decode($JSONString);

			$this->SendDebug('ReceiveData', $JSONString, 0);

			$topic = utf8_decode($data->Topic);
			$value = utf8_decode($data->Payload);

			// Check for unknown DTU's
			if ( strstr($topic, 'dtu/hostname' ) !== false )
			{
				$baseTopic = str_replace( 'dtu/hostname', '', $topic);

				if (array_search( $baseTopic, $topics) === false)
				{
					$topics[] = $baseTopic;
					$dtu = array( "topic" => $baseTopic, "name" => $value, "model" => "OpenDTU", 'serial' => '', "inverters" => array() );
					$devices[] = $dtu;

					$this->LogMessage('New OpenDTU found: '.json_encode( $dtu ), KL_MESSAGE );

					$this->SetBuffer("Devices", json_encode( $devices) );

				}

				return;

			}

			// Collect data of known DTU's

			foreach ( $devices as $index => $device)
			{
				if ( strstr($topic, $device['topic']) !== false )
				{
					$subTopic = substr( $topic, strlen($device['topic']) );

					// Data from DTU
					if ( strcmp( $subTopic, 'dtu/ip') === 0 )
					{
						$devices[$index]['ip'] = $data->Payload;
					}

					// Data from Inverter
					if ( strstr($subTopic , 'dtu') === false )
					{
						$topicParts = explode( '/', $subTopic);
						$serial = $topicParts[0];

						// Check for unknown inverters
						if ( $inverterIndex = array_search( $serial, array_column( $device['inverters'], 'serial')  ) === false )
						{
							// Only add new inverters if hwversion is sent
							if ($topicParts[1] == 'device' && $topicParts[2] == 'hwpartnumber')
							{
								$inverter = array( 'serial' => $serial, 'model' => $this->getModel( $value ), 'name' => 'Microinverter '.$this->getModel( $value ) , 'topic' => $device['topic'], 'ip' => '');
								$devices[$index]['inverters'][] = $inverter;
								$this->LogMessage('New inverter found: '.json_encode( $inverter ), KL_MESSAGE );

								$this->AddReceiveDataFilter( $device['topic'].$serial.'/name' );
							}
						} 
						else
						{
							if ( $topicParts[1] == 'name')
							{
								$devices[$index]['inverters'][$inverterIndex]['name'] = $value;
							}
						}


					}

					$this->SetBuffer("Devices", json_encode( $devices) );
					$this->SendDebug("Devices Buffer", json_encode( $devices) , 0);

					return;
				}
			}


		}

		private function AddReceiveDataFilter( $topic )
		{
			$topics = json_decode( $this->GetBuffer('Topics'), true);
			$topics[] = $topic;
			$filter = '.*(' . implode('|', $topics ). ').*' ;
			$this->SetReceiveDataFilter($filter);
			$this->SetBuffer('Topics', json_encode($topics));

			$this->SendDebug('AddReceiveDataFilter', $filter, 0);
		}

		public function GetConfigurationForm()
		{
			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
	
			if (floatval(IPS_GetKernelVersion()) < 5.3) {
				return json_encode($form);
			}
		
			$instanceList = IPS_GetInstanceListByModuleID("{3CEA9993-1F13-9C04-E421-5A3DB44431C3}");
			$instancesBySerial = array();

			foreach ($instanceList as $instanceID)
			{
				$serial = IPS_GetProperty($instanceID, 'Serial');
				if ($serial != "") $instancesBySerial[$serial] = $instanceID;
			}


			$devices = json_decode( $this->GetBuffer("Devices"), true);

			$tree = array();
			foreach ($devices as $index => $device)
			{
				// OpenDTUs
				$device['id'] = $index +1;
				$device['expanded'] = true;
				$tree[] = $device;

				// Inverters
				foreach( $device['inverters'] as $inverter)
				{
					$config['BaseTopic'] = $inverter['topic'];
					$config['Serial'] = $inverter['serial'];
					$config['Model'] = $inverter['model'];

					if ( array_key_exists( $inverter['serial'], $instancesBySerial))
					{
						$inverter['instanceID'] = $instancesBySerial[ $inverter['serial'] ];
					}
					$inverter['parent'] = $index +1;
					$inverter['create'] = array( 'moduleID' => '{3CEA9993-1F13-9C04-E421-5A3DB44431C3}', 'configuration' => $config);                   

					$tree[] = $inverter;
				}
			}

			$form['actions'][0]['values'] = $tree;

			return json_encode($form);
		}

		private function getModel( $hwpartnumber )
		{
			$hwpartnumber = intval($hwpartnumber) >> 8;

			switch ($hwpartnumber)
			{
				case 0x101010:
					return "HM-300";
				case 0x101020:
					return "HM-350";
				case 0x101040:
					return "HM-400";
				case 0x101110:
					return "HM-600";
				case 0x101120:
					return "HM-700";
				case 0x101140:
					return "HM-800";
				case 0x101210:
					return "HM-1200";
				case 0x101230:
					return "HM-1500";
				default:
					return "UNKNOWN";
			}
		}
	}

