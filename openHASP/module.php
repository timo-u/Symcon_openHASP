<?php

declare(strict_types=1);
class openHASP extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Hostname', 'Hostname');
		
		$this->RegisterPropertyBoolean('AutoDisplayBacklight', true);
		$this->RegisterPropertyBoolean('AutoCreateVariable', true);
		
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}'); //Automatisch mit der MQTT-Server Instanz verbiden
		
		
		
		
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
        $MQTTTopic = "hasp/" .$this->ReadPropertyString('Hostname').'/';
        $this->SetReceiveDataFilter('.*'.$MQTTTopic.'.*');
        $this->SendDebug('SetReceiveDataFilter()', 'Topic: '.$MQTTTopic, 0);
		
		$this->RegisterVariableProfiles();
		$this->Maintain();

    }
	private function Maintain()
	{
		$this->MaintainVariable('Online', $this->Translate('Online'), 0, 'OpenHASP.Online', 1, true);
		$this->MaintainVariable('Idle', $this->Translate('Idle'), 1, 'OpenHASP.Idle', 2, true);
		$this->MaintainVariable('Backlight', $this->Translate('Backlight'), 1, '', 3, true);
		$this->MaintainVariable('Page', $this->Translate('Page'), 1, '', 4, true);
		
		$this->MaintainAction("Backlight",true);
		$this->MaintainAction("Page",true);
	}
	
	public function RequestAction($Ident, $Value) {
			$this->SendDebug('RequestAction()', 'Ident: '.$Ident.' Value: '.$Value, 0);
			switch($Ident) {
				case "Backlight":
					$this->SendCommand('backlight='.$Value);
					break;
				case "Page":
					$this->SendCommand('page='.$Value);
					break;
			}
			
			if(preg_match('/p\d{1,2}b\d{1,3}_value/', $Ident))
			{
				$buttonId= substr($Ident,0,stripos($Ident,"_"));
				$this->SendCommand($buttonId.'.val='.$Value);
			}
			if(preg_match('/p\d{1,2}b\d{1,3}_text/', $Ident))
			{
				$buttonId= substr($Ident,0,stripos($Ident,"_"));
				$this->SendCommand($buttonId.'.text='.$Value);
			}
			if(preg_match('/p\d{1,2}b\d{1,3}_color/', $Ident))
			{
				$buttonId= substr($Ident,0,stripos($Ident,"_"));
				$this->SendCommand($buttonId.'.color='.$Value);
			}
		}
		
    public function ReceiveData($JSONString)
    {
        $receiveddata = json_decode($JSONString);
		
		$receivedtopic = $receiveddata->Topic;
		$data = $receiveddata->Payload;
		$this->SendDebug('ReceiveData()', 'Received Topic: '.$receivedtopic . ' Data: '.$data, 0);
		
		$expectedprefix = "hasp/" . $this->ReadPropertyString('Hostname') .'/LWT';
		if(stripos($receivedtopic,$expectedprefix) !==false) // Prüfen ob Topic mit dem Prefix des Gerätes beginnt
		{
			$this->HandleData('LWT', $data);
			return; 
		}
		
		$expectedprefix = "hasp/" . $this->ReadPropertyString('Hostname') .'/state/';
		
		if(stripos($receivedtopic,$expectedprefix) ===false) // Prüfen ob Topic mit dem Prefix des Gerätes beginnt
		{
			$this->SendDebug('ReceiveData()', 'Topic does not match', 0);
			return; // Abbrechen wenn das Topic nicht passt. 
		}
		$topic = substr($receivedtopic,strlen($expectedprefix)); //Prefix des Topics abschneiden
		
        
        $this->SendDebug('ReceiveData()', 'Topic: '.$topic . ' Data: '.$data, 0);

		$this->HandleData($topic, $data);
		
    }
	
	private function HandleData(string $topic, string $data)
	{
		if($topic == "idle")
		{
		switch ($data) {
			case 'short':
				$this->SetValue("Idle",1);
				break;
			case 'long':
				$this->SetValue("Idle",2);
				break;
			default:
				$this->SetValue("Idle",0);
			}
			if($this->ReadPropertyBoolean('AutoDisplayBacklight'))
			{
			switch ($data) {
			case 'short':
				$this->SendCommand('backlight=50');
				break;
			case 'long':
				$this->SendCommand('backlight=0');
				break;
			default:
				$this->SendCommand('backlight=255');
			}
			}
					
			
		}
		if($topic == "backlight")
		{		
			$data = json_decode($data);
			$this->SetValue("Backlight",$data->brightness);
		}
		if($topic == "page")
		{		
			$this->SetValue("Page",$data);
		}
		
		if(preg_match('/p\d{1,2}b\d{1,3}/', $topic))
		{	
			$data = json_decode($data);	
			if(property_exists($data, 'event'))
			{
			$key = $topic.'_event';
			if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) 
			{
				$this->MaintainVariable($key , $topic . " " . $this->Translate('Event'), 1, 'OpenHASP.BtnEvent', 10, true);
			
			switch ($data->event) {
			case 'down':
				$this->SetValue($key ,1);
				break;
			case 'long':
				$this->SetValue($key ,2);
				break;
			case 'hold':
				$this->SetValue($key ,3);
				break;
			case 'releas':
				$this->SetValue($key ,4);
				break;
			case 'changed':
				$this->SetValue($key ,5);
				break;	
			default:
				$this->SetValue($key ,0);
			}
			}
			}
			if(property_exists($data, 'val'))
			{
				$key = $topic.'_value';
				if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) 
				{
					$this->MaintainVariable($key, $topic . " " . $this->Translate('Value'), 1, '', 10, true);
					$this->MaintainAction($key,true);
					$this->SetValue($key,$data->val);
				}
			}
			if(property_exists($data, 'text'))
			{	
				$key = $topic.'_text';
				if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) 
				{
					$this->MaintainVariable($key, $topic . " " . $this->Translate('Text'), 3, '', 10, true);
					$this->SetValue($key,$data->text);
				}
			}
			if(property_exists($data, 'color'))
			{	
				$key = $topic.'_color';
				if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) 
				{
					$this->MaintainVariable($key, $topic . " " . $this->Translate('Color'), 3, '', 10, true);
					$this->SetValue($key,$data->color);
				}
			}
		}
		
		if($topic == "statusupdate")
		{	
				
			//$data = json_decode($data);
			//$this->SetValue("Backlight",$data->brightness);
		}
		if($topic == "LWT")
		{
		switch ($data) {
			case 'online':
				$this->SetValue("Online",1);
				$this->Online();
				break;
			default:
				$this->SetValue("Online",0);
			}
		}
	}
	private function Online()
	{
		$this->SendDebug('Online()', 'Gerät ist Online', 0);
	}
    public function SendCommand(string $command)
    {
        $MQTTTopic = "hasp/" .$this->ReadPropertyString('Hostname').'/command/';
        $this->SendDebug('SendCommand()', 'Topic: '.$MQTTTopic.' Command: '.$command, 0);

        $this->SendMQTT($MQTTTopic, $command);
    }

    public function Restart()
    {
        $this->SendCommand("Restart");
    }

    protected function SendMQTT($Topic, $Payload)
    {
        $resultServer = true;
        $resultClient = true;
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $Topic;
        $Server['Payload'] = $Payload;
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        //$this->SendDebug('SendMQTT()'.'MQTT Server', $ServerJSON, 0);
        $resultServer = @$this->SendDataToParent($ServerJSON);

        //MQTT Client
        $Buffer['PacketType'] = 3;
        $Buffer['QualityOfService'] = 0;
        $Buffer['Retain'] = false;
        $Buffer['Topic'] = $Topic;
        $Buffer['Payload'] = $Payload;
        $BufferJSON = json_encode($Buffer, JSON_UNESCAPED_SLASHES);

        $Client['DataID'] = '{97475B04-67C3-A74D-C970-E9409B0EFA1D}';
        $Client['Buffer'] = $BufferJSON;

        $ClientJSON = json_encode($Client);
        //$this->SendDebug('SendMQTT()'.'MQTT Client', $ClientJSON, 0);
        $resultClient = @$this->SendDataToParent($ClientJSON);

        return $resultServer === false && $resultClient === false;
    }
