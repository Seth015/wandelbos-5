# TNZ Wandelbos (Merged)

Dit is de samengevoegde versie van beide projectmappen, met `tnz-wandelbos-novaminds-seth` als primaire app-map.

## Onderdelen

- `index.php` - primaire entrypoint (redirect naar `home.php`)
- `home.php` - primaire interactieve homepage (spel + quiz)
- `history.php` - interactieve projectpagina met contentblokken uit admin
- `locaties.php` - locatie-overzicht
- `locatie.php` - detailpagina per locatie (`?slug=...`)
- `admin.php` - contentbeheer voor `history.php`
- `admin_locations.php` - locatiebeheer (CRUD), afbeeldingsupload, quizvragen
- `admin_qrcodes.php` - QR-overzicht, print, lokale PNG-opslag en ZIP-download
- `db.php` en `config.php` - gedeelde database/config laag
- `database/wandelbos_schema.sql` - volledige schema voor locaties + scanlog + contentblokken

## Lokaal draaien (XAMPP)

1. Plaats de map in `htdocs`.
2. Importeer `database/wandelbos_schema.sql` in MySQL/MariaDB.
3. Configureer environment variabelen (optioneel, met defaults):
   - kopieer `.env.example` naar je eigen omgeving (of zet variabelen in Apache/vhost)
4. Installeer frontend dependencies en bouw CSS:
   - `npm install`
   - `npm run build:css`
5. Start Apache en MySQL in XAMPP.
6. Open:
   - `http://localhost/Scalda/tnz-wandelbos-novaminds/tnz-wandelbos-novaminds-seth/index.php`

## Features (samengevoegd uit alle 3 projecten)

- Dynamische routekaart (Leaflet + OpenStreetMap)
- Locatie detailpagina met historische tekst, galerij, quiz en memory-spel
- Contentblok CMS voor project/homepage teksten
- Admin locatiebeheer met veilige uploadvalidatie
- QR-code beheer: printen, lokale PNG-export en ZIP-download
- Tailwind-gebaseerde UI met Seth-kleurenpalet

## Environment variabelen

- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `ADMIN_USERNAME`
- `ADMIN_PASSWORD` (minimaal voor development)
- `ADMIN_PASSWORD_HASH` (aanbevolen voor productie)
- `APP_URL` (optioneel)
- `QR_BASE_URL` (aanbevolen voor QR-codes op telefoon, bijvoorbeeld `https://jouwdomein.nl/tnz-wandelbos-novaminds`)

### QR-codes op telefoon

- Zet `QR_BASE_URL` naar de publieke URL die je telefoon kan openen.
- Gebruik bij IP-adressen altijd punten, bijvoorbeeld `http://192.168.56.1/tnz-wandelbos-novaminds` (niet `192.168/56/1`).
- Gebruik in productie bij voorkeur `https://...`.
- Na aanpassing: open `admin_qrcodes.php` en klik op **Sla op als PNG** om lokale QR-bestanden opnieuw te genereren.


