# bikeswap

Cele jako docker kontejner

# 1. Zastav kontejnery

docker compose down

# 2. Smaž databázový volume (to vynutí reinicializaci)

docker volume rm bikeswap_db_data

# 3. Znovu postav a spusť

docker compose up -d

### Dodatečné

- když jsem přesměrován na přihlášení, chci aby mě systém pak vrátil na původní stránku kam jsem chtěl jít
- chci aby byli kartičky celého kola klikatelné a přesměrovávali na detail, ne jen tlačítko detail. tlačítko rezervovat samozřejmě přesměruje na rezervaci.
- když jsem na stránce, kde se píše "načítání přihlašování", vždy vyskočí ten popup modal, ale když ho zavřu, chci aby mě to přesměrovalo zpět na homepage, ne jen na prázdnou stránku, kde je jen "načítání přihlášení". nebo nejlépe bys asi udělal, kdyby tento požadavek na přihlášení vyskakoval přes landing page, aby při zavření byla rovnou ta landing page a nemusel jsi přesměrovávat.
- proč je kolo, co je schválené na výpůjčku klasifikováno jako nedostupné v sekci sdílených kol? to nechci
- Důležité akce (zrušení rezervace apod.) by měl mít potvrzovací modal okna

- Při registraci zvýrazni nějakým způsobem důležitost zadání kontaktu (email, telefon), klidně ty bannery co jsou na přehledu nebo když se zobrazuje že je kolo odcizené přímo na stránce kola.  Zdůrazni že je to pro dobro uživatele a že to umožňuje rychlejší kontakt.
- Přidej funkci zapomenuté heslo + přidej požadavky pro hesla (při registraci)
- jakmile je kolo schváleno na výpůjčku, chci aby to u něho bylo vidět (podobně jako u odcizeného) a bude mít i badge na kartě s datem odkdy dokdy.


u telefonu teď ale zmizela kolonka nepovinné.

u hesla chci aby se při splnění podmínky vyplnilo celé kolečko, nejen kružnice

kolonka zapomněli jste heslo chci pod samotné input pole heslo

- když jsem přihlášený a kouknu do sdílených kol a vidím tam to své, chci aby u něho na kartičce i v detailu bylo přesměrování ke všem rezervacím které jsou k tomuto danému kolu

- sdílená kola půjde taky hledat, filtrovat apod (např. dle barvy)
- konečně doděláme user profile, uživatel si v sekci svého profilu bude moci měnit své údaje / user preferences co se týče zpráv na mail atd.
- V admin panelu bych byl rád, kdyby admin mohl též uživatelům měnit údaje, i kolům / mazat je / přidávat je (prostě celý CRUD). měl by také vidět nebo mít možnost vstoupit do konverzací (všech), ostatní ho neuvidí dokud samozřejmě nenapíše (podobný badge jak policie). V admin panelu zatím nefunguje ban uživatele ani změna role (tu bych chtěl udělat stylem dropdownu), ale to oprav s přidáním všech ostatních funkcí co jsem ti napsal 
- Když failne captcha nebo jakkoliv failne i prostě odeslání nějakého formuláře, chci aby se informace v něm zachovali (bezpečným způsobem)
- Policie by měla vidět číslo/email vlastníka nalezeného kola pro rychlý kontakt
- Policie smí na kolo umístit Upozornění na dlouhostojící kolo. To odkazuje na veřejné parkoviště kol u hlavního nádraží pardubice, kde smí kola nepřetržitě stát maximálně 2 týdny. Policie tedy bude moci i zadat lhůtu, do které si má vlastník kolo vyzvednout, než bude odvezeno pryč. Toto bude moci dělat i admin samozřejmě
- oznámení bys mohl více kategorizovat. rozděl to na hlavní 2 skupiny: akce a zprávy. akce (např zrušení) budou na první pohled poznat (červený křízek + červený box)
- Uživatel bude mít u rezervací možnost zapnout auto accepting. V tomto případě tedy uživatel bude moci i navolit, kdy bude kolo volné k výpůjčce. viděl bych to asi tak, že si bude moct zvolit pravidelnost a dny (ppodobně jak budík na mobilu) a k tomu i vybírat třeba úplně specifická data, která tam nechce zahrnout.
- Projdeme celý systém posílání mailů, jelikož do toho jsme nijak nezabrousili. Budu chtít, aby skoro každá akce byla teda oznamována do mailu (v podstatě každý oznaámení), ale uživatel si může v profilu zvolit preference (že třeba nechde aby mu to chodilo). přidej jinak taky do oznámení to, že výpůjčka byla třeba zrušena
- Dvoufázové ověření - řekni mi svůj názor co bude nejlepší implementovat, zda telefonní sms nebo auth aplikaci (google authenticator)
- K jednotlivým uživatelům se na pozadí budou sbírat hw informace o něm (pro lepší dohledání pro admina). Tudíž budeme muset vymyslet, jak to udělat, aby to bylo v souladu s zákonem. asi postačí jen cookie banner?
- lepší geolokace


### Až na konec

CELÝ PROJEKT MI DETAILNĚ VYSVĚTLI (POTŘEBUJI VĚDĚT KDE CO JE, PROČ JSOU VĚCI IMPLEMENTOVÁNY TAK JAK JSOU APOD.)