private function RegisterVariableProfiles()
    {
        $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);

        
        if (!IPS_VariableProfileExists('OpenHASP.Idle')) {
            IPS_CreateVariableProfile('OpenHASP.Idle', 1);
            IPS_SetVariableProfileText('OpenHASP.Idle', '', '');
			IPS_SetVariableProfileIcon("OpenHASP.Idle",  "Hourglass");
			IPS_SetVariableProfileAssociation('OpenHASP.Idle', 0, $this->Translate('Off'), '', -1);
            IPS_SetVariableProfileAssociation('OpenHASP.Idle', 1, $this->Translate('Short'), '', -1);
			IPS_SetVariableProfileAssociation('OpenHASP.Idle', 2, $this->Translate('Long'), '', -1);
        }
		if (!IPS_VariableProfileExists('OpenHASP.BtnEvent')) {
            IPS_CreateVariableProfile('OpenHASP.BtnEvent', 1);
            IPS_SetVariableProfileText('OpenHASP.BtnEvent', '', '');
			IPS_SetVariableProfileIcon("OpenHASP.BtnEvent",  "Execute");
			IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 0, $this->Translate('Up'), '', -1);
            IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 1, $this->Translate('Down'), '', -1);
			IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 2, $this->Translate('Long'), '', -1);
			IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 3, $this->Translate('Hold'), '', -1);
			IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 4, $this->Translate('Release'), '', -1);
			IPS_SetVariableProfileAssociation('OpenHASP.BtnEvent', 5, $this->Translate('Changed'), '', -1);       
	   }
	   if (!IPS_VariableProfileExists('OpenHASP.Online')) {
            IPS_CreateVariableProfile('OpenHASP.Online', 0);
            IPS_SetVariableProfileAssociation('OpenHASP.Online', 0, $this->Translate('Offline'), '', 0xFF0000);
            IPS_SetVariableProfileAssociation('OpenHASP.Online', 1, $this->Translate('Online'), '', 0x00FF00);
        }
    }


}
