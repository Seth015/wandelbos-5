<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$originBody = <<<'HTML'
<p>In 1505 kreeg Jeronimus Laureyn, rentmeester van de koning en heer van Watervliet, toestemming om een nieuwe polder droog te leggen. Het was zijn zesde polder. Hij noemde dit gebied de <strong>Sint-Philippinepolder</strong>, als eerbetoon aan Filips de Schone, de toenmalige vorst van Vlaanderen.</p>

<p>Jeronimus kreeg ook toestemming om in deze polder een gesloten stad te bouwen: <strong>Philippine</strong>. In de beginperiode was Philippine bestuurlijk afhankelijk van het nabijgelegen Watervliet, eveneens door Jeronimus gesticht.</p>

<p>Rond 1500 kwam Jeronimus tijdens een overtocht op de Braakman in een zware storm terecht. Volgens de overlevering beloofde hij, zodra men veilig aan land kwam, een stad met kerk te stichten ter ere van de “sterre der hemel”. Die belofte werd een belangrijk onderdeel van de lokale geschiedenis.</p>

<p>Zijn ambitie was groot: Philippine moest uitgroeien tot een havenstad die kon concurreren met Antwerpen. Dat plan kwam niet volledig tot bloei, onder meer door het vroege overlijden van Jeronimus, de Tachtigjarige Oorlog, de verzanding van de Braakman en later de behoefte aan diepere vaargeulen.</p>

<h3>Van havenplaats naar mosselstad</h3>
<p>Tot 1952 lag Philippine direct aan open water via de Braakman. Door voortdurende verzanding was intensief onderhoud nodig. Zelfs met een spuikom en baggerwerk bleef dit een uitdaging. Met de aanleg van het <strong>Philippinekanaal</strong> (ca. 1900) bleef verbinding met de Westerschelde en Noordzee mogelijk.</p>

<p>In de 19e en vroege 20e eeuw kende Philippine een bloeiende mosselvisserij en -handel. Na de definitieve afdamming van de Braakman in 1952 verloor het dorp zijn havenfunctie. Toch bleef de identiteit behouden: Philippine groeide uit tot de bekende <strong>Mosselstad</strong>, met restaurants rond het dorpsplein die jaarlijks veel bezoekers trekken.</p>

<h3>Water, strijd en verdedigingswerken</h3>
<p>De omgeving van Philippine werd sterk gevormd door overstromingen, inpolderingen en oorlog. Na de doorbraak van de Nieuwersluis (1488) veranderde het landschap ingrijpend en groeide de Braakman uit tot een grote zeearm. Dat bracht zowel dreiging als economische kansen.</p>

<p>Tussen 1568 en 1794 ontstonden de <strong>Staats-Spaanse linies</strong> in Zeeuws-Vlaanderen en net over de grens in België: een netwerk van forten, schansen, dijken, grachten en inundatiegebieden. Door land gecontroleerd onder water te zetten, werd het terrein moeilijk begaanbaar voor vijandelijke legers.</p>

<p>Veel fortificaties waren van aarde: sneller en goedkoper aan te leggen, en vaak beter bestand tegen kanonvuur dan steen. De linies markeerden niet alleen een militaire grens, maar ook een politieke en religieuze scheidslijn in de regio.</p>

<h3>Prins Maurits en het Mauritsfort</h3>
<p>In de periode van de Tachtigjarige Oorlog was Philippine strategisch belangrijk. In 1588 werd het <strong>Mauritsfort</strong> aangelegd om de omgeving en scheepvaart te controleren. Tijdens de veldtochten rond 1600, in de aanloop naar de Slag bij Nieuwpoort, speelde dit gebied een rol in de bewegingen van Staatse troepen.</p>

<p>Nadat Philippine in 1633 in Staatse handen kwam, verloor het kleinere Mauritsfort geleidelijk zijn functie. Door stormschade en verval verdween het grotendeels uit het landschap. De sporen van wallen, oude structuren en plaatsnamen herinneren nog altijd aan deze periode.</p>

<h3>Een dorp met een gelaagd verleden</h3>
<p>Philippine ontwikkelde zich van polderproject tot vestingplaats, van haven- en vissersdorp tot culinair bekend dorp in Zeeuws-Vlaanderen (gemeente Terneuzen sinds 2003). Oude grachten, dijken, het kanaal en lokale vondsten – zoals resten van vestingmuren (stenen beren) – houden het verleden zichtbaar.</p>

<p>Dit project maakt die geschiedenis toegankelijk door verhalen, locaties en erfgoed met elkaar te verbinden.</p>
HTML;

$pdo = getPdo();
$stmt = $pdo->prepare(
    'INSERT INTO content_blocks (id, title, body)
     VALUES (:id, :title, :body)
     ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)'
);

$stmt->execute([
    ':id' => 'home_origin_title',
    ':title' => 'Over het project',
    ':body' => null,
]);

$stmt->execute([
    ':id' => 'home_origin_body',
    ':title' => null,
    ':body' => $originBody,
]);

echo "Over het project is netter gemaakt." . PHP_EOL;
