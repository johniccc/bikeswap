# BikeSwap

Webová platforma pro registraci, sdílení a správu jízdních kol. Umožňuje vlastníkům nabízet kola k vypůjčení, spravovat rezervace, hlásit krádeže a koordinovat nálezy s policií.

**Maturitní projekt** — SPŠe Pardubice, 2025/2026
**Autor:** Jan Štefáček

## Použité technologie

| Vrstva     | Technologie                                       |
| ---------- | ------------------------------------------------- |
| Backend    | PHP 8.3 (vlastní framework, bez Laravel/Symfony)  |
| Databáze   | MySQL 8 (PDO)                                     |
| Frontend   | HTML, CSS, vanilla JavaScript                     |
| CAPTCHA    | Cloudflare Turnstile                              |
| QR kódy    | chillerlan/php-qrcode                             |
| PDF export | dompdf/dompdf                                     |
| 2FA (TOTP) | spomky-labs/otphp                                 |
| Prostředí  | Docker (Apache + MySQL)                           |

## Hlavní funkce

- **Registrace kol** — přidání kol s fotkami, QR kódem a parametry
- **Sdílení kol** — nastavení dostupnosti, vyloučená data, automatické rezervace
- **Rezervace** — vytvoření, schválení/zamítnutí vlastníkem, dispute s důkazy
- **Hlášení krádeží** — formuláře s geolokací a možností přiřazení policii
- **Nálezy** — veřejný formulář s Turnstile CAPTCHA, konverzace s vlastníkem
- **Notifikace** — in-app + e-mailové (s uživatelskými preferencemi)
- **2FA** — TOTP autentizace s recovery kódy
- **Administrace** — správa uživatelů, kol, rezervací, varování, konverzací
- **Karma systém** — hodnocení uživatelů na základě aktivit

## Uživatelské role

| Role     | Popis                                           |
| -------- | ----------------------------------------------- |
| `user`   | Běžný uživatel — vlastník nebo vypůjčitel kola  |
| `police` | Přístup do admin panelu, správa krádeží/nálezů  |
| `admin`  | Plný přístup ke správě celé platformy           |

## Architektura

Vlastní MVC framework s dependency injection kontejnerem a middleware pipeline.

```
Request → index.php → App → Router → Middleware[] → Controller → Response
```

### Struktura projektu

```
bikeswap/
├── config/
│   ├── app.php                 # Konfigurace aplikace
│   └── routes.php              # Definice rout
├── database/
│   ├── schema.sql              # Databázové schéma (20 tabulek)
│   └── seeds.sql               # Testovací data
├── docker/
│   ├── apache.conf             # Konfigurace Apache
│   └── entrypoint.sh           # Docker entrypoint
├── public/                     # Webroot (document root)
│   ├── index.php               # Vstupní bod aplikace
│   ├── .htaccess               # URL rewriting
│   ├── css/style.css           # Styly
│   └── js/                     # Klientský JavaScript
├── src/
│   ├── Controllers/            # HTTP handlery (13 + 6 admin)
│   ├── Core/                   # Jádro frameworku (App, Router, Container, Database, Session…)
│   ├── Entity/                 # Datové objekty (bez active record)
│   ├── Helpers/                # Globální helpery (e(), old(), view(), redirect()…)
│   ├── Middleware/              # Auth, Admin, CSRF, RateLimit…
│   ├── Repository/             # SQL dotazy (veškeré DB operace)
│   ├── Response/               # ViewResponse, JsonResponse, RedirectResponse, FileResponse
│   └── Services/               # Business logika (email, turnstile, QR, upload…)
├── storage/
│   └── uploads/                # Uživatelské soubory (bikes/, reports/)
├── templates/                  # PHP šablony (12 adresářů)
├── composer.json
├── docker-compose.yaml
└── Dockerfile
```

### Klíčové koncepty

- **Controllers** — přijímají HTTP request, validují vstup, volají services, vrací Response objekt
- **Services** — business logika, orchestrace repositories, odesílání e-mailů/notifikací
- **Repositories** — veškeré SQL dotazy přes PDO wrapper s prepared statements
- **Entities** — čisté datové objekty konstruované z DB řádků (bez active record)
- **Middleware** — `handle(Request): ?Response` — vrátí Response pro přerušení, `null` pro pokračování
- **DI Container** — auto-wiring přes PHP reflection, singleton registrace

## Lokální vývoj

### Požadavky

- Docker a Docker Compose

### Spuštění

```bash
docker compose up -d
```

Aplikace běží na **http://localhost**.

### Reset databáze

```bash
docker compose down
docker volume rm bikeswap_db_data
docker compose up -d
```

### Testovací účty

Po importu `database/seeds.sql`:

| Role          | E-mail                     | Heslo           |
| ------------- | -------------------------- | --------------- |
| Administrátor | `admin@admin.cz`           | `BikeSwap2026!` |
| Policie       | `policie@policie.cz`       | `BikeSwap2026!` |
| Vlastník      | `vlastnik@vlastnik.cz`     | `BikeSwap2026!` |
| Vypůjčitel    | `vypujcitel@vypujcitel.cz` | `BikeSwap2026!` |

## Nasazení

**Server:** `stefacja22.mp.spse-net.cz`
**SFTP:** `sftp.stefacja22.mp.spse-net.cz:2245` (uživatel `stefacja22`)
**Webroot:** `/web/`

### Plochá struktura

Na serveru není `public/` podsložka — obsah `public/` se nahraje přímo do `web/`:

| Lokální zdroj       | Cíl na serveru      | Poznámka                   |
| ------------------- | ------------------- | -------------------------- |
| `public/.htaccess`  | `web/.htaccess`     | URL rewriting              |
| `public/index.php`  | `web/index.php`     | Vstupní bod                |
| `public/css/`       | `web/css/`          | Styly                      |
| `public/js/`        | `web/js/`           | JavaScript                 |
| `config/`           | `web/config/`       | Konfigurace                |
| `src/`              | `web/src/`          | Zdrojový kód               |
| `templates/`        | `web/templates/`    | Šablony                    |
| `vendor/`           | `web/vendor/`       | Composer závislosti        |
| `composer.json`     | `web/composer.json` | Definice závislostí        |
| `composer.lock`     | `web/composer.lock` | Zamčené verze              |
| `.env.production`   | `web/.env`          | **Přejmenovat** na `.env`  |

**Nenahrávat:** `.env`, `.git/`, `.claude/`, `docker/`, `docker-compose.yaml`, `Dockerfile`, `database/`, `README.md`, `storage/uploads/*`

### Po nahrání

1. Importovat `database/schema.sql` a `database/seeds.sql` přes phpMyAdmin
2. Nastavit oprávnění: `chmod -R 775 web/storage/`
