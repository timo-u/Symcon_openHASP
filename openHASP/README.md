# openHASP
Beschreibung des Moduls.

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

- IP-Symcon ab Version 6.
- openHASP-Display

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
         |

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
       |         |
       |         |

#### Profile

Name   | Typ
------ | -------
       |
       |

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

### 7. PHP-Befehlsreferenz

`boolean OHASP_Restart(integer $InstanzID);`
Die Funktion startet das Display neu. 

Beispiel:
`OHASP_Restart(12345);`


`boolean SendCommand(integer $InstanzID, string $Command);`
Senden eines Kommandos an das Display

Beispiel:
`SendCommand(12345,'restart');`