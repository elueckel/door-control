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

			$this->RegisterPropertyString("IP","");
			$this->RegisterPropertyBoolean("System_Messages", 0);
			$this->RegisterPropertyInteger("Timer", "60");
			$this->RegisterPropertyInteger("System_BatteryThreshold", "15");

			$this->RegisterTimer("WLAN BBQ Thermometer",0,"WT_CyclicTask(\$_IPS['TARGET']);");


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

			$this->MaintainVariable('CoreTemp_Pork', $this->Translate('Core Temperature Pork'), vtString, 'WT.CoreTemp_Pork', $vpos++,$this->ReadPropertyBoolean('CoreTemp') == 1);
			$this->MaintainVariable('CoreTemp_Beef', $this->Translate('Core Temperature Beef'), vtString, 'WT.CoreTemp_Beef', $vpos++,$this->ReadPropertyBoolean('CoreTemp') == 1);
			$this->MaintainVariable('CoreTemp_Calf', $this->Translate('Core Temperature Calf'), vtString, 'WT.CoreTemp_Calf', $vpos++,$this->ReadPropertyBoolean('CoreTemp') == 1);

			//$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
			//$this->SetTimerInterval("WLAN BBQ Thermometer",$TimerMS);

			if ($this->ReadPropertyBoolean('CoreTemp') == 1) {
				$this->EnableAction("CoreTemp_Pork");
				$this->EnableAction("CoreTemp_Beef");
				$this->EnableAction("CoreTemp_Calf");
				$this->EnableAction("CoreTemp_Chicken");
				$this->EnableAction("CoreTemp_Venison");
				$this->EnableAction("CoreTemp_Lamb");
				$this->EnableAction("CoreTemp_Fish");
			}
		}




		public function NotifyApp() {
			$NotifierTitle = "BBQ Thermometer";
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
				$EmailTitle = "BBQ Thermometer";
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