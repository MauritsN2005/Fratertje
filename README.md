# 't Fratertje

Laravel-applicatie voor vogelvereniging 't Fratertje. Op de openbare website kunnen bezoekers de contributietarieven bekijken en zich aanmelden of afmelden. In de administratie kunnen beheerders leden, lidsoorten, kweeknummers, aanvragen en facturen beheren.

## Vereisten

- PHP 8.4.1 of hoger. Het project is getest met PHP 8.5.4.
- Composer en MySQL. De gebruikte MySQL-versie was 8.0.45.
- PHP-extensies voor Laravel, waaronder `pdo_mysql`, `mbstring`, `openssl`, `curl`, `dom` en `xml`.
- Voor de SQLite-tests zijn `pdo_sqlite` en `sqlite3` nodig.

De applicatie gebruikt Laravel 13.30.1. Composer installeert de versies die in `composer.lock` staan.

## Installatie

1. Open de projectmap in een terminal en voer `composer install --no-interaction` uit.
2. Kopieer `.env.example` naar `.env`. Vul de instellingen van een **nieuwe, lege MySQL-database** in.
3. Voer `php artisan key:generate` uit.
4. Voer `php artisan migrate --seed --no-interaction` uit om de database op te bouwen en de vaste gegevens toe te voegen.
5. Start de applicatie met `php artisan serve` of gebruik Laravel Herd. Zet `APP_URL` in `.env` op het adres waarop je de applicatie opent. Bij `php artisan serve` zie je dat adres in de terminal.

De pagina’s laden `public/app.css` en `public/app.js` rechtstreeks. Voor deze interface hoef je geen Vite-build uit te voeren.

## Lokaal testaccount

Open `/login` en gebruik het lokale testaccount.

| Veld | Waarde |
| --- | --- |
| E-mailadres | `beheerder@fratertje.test` |
| Wachtwoord | `Fratertje-Demo-2026!` |

`DatabaseSeeder` maakt dit account aan in de omgevingen `local` en `testing`. Het wachtwoord wordt gehasht opgeslagen.

## Instellingen

- Zet de database-instellingen en `APP_KEY` in `.env`.
- Gebruik `APP_LOCALE=nl` voor Nederlandse meldingen.
- Zet `APP_URL` op het adres waarop je de applicatie gebruikt. Dat adres is nodig voor de ondertekende afmeldlinks.
- Gebruik `MAIL_MAILER=log` om e-mails lokaal te testen. De berichten komen in `storage/logs/laravel.log` terecht. Gebruik voor tests en demonstraties fictieve adressen, bijvoorbeeld op `example.test`.

De automatische omzetting van jeugdleden naar volwassen leden draait via de Laravel-scheduler. Start die lokaal met `php artisan schedule:work`. Je kunt de omzetting ook handmatig uitvoeren met `php artisan members:age`.

## Testen

Draai de standaardtests met SQLite in het geheugen.

```bash
php vendor/bin/pest --compact
```

Maak voor de MySQL-tests een aparte lege database `fratertje_test` aan en gebruik de instellingen uit `phpunit.mysql.xml`.

```bash
php vendor/bin/pest -c phpunit.mysql.xml --compact
```

`TestCase` weigert MySQL-testdatabases waarvan de naam niet eindigt op `_test`. Gebruik voor tests altijd een afzonderlijke database. Bij de uitgevoerde MySQL-test zijn 84 tests geslaagd.

De compatibiliteitstests controleren aanmelden, afmelden en herstellen op de bestaande databasestructuur. Gebruik daarvoor een aparte database `fratertje_compat_test` met een kopie van die structuur.

```bash
php vendor/bin/pest -c phpunit.compat.xml --compact
```

Controleer de PHP-opmaak met Pint.

```bash
php vendor/bin/pint --test
```

## Bedrijfsregels

De vereniging heeft drie lidsoorten.

| Lidsoort | Leeftijd | NBvV-lidmaatschap | Jaarlijks basistarief |
| --- | --- | --- | --- |
| Jeugdlid | Jonger dan 18 jaar | Ja | € 18 |
| Volwassen lid | Vanaf 18 jaar | Ja | € 36 |
| Gastlid | Alle leeftijden | Nee | € 18 |

Een volwassen lid wordt in de opdracht ook seniorlid of standaardlid genoemd. Jeugdleden en volwassen leden hebben een uniek NBvV-lidnummer, ook als hun lidmaatschap nog niet actief is. In de applicatie heet dit het **kweeknummer**. De opdracht gebruikt hiervoor ook het woord *ringnummer*. Een gastlid mag geen kweeknummer hebben en kan wel actief zijn.

Voor de contributie betaalt een lid het hele kalenderjaar waarin het 18 wordt nog het jeugdtarief. Dat geldt ook bij een verjaardag op 1 januari. Een tariefwijziging geldt vanaf het volgende jaar. Bestaande facturen bewaren hun oorspronkelijke bedrag en gegevens.

Bij aanmelden en afmelden telt de applicatie drie weken op bij de aanvraagdatum. Daarna neemt ze de eerste dag van de volgende maand. Bij een afmelding telt de beëindigingsmaand mee voor de terugbetaling.

- Aanmelden op 5 maart geeft een ingangsdatum van 1 april en negen contributiemaanden.
- Aanmelden op 25 maart geeft een ingangsdatum van 1 mei en acht contributiemaanden.
- Bij beëindiging per 1 juni blijven er zeven maanden over voor terugbetaling.

## Opbouw van de applicatie

- `MemberRequest` valideert de gegevens van leden en aanmeldingen.
- `MemberService` slaat het lid, adres en kweeknummer binnen één databasetransactie op en verwerkt archiveren en herstellen.
- `RequestService` verwerkt aanmeldingen die op beoordeling wachten en bevestigde afmeldingen.
- Bij het aanmaken van een factuur zorgt het event `InvoiceRequested` ervoor dat `CalculateInvoice` het bedrag berekent en opslaat.
- `MembershipDates` bevat helpers voor de maandgrens, het jeugdtariefjaar en de weergave van bedragen.
- `MembershipRequest` bewaart de naam van de aanvrager, de verklaring, de versie van de verklaring en het tijdstip van instemming in de JSON-gegevens van de aanvraag. Het formulier gebruikt geen gekwalificeerde elektronische handtekening.

De database gebruikt foreign keys en unieke voorwaarden voor adreskoppelingen en kweeknummers. MySQL-controles bewaken de lidsoortcodes en de NBvV-regel voor gastleden. Laravel controleert daarnaast of een jeugdlid of volwassen lid een bijbehorend kweeknummer heeft. Een rechtstreekse SQL-wijziging kan die laatste controle omzeilen.

## Oplevering

Deze repository bevat de broncode, migrations, seeders en tests. Gebruik `composer install` om `vendor` op te bouwen en maak je eigen `.env` aan op basis van `.env.example`.

De demonstratie is opgenomen in de bestaande lokale omgeving met fictieve gegevens. De automatische tests zijn uitgevoerd op afzonderlijke testdatabases.
