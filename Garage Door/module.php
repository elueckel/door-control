<?php

declare(strict_types=1);

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}


	class GarageDoor extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			if (IPS_VariableProfileExists("GD.DoorStatus") == false) {
				IPS_CreateVariableProfile("GD.DoorStatus", 1);
				IPS_SetVariableProfileIcon("GD.DoorStatus", "Door");
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 100, $this->Translate("Open"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 104, $this->Translate("Closed"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 110, $this->Translate("Ventilation"), "", -1);
			}

			if (IPS_VariableProfileExists("GD.DoorOperation") == false) {
				IPS_CreateVariableProfile("GD.DoorOperation", 1);
				IPS_SetVariableProfileIcon("GD.DoorOperation", "Door");
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 200, $this->Translate("No Movement"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 201, $this->Translate("Closing"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 202, $this->Translate("Opening"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 203, $this->Translate("Ventilation Opening"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 204, $this->Translate("Ventilation Closing"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 205, $this->Translate("Ventilation Reversing"), "", -1);
			}

			if (IPS_VariableProfileExists("GD.DoorSwitchStandard") == false) {
				IPS_CreateVariableProfile("GD.DoorSwitchStandard", 1);
				IPS_SetVariableProfileIcon("GD.DoorSwitchStandard", "Door");
				IPS_SetVariableProfileAssociation("GD.DoorSwitchStandard", 300, $this->Translate("Open"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorSwitchStandard", 304, $this->Translate("Close"), "", -1);
			}

			$this->RegisterPropertyInteger("GarageDoorActorVariable", false);
			$this->RegisterPropertyInteger("GarageDoorActorTiggerTime", "500");
			$this->RegisterPropertyInteger("GarageDoorTravelTimeUp", "20");
			$this->RegisterPropertyInteger("GarageDoorTravelTimeDown", "20");
			$this->RegisterPropertyInteger("GarageDoorSensor", 0);
			$this->RegisterPropertyBoolean("WriteToLog", 0);
			$this->RegisterPropertyBoolean("HomekitSwitchVariable", 0);

			$this->RegisterPropertyInteger("PositionSensorUsed", 0);
			$this->RegisterPropertyInteger("Tiltsensor", false);
			$this->RegisterPropertyInteger("DoorSensorOpen", false);
			$this->RegisterPropertyInteger("DoorSensorClosed", false);

			$this->RegisterPropertyBoolean("VentilationReverseToOriginalState", true);
			$this->RegisterPropertyInteger("VentilationMode", "0");
			$this->RegisterPropertyInteger("VentilationOpenTimer", "1");
			$this->RegisterPropertyInteger("VentilationHumidityThreshold", "55");
			$this->RegisterPropertyInteger("VentilationHumiditySensor",0);
			//$this->RegisterPropertyString('VentilationTimeStart', '{"hour":9, "minute": 0, "second": 0}');
			//$this->RegisterPropertyString('VentilationTimeStop', '{"hour":18, "minute": 0, "second": 0}');
			$this->RegisterPropertyBoolean("VentilationManualVariable", 0);

			//$this->RegisterPropertyBoolean("AutoCloseActive", false);
			$this->RegisterPropertyInteger("AutoCloseTimer", "5");

			$this->RegisterPropertyString('AutoCloseAtNightTime', '{"hour":21, "minute": 0, "second": 0}');
			
			$this->RegisterTimer("Garage Door - Ventilation Timer",0,"GD_Ventilation(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door - Auto Close Timer",0,"GD_DoorAutoClose(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door - Auto Close Night Timer",0,"GD_DoorAutoCloseNight(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door - Movement Indicator",0,"GD_DoorOpenCloseStopMovement(\$_IPS['TARGET']);");

			$i = 10;

			$this->RegisterVariableBoolean('DoorSwitchButton', $this->Translate('Door Switch Button'),"~Switch", $i++);
				$this->EnableAction("DoorSwitchButton");
				$DoorSwitchButtonID = $this->GetIDForIdent('DoorSwitchButton');	
				if (IPS_GetObject($DoorSwitchButtonID)['ObjectType'] == 2) {
						$this->RegisterMessage($DoorSwitchButtonID, VM_UPDATE);
				}
			$this->RegisterVariableInteger('DoorStatus', $this->Translate('Door Status'),"GD.DoorStatus", $i++);
				$this->SetValue('DoorStatus',"104");

			$this->RegisterVariableInteger('DoorCurrentOperation', $this->Translate('Door Current Operation'),"GD.DoorOperation", $i++);
				$this->SetValue('DoorCurrentOperation',"200");				
				
			$this->RegisterVariableBoolean('VentilationManual', $this->Translate('Ventilation'),"~Switch", $i++);
				$this->EnableAction("VentilationManual");
				$VentilationManualID = $this->GetIDForIdent('VentilationManual');
				if (IPS_GetObject($VentilationManualID)['ObjectType'] == 2) {
						$this->RegisterMessage($VentilationManualID, VM_UPDATE);
				}	
			
			$this->RegisterVariableBoolean('AutoCloseFunction', $this->Translate('Auto Close Function'),"~Switch", $i++);
				$this->EnableAction("AutoCloseFunction");
				$AutoCloseFunctionID = $this->GetIDForIdent('AutoCloseFunction');

				if (IPS_GetObject($AutoCloseFunctionID)['ObjectType'] == 2) {
						$this->RegisterMessage($AutoCloseFunctionID, VM_UPDATE);
				}
				
			$this->RegisterVariableBoolean('AutoCloseAtNightTimeActive', $this->Translate('Auto Close At Night'),"~Switch", $i++);
				$this->EnableAction("AutoCloseAtNightTimeActive");
				$AutoCloseAtNightTimeActiveID = $this->GetIDForIdent('AutoCloseAtNightTimeActive');
				if (IPS_GetObject($AutoCloseAtNightTimeActiveID)['ObjectType'] == 2) {
						$this->RegisterMessage($AutoCloseAtNightTimeActiveID, VM_UPDATE);
				}

		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();

			$vpos = 100;

			$this->MaintainVariable('DoorSwitchHomeKit', $this->Translate('Door Switch Homekit'), vtInteger, '~ShutterMoveStop', 1 ,$this->ReadPropertyBoolean('HomekitSwitchVariable') == true);
			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
				$this->EnableAction("DoorSwitchHomeKit");
				$this->SetValue('DoorSwitchHomeKit',"4");
				$DoorSwitchHomeKitID = $this->GetIDForIdent('DoorSwitchHomeKit');
				if (IPS_GetObject($DoorSwitchHomeKitID)['ObjectType'] == 2) {
						$this->RegisterMessage($DoorSwitchHomeKitID, VM_UPDATE);
				}
			}

			$this->MaintainVariable('DoorPositionError', $this->Translate('Door Position Error'), vtBoolean, '~Switch', 200 ,$this->ReadPropertyInteger('PositionSensorUsed') != 0);
			/*
			//$OpenVariableID = @IPS_GetObjectIDByIdent('OpenDoor', $this->InstanceID);
			$OpenVariableID = $this->GetIDForIdent('OpenDoor');
			if (IPS_GetObject($OpenVariableID)['ObjectType'] == 2) {
					$this->RegisterMessage($OpenVariableID, VM_UPDATE);
			}
			*/
			
			$this->AutoCloseAtNightTimer();
				
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {

			$this->SendDebug("Door Trigger","", 0);
			$this->SendDebug("Door Trigger","******************************", 0);
			$this->SendDebug("Door Trigger","Variable the was triggered: ".(IPS_GetObject($SenderID)["ObjectName"]), 0);

			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == true) {
				$DoorSwitchHomeKitID = $this->GetIDForIdent('DoorSwitchHomeKit');
			} else {
				$DoorSwitchHomeKitID = "00001";
			}
			/*
			if (GetValue($this->GetIDForIdent('VentilationManual') == true)) {
				$VentilationManualID = $this->GetIDForIdent('VentilationManual');
			} else {
				$VentilationManualID = "00002";
			}
			*/

			if (GetValue($this->GetIDForIdent('AutoCloseFunction')) == true) {
				$AutoCloseFunctionID = $this->GetIDForIdent('AutoCloseFunction');
			} else {
				$AutoCloseFunctionID = "00003";
			}
			/*
			if (GetValue($this->GetIDForIdent('AutoCloseAtNightTimeActive')) == true) {
				$AutoCloseAtNightTimeActiveID = $this->GetIDForIdent('AutoCloseAtNightTimeActive');
			} else {
				$AutoCloseAtNightTimeActiveID = "00004";
			}
			*/

			$DoorCurrentOperation = GetValue($this->GetIDForIdent('DoorCurrentOperation'));

			if ($SenderID == $this->GetIDForIdent('DoorSwitchButton') AND (GetValueBoolean($this->GetIDForIdent('DoorSwitchButton')) == true) AND $DoorCurrentOperation == "200") {
				
				$this->SendDebug("Door Trigger","Door Switch Button", 0);

				if (GetValueInteger($this->GetIDForIdent('DoorStatus')) != 100 AND (GetValueBoolean($this->GetIDForIdent('DoorSwitchButton')) == true)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button was pressed request to open'),0);
					$this->SetValue('DoorSwitchButton',false); 
					if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit is active and therefore triggered'),0);
						$this->SetValue('DoorSwitchHomeKit',"0");	
					}
					else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button will trigger door'),0);
						$this->SetBuffer('DoorSwitchRequest',"Open");
						$this->DoorController();	
					}
					
				}

				if (GetValueInteger($this->GetIDForIdent('DoorStatus')) == 100  AND (GetValueBoolean($this->GetIDForIdent('DoorSwitchButton')) == true)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button was pressed request to close'),0);
					$this->SetValue('DoorSwitchButton',false);
					if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit is active and therefore triggered'),0);
						$this->SetValue('DoorSwitchHomeKit',"4");	
					}
					else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button will trigger door'),0);
						if ($this->ReadPropertyInteger('GarageDoorSensor') !="") {
							if (GetValue($this->ReadPropertyInteger('GarageDoorSensor')) == false) {
								$this->SetBuffer('DoorSwitchRequest',"Close");
								$this->DoorController();
							} else {
								$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
							}
						} else {
							$this->SetBuffer('DoorSwitchRequest',"Close");
							$this->DoorController();
						}
					}				
				}
			}

			if ($SenderID == $DoorSwitchHomeKitID AND $DoorCurrentOperation == "200") {

				$this->SendDebug("Door Trigger","Homekit", 0);
				
				if (GetValueInteger($this->GetIDForIdent('DoorStatus')) == 104 AND (GetValueInteger(@$this->GetIDForIdent('DoorSwitchHomeKit')) == 0)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit was triggered to OPEN door - this can happen directly or via the button'),0);
					$this->SetBuffer('DoorSwitchRequest',"Open");
					$this->DoorController();		
				}

				if (GetValueInteger($this->GetIDForIdent('DoorStatus')) == 100 AND (GetValueInteger($this->GetIDForIdent('DoorSwitchHomeKit')) == 4)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit was triggered to CLOSE door - this can happen directly or via the button'),0);
					if ($this->ReadPropertyInteger('GarageDoorSensor') !="") {
						if (GetValue($this->ReadPropertyInteger('GarageDoorSensor')) == false) {
							$this->SetBuffer('DoorSwitchRequest',"Close");
							$this->DoorController();
						} else {
							$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
						}
					} else {
						$this->SetBuffer('DoorSwitchRequest',"Close");
						$this->DoorController();
					}		
				}
			}
			
			if ($SenderID == $this->GetIDForIdent('VentilationManual')) {
				$this->SendDebug("Door Trigger","Ventilation", 0);
				
				if (GetValueInteger(@$this->GetIDForIdent('DoorStatus')) == 104 AND (GetValueBoolean($this->GetIDForIdent('VentilationManual')) == true)) {
					$this->SendDebug($this->Translate('Ventilation Manual'),$this->Translate('Manual OPENING for ventilation'),0);
					$this->SetBuffer('DoorVentilationRequest',"Ventilate - Open");
					$this->VentilationDoorOpenClose();		
				}

				if (GetValueInteger($this->GetIDForIdent('DoorStatus')) == 110 AND (GetValueBoolean($this->GetIDForIdent('VentilationManual')) == false)) {
					$this->SendDebug($this->Translate('Ventilation Manual'),$this->Translate('Manual OPENING ending ventilation'),0);
					$this->SetBuffer('DoorVentilationRequest',"Ventilate - Close");
					$this->VentilationDoorOpenClose();	
				}
			}
			//Ab hier weiter - Klasse anlegen / Timer für AutoClose an / aus
			if ($SenderID == $this->GetIDForIdent('AutoCloseAtNightTimeActive')) {
				$this->SendDebug("Door Trigger","", 0);
				$this->SendDebug("Door Trigger","******************************", 0);
				$this->SendDebug("Door Trigger","Variable the was triggered: ".(IPS_GetObject($SenderID)["ObjectName"]), 0);
				$this->AutoCloseAtNightTimer();	
			}
	
		}

		public function AutoCloseAtNightTimer() {

			if (GetValueBoolean($this->GetIDForIdent('AutoCloseAtNightTimeActive')) == true) {

				$AutoCloseTimeJson = json_decode($this->ReadPropertyString('AutoCloseAtNightTime'),true);
				$Hour = $AutoCloseTimeJson["hour"];
				$Minute = $AutoCloseTimeJson["minute"];
				$Second = $AutoCloseTimeJson["second"];
				$now = new DateTime();
				$target = new DateTime();
				$target->setTime($Hour,$Minute,$Second); 
				if ($target < $now) {
					$target->modify('+1 day');
				}
				$target->setTime($Hour,$Minute,$Second);
				$diff = $target->getTimestamp() - $now->getTimestamp();
				$Timer = $diff * 1000;
				$this->SetTimerInterval('Garage Door - Auto Close Night Timer', $Timer);
			} else if (GetValueBoolean($this->GetIDForIdent('AutoCloseAtNightTimeActive')) == false) {
				$this->SendDebug($this->Translate('Auto Close Night Timer'),$this->Translate('In-active'),0);
				$this->SetTimerInterval("Garage Door - Auto Close Night Timer",0);	
			}

		}


		public function DoorController() {

			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Controller is evaluating options'),0);

			$DoorSwitchRequest = $this->GetBuffer('DoorSwitchRequest'); 
			$DoorStatus = GetValue($this->GetIDForIdent('DoorStatus'));
			$DoorStatusID = $this->GetIDForIdent('DoorStatus');

			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Current Status of door is '.$DoorStatus),0);
			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('New Door Position Request '.$DoorSwitchRequest),0);
			

			if ($DoorSwitchRequest == "Open") { //Open the door

				if ($DoorStatus == "104") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was CLOSED and will be OPENED'),0);
					SetValue($DoorStatusID,"100");
					$this->DoorOpenClose();
				}
				elseif ($DoorStatus == "110") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was on ventialation mode and will be OPENED'),0);
					SetValue($DoorStatusID,"100");
					$this->DoorOpenClose();
				}
				elseif ($DoorStatus == "100") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was already OPEN - do nothing'),0);
				}
			}

			if ($DoorSwitchRequest == "Close") { //Close the door

				if ($DoorStatus == "100") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was OPEN and will be CLOSED'),0);
					$this->SetValue('DoorStatus',"104");
					$this->DoorOpenClose();
				}
				elseif ($DoorStatus == "110") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was on ventialation mode and will be CLOSED'),0);
					$this->SetValue('DoorStatus',"104");
					$this->DoorOpenClose();
				}
				elseif ($DoorStatus == "104") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was already CLOSED - do nothing'),0);
				}
			}

		}

		public function DoorOpenClose() {

			$DoorSwitchRequest = $this->GetBuffer('DoorSwitchRequest');
			$GarageDoorActor = $this->ReadPropertyInteger('GarageDoorActorVariable');
			$GarageDoorActorTiggerTime = $this->ReadPropertyInteger('GarageDoorActorTiggerTime');
			//$DoorCurrentOperation = GetValueInteger($this->ReadPropertyInteger('DoorCurrentOperation'));
			$DoorCurrentOperation = GetValueInteger($this->GetIDForIdent('DoorCurrentOperation'));

			$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('The door switch has been triggered and turned on/off'),0);
			
			RequestAction($GarageDoorActor, true);
			//SetValueBoolean($GarageDoorActor, true);
			IPS_Sleep($GarageDoorActorTiggerTime);
			RequestAction($GarageDoorActor, false);
			//SetValueBoolean($GarageDoorActor, false);

			if ($DoorSwitchRequest == "Open") {
				$this->SetValue('DoorCurrentOperation',"202");	
				$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door is set to moving to OPEN position'),0);
				if ($this->ReadPropertyBoolean("WriteToLog") == true) {
					$this->LogMessage($this->Translate('Garage is opening'), KL_MESSAGE);
				}
				$TimerGarageDoorTravelTimeUpMS = $this->ReadPropertyInteger("GarageDoorTravelTimeUp") * 1000;
				$this->SetTimerInterval("Garage Door - Movement Indicator",$TimerGarageDoorTravelTimeUpMS);

				if (GetValue($this->GetIDForIdent('AutoCloseFunction')) == true) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Requesting Auto Close'),0);
					$this->DoorAutoCloseWait();
				}
			} elseif ($DoorSwitchRequest == "Close" AND $DoorCurrentOperation == "200") {
				$this->SetValue('DoorCurrentOperation',"201");
				$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door is set to moving to CLOSE position'),0);
				if ($this->ReadPropertyBoolean("WriteToLog") == true) {
					$this->LogMessage($this->Translate('Garage is closing'), KL_MESSAGE);
				}
				$TimerGarageDoorTravelTimeDownMS = $this->ReadPropertyInteger("GarageDoorTravelTimeDown") * 1000;
				$this->SetTimerInterval("Garage Door - Movement Indicator",$TimerGarageDoorTravelTimeDownMS);
			}
			
		}

		public function VentilationDoorOpenClose() {

			$DoorVentilationRequest = $this->GetBuffer('DoorVentilationRequest');
			$DoorStatus = GetValue($this->GetIDForIdent('DoorStatus'));
			$GarageDoorActor = $this->ReadPropertyInteger('GarageDoorActorVariable');
			$GarageDoorActorTiggerTime = $this->ReadPropertyInteger('GarageDoorActorTiggerTime');
			$VentilationOpenTimer = $this->ReadPropertyInteger('VentilationOpenTimer');
			/*
			Wenn zu dann auf stop reverse
			Wenn auf dann zu auf stop reverse
			Wenn lüften => auf dann auf
			Wenn lüften => zu dann auf warten zu
			*/

			if ($DoorVentilationRequest == "Ventilate - Open") {
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Ventilation ****'),0);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Opening ****'),0);
				$this->SetValue('DoorCurrentOperation',"203");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				IPS_Sleep($VentilationOpenTimer * 1000);
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Reversing ****'),0);
				$this->SetValue('DoorCurrentOperation',"205");
				$this->SetValue('DoorStatus',"100");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				$this->SetValue('DoorCurrentOperation',"200");
				$this->SetValue('DoorStatus',"110");
			} else if ($DoorVentilationRequest == "Ventilate - Close") { 
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Ventilation ****'),0);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Closing ****'),0);
				$this->SetValue('DoorCurrentOperation',"204");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Closed door ****'),0);
				$this->SetValue('DoorCurrentOperation',"200");
				$this->SetValue('DoorStatus',"104");
			}

		}

		public function DoorOpenCloseStopMovement() {

			$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door finished its movement based on the timer setup for moving up or down'),0);
			$this->SetValue('DoorCurrentOperation',"200");	
			$this->SetTimerInterval("Garage Door - Movement Indicator",0);

			if (GetValueBoolean($this->GetIDForIdent('VentilationManual')) == true AND GetValue($this->GetIDForIdent('DoorStatus')) == "104") {
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('Restoring Ventilation'),0);
				$this->SetBuffer('DoorVentilationRequest',"Ventilate - Open");
				$this->VentilationDoorOpenClose();	
			}

			if ($this->ReadPropertyInteger('PositionSensorUsed') != 0) {
				$this->PostionSensors();
			}

		}

		public function DoorAutoCloseWait() {

			$AutoCloseTimer = $this->ReadPropertyInteger('AutoCloseTimer');

			$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Timer for autoclose was started'),0);

			$AutoCloseTimerMS = $this->ReadPropertyInteger("AutoCloseTimer") * 60000;
			$this->SetTimerInterval("Garage Door - Auto Close Timer",$AutoCloseTimerMS);

		}

		public function DoorAutoClose() {

			if ($this->ReadPropertyInteger('GarageDoorSensor') !="") {
				if (GetValue($this->ReadPropertyInteger('GarageDoorSensor')) == false) {
					$DoorSensorStatus = "Not Blocked";
				} else {
					$DoorSensorStatus = "Blocked";
				}
			} else {
				$DoorSensorStatus = "Not Blocked";
			}

			$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close function is closing door'),0);

			$this->SetTimerInterval("Garage Door - Auto Close Timer",0);

			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
				$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close is triggering Homekit to close door'),0);
				if ($DoorSensorStatus == "Not Blocked") {	
						$this->SetValue('DoorSwitchHomeKit',"4");
				} else {
					$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
				}	
			}
			else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
				$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close is tiggering standard function to close door'),0);
				if ($DoorSensorStatus == "Not Blocked") {	
						$this->SetBuffer('DoorSwitchRequest',"Close");	
						$this->DoorController();
				} else {
					$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
				}
			}			
		}

		public function DoorAutoCloseNight() {

			//Check door Sensor if exists and if blocked
			if ($this->ReadPropertyInteger('GarageDoorSensor') !="") {
				if (GetValue($this->ReadPropertyInteger('GarageDoorSensor')) == false) {
					$DoorSensorStatus = "Not Blocked";
				} else {
					$DoorSensorStatus = "Blocked";
				}
			} else {
				$DoorSensorStatus = "Not Blocked";
			}

			$this->SendDebug($this->Translate('Door Auto Close at night'),$this->Translate('Auto Close at night function is closing door'),0);

			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
				$this->SendDebug($this->Translate('Door Auto Close at night'),$this->Translate('Auto Close at night is triggering Homekit to close door'),0);
				if ($DoorSensorStatus == "Not Blocked") {
						if (GetValueBoolean($this->GetIDForIdent('AutoCloseAtNightTimeActive')) == true) {
							$this->AutoCloseAtNightTimer();
						}	
						$this->SetValue('DoorSwitchHomeKit',"4");
				} else {
					$this->SendDebug($this->Translate('Door Auto Close at night'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
				}
			} else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
				$this->SendDebug($this->Translate('Door Auto Close at night'),$this->Translate('Auto Close at night is tiggering standard function to close door'),0);
				
				if ($DoorSensorStatus == "Not Blocked") {
						if (GetValueBoolean($this->GetIDForIdent('AutoCloseAtNightTimeActive')) == true) {
							$this->AutoCloseAtNightTimer();
						}	
						$this->SetBuffer('DoorSwitchRequest',"Close");	
						$this->DoorController();
				} else {
					$this->SendDebug($this->Translate('Door Auto Close at night'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
				}
			}
				
		}

		public function PostionSensors() {
			
			$DoorStatus = GetValueInteger($this->GetIDForIdent('DoorStatus'));
			$PositionSensorUsed = $this->ReadPropertyInteger('PositionSensorUsed');
			$Tiltsensor = $this->ReadPropertyInteger('Tiltsensor');
			$DoorSensorOpen = $this->ReadPropertyInteger('DoorSensorOpen');
			$DoorSensorClosed = $this->ReadPropertyInteger('DoorSensorClosed');

			if ($PositionSensorUsed == 1) {
				IPS_Sleep(500);
				if ($DoorStatus == 100) { //open
					if ( GetValue($Tiltsensor) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate(' ALL OK - Tiltsensor reports OPEN and position should be OPEN.'),0);
						$this->SetValue('DoorPositionError',false);
					} else if ( GetValue($Tiltsensor) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR - Tiltsensor reports CLOSED and position should be OPEN.'),0);
						$this->SetValue('DoorPositionError',true);
					} 
				} else if ($DoorStatus == 110) { //ventilation - sensor reports open
					if ( GetValue($Tiltsensor) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate(' ALL OK - Tiltsensor reports OPEN and position should be VENTILATION.'),0);
						$this->SetValue('DoorPositionError',false);
					} else if ( GetValue($Tiltsensor) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR - Tiltsensor reports CLOSED and position should be VENTILATION.'),0);
						$this->SetValue('DoorPositionError',true);
					} 
				} else if ($DoorStatus == 104) {
					if ( GetValue($Tiltsensor) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate(' ALL OK - Tiltsensor reports CLOSED and position should be CLOSED.'),0);
						$this->SetValue('DoorPositionError',false);
					} else if ( GetValue($Tiltsensor) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR - Tiltsensor reports OPEN and position should be CLOSED.'),0);
						$this->SetValue('DoorPositionError',true);
					} 
				}
			} else if ($PositionSensorUsed == 2) {
				ips_sleep(500);
				if ($DoorStatus == 100) { //open
					if ( GetValue($DoorSensorOpen) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ALL OK - Sensor Open is true (OPEN) and position should be OPEN.'),0);
						$this->SetValue('DoorPositionError',false);
					} else if ( GetValue($DoorSensorOpen) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR -  Sensor Open is false (door is NOT OPEN) and position should be OPEN.'),0);
						$this->SetValue('DoorPositionError',true);
					} 
				} else if ($DoorStatus == 110) { //ventilation - sensor reports open
					$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('VENTILATION is currently not supported'),0);
					/*
					if ( GetValue($DoorSensorOpen) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ALL OK - Sensor Open is false and position should be VENTILATION.'),0);
					} else if ( GetValue($DoorSensorOpen) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR - Tiltsensor reports CLOSED and position should be VENTILATION.'),0);
					} */
				} else if ($DoorStatus == 104) {
					if ( GetValue($DoorSensorClosed) == true) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ALL OK - Sensor Closed is true (CLOSED) and position should be CLOSED.'),0);
						$this->SetValue('DoorPositionError',false);
					} else if ( GetValue($DoorSensorClosed) == false) {
						$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('ERROR - Sensor Closed is false (door is NOT CLOSED) and position should be CLOSED.'),0);
						$this->SetValue('DoorPositionError',true);
					} 
				}
			} else if ($PositionSensorUsed == 0) {
				$this->SendDebug($this->Translate('Position Door Sensor'),$this->Translate('Not configured or activated.'),0);
			}
		
		}

		public function NotifyApp() {
			$NotifierTitle = "Garage";
			$NotifierMessage = $this->GetBuffer("NotifierMessage");
			if ($NotifierMessage == "") {
				$NotifierMessage = "Test Message";
			}
			$WebFrontMobile = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}')[0];
			$this->SendDebug("Notifier","********** App Notifier **********", 0);
			$this->SendDebug("Notifier","Message: ".$NotifierMessage." was sent", 0);			
			WFC_PushNotification($WebFrontMobile, $NotifierTitle, $NotifierMessage , "", 0);
		}

		public function EmailApp() {
			$EmailVariable = $this->ReadPropertyInteger("EmailVariable"); 
			if ($EmailVariable != "") {	
				$NotifierMessage = $this->GetBuffer("NotifierMessage");
				$EmailTitle = "Garage";
				if ($NotifierMessage == "") {
					$NotifierMessage = "Test Message";
				}
				$this->SendDebug("Email","********** Email **********", 0);
				$this->SendDebug("Email","Message: ".$NotifierMessage." was sent", 0);			
				SMTP_SendMail($EmailVariable, $EmailTitle, $NotifierMessage);
			}
			else {
				echo $this->Translate('Email Instance is not configured');
			}
		}

		public function RequestAction($Ident, $Value) {
		
			$this->SetValue($Ident, $Value);
		
		}

	}