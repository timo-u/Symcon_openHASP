# openHASP
Das Modul stellt die Verbinung mit einem openHASP-Display über MQTT bereit. 

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

die Instanz openHASP verbidet sich mit einem openHASP Display via MQTT

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- openHASP-Display [openHASP](https://openhasp.com/)

### 3. Software-Installation

* Über den Module Store das 'openHASP'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: [URL](https://github.com/timo-u/Symcon_openHASP)

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'openHASP'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Hostname | Name des Displays zur Identifikation
Hintergrundbeleuchtung automatisch dimmen | Dimmt die Beleuchtung im kurzen Leerlauf (idle->short) auf ca 20% ab 
Hintergrundbeleuchtung automatisch abschalten| Schaltet die Beleuchtung im Leerlauf (idle->long) automatisch ab
Variablen automatisch erstellen | Dadurch werden die Variablen zu Ereignissen und Variablen automatisch erstellt. 
Display Inhalt schreiben | Entfernt die Elemente auf dem Display und erstellt die nachfolgen UI_Elemente selbstständig. 
Ui-Elemente | Liste aus Elementen, die auf dem Displayangezeigt werden sollen. 
Datum und Uhrzeit im Header anzeigen | Blendet auf allen Seiten das Datum und die Uhrzeit ein
Buttons zum Umblättern anzeigen | Blendet die Buttons zum Umblättern am unteren Bildrand ein. 


__Ui-Elemente__:

#### Typ
Legt den Typ des UI-Elements fest, der angezeigt werden soll.

#### Beschriftung
Setzt die beschriftung des Labels oder des Buttons. 
* Ein Label kann eine Farbe im Text enthalten z.B. '#32C9AC Symcon'
* Ein Label kann mit Platzhaltern arbeiten, in der die nachfolgende Variable, die als Objekt ausgewählt ist eingefügt wird. Beispeil 'Temp: %s Grad'  (%s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben [PHP sprintf](https://www.php.net/manual/en/function.sprintf.php))

#### Parameter überschreiben
Überschreibt die automatisch generierten Parameter der UI-Elemente. Der Inhalt muss in JSON-Form geschrieben werden. z.B. '{"text_font":50,"h":60}'
[Liste der Objektparameteter](https://openhasp.com/0.7.0/design/objects/)

#### Abstand
Der Abstand zum Beginn des unteren Objekts.
Der Abstand kann auch negativ sein, um das nachfolgene Objekt auf die gleiche Höhe zu bringen. 

#### Objekt 
Der Objekttyp ist abhängig vom ausgewählten Typ des UI-Elements! 

UI-Element-Typ     | Objekttyp | Beschreibung
-------- | ------------------ | ------------------
Label | String, Integer, Float  | die Beschriftung kann Variablen nutzen. z.B. 'Temp: %s Grad' 
Button | Skript  | Das Script wird beim Drücken des Buttons ausgeführt 
Toggel Button | Boolean  | Der Wert wird sofern vorhanden über eine RequestAction geschaltet. Sofern keine RequestAction verfügbar ist wird die Variable direkt geschaltet. 
Slider | Integer  | Die Min- und Max-Werte werden über "Parameter überschreiben" gesetzt 


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Leerlauf | Integer | Zeigt den Leerlaufzustand an 
Hintergrundbeleuchtung |Integer | Wert der Hintergrundbeleuchtung (Kann geschaltet werden)
Seite  |Integer | Aktuell aufgerufenen Seite (Kann geschaltet werden)
Online| Boolean | Online Status


#### Profile

Name   | Typ
------ | -------
OpenHASP.Idle  | Integer
OpenHASP.BtnEvent | Integer
OpenHASP.Online | Boolean

### 6. WebFront

z.Z. Keine 

### 7. PHP-Befehlsreferenz

`boolean OHASP_Restart(integer $InstanzID);`
Die Funktion startet das Display neu. 

Beispiel:
`OHASP_Restart(12345);`


`boolean SendCommand(integer $InstanzID, string $Command);`
Senden eines Kommandos an das Display

Beispiel:
`SendCommand(12345,'restart');`


`boolean SetItemText(integer $InstanzID, int $page, int $objectId, string $value);
Setzt den Text eines UI-Elements

Beispiel:
`SetItemText(12345,1,5,'Licht Aus');`


`boolean SetItemValue(integer $InstanzID, int $page, int $objectId, int $value);`
Setzt den Wert eines Toggel Buttons oder Sliders

Beispiel Slider auf 50:
`SetItemValue(12345,1,5,50);`\

Beispiel Toggel Button auf "Ein":
`SetItemValue(12345,1,5,intval(true));`

