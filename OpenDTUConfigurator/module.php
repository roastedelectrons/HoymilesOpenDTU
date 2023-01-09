<?php

declare(strict_types=1);
	class OpenDTUConfigurator extends IPSModule
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

			$this->ResetReceiveDataFilter();
		}

		public function ReceiveData($JSONString)
		{

			$devices = json_decode( $this->GetBuffer("Devices"), true);

			$topics = array_column( $devices, 'topic');

			$data = json_decode($JSONString);

			$this->SendDebug('ReceiveData', $JSONString, 0);
	
			$topic = $data->Topic;
			$payload = $data->Payload;

			// Check for unknown DTU's
			if ( strstr($topic, 'dtu/hostname' ) !== false )
			{
				$baseTopic = str_replace( 'dtu/hostname', '', $topic);

				if (array_search( $baseTopic, $topics) === false)
				{
					$topics[] = $baseTopic;
					$dtu = array( "topic" => $baseTopic, "name" => $payload, "model" => "OpenDTU", 'serial' => '', "inverters" => array() );
					$devices[] = $dtu;

					$this->LogMessage('New OpenDTU found: '.json_encode( $dtu ), KL_MESSAGE );

					$this->SetBuffer("Devices", json_encode( $devices) );
					$this->SendDebug("Devices Buffer", json_encode( $devices) , 0);

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
						$devices[$index]['ip'] = $payload;
					}

					// Data from Inverter
					if ( strstr($subTopic , 'dtu') === false )
					{
						$topicParts = explode( '/', $subTopic);
						$serial = $topicParts[0];

						// Check for unknown inverters
						$inverterIndex = array_search( $serial, array_column( $device['inverters'], 'serial')  );

						if ( $inverterIndex === false)
						{
							// Only add new inverters if hwversion is sent
							if ($topicParts[1] == 'device' && $topicParts[2] == 'hwpartnumber')
							{
								$inverter = array( 'serial' => $serial, 'model' => $this->getModel( $payload ), 'name' => 'Microinverter '.$this->getModel( $payload ) , 'topic' => $device['topic'], 'ip' => '');
								$devices[$index]['inverters'][] = $inverter;
								$this->LogMessage('New inverter found: '.json_encode( $inverter ), KL_MESSAGE );

								$this->AddReceiveDataFilter( $device['topic'].$serial.'/name' );
							}
						} 
						else
						{
							if ( $topicParts[1] == 'name')
							{
								$devices[$index]['inverters'][$inverterIndex]['name'] = $payload;
							}
						}


					}

					$this->SetBuffer("Devices", json_encode( $devices) );
					$this->SendDebug("Devices Buffer", json_encode( $devices) , 0);

					return;
				}
			}


		}

		public function GetConfigurationForm()
		{
			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
	
			// Get existing instances connected to the same MQTT-Server-Instance
            $connectedInstances = [];
            foreach (IPS_GetInstanceListByModuleID('{3CEA9993-1F13-9C04-E421-5A3DB44431C3}') as $instanceID) 
			{
                if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) 
				{
                    // Add the instance ID to a list for the given address. Even though addresses should be unique, users could break things by manually editing the settings
					$connectedInstance['topic'] = IPS_GetProperty($instanceID, 'BaseTopic');
					$connectedInstance['serial'] = IPS_GetProperty($instanceID, 'Serial');
					$connectedInstance['model'] = IPS_GetProperty($instanceID, 'Model');
					$connectedInstance['name'] = IPS_GetName($instanceID);
					$connectedInstance['ip'] = "";
					$connectedInstance['parent'] = $connectedInstance['topic'];
					if ($connectedInstance['parent'] == "") $connectedInstance['parent'] = "<empty>";
					$connectedInstance['instanceID'] = $instanceID;
					
					$connectedInstances[] = $connectedInstance;
                }
            }

			// Get devices found from MQTT-Server
			$devices = json_decode( $this->GetBuffer("Devices"), true);
			$this->SendDebug("Devices Buffer", json_encode( $devices) , 0);
			
			$tree = array();
			$index = 0;


			// Add found devices to configuration tree
			foreach ($devices as $index => $device)
			{
				// OpenDTUs
				$device['id'] = $device['topic'];
				$device['expanded'] = true;
				$tree[] = $device;

				// Inverters
				foreach( $device['inverters'] as $inverter)
				{
					$config['BaseTopic'] = $inverter['topic'];
					$config['Serial'] = $inverter['serial'];
					$config['Model'] = $inverter['model'];
					$inverter['instanceID'] = 0;
					$inverter['parent'] = $inverter['topic'];
					$inverter['create'] = array( 'moduleID' => '{3CEA9993-1F13-9C04-E421-5A3DB44431C3}', 'configuration' => $config);                   

					$tree[] = $inverter;
				}
			}


			// Add existing instances to configuration tree
			foreach( $connectedInstances as $instance)
			{
				$match = false;

				foreach ( $tree as $treeIndex => $entry)
				{
					// If device from MQTT server matches existing instance, replace it with the existing instance
					if ($instance['topic'] == $entry['topic'] && $instance['serial'] == $entry['serial'] && $entry['model'] != "OpenDTU" )
					{
						$id = $entry['instanceID'];

						$entry['name'] = $instance['name'];
						$entry['model'] = $instance['model'];
						$entry['instanceID'] = $instance['instanceID'];

						if ( $id == 0 )
						{
							$tree[$treeIndex] = $entry;
						}
						else
						{
							// If more than one instance with the same topic/serial exist, add a new entry to tree
							$tree[] = $entry;
						}
						$match = true;
						break;
					}

				}
				
				if ( !$match)
				{
					if ( array_search ($instance['topic'], array_column( $tree, "topic") ) === false )
					{
						// Add new DTU
						$newDevice['name'] = "OpenDTU ".$this->Translate("not available");
						$newDevice['topic'] = $instance['topic'];
						$newDevice['model'] = "OpenDTU";
						$newDevice['ip'] = $this->Translate("not available");
						$newDevice['serial'] = "";
						$newDevice['id'] = $instance['topic'];
						if ($newDevice['id'] == "") $newDevice['id'] = "<empty>";
						$newDevice['expanded'] = true;
						$tree[] = $newDevice;								
					}
	
					$instance['ip'] = $this->Translate("not available");
					$tree[] = $instance;
				}

			}


			$form['actions'][0]['values'] = $tree;

			return json_encode($form);
		}

		public function Reset()
		{
			$this->SetBuffer("Devices", "[]");
			$this->ResetReceiveDataFilter();
			$this->ReloadForm();
		}

		private function ResetReceiveDataFilter()
		{
			$this->SetBuffer("Topics", "[]");
			$this->AddReceiveDataFilter('/dtu/hostname');
			$this->AddReceiveDataFilter('/dtu/ip');
			$this->AddReceiveDataFilter('/device/hwpartnumber');			
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

