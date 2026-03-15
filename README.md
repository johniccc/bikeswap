# bikeswap

## Lokální vývoj (Docker)

```bash
# Spuštění
docker compose up -d

# Reset databáze (smaže data!)
docker compose down
docker volume rm bikeswap_db_data
docker compose up -d
```

Aplikace běží na `http://localhost`.

## Nasazení na školní server

Server: `stefacja22.mp.spse-net.cz`
SFTP: `sftp.stefacja22.mp.spse-net.cz:2245` (uživatel `stefacja22`)
Webroot: `/web/`

### 1. Příprava souborů

Na serveru musí být **plochá struktura** — vše leží přímo ve složce `web/`:

```
web/
├── .env                    ← přejmenovaný .env.production
├── .htaccess               ← z public/
├── index.php               ← z public/
├── css/
│   └── style.css
├── js/
│   └── app.js
├── config/
│   ├── app.php
│   └── routes.php
├── src/
│   ├── Controllers/
│   ├── Core/
│   ├── Entity/
│   ├── Helpers/
│   ├── Middleware/
│   ├── Repository/
│   ├── Response/
│   └── Services/
├── templates/
│   ├── admin/
│   ├── auth/
│   ├── bike/
│   ├── errors/
│   ├── found/
│   ├── home/
│   ├── layouts/
│   ├── notifications/
│   ├── partials/
│   ├── profile/
│   ├── reservation/
│   └── theft/
├── storage/
│   ├── uploads/
│   │   ├── bikes/
│   │   └── reports/
│   ├── logs/
│   └── cache/
├── vendor/                 ← composer install
├── composer.json
└── composer.lock
```

### 2. Nahrání přes SFTP

```bash
sftp -P 2245 stefacja22@sftp.stefacja22.mp.spse-net.cz
```

**Nahrát tyto složky/soubory do `web/`:**

| Zdroj (lokální)    | Cíl na serveru      | Poznámka                               |
| ------------------ | ------------------- | -------------------------------------- |
| `public/.htaccess` | `web/.htaccess`     | Front controller rewrite               |
| `public/index.php` | `web/index.php`     | Vstupní bod aplikace                   |
| `public/css/`      | `web/css/`          | Styly                                  |
| `public/js/`       | `web/js/`           | JavaScript                             |
| `config/`          | `web/config/`       | Konfigurace                            |
| `src/`             | `web/src/`          | PHP kód aplikace                       |
| `templates/`       | `web/templates/`    | Šablony                                |
| `composer.json`    | `web/composer.json` | Závislosti                             |
| `composer.lock`    | `web/composer.lock` | Zamčené verze                          |
| `.env.production`  | `web/.env`          | **Přejmenovat!** Produkční konfigurace |

**Nenahrávat:**

- `.env` (lokální konfigurace)
- `.git/`, `.claude/`
- `docker/`, `docker-compose.yaml`, `Dockerfile`, `.dockerignore`
- `database/` (SQL spouštět ručně, viz krok 4)
- `README.md`
- `storage/uploads/*` (vytvoří se automaticky)

### 3. Instalace závislostí

Na serveru přes SSH (pokud je dostupné):

```bash
ssh -p 2245 stefacja22@sftp.stefacja22.mp.spse-net.cz
cd web
composer install --no-dev --optimize-autoloader
```

Pokud SSH nefunguje, nahrát celou složku `vendor/` přes SFTP.

### 4. Databáze

Importovat schéma přes phpMyAdmin nebo CLI:

```bash
mysql -h db.mp.spse-net.cz -u stefacja22 -p stefacja22_1 < database/schema.sql
```

Volitelně naplnit testovacími daty:

```bash
mysql -h db.mp.spse-net.cz -u stefacja22 -p stefacja22_1 < database/seeds.sql
```

### 5. Oprávnění složek

Složka `storage/` musí být zapisovatelná webserverem:

```bash
chmod -R 775 web/storage/
```

### 6. Ověření

1. Otevřít `https://stefacja22.mp.spse-net.cz/`
2. Zkontrolovat přihlášení, registraci
3. Ověřit že e-maily chodí (registrace, zapomenuté heslo)

## Testovací účty (seeds.sql)

| Email              | Heslo     | Role   |
| ------------------ | --------- | ------ |
| vlastnik@test.cz   | Heslo123! | user   |
| vypujcitel@test.cz | Heslo123! | user   |
| policie@test.cz    | Heslo123! | police |
| admin@test.cz      | Heslo123! | admin  |

### Dodatečné

