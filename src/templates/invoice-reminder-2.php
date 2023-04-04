<?php defined('WPINC') || exit; ?>

Geachte <?php echo $patient['name']; ?>,
  
Eerder verzochten wij u om onderstaande rekening te betalen. De rekening is niet betaald. Dit betekent dat u uw verplichting niet bent nagekomen, u bent in verzuim. 
 
De betalingstermijn/reactiedatum op uw eerder verkregen aanmaning is reeds verstreken. Wij verzoeken u daarom om binnen 07 dagen <strong>het verschuldigde bedrag (<?php echo $decimalcomma($invoice['ReimburseAmount']); ?> EURO)</strong> over te maken op rekeningnummer <strong>IBAN: NL07 ABNA 0246 3566 69</strong> t.n.v. <strong>Excellent Klinieken</strong>. Vermeld bij uw betaling altijd het <strong>factuurnummer <?php echo $invoice['DeclaratieNummer']; ?></strong>. De reactiedatum is <strong><?php echo $due_date_formatted; ?></strong>.

<strong>Incassokosten</strong>

Als uw betaling niet op tijd op onze rekening staat, bent u een vergoeding verschuldigd voor de buitengerechtelijke incassokosten. Deze incassokosten zijn <?php echo $decimalcomma($exclBtw); ?> EURO.  Wij kunnen de btw over dit bedrag niet verrekenen. Dit houdt in dat het bedrag van <?php echo $decimalcomma($invoice['ReimburseAmount']); ?> wordt verhoogd met een percentage dat gelijk is aan de btw (inclusief incassokosten) als wij de vordering uit handen moeten geven. Het bedrag aan btw is <?php echo $decimalcomma($btw); ?> EURO. In dit geval wordt het totaal te vorderen bedrag uiteindelijk <?php echo $decimalcomma($invoice['ReimburseAmount'] + $btw + $exclBtw); ?> EURO.

Mocht u geen actie ondernemen dan zal uw factuur/facturen zelfwerkend overgedragen worden aan het incassobureau. Let u alstublieft hierop!!! 

Indien uw betaling deze brief heeft gekruist, kunt u deze betalingsherinnering als niet verzonden beschouwen. Denkt u dat er een misverstand is, of heeft u nog een vraag, neemt u dan contact met ons op.

Hopende u hiermee voldoende te hebben geïnformeerd. 
 
Hoogachtend,

Financiële administratie
Team Excellent Klinieken
Spuiboulevard 334
3311 GR Dordrecht
T. 085 04 10 150 keuzemenu 3
E. <a href="mailto:admin@excellentklinieken.nl">admin@excellentklinieken.nl</a>
<a href="https://www.excellentklinieken.nl">www.excellentklinieken.nl</a>

<img src="<?php echo esc_url($plugin_dir_url . 'src/assets/email-footer-logo.png'); ?>" alt="excellent klinieken" />