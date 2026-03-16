<div class="privacy-page">
  <h1>Ochrana osobních údajů</h1>
  <p class="privacy-date">Poslední aktualizace: <?= date('d.m.Y') ?></p>

  <div class="card">
    <div class="card-body privacy-content">

      <h2>1. Správce údajů</h2>
      <p>
        Správcem osobních údajů je provozovatel platformy BikeSwap v rámci školního projektu.
        Kontakt: prostřednictvím formuláře na webu.
      </p>

      <h2>2. Jaké údaje sbíráme</h2>

      <h3>2.1 Registrační údaje</h3>
      <p>Při registraci sbíráme: jméno, příjmení, e-mailovou adresu, telefonní číslo (nepovinné) a adresu (nepovinná).</p>

      <h3>2.2 Bezpečnostní logy (oprávněný zájem)</h3>
      <p>
        Pro zajištění bezpečnosti platformy automaticky zaznamenáváme:
      </p>
      <ul>
        <li>IP adresu</li>
        <li>User-Agent (identifikátor prohlížeče)</li>
        <li>Typ akce (přihlášení, odhlášení, změny profilu, administrátorské akce)</li>
        <li>Datum a čas akce</li>
      </ul>
      <p>
        Tyto údaje zpracováváme na základě <strong>oprávněného zájmu</strong> (čl. 6 odst. 1 písm. f) GDPR)
        za účelem ochrany před neoprávněným přístupem a zneužitím platformy.
        Souhlas s cookies není pro toto zpracování vyžadován.
      </p>

      <h3>2.3 Informace o zařízení (se souhlasem)</h3>
      <p>
        Pokud udělíte souhlas kliknutím na „Přijmout vše" v banneru cookies, sbíráme doplňkové informace o vašem zařízení:
      </p>
      <ul>
        <li>Rozlišení obrazovky</li>
        <li>Časové pásmo</li>
        <li>Jazyk prohlížeče</li>
        <li>Platforma (operační systém)</li>
        <li>Hloubka barev</li>
        <li>Podpora dotykového ovládání</li>
        <li>Nastavení Do Not Track</li>
      </ul>
      <p>
        Tyto informace zpracováváme na základě <strong>vašeho souhlasu</strong> (čl. 6 odst. 1 písm. a) GDPR).
        Slouží k lepší identifikaci zařízení pro administrátory při řešení sporů a bezpečnostních incidentů.
      </p>

      <h2>3. Cookies</h2>

      <h3>Nezbytné cookies</h3>
      <table>
        <thead>
          <tr>
            <th>Název</th>
            <th>Účel</th>
            <th>Doba trvání</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>PHPSESSID</code></td>
            <td>Identifikace uživatelské relace</td>
            <td>Do zavření prohlížeče</td>
          </tr>
          <tr>
            <td><code>cookie_consent</code></td>
            <td>Zapamatování volby souhlasu s cookies</td>
            <td>365 dní</td>
          </tr>
        </tbody>
      </table>

      <h3>Analytické cookies (vyžadují souhlas)</h3>
      <p>
        Pokud zvolíte „Přijmout vše", povolíte sběr informací o zařízení popsaný v bodě 2.3.
        Tyto informace se ukládají na server a nejsou sdíleny s třetími stranami.
      </p>

      <h2>4. Doba uchování údajů</h2>
      <ul>
        <li><strong>Registrační údaje:</strong> po dobu existence účtu</li>
        <li><strong>Bezpečnostní logy:</strong> 1 rok od vytvoření záznamu</li>
        <li><strong>Informace o zařízení:</strong> 1 rok od posledního přístupu</li>
        <li><strong>Záznamy o souhlasu:</strong> 3 roky (pro prokázání souhlasu dle GDPR)</li>
      </ul>

      <h2>5. Vaše práva</h2>
      <p>Máte právo:</p>
      <ul>
        <li>Na přístup k vašim osobním údajům</li>
        <li>Na opravu nepřesných údajů</li>
        <li>Na výmaz údajů („právo být zapomenut")</li>
        <li>Na omezení zpracování</li>
        <li>Na přenositelnost údajů</li>
        <li>Odvolat souhlas se zpracováním (kdykoli, bez vlivu na zákonnost předchozího zpracování)</li>
      </ul>
      <p>
        Souhlas s cookies můžete kdykoli odvolat kliknutím na tlačítko níže.
        Po odvolání se vám znovu zobrazí banner pro výběr nastavení cookies.
      </p>

      <h2>6. Zabezpečení údajů</h2>
      <p>
        Osobní údaje jsou chráněny šifrovaným připojením (HTTPS), hashovanými hesly (bcrypt)
        a přístup k nim je omezen pouze na oprávněné administrátory platformy.
      </p>

      <div class="privacy-consent-reset">
        <h2>7. Změna nastavení cookies</h2>
        <p>Kliknutím na tlačítko odvoláte svůj aktuální souhlas a budete moci zvolit nové nastavení.</p>
        <button type="button" class="btn btn-primary" id="reset-cookie-consent">
          <i data-lucide="settings"></i> Změnit nastavení cookies
        </button>
      </div>

    </div>
  </div>
</div>