- když jsem přesměrován na přihlášení, chci aby mě systém pak vrátil na původní stránku kam jsem chtěl jít
- chci aby byli kartičky celého kola klikatelné a přesměrovávali na detail, ne jen tlačítko detail. tlačítko rezervovat samozřejmě přesměruje na rezervaci.
- když jsem na stránce, kde se píše "načítání přihlašování", vždy vyskočí ten popup modal, ale když ho zavřu, chci aby mě to přesměrovalo zpět na homepage, ne jen na prázdnou stránku, kde je jen "načítání přihlášení". nebo nejlépe bys asi udělal, kdyby tento požadavek na přihlášení vyskakoval přes landing page, aby při zavření byla rovnou ta landing page a nemusel jsi přesměrovávat.
- proč je kolo, co je schválené na výpůjčku klasifikováno jako nedostupné v sekci sdílených kol? to nechci
- Důležité akce (zrušení rezervace apod.) by měl mít potvrzovací modal okna
- Při registraci zvýrazni nějakým způsobem důležitost zadání kontaktu (email, telefon), klidně ty bannery co jsou na přehledu nebo když se zobrazuje že je kolo odcizené přímo na stránce kola. Zdůrazni že je to pro dobro uživatele a že to umožňuje rychlejší kontakt.
- Přidej funkci zapomenuté heslo + přidej požadavky pro hesla (při registraci)
- jakmile je kolo schváleno na výpůjčku, chci aby to u něho bylo vidět (podobně jako u odcizeného) a bude mít i badge na kartě s datem odkdy dokdy.
- Projdeme celý systém posílání mailů, jelikož do toho jsme nijak nezabrousili. Budu chtít, aby skoro každá akce byla teda oznamována do mailu (v podstatě každý oznaámení), ale uživatel si může v profilu zvolit preference (že třeba nechde aby mu to chodilo). přidej jinak taky do oznámení to, že výpůjčka byla třeba zrušena
- konečně doděláme user profile, uživatel si v sekci svého profilu bude moci měnit své údaje / user preferences co se týče zpráv na mail atd.
- v profilu ani při registraci se nehlídá formát telefonu. také, nemělo by být rozděleno jméno a příjmení?
- v profilu přidej změnu hesla
- když jsem přihlášený a kouknu do sdílených kol a vidím tam to své, chci aby u něho na kartičce i v detailu bylo přesměrování ke všem rezervacím které jsou k mému danému kolu
- sdílená kola půjde taky hledat, filtrovat apod (např. dle barvy)
- v profilu přidej změnu emailu (srovnej rovnou i pořadí věcí které se tam upravují, email dej asi nad heslo, nebo prostě jak uznáš za vhodné)
- V admin panelu bych byl rád, kdyby admin mohl též uživatelům měnit údaje, i kolům / mazat je / přidávat je (prostě celý CRUD). měl by také vidět nebo mít možnost vstoupit do konverzací (všech), ostatní ho neuvidí dokud samozřejmě nenapíše (podobný badge jak policie). V admin panelu zatím nefunguje ban uživatele ani změna role (tu bych chtěl udělat stylem dropdownu), ale to oprav s přidáním všech ostatních funkcí co jsem ti v tomto bodu napsal
- Když failne captcha nebo jakkoliv failne i prostě odeslání nějakého formuláře, chci aby se informace v něm zachovali (bezpečným způsobem)
- projeď prosimtě pořádně design v celém adminu (použij frontend skills, ui ux skills a superpowers/previews skill). tady máš hlavní věci, které tam teď designově nefungují a nesedí do zbytku aplikace: sekce konverzace není mobilně responzivní (řádky přetékají mimo obrazovku), chat okno konverzace vypadá úplně jinak než všude jinde (jak kdyby nenastylovaný), akce u uživatele jsou all over the place (blokace uživatele, pak najednou změna role), prostě to by chtělo uspořádat + změna role je neintuitiví (tlačítko změna role jsem nevěděl že potvrzuje + nevěděl jsem, že tam je vůbec dropdown), a dole kola uživatele mají hrozně u sebe všechny prvky (title, počet a tlačítko přidat kolo)k
- Policie by měla vidět číslo/email vlastníka nalezeného kola pro rychlý kontakt
- Policie smí na kolo umístit Upozornění na dlouhostojící kolo. To odkazuje na veřejné parkoviště kol u hlavního nádraží pardubice, kde smí kola nepřetržitě stát maximálně 2 týdny. Policie tedy bude moci i zadat lhůtu, do které si má vlastník kolo vyzvednout, než bude odvezeno pryč. Toto bude moci dělat i admin samozřejmě
- oznámení bys mohl více kategorizovat. rozděl to na hlavní 2 skupiny: akce a zprávy. akce (např zrušení) budou na první pohled poznat (červený křízek + červený box)
- na stránce aktivní upozornění by mělo být tlačítko přidat upozornění lépe zarovnané (nejlépe asi na pravou stranu, ne takto u titlu)
- když ve formuláři pro upozornění na dlouhostojící kola zvolím kolo z nabídky, chci aby se ikona + po výběru změnila na minus a pak zase zpět
- ikonky oznámení se vůbec nezměnili (přidávám jen, že to chci u všech uživatelů, kdyby to už předtím nebylo jasné)
- Uživatel bude mít u rezervací možnost zapnout auto accepting. V tomto případě tedy uživatel bude moci i navolit, kdy bude kolo volné k výpůjčce. viděl bych to asi tak, že si bude moct zvolit pravidelnost a dny (ppodobně jak budík na mobilu) a k tomu i vybírat třeba úplně specifická data, která tam nechce zahrnout.
- 5epo přihlášení jsem byll přesměrován na notification json z nějakýho fakin důvodu
- failuje captcha (nebo takhle, banner říká suuccess ale neposunu se dál). stane se to jen jednou za čas ale i tak
- je dobrý nápad dávat barvy do dropdownu?
- domlouvali jsme se že policie bude moct řešit spory? taky, teď mi to píše že policie nemá přístup ke kolům

- Dvoufázové ověření - řekni mi svůj názor co bude nejlepší implementovat, zda telefonní sms nebo auth aplikaci (google authenticator)
- K jednotlivým uživatelům se na pozadí budou sbírat hw informace o něm (pro lepší dohledání pro admina). Tudíž budeme muset vymyslet, jak to udělat, aby to bylo v souladu s zákonem. asi postačí jen cookie banner?

- otestovat geolokaci
- emaily otestovat


### Až na konec

CELÝ PROJEKT MI DETAILNĚ VYSVĚTLI (POTŘEBUJI VĚDĚT KDE CO JE, PROČ JSOU VĚCI IMPLEMENTOVÁNY TAK JAK JSOU APOD.)
