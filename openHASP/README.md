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
Automatische Hintergrundbeleuchtung | Schaltet die Beleuchtung im Leerlauf automatisch ab
Variablen automatisch erstellen | Dadurch werden die Variablen zu Ereignissen und Variablen automatisch erstellt. 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Leerlauf | Integer | Zeigt den Leerlaufzustand an 
Hintergrundbeleuchtung |Integer | Wert der Hintergrundbeleuchtung (Kann geschaltet werden)
Seite  |Integer | Aktuell aufgerufenen Seite (Kann geschaltet werden)
Online| Boolean | Online Status
       |         |
       |         |

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