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

			//UTF-8 Fix for Symcon 6.3
            if (IPS_GetKernelDate() > 1670886000) {
                $data->Payload = utf8_decode($data->Payload);
            }

			$topic = $data->Topic;
			$payload = $data->Payload;

			// Check for unknown DTU's
			if ( strstr($topic, 'dtu/hostname' ) !== false )
			{
				$baseTopic = str_replace( 'dtu/hostname', '', $topic);

				if (array_search( $baseTopic, $topics) === false)
				{
					$topics[] = $baseTopic;
					$dtu = array( "topic" => $baseTopic, "name" => $payload, "model" => "OpenDTU", 'ip'=> '', 'serial' => '', "inverters" => array() );
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
								$inverter = array( 'serial' => $serial, 'model' => $this->getModel( $payload ), 'name' => 'Microinverter '.$this->getModel( $payload ) , 'topic' => '', 'ip' => '', 'parent' => $device['topic']);
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
            $dtuInstances = [];
			$inverterInstances = [];
			// OpenDTU instances
            foreach (IPS_GetInstanceListByModuleID('{40505457-AB2C-057B-C9D7-657EBB53A528}') as $instanceID) 
			{
                if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) 
				{
					$dtuInstance = array();
					$dtuInstance['instanceID'] = $instanceID;
					$dtuInstance['topic'] = IPS_GetProperty($instanceID, 'BaseTopic');
					$dtuInstance['serial'] = '';
					$dtuInstance['model'] = 'OpenDTU';
					$dtuInstance['name'] = IPS_GetName($instanceID);
					$dtuInstance['ip'] = '';
					$dtuInstance['id'] = IPS_GetProperty($instanceID, 'BaseTopic');
					$dtuInstance['expanded'] = true;

					$dtuInstances[] = $dtuInstance;

					// Microinverter intances connectet to this dtu
					foreach (IPS_GetInstanceListByModuleID('{3CEA9993-1F13-9C04-E421-5A3DB44431C3}') as $inverterID) 
					{
						if (IPS_GetInstance($inverterID)['ConnectionID'] === $instanceID ) 
						{
							$inverter = array();
							$inverter['instanceID'] = $inverterID;
							$inverter['topic'] = '';
							$inverter['serial'] = IPS_GetProperty($inverterID, 'Serial');
							$inverter['model'] = IPS_GetProperty($inverterID, 'Model');
							$inverter['name'] = IPS_GetName($inverterID);
							$inverter['ip'] = "";
							$inverter['parent'] = IPS_GetProperty($instanceID, 'BaseTopic');
							$inverter['expanded'] = true;

							$inverterInstances[] = $inverter;
						}
					}
                }
            }

			// Get devices found from MQTT-Server
			$devices = json_decode( $this->GetBuffer("Devices"), true);
			$this->SendDebug("Devices Buffer", json_encode( $devices) , 0);
			
			$tree = array();
			$index = 0;


			// Add found devices to configuration tree
			$dtus = array();
			$inverters = array();
			foreach ($devices as $index => $device)
			{
				// OpenDTUs
				$dtuConfig = array();
				$dtuConfig['BaseTopic'] = $device['topic'];
				$dtu= $device;
				unset($dtu['invertes']);
				$dtu['id'] = $device['topic'];
				$dtu['instanceID'] = 0;
				$dtu['expanded'] = true;
				$dtu['create'] = array( 'moduleID' => '{40505457-AB2C-057B-C9D7-657EBB53A528}', 'configuration' => $dtuConfig);  
				$dtus[] = $dtu;

				// Inverters
				foreach( $device['inverters'] as $inverter)
				{
					$config = array();
					//$config['BaseTopic'] = $inverter['topic'];
					$config['Serial'] = $inverter['serial'];
					$config['Model'] = $inverter['model'];
					$inverter['instanceID'] = 0;
					//$inverter['parent'] = $inverter['topic'];
					$inverter['create'] = [
						array( 'moduleID' => '{3CEA9993-1F13-9C04-E421-5A3DB44431C3}', 'configuration' => $config),
						array( 'moduleID' => '{40505457-AB2C-057B-C9D7-657EBB53A528}', 'configuration' => $dtuConfig)
					];                   

					$inverters[] = $inverter;
				}
			}

			// Add existing instances to configuration tree
			foreach( $dtuInstances as $instance)
			{
				$match = false;

				foreach ( $dtus as $index=> $dtu)
				{
					// If device from MQTT server matches existing instance, replace it with the existing instance
					// OpenDTU's
					
					if ($instance['topic'] == $dtu['topic'])
					{
						$instance['ip'] = $dtu['ip'];
						$instance['create'] = $dtu['create'];

						if ( $dtu['instanceID'] == 0)
						{
							$dtus[$index] = $instance;
						}
						else
						{
							$dtus[] = $instance;
						}
						$match = true;
						break;
					}
				}

				if ( !$match)
				{
					$dtus[] = $instance;
				}
			}

			foreach( $inverterInstances as $instance)
			{
				$match = false;

				foreach ( $inverters as $index=> $inverter)
				{
					// If device from MQTT server matches existing instance, replace it with the existing instance
					// Inverter's
					
					if ($instance['serial'] == $inverter['serial'])
					{
						$instance['create'] = $inverter['create'];

						if ( $inverter['instanceID'] == 0)
						{
							$inverters[$index] = $instance;
						}
						else
						{
							$inverters[] = $instance;
						}
						$match = true;
						break;
					}
				}

				if ( !$match)
				{
					$inverters[] = $instance;
				}
			}

			$tree = array_merge($dtus, $inverters);
			
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

