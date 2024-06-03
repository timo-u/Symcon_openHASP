<?php

declare(strict_types=1);
class openHASP extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Hostname', 'Hostname');
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
        $MQTTTopic = "hasp/" .$this->ReadPropertyString('Hostname').'/state/';
        $this->SetReceiveDataFilter('.*'.$MQTTTopic.'.*');
        $this->SendDebug('SetReceiveDataFilter()', 'Topic: '.$MQTTTopic, 0);

    }
    public function ReceiveData($JSONString)
    {

        $data = json_decode($JSONString);
        $data = $data->Payload;
        $this->SendDebug('ReceiveData()', 'data: '.$data, 0);

        $data = json_decode($data);

    }
    public function SendCommand(string $command)
    {
        $MQTTTopic = "hasp/" .$this->ReadPropertyString('Hostname').'/command/';
        $this->SendDebug('SendCommand() Topic', $MQTTTopic, 0);
        $this->SendDebug('SendCommand() Command', $command, 0);
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
        $this->SendDebug('Downlink()'.'MQTT Server', $ServerJSON, 0);
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
        $this->SendDebug('Downlink()'.'MQTT Client', $ClientJSON, 0);
        $resultClient = @$this->SendDataToParent($ClientJSON);

        return $resultServer === false && $resultClient === false;
    }

}
