# 't Fratertje

Laravel-applicatie voor de ledenadministratie van vogelvereniging 't Fratertje. De openbare website bevat de contributietarieven en formulieren voor aanmelden en afmelden. In de administratie kunnen leden, lidsoorten, kweeknummers, aanvragen en facturen worden beheerd.

## Vereisten

- Gebruik PHP 8.5.4. Met die versie is het project getest.
- Laravel 13.30.1, zoals vastgelegd in composer.lock.
- MySQL 8.0.45 en Composer.
- De gebruikelijke PHP-extensies voor Laravel, waaronder pdo_mysql, mbstring, openssl, curl, dom en xml.
- Voor de SQLite-tests: pdo_sqlite en sqlite3.

De bestaande Laravel-versie is behouden. Of Laravel 13 door de opleiding wordt ondersteund, moet nog worden afgestemd.

## Installatie in een nieuwe omgeving

1. Pak de applicatie uit en voer `composer install --no-interaction` uit.
2. Kopieer `.env.example` naar `.env` en vul de instellingen van een nieuwe lege MySQL-database in.
3. Voer `php artisan key:generate` uit.
4. Voer `php artisan migrate --seed --no-interaction` uit.
5. Start de applicatie met `php artisan serve` of gebruik Laravel Herd. Zet `APP_URL` in `.env` gelijk aan het adres dat je in de browser opent. Bij `php artisan serve` staat dat adres in de terminal.

De projectmap op de huidige computer is `C:\laravel\fratertje_pgb09`. De bijbehorende website is `http://fratertje_pgb09.test`. De bestaande `.env` is behouden. Tijdens de demonstratie wordt deze echte lokale omgeving gebruikt.

De database heeft zes bestaande migrations. Er zijn geen extra tabellen, kolommen of triggers nodig voor deze versie. De instemmingsverklaring wordt opgeslagen in de JSON-gegevens van de aanvraag.

De views laden `public/app.css` en `public/app.js` rechtstreeks. Voor de huidige interface is geen Vite-build nodig.

## Lokaal testaccount

- E-mailadres: `beheerder@fratertje.test`
- Wachtwoord: `Fratertje-Demo-2026!`

Dit account is uitsluitend bedoeld voor lokaal testen. DatabaseSeeder maakt het alleen in de omgevingen local en testing. Wachtwoorden worden gehasht opgeslagen.

## Instellingen

Database-instellingen en APP_KEY staan in `.env`. Gebruik `APP_LOCALE=nl` voor de Nederlandse meldingen. `APP_URL` moet overeenkomen met het adres waarop de website wordt geopend, omdat afmeldlinks worden ondertekend.

Voor lokale e-mail wordt `MAIL_MAILER=log` gebruikt. Berichten staan dan in `storage/logs/laravel.log` en worden niet naar een echte ontvanger verstuurd. Gebruik bij demonstraties alleen fictieve adressen, bijvoorbeeld op `example.test`.

De automatische omzetting van jeugd naar volwassen lid draait via de scheduler. Start die lokaal met `php artisan schedule:work`. Het commando kan ook handmatig worden uitgevoerd met `php artisan members:age`.

## Testen

```powershell
php vendor/bin/pest --compact
php vendor/bin/pest -c phpunit.mysql.xml --compact
```

De eerste opdracht gebruikt SQLite in het geheugen. Voor de tweede is een aparte MySQL-database `fratertje_test` nodig. De testinstellingen mogen nooit naar de database van de gebruikte website verwijzen. TestCase weigert MySQL-testdatabases waarvan de naam niet eindigt op `_test`.

De extra tests in `tests/Compatibility` controleren aanmelden, afmelden en herstel op de bestaande databasestructuur. Ze hebben een afzonderlijke database met de naam `fratertje_compat_test` nodig. Het bijbehorende profiel is `phpunit.compat.xml`.

Controle van de PHP-opmaak:

```powershell
php vendor/bin/pint --test
```

## Bedrijfsregels

Er zijn precies drie lidsoorten:

- Jeugdlid: jonger dan 18 jaar.
- Volwassen lid: vanaf 18 jaar. Senior- en standaardlid betekenen hetzelfde.
- Gastlid: alle leeftijden, zonder NBvV-lidmaatschap.

Jeugd- en volwassen leden hebben altijd een uniek NBvV-lidnummer/kweeknummer, ook als hun lidmaatschap nog niet actief is. Een gastlid mag geen kweeknummer hebben. Een actief gastlid zonder nummer is dus geldig. Met ringnummer wordt in deze opdracht hetzelfde NBvV-/kweeknummer bedoeld.

De leeftijd bepaalt de lidsoort. Voor contributie geldt een andere regel: in het hele kalenderjaar waarin iemand 18 wordt, blijft het jeugdtarief gelden. Dit geldt ook voor een verjaardag op 1 januari.

De jaarlijkse basisprijzen zijn 36 euro voor volwassen leden en 18 euro voor jeugd- en gastleden. Een tariefwijziging via beheer geldt vanaf het volgende jaar. Bestaande facturen bewaren hun bedrag en een momentopname van de gegevens.

Voor aanmelden en afmelden geldt de bevestigde termijn: tel drie weken op en neem daarna de eerste dag van de volgende maand. De einddatum is exclusief. Voorbeelden: 5 maart wordt 1 april, met negen contributiemaanden; 25 maart wordt 1 mei, met acht maanden. Vanaf 1 juni zijn er zeven maanden over.

## Belangrijkste onderdelen

- `MemberRequest` valideert de leden- en aanmeldformulieren.
- `MemberService` slaat lid, adres en nummer binnen één transactie op en regelt archiveren en herstellen.
- `RequestService` verwerkt aanmeldingen uit quarantaine en bevestigde afmeldingen.
- `InvoiceRequested` wordt verzonden bij het aanmaken van een factuur. `CalculateInvoice` berekent en bewaart het bedrag.
- `MembershipDates` bevat helpers voor de maandgrens, het jeugdtariefjaar en geldnotatie.
- `MembershipRequest` bewaart naam, tekst, versie en tijdstip van de digitale instemming. Het formulier gebruikt geen gekwalificeerde elektronische handtekening.

De database bewaakt foreign keys, unieke adressenkoppelingen en unieke nummers. De MySQL CHECKs bewaken de soortcodes en de NBvV-uitzondering voor gastleden. Aanvullende samenhang wordt gecontroleerd door Laravel-validatie en de services. Rechtstreekse SQL buiten de applicatie om kan sommige controles omzeilen, zoals het aanmaken van een lid zonder bijbehorende nummerrecord.

## Oplevering

Bij de applicatie horen het verslag, het ERD, de controletabel, de testresultaten, de MySQL-export met fictieve gegevens en de demonstratievideo. Deze opleverbestanden worden apart van de broncode geleverd. De actuele status en eventuele ontbrekende onderdelen staan in de controletabel.

De bronexport bevat geen vendor, ingevulde `.env`, sessies, logs of private back-ups. De gebruikte databasestructuur blijft gelijk aan de bestaande structuur. Geautomatiseerde tests draaien alleen op afzonderlijke testdatabases. Voor de demonstratie zijn fictieve gegevens gebruikt en is vooraf een volledige back-up gemaakt.
