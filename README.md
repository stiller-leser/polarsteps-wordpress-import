# Polarsteps Importer für WordPress

**Importiere deine Polarsteps-Reiseberichte als WordPress-Beiträge inkl. Bildern, Ortsdaten und originalen Veröffentlichungsdaten.**

---

## 📌 Beschreibung

Das **Polarsteps Importer**-Plugin ermöglicht es dir, deine **Polarsteps-Steps** (Reiseberichte) automatisch als WordPress-Beiträge zu importieren. Das Plugin unterstützt:

- **Automatischen Import** via WordPress-Cron (stündlich oder in einem einstellbaren Intervall).
- **Manuellen Import** per Knopfdruck.
- **Bilderimport** aus `large_thumbnail_path` als Beitragsbild.
- **Ortsdaten** (Latitude, Longitude, Ortsname, Land) als Meta-Daten.
- **Originale Veröffentlichungsdaten** der Steps.
- **Debug-Modus** zum Testen ohne Beiträge zu erstellen.
- **Verschlüsselung** des Polarsteps-Tokens für mehr Sicherheit.

---

## 📦 Installation

### 1. Plugin installieren
1. Lade das Plugin-Verzeichnis in `/wp-content/plugins/` hoch oder installiere es über das WordPress-Admin-Panel.
2. Aktiviere das Plugin im WordPress-Adminbereich unter **Plugins**.

### 2. Einstellungen konfigurieren
1. Gehe zu **Einstellungen → Polarsteps Importer**.
2. Trage deine **Trip-ID** und dein **Remember-Token** ein.
   - **Trip-ID**: Finde sie in der URL deiner Polarsteps-Reise (z. B. `https://www.polarsteps.com/[Benutzername]/[Trip-ID]`).
   - **Remember-Token**: Finde es in den Cookies deiner Polarsteps-Sitzung (z. B. mit den Entwicklertools deines Browsers).
3. Wähle das **Aktualisierungsintervall** (Standard: 1 Stunde).
4. Wähle den **Beitragsstatus** (Entwurf oder Veröffentlicht).
5. *(Optional)* Aktiviere den **Debug-Modus**, um nur Logs zu schreiben, ohne Beiträge zu erstellen.

---

## 🚀 Verwendung

### 1. Automatischer Import
- Das Plugin nutzt **WordPress-Cron**, um regelmäßig nach neuen Steps zu suchen.
- Das Intervall kannst du in den Einstellungen anpassen.

### 2. Manueller Import
- Klicke auf der Einstellungsseite auf **"Jetzt importieren"**, um den Import sofort auszuführen.

### 3. Debug-Logs anzeigen
- Die Logs findest du unter **Einstellungen → Polarsteps Logs** oder in der Datei `wp-content/debug.log`.
- **Aktiviere Debugging in WordPress**, indem du in deiner `wp-config.php` folgendes hinzufügst:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
