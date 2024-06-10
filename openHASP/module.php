<?php

declare(strict_types=1);
class openHASP extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Hostname', 'Hostname');

        $this->RegisterPropertyBoolean('AutoDimBacklight', false);
		$this->RegisterPropertyBoolean('AutoShutdownBacklight', false);
        $this->RegisterPropertyBoolean('AutoCreateVariable', false);


        $this->RegisterPropertyBoolean('WriteDisplayContent', false);
        $this->RegisterPropertyString('UiElements', "");
        $this->RegisterPropertyBoolean('DisplayDateTimeHeader', true);
        $this->RegisterPropertyBoolean('DisplayPageControlFooter', true);
		
		$this->RegisterAttributeString("ElementToObjectMapping", "{}"); 

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

        $this->UpdateElements();

        $this->RewriteDisplay();

        $this->RegisterVariableProfiles();
        $this->Maintain();

    }

    private function UpdateElements()
    {
        $UiElements = json_decode($this->ReadPropertyString("UiElements"), true);
		if($UiElements==null)
			$UiElements= array();
		
        $count = 1;
		//Reference
        //Unregister
        foreach ($this->GetReferenceList() as $id) {
            $this->UnregisterReference($id);
        }
		//Messages
        //Unregister all messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        
        foreach ($UiElements as &$element) {
            if($this->json_validate($element['OverrideParameter']) || $element['OverrideParameter'] == "") {

            } else {
                echo "JSON Fehler bei UI-Element ".$count.":". PHP_EOL . '"'.$element['OverrideParameter'].'"'. PHP_EOL ;
            }
            if($element['Type'] == 1 && $element['Object'] != 1) { // Button & Element ausgewählt==> Script
                if(!IPS_ObjectExists($element['Object'])) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Das Objekt mit der ID: '.$element['Object'].' existiert nicht'. PHP_EOL ;
                }

                if(IPS_GetObject($element['Object'])['ObjectType'] != 3) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Objekt mit der ID: '.$element['Object'].' ist kein Script'. PHP_EOL .
                            'Das Objekt für einen Button muss vom Typ "Skript" sein.'. PHP_EOL  ;
                }
            }
            if($element['Type'] == 2 && $element['Object'] != 1) { // Toggle Button & Element ausgewählt==> Variable
                if(!IPS_ObjectExists($element['Object'])) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Das Objekt mit der ID: '.$element['Object'].' existiert nicht'. PHP_EOL ;
                }

                if(IPS_GetObject($element['Object'])['ObjectType'] != 2) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Objekt mit der ID: '.$element['Object'].' ist kein Script'. PHP_EOL .
                            'Das Objekt für einen Toggle-Button muss vom Typ "Varaible" sein.'. PHP_EOL  ;
                }
            }
            if($element['Type'] == 3 && $element['Object'] != 1) { // Slider & Element ausgewählt==> Variable
                if(!IPS_ObjectExists($element['Object'])) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Das Objekt mit der ID: '.$element['Object'].' existiert nicht'. PHP_EOL ;
                }

                if(IPS_GetObject($element['Object'])['ObjectType'] != 2) {
                    echo 	"Fehler bei ausgewähltem Objekt ".$count.":". PHP_EOL .
                            'Objekt mit der ID: '.$element['Object'].' ist kein Script'. PHP_EOL .
                            'Das Objekt für einen Button muss vom Typ "Varaible" sein.'. PHP_EOL  ;
                }
            }
			
			if( $element['Object'] != 1)
			{
			$this->RegisterReference($element['Object']);
			if($element['Type'] == 0|| $element['Type'] == 2|| $element['Type'] == 3)
			{
				$this->RegisterMessage($element['Object'], VM_UPDATE);
			}
			}
			
            $count++;
        }
    }

    public function GetConfigurationForm()
    {

        $data = json_decode(file_get_contents(__DIR__ . "/form.json"), false);
        $UiElements = $this->ReadPropertyString("UiElements");

        $this->SendDebug('GetConfigurationForm()', 'UI-Elements:'. $UiElements, 0);

        //Only add default element if we do not have anything in persistence
        if($UiElements == "" || $UiElements == "[]") {
            $data->elements[4]->values[] = array(
                "Type" => 0,
                "Caption" => "#32C9AC Symcon",
                "OverrideParameter" => '{"text_font":50,"h":60}',
                "Margin" => 30,
                "Object" => 1
            );
        }


        return json_encode($data);

    }
    //Ab PHP 8.3 ist die Funktion bestandteil von PHP
    private function json_validate(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function Maintain()
    {
        $this->MaintainVariable('Online', $this->Translate('Online'), 0, 'OpenHASP.Online', 1, true);
        $this->MaintainVariable('Idle', $this->Translate('Idle'), 1, 'OpenHASP.Idle', 2, true);
        $this->MaintainVariable('Backlight', $this->Translate('Backlight'), 1, '', 3, true);
        $this->MaintainVariable('Page', $this->Translate('Page'), 1, '', 4, true);

        $this->MaintainAction("Backlight", true);
        $this->MaintainAction("Page", true);
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SendDebug('RequestAction()', 'Ident: '.$Ident.' Value: '.$Value, 0);
        switch($Ident) {
            case "Backlight":
                $this->SendCommand('backlight='.$Value);
                break;
            case "Page":
                $this->SendCommand('page='.$Value);
                break;
        }

        if(preg_match('/p\d{1,2}b\d{1,3}_value/', $Ident)) {
            $buttonId = substr($Ident, 0, stripos($Ident, "_"));
            $this->SendCommand($buttonId.'.val='.$Value);
        }
        if(preg_match('/p\d{1,2}b\d{1,3}_text/', $Ident)) {
            $buttonId = substr($Ident, 0, stripos($Ident, "_"));
            $this->SendCommand($buttonId.'.text='.$Value);
        }
        if(preg_match('/p\d{1,2}b\d{1,3}_color/', $Ident)) {
            $buttonId = substr($Ident, 0, stripos($Ident, "_"));
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
        if(stripos($receivedtopic, $expectedprefix) !== false) { // Prüfen ob Topic mit dem Prefix des Gerätes beginnt
            $this->HandleData('LWT', $data);
            return;
        }

        $expectedprefix = "hasp/" . $this->ReadPropertyString('Hostname') .'/state/';

        if(stripos($receivedtopic, $expectedprefix) === false) { // Prüfen ob Topic mit dem Prefix des Gerätes beginnt
            $this->SendDebug('ReceiveData()', 'Topic does not match', 0);
            return; // Abbrechen wenn das Topic nicht passt.
        }
        $topic = substr($receivedtopic, strlen($expectedprefix)); //Prefix des Topics abschneiden


        $this->SendDebug('ReceiveData()', 'Topic: '.$topic . ' Data: '.$data, 0);

        $this->HandleData($topic, $data);

    }

    private function HandleData(string $topic, string $data)
    {
		$ElementToObjectMapping= json_decode($this->ReadAttributeString("ElementToObjectMapping"));
		
        if($topic == "idle") {
            switch ($data) {
                case 'short':
                    $this->SetValue("Idle", 1);
                    break;
                case 'long':
                    $this->SetValue("Idle", 2);
                    break;
                default:
                    $this->SetValue("Idle", 0);
            }
            if($this->ReadPropertyBoolean('AutoDimBacklight')) {
                switch ($data) {
                    case 'short':
                        $this->SendCommand('backlight=50');
                        break;
                    case 'long':
                        break;
                    default:
                        $this->SendCommand('backlight=255');
                }
            }
			if($this->ReadPropertyBoolean('AutoShutdownBacklight')) {
				if($data=='long')
					$this->SendCommand('backlight=0');
				}


        }
        if($topic == "backlight") {
            $data = json_decode($data);
            $this->SetValue("Backlight", $data->brightness);
        }
        if($topic == "page") {
            $this->SetValue("Page", $data);
        }

        if(preg_match('/p\d{1,2}b\d{1,3}/', $topic)) {
			
			$found_key = array_search($topic, array_column($ElementToObjectMapping, 'objkey'));
			$this->SendDebug('FoundMapping()', $found_key, 0);
			$Element = null;
			if($found_key!=false)
			{
				$Element = $ElementToObjectMapping[$found_key]->data;
				$this->SendDebug('FoundMapping()', json_encode($Element) ,0);
			}
			 
            $data = json_decode($data);
            if(property_exists($data, 'event')) {
                $key = $topic.'_event';
                if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) {
                    $this->MaintainVariable($key, $topic . " " . $this->Translate('Event'), 1, 'OpenHASP.BtnEvent', 10, true);

                    switch ($data->event) {
                        case 'down':
                            $this->SetValue($key, 1);
                            break;
                        case 'long':
                            $this->SetValue($key, 2);
                            break;
                        case 'hold':
                            $this->SetValue($key, 3);
                            break;
                        case 'releas':
                            $this->SetValue($key, 4);
                            break;
                        case 'changed':
                            $this->SetValue($key, 5);
                            break;
                        default:
                            $this->SetValue($key, 0);
                    }
                }
			if($Element != null 
			&& $Element->type == 1 
			&& $data->event =='down' ) // Wenn Typ=Button und Button gedrückt
			{
				if($Element->objectId != 1)
				{
					IPS_RunScript(	$Element->objectId);
					$this->SendDebug('IPS_RunScript()', $Element->objectId, 0);
				}
			}
				
            }
			
            if(property_exists($data, 'val')) {
                $key = $topic.'_value';
                if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) {
                    $this->MaintainVariable($key, $topic . " " . $this->Translate('Value'), 1, '', 10, true);
                    $this->MaintainAction($key, true);
                    $this->SetValue($key, $data->val);
                }
				if($Element != null 
				&& ($Element->type == 2|| $Element->type == 3) ) // Wenn Typ= Toggle Button und Value empfangen
				{
				if($Element->objectId != 1)
				{
					if(HasAction($Element->objectId))
					{
						RequestAction($Element->objectId,$data->val);
						$this->SendDebug('RequestAction()', $Element->objectId . " Value: ".$data->val , 0);
					}
					else
					{
						SetValue($Element->objectId,$data->val);
						$this->SendDebug('SetValue()', $Element->objectId . " Value: ".$data->val , 0);
					}
				}
				}
            }
            if(property_exists($data, 'text')) {
                $key = $topic.'_text';
                if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) {
                    $this->MaintainVariable($key, $topic . " " . $this->Translate('Text'), 3, '', 10, true);
                    $this->SetValue($key, $data->text);
                }
            }
            if(property_exists($data, 'color')) {
                $key = $topic.'_color';
                if (@$this->GetIDForIdent($key) != false || $this->ReadPropertyBoolean('AutoCreateVariable')) {
                    $this->MaintainVariable($key, $topic . " " . $this->Translate('Color'), 3, '', 10, true);
                    $this->SetValue($key, $data->color);
                }
            }
        }

        if($topic == "statusupdate") {

        }
        if($topic == "LWT") {
            switch ($data) {
                case 'online':
                    $this->SetValue("Online", 1);
                    $this->Online();
                    break;
                default:
                    $this->SetValue("Online", 0);
            }
        }
    }
	
	public function MessageSink($timestamp, $sendId, $messageID, $data)
    {
		 $this->SendDebug("MessageSink", "Message from sendId ".$sendId." with Message ".$messageID."\r\n Data: ".print_r($data, true),0);
        if ($messageID == VM_UPDATE) { // Auf aktualisierungen reagieren. 
			
			$ElementToObjectMapping= json_decode($this->ReadAttributeString("ElementToObjectMapping"));
			$elementsData = array_column($ElementToObjectMapping, 'data');
		
			$this->SendDebug("elementData",json_encode($elementsData),0);
			
			foreach ($elementsData as $Element) // alle UI-Elemente durchlaufen
			{
			if($Element->objectId != $sendId)
				continue;
			
			$this->SendDebug('FoundMapping()', json_encode($Element) ,0);
			
			if($Element->type== 2|| $Element->type==3) // Bei Toggel-Button und Slider 
			{	
				$this->SetItemValue($Element->page,$Element->id,intval($data[0])); 
			}
			if($Element->type == 0) //Bei Label 
			{	
				if($Element->caption == "")
				{
					$this->SetItemText($Element->page,$Element->id,strval($data[0])); // Bei Leerer Caption wird der Wert direkt geschrieben. 
				}
				else
				{
					$this->SetItemText($Element->page,$Element->id,sprintf($Element->caption ,($data[0]))); // sprintf %s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben 
				}
			}
			}
        }
    }

    private function Online()
    {
        $this->SendDebug('Online()', 'Gerät ist Online', 0);
        $this->RewriteDisplay();
    }

    public function RewriteDisplay()
    {
        $this->SendDebug('RewriteDisplay()', '', 0);
        if(!$this->ReadPropertyBoolean('WriteDisplayContent')) {
            return;
        }
		
        $displayheight = 480;
        $displaywidth = 480;
        $margin = 10;
        $labelHeight = 40;
        $sliderHeigh = 30;
        $buttonHeight = 70;

        $oneElementwidth = $displaywidth - (2 * $margin);
        $twoElementwidth = ($displaywidth - (3 * $margin)) / 2;
        $yStart = 0;
        $yStop = $displayheight;
        $page = 1;

        $this->SendCommand('clearpage all');

        if($this->ReadPropertyBoolean('DisplayDateTimeHeader')) {
            $yStart = $labelHeight + $margin;
			$text = json_decode('{"template":"\uE0ED %d.%m.%Y"}',true); // Wenn das Zeichen in das Array codiert ist werden die Symbole als Text angezeigt
            $this->AddJsonL(array_merge(array(	'obj' => 'label',
                                            'page' => 0,
                                            'id' => 200,
                                            'x' => $margin,
                                            'y' => 0,
                                            'h' => $labelHeight,
                                            'w' => $twoElementwidth,
                                            'align' => 0,
                                            'text_color' => '#FFFFFF'),$text));
											
			$text = json_decode('{"template":"\uE150 %H:%M"}',true); // Wenn das Zeichen in das Array codiert ist werden die Symbole als Text angezeigt
            $this->AddJsonL(array_merge(array(	'obj' => 'label',
                                            'page' => 0,
                                            'id' => 201,
                                            'x' => $displaywidth - $twoElementwidth - $margin,
                                            'y' => 0,
                                            'h' => $labelHeight,
                                            'w' => $twoElementwidth,
                                            'align' => 'right',
                                            'text_color' => '#FFFFFF'),$text));

        }
       
        if($this->ReadPropertyBoolean('DisplayPageControlFooter')) {
            $yStop = $displayheight - $margin - $buttonHeight;
			$text = json_decode('{"text":"\uE04D"}',true); // Wenn das Zeichen in das Array codiert ist werden die Symbole als Text angezeigt
            $this->AddJsonL(array_merge(array(	'obj' => 'btn',
                                            'page' => 0,
                                            'id' => 10,
                                            'x' => $margin,
                                            'y' => $displayheight - $buttonHeight,
                                            'h' => $buttonHeight,
                                            'w' => $twoElementwidth,
                                            'toggle' => false,
                                            'text_font' => 32,
                                            'action' => array('down' => 'page prev'),
                                            'mode' => 'break',
                                            'align' => 1,
                                            'text_color' => '#FFFFFF'),$text));
			
			$text = json_decode('{"text":"\uE054"}',true);
            $this->AddJsonL(array_merge(array(	'obj' => 'btn',
                                            'page' => 0,
                                            'id' => 11,
                                            'x' => $displaywidth - $twoElementwidth - $margin,
                                            'y' => $displayheight - $buttonHeight,
                                            'h' => $buttonHeight,
                                            'w' => $twoElementwidth,
                                            'toggle' => false,
                                            'text_font' => 32,
                                            'action' => array('down' => 'page next'),
                                            'mode' => 'break',
                                            'align' => 1,
                                            'text_color' => '#FFFFFF'),$text));
			// SWIPE 'jsonl {"page":0,"id":6,"obj":"obj","swipe":{"left":"page next","right":"page prev"},"x":0,"y":410,"h":70,"w":480,"opacity":1,"comment":"swipe-area-at-top"}'								
        }

        $UiElements = json_decode($this->ReadPropertyString("UiElements"), true);
        $itemcount = 1;
        $y = $yStart;
		$items=array();
	
        foreach ($UiElements as &$element) {
            $this->SendDebug('RewriteDisplay()', 'Caption: '.print_r($element, true), 0);
			
			
			
            try {
                $override = json_decode($element['OverrideParameter'], true);
            } catch (Exception $e) {
                $this->SendDebug('RewriteDisplay() Fehler', 'Element: ' .$itemcount .' Fehler in OverrideParameter: '.$element['OverrideParameter'], 0);
            }
            if ($override == null) {
                $override = array();
            }



            if($element['Type'] == 0) { //Label
                $h = $labelHeight;
                if($y + $h > $yStop) {
                    $y = $yStart;
                    $page++;
                }
                $array = array(		'obj' => 'label',
                                    'page' => $page,
                                    'id' => $itemcount,
                                    'x' => $margin,
                                    'y' => $y,
                                    'h' => $h,
                                    'w' => $oneElementwidth,
                                    'text' => $element['Caption'],
                                    'align' => 1);
				if($element['Object']!=1)
				{
				$array['text']=GetValue($element['Object']);
				}					
							
                $this->AddJsonL(array_merge($array, $override));
            }
            if($element['Type'] == 1 || ($element['Type'] == 2)) { //Button 1 /ToggleButton 2
                $h = $buttonHeight;
                if($y + $h > $yStop) {
                    $y = $yStart;
                    $page++;
                }
                $array = array(	'obj' => 'btn',
                                    'page' => $page,
                                    'id' => $itemcount,
                                    'x' => $margin,
                                    'y' => $y,
                                    'h' => $h,
                                    'w' => $oneElementwidth,
                                    'toggle' => ($element['Type'] == 2),
                                    'text' => $element['Caption'],
                                    'align' => 1);
				if($element['Type'] == 2 && $element['Object']!=1)
				{
					$array['val'] =intval(GetValue($element['Object']));
				}
				
				
                $this->AddJsonL(array_merge($array, $override));
            }

            if($element['Type'] == 3) { //Slider
                if($y + $sliderHeigh > $yStop) {
                    $y = $yStart;
                    $page++;
                }


                $h = $sliderHeigh;
                $array = array(	'obj' => 'slider',
                                    'page' => $page,
                                    'id' => $itemcount,
                                    'x' => $margin,
                                    'y' => $y,
                                    'h' => $h,
                                    'w' => $oneElementwidth
                                    );
				if($element['Object']!=1)
				{
					$array['val']=GetValue($element['Object']);
				}
                $this->AddJsonL(array_merge($array, $override));
                $y = $y + $margin; // zusätzlcher Abstand nach Slider
            }

			$items[] = Array("objkey"=>"p".$page."b".$itemcount , "data"=>  Array("page"=>$page,"id"=>$itemcount,"type"=>$element['Type'],"objectId"=>$element['Object'],"caption"=>$element['Caption']));

            $itemcount++;
            $y = $y + $h + $element['Margin'];
        }
		
		$this->SendDebug('SendCommand()', 'ElementToObjectMapping: '.json_encode($items), 0);
		$this->WriteAttributeString("ElementToObjectMapping", json_encode($items));

        $this->AddJsonL(array(	'page' => 1,
                                'id' => 0,
                                'prev' => $page));
        $this->AddJsonL(array(	'page' => $page,
                                'id' => 0,
                                'next' => 1));
        //$this->SendCommand('page 1');

    }
    private function AddJsonL(array $data)
    {
        $this->SendCommand('jsonl '.json_encode($data,JSON_UNESCAPED_SLASHES ));
    }

    public function SetItemValue(int $page, int $objectId, int $value)
    {
        $this->SendCommand('p'.$page.'b'.$objectId.'.val='.intval($value));
    }

    public function SetItemText(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["'.'p'.$page.'b'.$objectId.'.text='.$value.'"]');
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
            IPS_SetVariableProfileIcon("OpenHASP.Idle", "Hourglass");
            IPS_SetVariableProfileAssociation('OpenHASP.Idle', 0, $this->Translate('Off'), '', -1);
            IPS_SetVariableProfileAssociation('OpenHASP.Idle', 1, $this->Translate('Short'), '', -1);
            IPS_SetVariableProfileAssociation('OpenHASP.Idle', 2, $this->Translate('Long'), '', -1);
        }
        if (!IPS_VariableProfileExists('OpenHASP.BtnEvent')) {
            IPS_CreateVariableProfile('OpenHASP.BtnEvent', 1);
            IPS_SetVariableProfileText('OpenHASP.BtnEvent', '', '');
            IPS_SetVariableProfileIcon("OpenHASP.BtnEvent", "Execute");
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
