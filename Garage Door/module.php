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

			//$this->RegisterPropertyString("IP","");
			$this->RegisterPropertyBoolean("Active", 0);
			$this->RegisterPropertyInteger("GarageDoorVariable", "60");
			$this->RegisterPropertyInteger("GarageDoorSensor", "60");
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
			$this->MaintainVariable('CloseDoor', $this->Translate('Close Door'), vtString, '', $vpos++,$this->ReadPropertyBoolean('CloseVariable') == 1);
			$this->MaintainVariable('OpenDoor', $this->Translate('Open Door'), vtString, '', $vpos++,$this->ReadPropertyBoolean('OpenVariable') == 1);
			
			//$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
			//$this->SetTimerInterval("WLAN BBQ Thermometer",$TimerMS);

			if ($this->ReadPropertyBoolean('CloseVariable') == 1) {
				$this->EnableAction("CloseDoor");
			}
			if ($this->ReadPropertyBoolean('OpenVariable') == 1) {
				$this->EnableAction("OpenDoor");
			} 
				
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{
		
		//$this->SendDebug("Sender",$SenderID." ".$Message." ".$Data, 0);

			if ($SenderID == $this->GetIDForIdent('Active')) {
				
				$SenderValue = GetValue($SenderID);
				if ($SenderValue == 1) {
					$this->SendDebug("System","Module activated", 0);
					$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
					$this->SetTimerInterval("WLAN BBQ Thermometer",$TimerMS);
					$this->SetBuffer("UnreachCounter",0);
					$this->GetReadings();
				}
				else {
					$this->SetTimerInterval("WLAN BBQ Thermometer", "0");
					$this->ArchiveCleaning();
					$this->UnsetValuesAtShutdown();
					$this->SendDebug("System","Switching module off", 0);
				}
			}
			else {
				
			}
			

		}


		public function DoorController() {

			//hole wer einen Befehl geschickt hat
			//prüfe aktuellen Status
			//prüfe rückkehr



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

	}