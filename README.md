# bikeswap

Cele jako docker kontejner

# 1. Zastav kontejnery

docker compose down

# 2. Smaž databázový volume (to vynutí reinicializaci)

docker volume rm bikeswap_db_data

# 3. Znovu postav a spusť

docker compose up -d

## Bug fixes / missing features

- chybějící tlačítko na stáhnutí/tisk qr kódu
- uživatelský profil (nastavení apod)
- když naskenuji kolo, chci aby u něj byla rovnou možnost rezervace (pokud je k dispozici), prostě v detailu kola, když to je k dispozici
- rezervaci ukázat u každého kola (třeba výpůjčka --> není dostupná)
- uživatel vlastnící kolo dostane při možnosti rezervovat svoje kolo popup že to nelze (ve sdílených kolech nejjlépe nebude ani tlačítko na to)
- v konverzaci ať jsou zprávy přihlášeného uživatele vždy na pravé straně (jako v messenger aplikacích)
- realtime chat
- výběr hvězdiček je naopak z nějakého důvodu (chci výběr zleva doprava), highlight je správně ale pak fill hvězdiček je špatně
- vysvětli jak je vše ochráněné proti botům (výpůjčky nechci aby mě spamovali, nálezy nechci aby mě spamovali) --> zkontrolovat turnstile
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
- mažou se uploaded files i se smazáním kola?
- k čemu je v storage cahe, logs a reports?
- datum formát dd/mm/yyyy
- jde zvolit krádež před výrobou kola :O

- šlo by aby bylo dynamicky vidět které fotky jsou nahrané ve formuláři a rovnou vybírat hlavní, odebírat přidávat?


### Dodatečné
Admin (povinně)
Napadá tě co by ještě projekt mohl potřebovat?
Role policie (PDF export)
Statistiky
Validace HTML (W3C)


### Až na konec

CELÝ PROJEKT MI DETAILNĚ VYSVĚTLI (POTŘEBUJI VĚDĚT KDE CO JE, PROČ JSOU VĚCI IMPLEMENTOVÁNY TAK JAK JSOU APOD.)


smaž celý frontend a začni prosím od začátku. sice jsme se zlepšili, ale ne o moc. zapomeň na všechno co jsme doteď o designu a UX probírali a začni fakt od úplného začátku, jak kdyby soubory v "templates" nikdy neexistovali.

Stránky veřejná kola a moje kola teď hlásí 404 error.

Využívej pluginu "frontend-design". Představuji si moderní stránku čistě ve světlém módu, která estetikou sedí do tematiky cyklistiky. Používej moderní fonty, které jsou trendy, nejlépe Google Fonts. Vymysli pro projekt logo, které je též moderní a clean. NEPOUŽÍVEJ EMOJI, chci ikonky. vyber hezkou color pallete. 

na začátku bude hlavní landing page, která seznámí uživatele s projektem. na desktopu nahoře v navce bude možnost naskenovat/nahrát qr kód nebo zadat sériové číslo kola a přihlášení / registrace. na mobilu to udělej akorát aby to bylo responzivní (burger menu ideálně) po přihlášení bude možnost přejít do dashboardu, kde na desktopu bude sidebar a vpravo kontent, aplikace je však především mobile first, kde bude bottom navigation ve které bude veprostřed button na kameru, přes kterou bude možné skenovat qr kódy, nahrát obrázek (modal už je vytvořený) nebo zadat sériové číslo kola.  

Používej zaoblené rohy, stíny na boxech, hover effekty. jednoduché animace, designové prvky, které aplikaci vylepší. pokud můžeš, přidávej i stock images na vylepšení celkového feelu. Chci aby aplikace nepůsobila dětsky, má být profesionální ale zároveň moderní. používej moderní uzpůsobení sekcí. budu velice rád když se mě na otázky ohledně designu budeš doptávat

