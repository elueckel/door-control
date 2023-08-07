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
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 204, $this->Translate("Ventilation Closing Door"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorOperation", 205, $this->Translate("Ventilation Reversing"), "", -1);
			}

			if (IPS_VariableProfileExists("GD.DoorSwitchStandard") == false) {
				IPS_CreateVariableProfile("GD.DoorSwitchStandard", 1);
				IPS_SetVariableProfileIcon("GD.DoorSwitchStandard", "Door");
				IPS_SetVariableProfileAssociation("GD.DoorSwitchStandard", 300, $this->Translate("Open"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorSwitchStandard", 304, $this->Translate("Close"), "", -1);
			}

			//$this->RegisterPropertyString("IP","");
			$this->RegisterPropertyBoolean("Active", 0);
			$this->RegisterPropertyInteger("GarageDoorActorVariable", false);
			$this->RegisterPropertyInteger("GarageDoorActorTiggerTime", "500");
			$this->RegisterPropertyInteger("GarageDoorTravelTimeUp", "30");
			$this->RegisterPropertyInteger("GarageDoorTravelTimeDown", "30");
			$this->RegisterPropertyInteger("GarageDoorSensor", 0);
			$this->RegisterPropertyBoolean("WriteToLog", 0);
			$this->RegisterPropertyBoolean("HomekitSwitchVariable", 0);

			$this->RegisterPropertyBoolean("VentilationReverseToOriginalState", true);
			$this->RegisterPropertyInteger("VentilationMode", "0");
			$this->RegisterPropertyInteger("VentilationOpenTimer", "3");
			$this->RegisterPropertyInteger("VentilationHumidityThreshold", "55");
			$this->RegisterPropertyInteger("VentilationHumiditySensor",0);
			$this->RegisterPropertyString('VentilationTimeStart', '{"hour":9, "minute": 0, "second": 0}');
			$this->RegisterPropertyString('VentilationTimeStop', '{"hour":18, "minute": 0, "second": 0}');
			$this->RegisterPropertyBoolean("VentilationManualVariable", 0);

			$this->RegisterPropertyBoolean("AutoCloseActive", false);
			$this->RegisterPropertyInteger("AutoCloseTimer", "5");
			
			$this->RegisterTimer("Garage Door - Ventilation Timer",0,"GD_Ventilation(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door - Auto Close Timer",0,"GD_DoorAutoClose(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door - Movement Indicator",0,"GD_DoorOpenCloseStopMovement(\$_IPS['TARGET']);");

			$i = 10;

			$this->RegisterVariableBoolean('DoorSwitchButton', $this->Translate('Door Switch Button'),"~Switch", $i++);
				$this->EnableAction("DoorSwitchButton");
				$DoorSwitchButtonID = @IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID);	
				if (IPS_GetObject($DoorSwitchButtonID)['ObjectType'] == 2) {
						$this->RegisterMessage($DoorSwitchButtonID, VM_UPDATE);
				}
			$this->RegisterVariableInteger('DoorStatus', $this->Translate('Door Status'),"GD.DoorStatus", $i++);
				SetValue(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID),"104");	
			$this->RegisterVariableInteger('DoorCurrentOperation', $this->Translate('Door Current Operation'),"GD.DoorOperation", $i++);
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"200");			

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

			$this->MaintainVariable('DoorSwitchHomeKit', $this->Translate('Door Switch Homekit'), vtInteger, '~ShutterMoveStop', $vpos++,$this->ReadPropertyBoolean('HomekitSwitchVariable') == true);
			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
				$this->EnableAction("DoorSwitchHomeKit");
				SetValue(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID),"4");
				$DoorSwitchHomeKitID = @IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID);
				if (IPS_GetObject($DoorSwitchHomeKitID)['ObjectType'] == 2) {
						$this->RegisterMessage($DoorSwitchHomeKitID, VM_UPDATE);
				}
			}

			$this->MaintainVariable('VentilationManual', $this->Translate('Ventilation'), vtBoolean, '~Switch', $vpos++,$this->ReadPropertyBoolean('VentilationManualVariable') == true);
			if ($this->ReadPropertyBoolean('VentilationManualVariable') == 1) {
				$this->EnableAction("VentilationManual");
				$VentilationManualID = @IPS_GetObjectIDByIdent('VentilationManual', $this->InstanceID);
				if (IPS_GetObject($VentilationManualID)['ObjectType'] == 2) {
						$this->RegisterMessage($VentilationManualID, VM_UPDATE);
				}
				
			}

			$this->MaintainVariable('AutoCloseFunction', $this->Translate('Auto Close Function'), vtBoolean, '~Switch', $vpos++,$this->ReadPropertyBoolean('AutoCloseActive') == true);
			if ($this->ReadPropertyBoolean('AutoCloseActive') == 1) {
				$this->EnableAction("AutoCloseFunction");
				$AutoCloseFunctionID = @IPS_GetObjectIDByIdent('AutoCloseFunction', $this->InstanceID);
				if (IPS_GetObject($AutoCloseFunctionID)['ObjectType'] == 2) {
						$this->RegisterMessage($AutoCloseFunctionID, VM_UPDATE);
				}
				
			}

			$OpenVariableID = @IPS_GetObjectIDByIdent('OpenDoor', $this->InstanceID);
			if (IPS_GetObject($OpenVariableID)['ObjectType'] == 2) {
					$this->RegisterMessage($OpenVariableID, VM_UPDATE);
			}		
				
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {

			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == true) {
				$DoorSwitchHomeKitID = $this->GetIDForIdent('DoorSwitchHomeKit');
			} else {
				$DoorSwitchHomeKitID = "00001";
			}

			if ($this->ReadPropertyBoolean('VentilationManualVariable') == true) {
				$VentilationManualID = $this->GetIDForIdent('VentilationManual');
			} else {
				$VentilationManualID = "00002";
			}

			if ($this->ReadPropertyBoolean('AutoCloseActive') == true) {
				$AutoCloseFunctionID = $this->GetIDForIdent('AutoCloseFunction');
			} else {
				$AutoCloseFunctionID = "00003";
			}
			
			if ($SenderID == $this->GetIDForIdent('DoorSwitchButton') AND (GetValueBoolean(@IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID)) == true)) {
				$this->SendDebug("Door Trigger","", 0);
				$this->SendDebug("Door Trigger","******************************", 0);
				$this->SendDebug("Door Trigger","Variable the was triggered: ".(IPS_GetObject($SenderID)["ObjectName"]), 0);

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) != 100 AND (GetValueBoolean(@IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID)) == true)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button was pressed request to open'),0);
					SetValueBoolean(@IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID),0); 
					if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit is active and therefore triggered'),0);
						SetValue(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID),"0");	
					}
					else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button will trigger door'),0);
						$this->SetBuffer('DoorSwitchRequest',"Open");
						$this->DoorController();	
					}
					
				}

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) == 100  AND (GetValueBoolean(@IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID)) == true)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Button was pressed request to close'),0);
					SetValueBoolean(@IPS_GetObjectIDByIdent('DoorSwitchButton', $this->InstanceID),0);
					if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
						$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit is active and therefore triggered'),0);
						SetValue(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID),"4");	
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

			if ($SenderID == $DoorSwitchHomeKitID) {
				$this->SendDebug("Door Trigger","", 0);
				$this->SendDebug("Door Trigger","******************************", 0);
				$this->SendDebug("Door Trigger","Variable the was triggered: ".(IPS_GetObject($SenderID)["ObjectName"]), 0);

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) == 104 AND (GetValueInteger(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID)) == 0)) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Homekit was triggered to OPEN door - this can happen directly or via the button'),0);
					$this->SetBuffer('DoorSwitchRequest',"Open");
					$this->DoorController();		
				}

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) == 100 AND (GetValueInteger(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID)) == 4)) {
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
			
			if ($SenderID == $VentilationManualID) {
				$this->SendDebug("Door Trigger","", 0);
				$this->SendDebug("Door Trigger","******************************", 0);
				$this->SendDebug("Door Trigger","Variable the was triggered: ".(IPS_GetObject($SenderID)["ObjectName"]), 0);

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) == 104 AND (GetValueBoolean(@IPS_GetObjectIDByIdent('VentilationManual', $this->InstanceID)) == true)) {
					$this->SendDebug($this->Translate('Ventilation Manual'),$this->Translate('Manual OPENING for ventilation'),0);
					$this->SetBuffer('DoorVentilationRequest',"Ventilate - Open");
					$this->VentilationDoorOpenClose();		
				}

				if (GetValueInteger(@IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID)) == 110 AND (GetValueBoolean(@IPS_GetObjectIDByIdent('VentilationManual', $this->InstanceID)) == false)) {
					$this->SendDebug($this->Translate('Ventilation Manual'),$this->Translate('Manual OPENING ending ventilation'),0);
					$this->SetBuffer('DoorVentilationRequest',"Ventilate - Close");
					$this->VentilationDoorOpenClose();	
				}
			}
	
		}


		public function DoorController() {

			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Controller is evaluating options'),0);

			$DoorSwitchRequest = $this->GetBuffer('DoorSwitchRequest'); 
			$DoorStatus = GetValue($this->GetIDForIdent('DoorStatus'));
			$DoorStatusID = @IPS_GetObjectIDByIdent('DoorStatus', $this->InstanceID);

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
					SetValue($DoorStatusID,"104");
					$this->DoorOpenClose();
				}
				elseif ($DoorStatus == "110") {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was on ventialation mode and will be CLOSED'),0);
					SetValue($DoorStatusID,"104");
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
			$DoorCurrentOperation = GetValue($this->ReadPropertyInteger('DoorCurrentOperation'));

			$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('The door switch has been triggered and turned on/off'),0);
			
			//RequestAction($GarageDoorActor, true);
			SetValueBoolean($GarageDoorActor, true);
			IPS_Sleep($GarageDoorActorTiggerTime);
			//RequestAction($GarageDoorActor, false);
			SetValueBoolean($GarageDoorActor, false);

			if ($DoorSwitchRequest == "Open") {
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"202");	
				$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door is set to moving to OPEN position'),0);
				if ($this->ReadPropertyBoolean("WriteToLog") == true) {
					IPS_LogMessage($this->Translate('Garage Door'), $this->Translate('Garage is opening'));
				}
				$TimerGarageDoorTravelTimeUpMS = $this->ReadPropertyInteger("GarageDoorTravelTimeUp") * 1000;
				$this->SetTimerInterval("Garage Door - Movement Indicator",$TimerGarageDoorTravelTimeUpMS);

				if (GetValue($this->GetIDForIdent('AutoCloseFunction')) == true) {
					$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('Requesting Auto Close'),0);
					$this->DoorAutoCloseWait();
				}

			} elseif ($DoorSwitchRequest == "Close") {
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"201");
				$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door is set to moving to CLOSE position'),0);
				if ($this->ReadPropertyBoolean("WriteToLog") == true) {
					IPS_LogMessage($this->Translate('Garage Door'), $this->Translate('Garage is closing'));
				}
				$TimerGarageDoorTravelTimeDownMS = $this->ReadPropertyInteger("GarageDoorTravelTimeDown") * 1000;
				$this->SetTimerInterval("Garage Door - Movement Indicator",$TimerGarageDoorTravelTimeDownMS);
			}
			/*
			if ($DoorCurrentOperation != "200") {
				$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('Trigger reqeust ignored since door was already moving'),0);
			}*/
			
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
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"203");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				IPS_Sleep($VentilationOpenTimer * 1000);
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Reversing ****'),0);
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"205");
				SetValue($DoorStatus,"100");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"200");
				SetValue($this->GetIDForIdent('DoorStatus'),"110");
			} else if ($DoorVentilationRequest == "Ventilate - Close") { 
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Ventilation ****'),0);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Closing ****'),0);
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"204");
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				RequestAction($GarageDoorActor, true);
				IPS_Sleep($GarageDoorActorTiggerTime);
				RequestAction($GarageDoorActor, false);
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('**** Closed door ****'),0);
				SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"200");
				SetValue($this->GetIDForIdent('DoorStatus'),"104");
			}

		}

		public function DoorOpenCloseStopMovement() {

			$this->SendDebug($this->Translate('Door Open Close'),$this->Translate('The door finished its movement based on the timer setup for moving up or down'),0);
			SetValue(@IPS_GetObjectIDByIdent('DoorCurrentOperation', $this->InstanceID),"200");	
			$this->SetTimerInterval("Garage Door - Movement Indicator",0);

			if (GetValueBoolean(@IPS_GetObjectIDByIdent('VentilationManual', $this->InstanceID)) == true AND GetValue($this->GetIDForIdent('DoorStatus')) == "104") {
				$this->SendDebug($this->Translate('Ventilation'),$this->Translate('Restoring Ventilation'),0);
				$this->SetBuffer('DoorVentilationRequest',"Ventilate - Open");
				$this->VentilationDoorOpenClose();	
			}

		}

		public function DoorAutoCloseWait() {

			$AutoCloseTimer = $this->ReadPropertyInteger('AutoCloseTimer');

			$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Timer for autoclose was started'),0);

			$AutoCloseTimerMS = $this->ReadPropertyInteger("AutoCloseTimer") * 60000;
			$this->SetTimerInterval("Garage Door - Auto Close Timer",$AutoCloseTimerMS);

		}

		public function DoorAutoClose() {

			$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close function is closing door'),0);

			$this->SetTimerInterval("Garage Door - Auto Close Timer",0);

			if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 1) {
				$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close is triggering Homekit to close door'),0);
				SetValue(@IPS_GetObjectIDByIdent('DoorSwitchHomeKit', $this->InstanceID),"4");	
			}
			else if ($this->ReadPropertyBoolean('HomekitSwitchVariable') == 0) {
				$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Auto Close is tiggering standard function to close door'),0);
				if (GetValue($this->ReadPropertyInteger('GarageDoorSensor')) == false AND $this->ReadPropertyInteger('GarageDoorSensor') !="") {
					$this->SetBuffer('DoorSwitchRequest',"Close");
					$this->DoorController();
				} else {
					$this->SendDebug($this->Translate('Door Auto Close'),$this->Translate('Trigger requesting to close door, but is blocked due to door sensor'),0);
				}	
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