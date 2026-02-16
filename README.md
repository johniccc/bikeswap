# bikeswap

Cele jako docker kontejner

TODO: otestovat teď, admin panel

# 1. Zastav kontejnery

docker compose down

# 2. Smaž databázový volume (to vynutí reinicializaci)

docker volume rm bikeswap_db_data

# 3. Znovu postav a spusť

docker compose up -d

- tlačítko na stáhnutí/tisk qr kódu
- uživatelský profil (nastavení apod)
- když naskenuji kolo, chci aby u něj byla rovnou možnost rezervace (pokud je k dispozici), prostě v detailu kola, když to je k dispozici
- rezervaci ukázat u každého kola (třeba výpůjčka --> není dostupná)
- uživatel vlastnící kolo dostane při možnosti rezervovat svoje kolo popup že to nelze (ve sdílených kolech nejjlépe nebude ani tlačítko na to)
- v konverzaci ať jsou zprávy přihlášeného uživatele vždy na pravé straně (jako v messenger aplikacích)
- realtime chat
- výběr hvězdiček je naopak z nějakého důvodu (chci výběr zleva doprava), highlight je správně ale pak fill hvězdiček je špatně
- vysvětli jak je vše ochráněné proti botům (výpůjčky nechci aby mě spamovali, nálezy nechci aby mě spamovali)
- pokud má uživatel nová oznámení popup jim to oznámí
- integrovaný foťák
- zrušení výpůjčky těsně před termínem sníží majiteli/půjčiteli karmu
- uživatel, který má už na daný termín neschválenou žádost o půjčku nemůže znovu žádat na stejný termín
- proč má vlastník možnost zamítnou a zároveň zavřít rezervaci?
- vylepšíme celý flow nevrácení půjčeného kola (navrhni ty, ale asi bych automaticky kola zapsal jako ztracené), zločinec dostane možnost se ohradit pokud se jedná pouze o omyl, bude ručně prověřen. Jinak dostane ban
- ověř zda policie eviduje případy dle čísel (kolonka číslo případu při nahlášení krádeže)
- když jsem přihlášený a jsem nálezce kola, ke konverzaci se dostanu i skrz můj profil, ne jen přes odkaz (jako nepřihlášený). nebo v tuto chvíli to píše zprávu "Uložte si odkaz na tuto stránku — je to váš jediný přístup ke konverzaci."
- zkontroluj zda karma systém funguje i pro nálezy
- při komunikacích zajisti konzistenci informací které o tom druhém vidím (když je uživatel nepřihlášený, je to jen anonym a nevidím o něm nic, jinak vidím jméno/nikcname přihlášeného)
- možnost uzavření konverzace při krádeži/nálezu
- historii konverzací o nálezech uvidím na profilu
- nepřihlášenému uživateli bude při naskenování nabídnuta registrace pro jednodušší vyřešení, poté bude přesměrován zpátky (tohle asi udělej nějak obecně, aby při jakékoliv akci mohl být lehce redirectován zpět, třeba když je přesměrován na login tak aby byl pak zase přesměrován zpět)