dám ti příklad jaký effort od tebe očekávám. např. přihlášení může být vyskakovací okno, které zabluruje za sebou background. dávám jako návrh, můžeš nebo nemusíš využít

- na všech stránkách není diakritika

- na stránce odcizená kola (stolen) není na desktopu mezera mezi zobrazit detail a nahlásti nález

- pozměníme color pallete, moc se mi nelíbí

- logo v navce má mezeru mezi bike a swap, ve footeru není

-  zkus logo ikonku předělat, tady ta je strašná, klidně použij něco z internetu nebo něco jednoduššího
-  stránka na nahlášení kol je rozbitá, název kola je v boxu na celou šířku obrazovky, zbytek je pak přilepen jen k levé části a je poloviční
-  tlačítko zaregistrujte se a registrace v modal oknu přihlášení nefungují (po kliknutí se nic nestane)
- na mobilu chci aby stránka krádeží zůstala v dashboardu a nepřesměrovala pryč, to stejné stránka sdílených kol
- místo číslo rámu piš sériové číslo
- tlačítko primární na u přidávání fotek při přidávání kola není vidět a překrývá se s odstraněním fotky
- nefunguje přidávání fotek (přidám je, ale žádná se ke kolu neuloží), nejspíš se ani nemažou
- vytvoř lepší layout pro tisk qr kódu, aby to byl jen qr kód s pokyny na vystřihnutí
- jakožto přihlášený uvidím detaily odcizení, ostatní veřejně ne
- při nahlašování nálezu kola/odcizení kola je formulář bez bočního paddingu a je přitlačen k okrajům stránky, to stejné i u konverzace
- po zavření se burger menu nevrátí zpátky z křížku
- oznámení bude pro přihlášeného uživatele na každé navce (i mimo dashboard)
- dashboard by měl mít větší prioritu v rámci nav menu
- notifikace nejsou responzivní, přetýkají mimo stránku, ale překvapivě jen na straně vlastníka kola (nejspíš delší texty??)
- když něco naleznu a jsem přihlášený, tak chci mít možnost se k tomu zpátky dostat i bez unikátního odkazu (to je jen pro nepřihlášené). dej to dostupné někde na profilu nebo v dashboardu
- 500 Chyba serveru SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1 ---> toto to vyhodí po uzavření konverzace
- v oznámeních zmizí z navky profil
- při rezervaci kola je kalendář squishnutej a tlačítka předchozí a další nejsou výrazná
- chci aby i notifikace byli realtime, takže aby i uživatel dostal na jakékoliv stránce v rámci tohoto projektu takový ten popup vpravo dole, takový ten hezký
- důležité nebo current eventy (nález kola ze strany majitele, probíhající nález kola ze strany nálezce, čekání na potvrzení výpůjčky) by mohly být nějak zvýrazněny v dashboardu, třeba v přehledu bude nahoře prostě připnutá mini sekce nebo třeba někde bude probíhající sekce nebo něco
- u všech konverzací se spodek input pole dotýká okraje boxu, ikdyžž tam input není tak se spodky dotýkají
- při posílání žádosti o sdílení by měl turnstile taky verifikovat + by tam měla být též veškerá ochrana proti botům
- při dávání hvězdiček chci aby vybrané byly vyplněné
- v seznamu výpůjček bude dokončeno ale i symbol neohdnoceno aby člověk věděl že má ohodontit
- při podání námitky mám tento error SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
- při nahlášení nevrácení kola by se mělo kolo posunout do nového stavu, a to jakožto nevrácené. bude mít svoje vlastní specifika. ten kdo ho navrátil může vznést tu námitku a než se uzná, zda je opravdu vinný nebo ne, zůstavá tento "case" otevřený a kolo je stále v statusu nevrácené. o rozsudku rozhodne admin. ten co označil kolo jako nevrácené musí vyplnit text ve kterém vše vysvětluje jak se stalo, ten co nevrátil se musí obhájit. nebo máš na to jiný názor?