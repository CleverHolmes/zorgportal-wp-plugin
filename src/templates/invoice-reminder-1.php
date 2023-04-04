<?php defined('WPINC') || exit; ?>

Geachte <?php echo $patient['name']; ?>,
 
Uit onze administratie is gebleken dat bijgevoegde factuur/facturen, met factuurnummer <?php echo $invoice['DeclaratieNummer']; ?> nog niet is/zijn voldaan. Mogelijk heeft u deze factuur/facturen over het hoofd gezien. Onze intentie is om u hierop te attenderen.

Wij verzoeken u het openstaande bedrag van <strong><?php echo $decimalcomma($invoice['ReimburseAmount']); ?> EURO</strong> uiterlijk <strong><?php echo $due_date_formatted; ?></strong> te voldoen. U kunt de betaling overmaken op rekeningnummer <strong>NL07 ABNA 0246 3566 69</strong> t.n.v. <strong>Excellent Klinieken</strong>. Vermeld bij uw betaling het <strong>factuurnummer: <?php echo $invoice['DeclaratieNummer']; ?></strong> in de omschrijving van de overboeking.<br/>

Indien uw betaling deze brief heeft gekruist, kunt u deze betalingsherinnering als niet verzonden beschouwen. Denkt u dat er een misverstand is, of heeft u nog een vraag, neemt u dan contact met ons op.

 
Hopende u voldoende geïnformeerd te hebben. 

Hoogachtend,

Financiële administratie
Team Excellent Klinieken
Weizigtweg 11
3314 JJ Dordrecht
T. 085 04 10 150 keuzemenu 3
E. <a href="mailto:admin@excellentklinieken.nl">admin@excellentklinieken.nl</a>
<a href="https://www.excellentklinieken.nl">www.excellentklinieken.nl</a>
IBAN: NL07 ABNA 0246 3566 69
BIC: ABNA NL2A
 
<img src="<?php echo esc_url($plugin_dir_url . 'src/assets/email-footer-logo.png'); ?>" alt="excellent klinieken" />