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
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 0, $this->Translate("Open"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 1, $this->Translate("Closed"), "", -1);
				IPS_SetVariableProfileAssociation("GD.DoorStatus", 5, $this->Translate("Ventilation"), "", -1);

			}

			//$this->RegisterPropertyString("IP","");
			$this->RegisterPropertyBoolean("Active", 0);
			$this->RegisterPropertyInteger("GarageDoorVariable", 0);
			$this->RegisterPropertyInteger("GarageDoorSensor", 0);
			$this->RegisterPropertyBoolean("WriteToLog", 0);
			$this->RegisterPropertyBoolean("CloseVariable", 0);
			$this->RegisterPropertyBoolean("OpenVariable", 0);

			$this->RegisterPropertyBoolean("VentilationActive", 0);
			$this->RegisterPropertyInteger("VentilationHumidityThreshold", "15");
			$this->RegisterPropertyInteger("VentilationTimeStart", "15");
			$this->RegisterPropertyInteger("VentilationTimeStop", "15");

			$this->RegisterPropertyBoolean("AutoCloseActive", 0);
			$this->RegisterPropertyInteger("AutoCloseTimer", 0);
			
			$this->RegisterTimer("Garage Door Ventilation",0,"GD_Ventilation(\$_IPS['TARGET']);");
			$this->RegisterTimer("Garage Door Auto Close",0,"GD_AutoClose(\$_IPS['TARGET']);");

			$i = 10;

			$this->RegisterVariableInteger('DoorSwitch', $this->Translate('Door Switch'),"~ShutterMoveStop", $i++);	
			$this->RegisterVariableInteger('DoorStatus', $this->Translate('Door Status'),"GD.DoorStatus", $i++);
			$this->EnableAction("DoorSwitch");	

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
			$this->MaintainVariable('CloseDoor', $this->Translate('Close Door'), vtBoolean, '~Switch', $vpos++,$this->ReadPropertyBoolean('CloseVariable') == 1);
			$this->MaintainVariable('OpenDoor', $this->Translate('Open Door'), vtBoolean, '~Switch', $vpos++,$this->ReadPropertyBoolean('OpenVariable') == 1);
			
			//$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
			//$this->SetTimerInterval("WLAN BBQ Thermometer",$TimerMS);


			if ($this->ReadPropertyBoolean('CloseVariable') == 1) {
				$this->EnableAction("CloseDoor");
			}
			if ($this->ReadPropertyBoolean('OpenVariable') == 1) {
				$this->EnableAction("OpenDoor");
			}

			if (IPS_GetObject('CloseVariable')['ObjectType'] == 2) {
					$this->RegisterMessage('CloseVariable', VM_UPDATE);
			}

			if (IPS_GetObject('OpenVariable')['ObjectType'] == 2) {
					$this->RegisterMessage('OpenVariable', VM_UPDATE);
			}

			if (IPS_GetObject('DoorSwitch')['ObjectType'] == 2) {
					$this->RegisterMessage('DoorSwitch', VM_UPDATE);
			}
				
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		
			$this->SendDebug("Sender",$SenderID." ".$Message." ".$Data, 0);

			if ($SenderID == $this->GetIDForIdent('CloseVariable')) {

				$this-SetBuffer('DoorTargetPosition',1);
				$this->DoorController();
				SetValue($SenderID,0); // Taster Emulieren
			}

			if ($SenderID == $this->GetIDForIdent('OpenVariable')) {

				$this-SetBuffer('DoorTargetPosition',0);
				$this->DoorController();
				SetValue($SenderID,0); // Taster Emulieren
				
			}

			if ($SenderID == $this->GetIDForIdent('DoorSwitch')) {
				
				$CurrentStatus = GetValue($SenderID);
				$this-SetBuffer('Doorstatus',$CurrentStatus);

			}
		

		}


		public function DoorController() {

			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Controller is evaluating options'),0);

			$DoorTargetPosition = $this->GetBuffer('DoorTargetPosition'); //can be 0 = open, 1 = closed or 5 = ventilation
			$DoorStatus = GetValue($this->GetIDForIdent('DoorStatus'));

			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Current Status of door is '.$DoorStatus),0);
			$this->SendDebug($this->Translate('Door Controller'),$this->Translate('New Status should be '.$DoorTargetPosition),0);

			if ($DoorTargetPosition == 0) {

				if ($DoorStatus == 1) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was CLOSED and will be OPENED'),0);
					$this->DoorTrigger();
				}
				elseif ($DoorStatus == 5) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was on ventialation mode and will be OPENED'),0);
					$this->DoorTrigger();
				}
				elseif ($DoorStatus == 0) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was already OPEN - do nothing'),0);
				}
			}

			if ($DoorTargetPosition == 1) {

				if ($DoorStatus == 0) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was OPEN and will be CLOSED'),0);
					$this->DoorTrigger();
				}
				elseif ($DoorStatus == 5) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was on ventialation mode and will be CLOSED'),0);
					$this->DoorTrigger();
				}
				elseif ($DoorStatus == 1) {
					$this->SendDebug($this->Translate('Door Controller'),$this->Translate('Door was already CLOSED - do nothing'),0);
				}
			}

		}

		public function DoorTrigger() {

			$DoorSwitch = GetValue($this->ReadPropertyInteger('GarageDoorVariable'));

			$this->SendDebug($this->Translate('Door Trigger'),$this->Translate('The door switch has been triggern and turned on/off'),0);

			RequestAction($DoorSwitch, true);
			IPS_Sleep(1000);
			RequestAction($DoorSwitch, false);

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