<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partners.php';

// Probeer content-blocks uit de database te halen
$content = [];
try {
    $content = getContentBlocks();
} catch (Throwable $e) {
    $content = [];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$heroTitle        = contentTitle($content, 'home_hero_title', 'Ontdek de Geschiedenis op een Interactieve Manier');
$heroSubtitle     = contentBody($content, 'home_hero_subtitle', 'Duik in het verleden met een meer interactieve leerervaring. Speel educatieve spellen en test je kennis met uitdagende quizzes over belangrijke historische gebeurtenissen.');
$heroCtaGame      = contentBody($content, 'home_hero_cta_game', 'Speel het Spel');
$heroCtaQuiz      = contentBody($content, 'home_hero_cta_quiz', 'Doe de Quiz');

$originTitle      = contentTitle($content, 'home_origin_title', 'Hoe is dit project ontstaan?');
$originBody       = contentBody($content, 'home_origin_body', 'Dit project is ontstaan uit de behoefte om geschiedenis toegankelijker en boeiender te maken voor een breed publiek.');
$originImagesRaw  = contentBody($content, 'home_origin_images', '');

$originImages = [];
$originImageLines = preg_split('/\R+/', $originImagesRaw) ?: [];

foreach ($originImageLines as $originImageLine) {
    $imageUrl = trim((string) $originImageLine);

    if ($imageUrl !== '') {
        $originImages[] = $imageUrl;
    }
}

$whyTitle         = contentTitle($content, 'home_why_title', 'Waarom dit project?');

$whyEducTitle     = contentTitle($content, 'home_why_educatief_title', 'Educatief');
$whyEducBody      = contentBody($content, 'home_why_educatief_body', 'Leer op een gestructureerde manier over belangrijke historische gebeurtenissen.');

$whyInterTitle    = contentTitle($content, 'home_why_interactief_title', 'Interactief');
$whyInterBody     = contentBody($content, 'home_why_interactief_body', 'Geen passief lezen meer. Doe mee met spellen, quizzes en interactieve opdrachten die je actief bij de stof betrekken.');

$whyAllTitle      = contentTitle($content, 'home_why_alle_leeftijden_title', 'Voor alle leeftijden');
$whyAllBody       = contentBody($content, 'home_why_alle_leeftijden_body', 'Of je nu scholier bent, docent of gewoon geïnteresseerd in geschiedenis: dit platform biedt voor iedereen een leuke manier om kennis te maken met het verleden.');

$footerTitle      = contentTitle($content, 'home_footer_title', 'Geschiedenis Project');
$footerBody       = contentBody($content, 'home_footer_body', 'Ontdek geschiedenis op een innovatieve en interactieve manier. Combineer spel, quiz en verdieping in één platform.');

$footerContactLbl = contentTitle($content, 'home_footer_contact', 'Contact');
$footerContactVal = contentBody($content, 'home_footer_contact', 'info@geschiedenisproject.nl');

$footerAddressLbl = contentTitle($content, 'home_footer_address', 'Adres');
$footerAddressVal = contentBody($content, 'home_footer_address', 'Amsterdam, Nederland');

$footerPhoneLbl   = contentTitle($content, 'home_footer_phone', 'Telefoon');
$footerPhoneVal   = contentBody($content, 'home_footer_phone', '+31 (0)20 123 4567');

$footerFollowLbl  = contentTitle($content, 'home_footer_follow', 'Volg ons');
$footerFollowVal  = contentBody($content, 'home_footer_follow', 'Facebook · Instagram · Twitter');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($heroTitle); ?> | Geschiedenis Project</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="seth-theme history-page min-h-screen flex flex-col bg-[#f5ecd8] text-[#2b2114] antialiased">
    <!-- Navigatie -->
    <header class="company-header border-b border-slate-300 px-4 py-3 sm:px-6">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold tracking-wide uppercase">TNZ Wandelbos</p>
                <p class="text-[11px] uppercase tracking-wider opacity-85">Historische route met interactieve locaties</p>
            </div>
            <nav class="flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wider">
                <a href="home.php" class="company-link">Start</a>
                <a href="locaties.php" class="company-link">Locaties</a>
                <a href="admin.php" class="company-link">Beheer</a>
                <a href="history.php#over" class="company-link">Over het project</a>
                <a href="history.php#spel" class="company-link">Spel</a>
                <a href="history.php#quiz" class="company-link">Quiz</a>
                <a href="history.php#contact" class="company-link">Contact</a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <!-- Hero sectie -->
        <section class="relative overflow-hidden border-y border-[#c29c6b]/70 bg-gradient-to-b from-[#f7ebd1] via-[#f3e2bf] to-[#e7d3a3] text-[#2b2114]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                <div class="max-w-2xl">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-widest uppercase">
                        <?php echo e($heroTitle); ?>
                    </h1>
                    <p class="mt-4 text-base sm:text-lg text-[#4a3520] leading-relaxed">
                        <?php echo $heroSubtitle; ?>
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#spel"
                           class="inline-flex items-center justify-center rounded-full bg-[#8b1e1e] px-8 py-3 text-sm font-semibold tracking-wide uppercase text-[#fdf5e6] shadow-md shadow-[#5c1515]/40 hover:bg-[#aa2525] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#8b1e1e] focus:ring-offset-[#f3e2bf] transition">
                            <?php echo e($heroCtaGame); ?>
                        </a>
                        <a href="#quiz"
                           class="inline-flex items-center justify-center rounded-full border border-[#6f4a1a] bg-transparent px-8 py-3 text-sm font-semibold tracking-wide uppercase text-[#3b2a1a] hover:bg-[#f3e2bf]/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#6f4a1a] focus:ring-offset-[#f3e2bf] transition">
                            <?php echo e($heroCtaQuiz); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hoe is dit project ontstaan -->
        <section id="over" class="py-16 sm:py-20 bg-[#f3e2bf]/40">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#2b1b0f] text-center tracking-[0.2em] uppercase">
                    <?php echo e($originTitle); ?>
                </h2>
                <div class="mt-10 grid gap-10 md:grid-cols-2 md:items-center">
                    <div class="text-sm sm:text-base leading-relaxed text-[#4a3520] space-y-4">
                        <?php echo $originBody; ?>
                    </div>
                    <div class="flex justify-center">
                        <?php if ($originImages !== []): ?>
                            <div class="w-full grid gap-3 sm:grid-cols-2">
                                <?php foreach ($originImages as $originImage): ?>
                                    <div class="rounded-xl border border-[#c29c6b] bg-[#f7ebd1] p-2 shadow-[0_0_30px_rgba(0,0,0,0.07)]">
                                        <img src="<?php echo e($originImage); ?>" alt="Over het project" class="h-44 w-full rounded-lg object-cover" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="w-full max-w-md aspect-[4/3] rounded-xl border border-dashed border-[#c29c6b] bg-[#f7ebd1] flex items-center justify-center text-center px-6 shadow-[0_0_40px_rgba(0,0,0,0.08)]">
                                <span class="text-sm text-[#7a5a37]">
                                    Voeg foto's toe via Beheer → Content → Over het project
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Interactief spel op leeftijdsniveaus -->
        <section id="spel" class="py-16 sm:py-20 bg-[#f8f0dd] border-y border-[#d0b079]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#2b1b0f] text-center tracking-[0.18em] uppercase">
                    Interactief geschiedenis&nbsp;spel
                </h2>
                <p class="mt-3 text-sm sm:text-base text-[#5b4631] text-center max-w-2xl mx-auto">
                    Kies je leeftijd, speel het spel op jouw niveau en ontdek stap voor stap welke gebeurtenissen wanneer in de geschiedenis plaatsvonden.
                </p>

                <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] items-start">
                    <!-- Spel: dynamische vragen op basis van leeftijd -->
                    <div class="bg-[#f5e4c2] rounded-xl shadow-sm border border-[#d0b079]/70 px-5 sm:px-6 py-6 sm:py-7">
                        <div class="border-b border-[#d0b079]/60 pb-5 mb-5">
                            <h3 class="text-base sm:text-lg font-semibold text-[#2b1b0f]">
                                Speel op jouw niveau
                            </h3>
                            <p class="mt-2 text-sm text-[#5b4631]">
                                Vul je leeftijd in. We kiezen automatisch vragen die passen bij jouw niveau, van basis tot expert.
                            </p>
                            <div class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-center">
                                <div class="flex-1">
                                    <label for="player-age" class="block text-xs font-semibold tracking-wide uppercase text-[#6b4b16]">
                                        Leeftijd
                                    </label>
                                    <input
                                        id="player-age"
                                        type="number"
                                        min="6"
                                        max="99"
                                        value="12"
                                        class="mt-1 w-full rounded-full border border-[#c29c6b] bg-[#f8f0dd] px-4 py-2 text-sm text-[#2b1b0f] shadow-inner focus:outline-none focus:ring-2 focus:ring-[#8b1e1e] focus:border-[#8b1e1e]"
                                    >
                                </div>
                                <button
                                    id="start-game"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full bg-[#8b1e1e] px-6 py-2.5 text-xs sm:text-sm font-semibold tracking-wide uppercase text-[#fdf5e6] shadow-md shadow-[#5c1515]/40 hover:bg-[#aa2525] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#8b1e1e] focus:ring-offset-[#f5e4c2] transition"
                                >
                                    Start het spel
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-[#7a5a37]">
                                We gebruiken je leeftijd alleen om het niveau van de vragen te bepalen.
                            </p>
                        </div>

                        <div id="history-game" class="space-y-4 hidden">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <span id="game-level-label" class="inline-flex items-center rounded-full bg-[#2b1b0f] text-[#fcefdc] px-4 py-1.5 text-[11px] font-semibold tracking-wide uppercase">
                                    Niveau
                                </span>
                                <span id="game-progress" class="text-xs font-medium text-[#6b4b16]">
                                    Vraag 1 van 1
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[#e3d0aa]/70">
                                <div id="game-progress-bar" class="h-full w-0 rounded-full bg-[#8b1e1e] transition-[width] duration-300"></div>
                            </div>

                            <div class="bg-[#f8f0dd] border border-[#d0b079]/70 rounded-lg px-4 sm:px-5 py-3.5 sm:py-4 shadow-sm">
                                <p id="game-question" class="text-sm sm:text-base font-semibold text-[#2b1b0f] leading-relaxed">
                                    Vraag verschijnt hier.
                                </p>
                                <p id="game-hint" class="mt-1 text-xs text-[#7a5a37] italic hidden"></p>
                            </div>

                            <div id="game-answers" class="space-y-3">
                                <!-- Antwoorden worden dynamisch ingevuld -->
                            </div>

                            <p id="game-feedback" class="text-sm font-medium text-[#6b4b16] min-h-[1.5rem]" aria-live="polite">
                            </p>

                            <div id="game-summary" class="hidden border-t border-[#d0b079]/60 pt-4 mt-2">
                                <p id="game-summary-text" class="text-sm text-[#5b4631]"></p>
                                <div id="game-review" class="mt-3 space-y-2 text-xs sm:text-sm text-[#5b4631] hidden"></div>
                                <button
                                    id="game-retry"
                                    type="button"
                                    class="mt-3 inline-flex items-center justify-center rounded-full border border-[#6f4a1a] bg-transparent px-5 py-2 text-xs sm:text-sm font-semibold tracking-wide uppercase text-[#3b2a1a] hover:bg-[#f3e2bf]/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#6f4a1a] focus:ring-offset-[#f5e4c2] transition"
                                >
                                    Speel opnieuw met een ander niveau
                                </button>
                            </div>
                        </div>

                        <div id="history-game-placeholder" class="text-sm text-[#5b4631]">
                            Kies eerst je leeftijd en klik op <span class="font-semibold">"Start het spel"</span> om te beginnen.
                        </div>
                    </div>

                    <!-- Toelichting niveaus -->
                    <aside class="bg-[#f5e4c2]/70 rounded-xl border border-dashed border-[#d0b079]/80 px-5 sm:px-6 py-6 sm:py-7">
                        <h3 class="text-base sm:text-lg font-semibold text-[#2b1b0f] mb-3">
                            Niveaus op basis van leeftijd
                        </h3>
                        <ul class="space-y-3 text-sm text-[#5b4631]">
                            <li>
                                <span class="font-semibold text-[#2b1b0f]">Niveau 1 – Basis (± 8–11 jaar)</span><br>
                                Vragen met herkenbare gebeurtenissen en duidelijke keuzes, ideaal voor de bovenbouw basisschool.
                            </li>
                            <li>
                                <span class="font-semibold text-[#2b1b0f]">Niveau 2 – Uitdagend (± 12–15 jaar)</span><br>
                                Meer detail, jaartallen en verbanden tussen gebeurtenissen, passend bij onderbouw voortgezet onderwijs.
                            </li>
                            <li>
                                <span class="font-semibold text-[#2b1b0f]">Niveau 3 – Expert (16+)</span><br>
                                Vragen met preciezere data, context en verdieping voor gevorderde leerlingen en geïnteresseerde volwassenen.
                            </li>
                        </ul>
                        <p class="mt-4 text-xs text-[#7a5a37]">
                            Tip: speel het spel meerdere keren met verschillende leeftijden om de verschillende niveaus te vergelijken.
                        </p>
                    </aside>
                </div>
            </div>
        </section>

        <!-- Quiz over de locatie -->
        <section id="quiz" class="py-14 sm:py-16 bg-[#2b1b0f] text-[#fcefdc] border-t border-[#c29c6b]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-1">
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-[0.16em] uppercase">
                            Quiz over de locatie
                        </h2>
                        <p class="mt-3 text-sm sm:text-base text-[#f2ddbf]">
                            Bedenk zelf vragen over jouw school, museum, wijk of plein, voeg antwoordopties toe en speel daarna je eigen quiz om je kennis te testen.
                        </p>
                    </div>
                    <div class="flex-1 flex md:justify-end">
                        <button
                            id="quiz-start"
                            type="button"
                            class="inline-flex items-center justify-center rounded-full bg-[#f5e4c2] px-8 py-3 text-sm font-semibold tracking-wide uppercase text-[#2b1b0f] shadow-md shadow-black/30 hover:bg-[#f9eed7] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f5e4c2] focus:ring-offset-[#2b1b0f] transition"
                        >
                            Start de quiz
                        </button>
                    </div>
                </div>

                <!-- Quiz-bouwer: bezoekers maken hun eigen vragen -->
                <div
                    id="quiz-builder"
                    class="border border-[#f5e4c2]/40 bg-[#3b2514] rounded-2xl px-5 sm:px-6 py-6 sm:py-7 shadow-[0_18px_40px_rgba(0,0,0,0.45)]"
                >
                    <div class="grid gap-4 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] items-start">
                        <div class="space-y-4">
                            <div>
                                <label for="quiz-location-name" class="block text-xs font-semibold tracking-[0.16em] uppercase text-[#f2ddbf]/80">
                                    Locatie
                                </label>
                                <input
                                    id="quiz-location-name"
                                    type="text"
                                    class="mt-1 w-full rounded-full border border-[#f5e4c2]/60 bg-[#4a3120] px-4 py-2 text-xs sm:text-sm text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-2 focus:ring-[#f5e4c2] focus:border-[#f5e4c2]"
                                    placeholder="Bijv. je school, museum, wijk of een plein in de buurt"
                                >
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label for="quiz-builder-question" class="block text-xs font-semibold tracking-[0.16em] uppercase text-[#f2ddbf]/80 mb-1.5">
                                        Vraag
                                    </label>
                                    <textarea
                                        id="quiz-builder-question"
                                        rows="3"
                                        class="w-full rounded-xl border border-[#f5e4c2]/60 bg-[#4a3120] px-3 py-2 text-xs sm:text-sm text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-2 focus:ring-[#f5e4c2] focus:border-[#f5e4c2]"
                                        placeholder="Bijv. Welke straat grenst direct aan dit gebouw?"
                                    ></textarea>
                                </div>
                                <div>
                                    <label for="quiz-builder-hint" class="block text-[11px] font-medium text-[#f2ddbf] mb-1">
                                        Hint (optioneel)
                                    </label>
                                    <input
                                        id="quiz-builder-hint"
                                        type="text"
                                        class="w-full rounded-full border border-[#f5e4c2]/50 bg-[#4a3120] px-3 py-1.5 text-xs text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-1 focus:ring-[#f5e4c2]"
                                        placeholder="Bijv. Denk aan de straat aan de zuidkant van het gebouw."
                                    >
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label for="quiz-builder-option-1" class="block text-[11px] font-medium text-[#f2ddbf] mb-1">
                                        Antwoord A
                                    </label>
                                    <input
                                        id="quiz-builder-option-1"
                                        type="text"
                                        class="w-full rounded-full border border-[#f5e4c2]/50 bg-[#4a3120] px-3 py-1.5 text-xs text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-1 focus:ring-[#f5e4c2]"
                                        placeholder="Bijv. Kerkstraat"
                                    >
                                </div>
                                <div>
                                    <label for="quiz-builder-option-2" class="block text-[11px] font-medium text-[#f2ddbf] mb-1">
                                        Antwoord B
                                    </label>
                                    <input
                                        id="quiz-builder-option-2"
                                        type="text"
                                        class="w-full rounded-full border border-[#f5e4c2]/50 bg-[#4a3120] px-3 py-1.5 text-xs text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-1 focus:ring-[#f5e4c2]"
                                        placeholder="Bijv. Schoollaan"
                                    >
                                </div>
                                <div>
                                    <label for="quiz-builder-option-3" class="block text-[11px] font-medium text-[#f2ddbf] mb-1">
                                        Antwoord C (optioneel)
                                    </label>
                                    <input
                                        id="quiz-builder-option-3"
                                        type="text"
                                        class="w-full rounded-full border border-[#f5e4c2]/50 bg-[#4a3120] px-3 py-1.5 text-xs text-[#fcefdc] placeholder:text-[#f2ddbf]/60 focus:outline-none focus:ring-1 focus:ring-[#f5e4c2]"
                                        placeholder="Bijv. Parkweg"
                                    >
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="flex items-center gap-2 text-xs text-[#f2ddbf]/90">
                                    <label for="quiz-builder-correct" class="font-medium">
                                        Welk antwoord is goed?
                                    </label>
                                    <select
                                        id="quiz-builder-correct"
                                        class="rounded-full border border-[#f5e4c2]/70 bg-[#4a3120] px-3 py-1.5 text-xs text-[#fcefdc] focus:outline-none focus:ring-1 focus:ring-[#f5e4c2]"
                                    >
                                        <option value="1">Antwoord A</option>
                                        <option value="2">Antwoord B</option>
                                        <option value="3">Antwoord C</option>
                                    </select>
                                </div>
                                <button
                                    id="quiz-builder-add"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full bg-[#f5e4c2] px-5 py-2 text-xs sm:text-sm font-semibold tracking-wide uppercase text-[#2b1b0f] shadow-md shadow-black/30 hover:bg-[#f9eed7] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f5e4c2] focus:ring-offset-[#2b1b0f] transition"
                                >
                                    Voeg vraag toe
                                </button>
                            </div>

                            <p id="quiz-builder-error" class="text-xs text-red-200 min-h-[1.2rem]"></p>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-[#f2ddbf]/80">
                                Overzicht van je vragen
                            </p>
                            <div
                                id="quiz-builder-list"
                                class="min-h-[3.5rem] rounded-xl border border-dashed border-[#f5e4c2]/50 bg-[#4a3120]/60 px-3 py-2 text-xs text-[#fcefdc] space-y-1"
                            >
                                <p class="text-[#f2ddbf]/85">
                                    Nog geen vragen toegevoegd. Vul een vraag in en klik op <span class="font-semibold">"Voeg vraag toe"</span>.
                                </p>
                            </div>
                            <p class="text-[11px] text-[#f2ddbf]/75">
                                Tip: maak bijvoorbeeld vragen over straatnamen, gebouwen, monumenten, waterwegen of gebeurtenissen die bij deze plek horen.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Speelmodus van de quiz -->
                <div
                    id="location-quiz"
                    class="border border-[#f5e4c2]/40 bg-[#3b2514] rounded-2xl px-5 sm:px-6 py-6 sm:py-7 shadow-[0_18px_40px_rgba(0,0,0,0.45)] hidden"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-[#f5e4c2]/30 pb-4 mb-4">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-[#f2ddbf]/80">
                                Quiz
                            </p>
                            <h3 class="text-base sm:text-lg font-semibold">
                                Geschiedenis van deze locatie
                            </h3>
                        </div>
                        <div class="flex-1 flex flex-col gap-1 text-xs text-[#f2ddbf]/80">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full bg-[#f5e4c2]/10 px-3 py-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 mr-2"></span>
                                    <span id="quiz-status-label">Actief</span>
                                </span>
                                <span id="quiz-progress" class="font-medium">
                                    Vraag 1 van 1
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[#6a422a]">
                                <div id="quiz-progress-bar" class="h-full w-0 rounded-full bg-[#f5e4c2] transition-[width] duration-300"></div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-[#4a3120] border border-[#f5e4c2]/30 rounded-lg px-4 sm:px-5 py-3.5 sm:py-4">
                            <p id="quiz-question" class="text-sm sm:text-base font-semibold leading-relaxed">
                                De quiz start zo.
                            </p>
                            <p id="quiz-hint" class="mt-1 text-xs text-[#f2ddbf]/80 italic hidden"></p>
                        </div>

                        <div id="quiz-answers" class="space-y-3">
                            <!-- Antwoorden worden dynamisch gevuld -->
                        </div>

                        <p id="quiz-feedback" class="text-sm font-medium text-[#fcefdc] min-h-[1.5rem]" aria-live="polite"></p>

                        <div id="quiz-summary" class="hidden border-t border-[#f5e4c2]/30 pt-4 mt-1">
                            <p id="quiz-summary-text" class="text-sm text-[#f2ddbf]"></p>
                            <div id="quiz-review" class="mt-3 space-y-2 text-xs sm:text-sm text-[#f2ddbf] hidden"></div>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <button
                                    id="quiz-restart"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full border border-[#f5e4c2] bg-transparent px-5 py-2 text-xs sm:text-sm font-semibold tracking-wide uppercase text-[#fcefdc] hover:bg-[#f5e4c2]/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f5e4c2] focus:ring-offset-[#2b1b0f] transition"
                                >
                                    Speel de quiz opnieuw
                                </button>
                                <a
                                    href="#spel"
                                    class="inline-flex items-center justify-center rounded-full border border-transparent bg-[#f5e4c2] px-5 py-2 text-xs sm:text-sm font-semibold tracking-wide uppercase text-[#2b1b0f] hover:bg-[#f9eed7] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f5e4c2] focus:ring-offset-[#2b1b0f] transition"
                                >
                                    Probeer ook het spel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer id="contact" class="company-footer border-t border-slate-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12 grid gap-10 md:grid-cols-3">
            <div>
                <h3 class="text-lg font-semibold">
                    <?php echo e($footerTitle); ?>
                </h3>
                <p class="mt-3 text-sm text-[#d4b98a]">
                    <?php echo $footerBody; ?>
                </p>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-[#fcefdc] tracking-wide uppercase">
                    <?php echo e($footerContactLbl); ?>
                </h4>
                <dl class="mt-3 space-y-2 text-sm text-[#d4b98a]">
                    <div>
                        <dt class="font-medium text-[#fcefdc]"><?php echo e($footerAddressLbl); ?></dt>
                        <dd><?php echo e($footerAddressVal); ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-[#fcefdc]"><?php echo e($footerPhoneLbl); ?></dt>
                        <dd><?php echo e($footerPhoneVal); ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-[#fcefdc]"><?php echo e($footerContactLbl); ?></dt>
                        <dd><?php echo e($footerContactVal); ?></dd>
                    </div>
                </dl>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-[#fcefdc] tracking-wide uppercase">In samenwerking met</h4>
                <?php renderPartnersFooterLogos('mt-3 footer-logos footer-logos-inline'); ?>
            </div>
        </div>
        <div class="border-t border-slate-300">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#b79a6c]">
                <span>&copy; <?php echo date('Y'); ?> Geschiedenis Project. Alle rechten voorbehouden.</span>
                <span class="sm:text-right">Ontwikkeld als educatief project.</span>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ageInput = document.getElementById('player-age');
            const startButton = document.getElementById('start-game');
            const gameContainer = document.getElementById('history-game');
            const placeholder = document.getElementById('history-game-placeholder');
            const questionEl = document.getElementById('game-question');
            const gameHintEl = document.getElementById('game-hint');
            const answersEl = document.getElementById('game-answers');
            const progressEl = document.getElementById('game-progress');
            const gameProgressBar = document.getElementById('game-progress-bar');
            const feedbackEl = document.getElementById('game-feedback');
            const summaryBox = document.getElementById('game-summary');
            const summaryText = document.getElementById('game-summary-text');
            const gameReviewBox = document.getElementById('game-review');
            const retryButton = document.getElementById('game-retry');
            const levelLabel = document.getElementById('game-level-label');

            if (!ageInput || !startButton || !gameContainer) {
                return;
            }

            const levelNames = {
                kind: 'Niveau 1 – Basis (± 8–11 jaar)',
                jongeren: 'Niveau 2 – Uitdagend (± 12–15 jaar)',
                volwassenen: 'Niveau 3 – Expert (16+ jaar)'
            };

            const questionBank = [
                // Niveau 1 – kinderen
                {
                    group: 'kind',
                    question: 'Welke gebeurtenis vond het eerst plaats?',
                    answers: [
                        {text: 'De bouw van de piramides in Egypte', correct: true},
                        {text: 'De uitvinding van de smartphone', correct: false},
                        {text: 'De landing op de maan', correct: false}
                    ],
                    hint: 'Denk aan hoe lang piramides al bestaan.',
                    explanation: 'De piramides zijn duizenden jaren oud, veel ouder dan ruimtevaart en smartphones.'
                },
                {
                    group: 'kind',
                    question: 'Wat hoorde het meest bij de middeleeuwen?',
                    answers: [
                        {text: 'Een ridder in een harnas', correct: true},
                        {text: 'Een elektrische auto', correct: false},
                        {text: 'Een gameconsole', correct: false}
                    ],
                    hint: 'Denk aan kastelen en toernooien.',
                    explanation: 'Ridders in harnas horen bij kastelen en de middeleeuwen.'
                },
                {
                    group: 'kind',
                    question: 'Welke uitvinding kwam eerder?',
                    answers: [
                        {text: 'De stoomtrein', correct: true},
                        {text: 'De laptop', correct: false},
                        {text: 'De tablet', correct: false}
                    ],
                    hint: 'Kijk naar oude afbeeldingen van fabrieken en vervoer.',
                    explanation: 'De stoomtrein werd in de 19e eeuw belangrijk; computers zijn veel recenter.'
                },
                {
                    group: 'kind',
                    question: 'Wat is een belangrijke gebeurtenis uit de 20e eeuw?',
                    answers: [
                        {text: 'De Eerste Wereldoorlog', correct: true},
                        {text: 'De Romeinse tijd', correct: false},
                        {text: 'De steentijd', correct: false}
                    ],
                    hint: 'Let op de jaartallen 1914–1918.',
                    explanation: 'De Eerste Wereldoorlog (1914–1918) hoort bij de 20e eeuw.'
                },

                // Niveau 2 – jongeren
                {
                    group: 'jongeren',
                    question: 'In welke eeuw begon de Franse Revolutie?',
                    answers: [
                        {text: '18e eeuw', correct: true},
                        {text: '16e eeuw', correct: false},
                        {text: '20e eeuw', correct: false}
                    ],
                    hint: 'Denk aan 1789 en het omverwerpen van een koning.',
                    explanation: 'De Franse Revolutie begon in 1789, aan het einde van de 18e eeuw.'
                },
                {
                    group: 'jongeren',
                    question: 'Zet in gedachten de gebeurtenissen op volgorde van oud naar nieuw. Welke gebeurtenis is dan de middelste? 1) Romeinse Rijk, 2) Industriële Revolutie, 3) Tweede Wereldoorlog.',
                    answers: [
                        {text: 'Industriële Revolutie', correct: true},
                        {text: 'Romeinse Rijk', correct: false},
                        {text: 'Tweede Wereldoorlog', correct: false}
                    ],
                    hint: 'Plaats de drie gebeurtenissen in een tijdlijn.',
                    explanation: 'Eerst was het Romeinse Rijk, daarna de Industriële Revolutie (18e–19e eeuw), en pas later de Tweede Wereldoorlog.'
                },
                {
                    group: 'jongeren',
                    question: 'Welke ontdekking zorgde ervoor dat mensen sneller over zee konden varen en nieuwe gebieden konden ontdekken?',
                    answers: [
                        {text: 'Betere kaarten en kompassen', correct: true},
                        {text: 'Het internet', correct: false},
                        {text: 'Elektrische verlichting', correct: false}
                    ],
                    hint: 'Waarmee navigeerden ontdekkingsreizigers vroeger op zee?',
                    explanation: 'Goede kaarten en kompassen maakten lange zeereizen en ontdekkingsreizen mogelijk.'
                },
                {
                    group: 'jongeren',
                    question: 'Waardoor werden veel mensen in steden in de 19e eeuw ineens fabrieksarbeider?',
                    answers: [
                        {text: 'Door de opkomst van machines en fabrieken', correct: true},
                        {text: 'Door de uitvinding van het schrift', correct: false},
                        {text: 'Door de ontdekking van vuur', correct: false}
                    ],
                    hint: 'Denk aan grote stoommachines en fabrieken.',
                    explanation: 'Tijdens de Industriële Revolutie zorgden machines en fabrieken voor veel nieuwe banen in steden.'
                },

                // Niveau 3 – volwassenen
                {
                    group: 'volwassenen',
                    question: 'In welk jaar begon de Tweede Wereldoorlog in Europa?',
                    answers: [
                        {text: '1939', correct: true},
                        {text: '1914', correct: false},
                        {text: '1945', correct: false}
                    ],
                    hint: 'Kijk naar het jaar waarin Duitsland Polen binnenviel.',
                    explanation: 'De Duitse inval in Polen in 1939 wordt gezien als het begin van de Tweede Wereldoorlog in Europa.'
                },
                {
                    group: 'volwassenen',
                    question: 'Welke ontwikkeling hoort het meest bij de Koude Oorlog?',
                    answers: [
                        {text: 'Wapenwedloop tussen de VS en de Sovjet-Unie', correct: true},
                        {text: 'De opkomst van het Romeinse Rijk', correct: false},
                        {text: 'De uitvinding van het wiel', correct: false}
                    ],
                    hint: 'Denk aan de periode na de Tweede Wereldoorlog met veel spanningen maar geen directe oorlog.',
                    explanation: 'De Koude Oorlog werd gekenmerkt door spanning en een wapenwedloop tussen twee machtsblokken.'
                },
                {
                    group: 'volwassenen',
                    question: 'Welke gebeurtenis markeert het einde van de middeleeuwen voor veel historici?',
                    answers: [
                        {text: 'De val van Constantinopel (1453)', correct: true},
                        {text: 'De ontdekking van vuur', correct: false},
                        {text: 'De Franse Revolutie', correct: false}
                    ],
                    hint: 'Het gaat om een belangrijke stad tussen Europa en Azië.',
                    explanation: 'De val van Constantinopel in 1453 (en soms de ontdekking van Amerika in 1492) wordt vaak gezien als einde van de middeleeuwen.'
                },
                {
                    group: 'volwassenen',
                    question: 'Welke technologische ontwikkeling hoort het sterkst bij de 2e Industriële Revolutie (±1870–1914)?',
                    answers: [
                        {text: 'Elektriciteit en lopende band-productie', correct: true},
                        {text: 'De uitvinding van het schrift', correct: false},
                        {text: 'De overgang van jagers-verzamelaars naar landbouw', correct: false}
                    ],
                    hint: 'Denk aan grote fabrieken met lopende banden en veel licht.',
                    explanation: 'Elektriciteit, staal en massaproductie zijn kernkenmerken van de 2e Industriële Revolutie.'
                }
            ];

            function shuffleArray(array) {
                const arr = array.slice();
                for (let i = arr.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [arr[i], arr[j]] = [arr[j], arr[i]];
                }
                return arr;
            }

            function getGroupByAge(age) {
                if (age <= 11) return 'kind';
                if (age <= 15) return 'jongeren';
                return 'volwassenen';
            }

            let currentQuestions = [];
            let currentIndex = 0;
            let score = 0;
            let currentGroup = 'kind';
            let acceptingAnswers = true;

            function renderQuestion() {
                const current = currentQuestions[currentIndex];
                if (!current) {
                    return;
                }

                questionEl.textContent = current.question;
                progressEl.textContent = 'Vraag ' + (currentIndex + 1) + ' van ' + currentQuestions.length;

                if (gameHintEl) {
                    if (current.hint) {
                        gameHintEl.textContent = 'Hint: ' + current.hint;
                        gameHintEl.classList.remove('hidden');
                    } else {
                        gameHintEl.textContent = '';
                        gameHintEl.classList.add('hidden');
                    }
                }

                if (gameProgressBar && currentQuestions.length) {
                    const percentage = Math.round((currentIndex / currentQuestions.length) * 100);
                    gameProgressBar.style.width = percentage + '%';
                }

                answersEl.innerHTML = '';
                feedbackEl.textContent = '';

                const shuffledAnswers = shuffleArray(current.answers);
                shuffledAnswers.forEach(function (answer) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full text-left rounded-full border border-[#6f4a1a] bg-[#f8f0dd] px-4 py-2.5 text-sm text-[#2b1b0f] hover:bg-[#f3e2bf] hover:border-[#8b1e1e] transition focus:outline-none focus:ring-2 focus:ring-[#8b1e1e] focus:ring-offset-2 focus:ring-offset-[#f5e4c2]';
                    button.textContent = answer.text;
                    button.dataset.correct = answer.correct ? '1' : '0';

                    button.addEventListener('click', function () {
                        if (!acceptingAnswers) return;
                        acceptingAnswers = false;

                        const isCorrect = button.dataset.correct === '1';
                        if (isCorrect) {
                            score++;
                            feedbackEl.textContent = 'Goed gedaan! Dit klopt.';
                            feedbackEl.classList.remove('text-[#9a1f1f]');
                            feedbackEl.classList.add('text-[#2b5b1b]');
                            button.classList.remove('border-[#6f4a1a]', 'hover:bg-[#f3e2bf]');
                            button.classList.add('border-green-700', 'bg-green-100/80');
                        } else {
                            feedbackEl.textContent = 'Niet helemaal. Lees de uitleg goed en probeer de volgende vraag!';
                            feedbackEl.classList.remove('text-[#2b5b1b]');
                            feedbackEl.classList.add('text-[#9a1f1f]');
                            button.classList.remove('border-[#6f4a1a]', 'hover:bg-[#f3e2bf]');
                            button.classList.add('border-red-700', 'bg-red-100/80');

                            const correctBtn = Array.from(answersEl.querySelectorAll('button')).find(function (b) {
                                return b.dataset.correct === '1';
                            });
                            if (correctBtn) {
                                correctBtn.classList.remove('border-[#6f4a1a]');
                                correctBtn.classList.add('border-green-700', 'bg-green-100/80');
                            }
                        }

                        if (current.explanation) {
                            const explanation = document.createElement('p');
                            explanation.className = 'mt-1 text-xs text-[#5b4631]';
                            explanation.textContent = current.explanation;
                            feedbackEl.appendChild(explanation);
                        }

                        setTimeout(function () {
                            currentIndex++;
                            if (currentIndex >= currentQuestions.length) {
                                showSummary();
                            } else {
                                acceptingAnswers = true;
                                renderQuestion();
                            }
                        }, 1200);
                    });

                    answersEl.appendChild(button);
                });
            }

            function renderGameReview() {
                if (!gameReviewBox || !currentQuestions.length) {
                    return;
                }

                gameReviewBox.innerHTML = '';

                const title = document.createElement('p');
                title.className = 'font-semibold';
                title.textContent = 'Overzicht van de vragen en juiste antwoorden:';
                gameReviewBox.appendChild(title);

                const list = document.createElement('ol');
                list.className = 'mt-1 space-y-1 list-decimal list-inside';

                currentQuestions.forEach(function (q) {
                    const li = document.createElement('li');
                    const correct = (q.answers || []).find(function (a) {
                        return a.correct;
                    });
                    const questionText = q.question || '';

                    const strong = document.createElement('span');
                    strong.className = 'font-medium';
                    strong.textContent = questionText;

                    const line = document.createElement('div');
                    line.appendChild(strong);

                    if (correct) {
                        const answerSpan = document.createElement('span');
                        answerSpan.className = 'block text-xs sm:text-sm';
                        answerSpan.textContent = 'Juiste antwoord: ' + correct.text;
                        line.appendChild(answerSpan);
                    }

                    if (q.explanation) {
                        const expl = document.createElement('span');
                        expl.className = 'block text-xs text-[#7a5a37]';
                        expl.textContent = q.explanation;
                        line.appendChild(expl);
                    }

                    li.appendChild(line);
                    list.appendChild(li);
                });

                gameReviewBox.appendChild(list);
                gameReviewBox.classList.remove('hidden');
            }

            function showSummary() {
                summaryBox.classList.remove('hidden');
                const percentage = Math.round((score / currentQuestions.length) * 100);

                let feedback;
                if (percentage === 100) {
                    feedback = 'Fantastisch! Je hebt alle vragen op dit niveau goed beantwoord.';
                } else if (percentage >= 60) {
                    feedback = 'Mooi resultaat! Je beheerst dit niveau al goed, maar er is altijd ruimte om nog meer te ontdekken.';
                } else {
                    feedback = 'Goed geprobeerd! Met nog een ronde op dit of een ander niveau leer je het steeds beter plaatsen in de tijd.';
                }

                summaryText.textContent = 'Je scoorde ' + score + ' van de ' + currentQuestions.length + ' vragen (' + percentage + '%). ' + feedback;
                renderGameReview();
            }

            function updateLevelLabel() {
                levelLabel.textContent = levelNames[currentGroup] || 'Niveau';
            }

            function startGame() {
                const age = parseInt(ageInput.value, 10);

                if (isNaN(age) || age < 6) {
                    ageInput.focus();
                    ageInput.classList.add('ring-2', 'ring-red-600');
                    feedbackEl.textContent = 'Vul een geldige leeftijd (vanaf 6 jaar) in om te starten.';
                    feedbackEl.classList.add('text-[#9a1f1f]');
                    return;
                }

                ageInput.classList.remove('ring-2', 'ring-red-600');

                currentGroup = getGroupByAge(age);
                const allForGroup = questionBank.filter(function (q) {
                    return q.group === currentGroup;
                });

                const shuffled = shuffleArray(allForGroup);
                currentQuestions = shuffled.slice(0, Math.min(4, shuffled.length));

                if (!currentQuestions.length) {
                    currentQuestions = shuffleArray(questionBank).slice(0, 4);
                }

                score = 0;
                currentIndex = 0;
                acceptingAnswers = true;

                summaryBox.classList.add('hidden');
                summaryText.textContent = '';
                if (gameReviewBox) {
                    gameReviewBox.classList.add('hidden');
                    gameReviewBox.innerHTML = '';
                }
                if (gameProgressBar) {
                    gameProgressBar.style.width = '0%';
                }

                placeholder.classList.add('hidden');
                gameContainer.classList.remove('hidden');

                updateLevelLabel();
                renderQuestion();
            }

            startButton.addEventListener('click', startGame);
            retryButton.addEventListener('click', startGame);

            ageInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    startGame();
                }
            });

            // ---- Quiz over de locatie ----
            const quizStartBtn = document.getElementById('quiz-start');
            const quizContainer = document.getElementById('location-quiz');
            const quizQuestionEl = document.getElementById('quiz-question');
            const quizHintEl = document.getElementById('quiz-hint');
            const quizAnswersEl = document.getElementById('quiz-answers');
            const quizProgressEl = document.getElementById('quiz-progress');
            const quizProgressBar = document.getElementById('quiz-progress-bar');
            const quizFeedbackEl = document.getElementById('quiz-feedback');
            const quizSummaryBox = document.getElementById('quiz-summary');
            const quizSummaryText = document.getElementById('quiz-summary-text');
            const quizReviewBox = document.getElementById('quiz-review');
            const quizRestartBtn = document.getElementById('quiz-restart');
            const quizStatusLabel = document.getElementById('quiz-status-label');

            const quizLocationInput = document.getElementById('quiz-location-name');
            const quizBuilderQuestion = document.getElementById('quiz-builder-question');
            const quizBuilderHint = document.getElementById('quiz-builder-hint');
            const quizBuilderOption1 = document.getElementById('quiz-builder-option-1');
            const quizBuilderOption2 = document.getElementById('quiz-builder-option-2');
            const quizBuilderOption3 = document.getElementById('quiz-builder-option-3');
            const quizBuilderCorrect = document.getElementById('quiz-builder-correct');
            const quizBuilderAdd = document.getElementById('quiz-builder-add');
            const quizBuilderList = document.getElementById('quiz-builder-list');
            const quizBuilderError = document.getElementById('quiz-builder-error');

            if (quizStartBtn && quizContainer && quizQuestionEl && quizBuilderQuestion && quizBuilderAdd) {
                let quizQuestions = [];
                let quizIndex = 0;
                let quizScore = 0;
                let quizAccepting = true;

                function renderQuizList() {
                    quizBuilderList.innerHTML = '';

                    if (!quizQuestions.length) {
                        const p = document.createElement('p');
                        p.className = 'text-[#f2ddbf]/85';
                        p.textContent = 'Nog geen vragen toegevoegd. Vul een vraag in en klik op "Voeg vraag toe".';
                        quizBuilderList.appendChild(p);
                        return;
                    }

                    const count = document.createElement('p');
                    count.className = 'font-semibold text-[#fcefdc]';
                    count.textContent = 'Je hebt ' + quizQuestions.length + ' vraag' + (quizQuestions.length > 1 ? 'en' : '') + ' toegevoegd.';
                    quizBuilderList.appendChild(count);

                    const list = document.createElement('ol');
                    list.className = 'mt-1 space-y-1 list-decimal list-inside';
                    quizQuestions.forEach(function (q) {
                        const li = document.createElement('li');
                        li.textContent = q.question.length > 80 ? q.question.slice(0, 77) + '…' : q.question;
                        list.appendChild(li);
                    });
                    quizBuilderList.appendChild(list);
                }

                function addQuizQuestion() {
                    quizBuilderError.textContent = '';

                    const questionText = (quizBuilderQuestion.value || '').trim();
                    const hintText = (quizBuilderHint && quizBuilderHint.value || '').trim();
                    const opt1 = (quizBuilderOption1.value || '').trim();
                    const opt2 = (quizBuilderOption2.value || '').trim();
                    const opt3 = (quizBuilderOption3.value || '').trim();

                    if (!questionText) {
                        quizBuilderError.textContent = 'Vul eerst een vraag in.';
                        quizBuilderQuestion.focus();
                        return;
                    }
                    if (!opt1 || !opt2) {
                        quizBuilderError.textContent = 'Vul minstens antwoord A en B in.';
                        if (!opt1) {
                            quizBuilderOption1.focus();
                        } else {
                            quizBuilderOption2.focus();
                        }
                        return;
                    }

                    const options = [opt1, opt2];
                    if (opt3) {
                        options.push(opt3);
                    }

                    let correctIndex = parseInt(quizBuilderCorrect.value, 10) - 1;
                    if (correctIndex < 0 || correctIndex >= options.length) {
                        correctIndex = 0;
                    }

                    const locationName = (quizLocationInput && quizLocationInput.value.trim()) || 'deze locatie';

                    const answers = options.map(function (text, index) {
                        return {
                            text: text,
                            correct: index === correctIndex
                        };
                    });

                    quizQuestions.push({
                        question: questionText,
                        hint: hintText || '',
                        answers: answers,
                        explanation: 'Deze vraag gaat over ' + locationName + '. Bespreek samen waarom het juiste antwoord klopt.'
                    });

                    quizBuilderQuestion.value = '';
                    if (quizBuilderHint) {
                        quizBuilderHint.value = '';
                    }
                    quizBuilderOption1.value = '';
                    quizBuilderOption2.value = '';
                    quizBuilderOption3.value = '';
                    quizBuilderQuestion.focus();

                    renderQuizList();
                }

                function renderQuizQuestion() {
                    const current = quizQuestions[quizIndex];
                    if (!current) {
                        return;
                    }

                    quizQuestionEl.textContent = current.question;
                    quizProgressEl.textContent = 'Vraag ' + (quizIndex + 1) + ' van ' + quizQuestions.length;

                    if (quizHintEl) {
                        if (current.hint) {
                            quizHintEl.textContent = 'Hint: ' + current.hint;
                            quizHintEl.classList.remove('hidden');
                        } else {
                            quizHintEl.textContent = '';
                            quizHintEl.classList.add('hidden');
                        }
                    }

                    if (quizProgressBar && quizQuestions.length) {
                        const percentage = Math.round((quizIndex / quizQuestions.length) * 100);
                        quizProgressBar.style.width = percentage + '%';
                    }
                    quizFeedbackEl.textContent = '';
                    quizFeedbackEl.classList.remove('text-emerald-300', 'text-red-200');

                    quizAnswersEl.innerHTML = '';
                    const shuffled = shuffleArray(current.answers);
                    shuffled.forEach(function (answer) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'w-full text-left rounded-full border border-[#f5e4c2]/70 bg-[#4a3120] px-4 py-2.5 text-sm sm:text-base text-[#fcefdc] hover:bg-[#5a3b25] hover:border-[#ffffff] transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f5e4c2] focus:ring-offset-[#2b1b0f]';
                        btn.textContent = answer.text;
                        btn.dataset.correct = answer.correct ? '1' : '0';

                        btn.addEventListener('click', function () {
                            if (!quizAccepting) return;
                            quizAccepting = false;

                            const isCorrect = btn.dataset.correct === '1';
                            if (isCorrect) {
                                quizScore++;
                                quizFeedbackEl.textContent = 'Goed gedaan! Dit antwoord past bij wat jij over deze plek hebt bedacht.';
                                quizFeedbackEl.classList.add('text-emerald-300');
                                btn.classList.remove('border-[#f5e4c2]/70', 'hover:bg-[#5a3b25]');
                                btn.classList.add('border-emerald-300', 'bg-emerald-900/40');
                            } else {
                                quizFeedbackEl.textContent = 'Niet helemaal volgens je eigen quiz. Kijk samen naar de toelichting en probeer de volgende vraag opnieuw.';
                                quizFeedbackEl.classList.add('text-red-200');
                                btn.classList.remove('border-[#f5e4c2]/70', 'hover:bg-[#5a3b25]');
                                btn.classList.add('border-red-300', 'bg-red-900/40');

                                const correctBtn = Array.from(quizAnswersEl.querySelectorAll('button')).find(function (b) {
                                    return b.dataset.correct === '1';
                                });
                                if (correctBtn) {
                                    correctBtn.classList.add('border-emerald-300', 'bg-emerald-900/40');
                                }
                            }

                            if (current.explanation) {
                                const expl = document.createElement('p');
                                expl.className = 'mt-1 text-xs text-[#f2ddbf]';
                                expl.textContent = current.explanation;
                                quizFeedbackEl.appendChild(expl);
                            }

                            setTimeout(function () {
                                quizIndex++;
                                if (quizIndex >= quizQuestions.length) {
                                    showQuizSummary();
                                } else {
                                    quizAccepting = true;
                                    renderQuizQuestion();
                                }
                            }, 1300);
                        });

                        quizAnswersEl.appendChild(btn);
                    });
                }

                function renderQuizReview() {
                    if (!quizReviewBox || !quizQuestions.length) {
                        return;
                    }

                    quizReviewBox.innerHTML = '';

                    const title = document.createElement('p');
                    title.className = 'font-semibold';
                    title.textContent = 'Overzicht van je vragen en de juiste antwoorden:';
                    quizReviewBox.appendChild(title);

                    const list = document.createElement('ol');
                    list.className = 'mt-1 space-y-1 list-decimal list-inside';

                    quizQuestions.forEach(function (q) {
                        const li = document.createElement('li');
                        const correct = (q.answers || []).find(function (a) {
                            return a.correct;
                        });
                        const questionText = q.question || '';

                        const strong = document.createElement('span');
                        strong.className = 'font-medium';
                        strong.textContent = questionText;

                        const line = document.createElement('div');
                        line.appendChild(strong);

                        if (correct) {
                            const answerSpan = document.createElement('span');
                            answerSpan.className = 'block text-xs sm:text-sm';
                            answerSpan.textContent = 'Juiste antwoord: ' + correct.text;
                            line.appendChild(answerSpan);
                        }

                        if (q.hint) {
                            const hintSpan = document.createElement('span');
                            hintSpan.className = 'block text-[11px] text-[#f2ddbf]/80';
                            hintSpan.textContent = 'Hint die je had opgegeven: ' + q.hint;
                            line.appendChild(hintSpan);
                        }

                        li.appendChild(line);
                        list.appendChild(li);
                    });

                    quizReviewBox.appendChild(list);
                    quizReviewBox.classList.remove('hidden');
                }

                function showQuizSummary() {
                    quizSummaryBox.classList.remove('hidden');
                    quizStatusLabel.textContent = 'Afgerond';
                    const percentage = Math.round((quizScore / quizQuestions.length) * 100);

                    let text;
                    if (percentage === 100) {
                        text = 'Top! Je hebt alle vragen van je eigen quiz goed beantwoord. Jij kent deze locatie heel goed.';
                    } else if (percentage >= 60) {
                        text = 'Mooi gedaan! Je kent de locatie al best goed. Misschien kun je nog een paar extra vragen bedenken voor de volgende ronde.';
                    } else {
                        text = 'Je hebt al een begin gemaakt. Door de quiz nog een keer te spelen of nieuwe vragen te maken, leer je de omgeving beter kennen.';
                    }

                    quizSummaryText.textContent = 'Je scoorde ' + quizScore + ' van de ' + quizQuestions.length + ' vragen (' + percentage + '%). ' + text;
                    renderQuizReview();
                }

                function startQuiz() {
                    quizBuilderError.textContent = '';

                    if (!quizQuestions.length) {
                        quizBuilderError.textContent = 'Maak eerst je eigen quiz door minstens één vraag toe te voegen.';
                        quizBuilderQuestion.focus();
                        return;
                    }

                    quizIndex = 0;
                    quizScore = 0;
                    quizAccepting = true;
                    quizStatusLabel.textContent = 'Actief';
                    quizSummaryBox.classList.add('hidden');
                    quizSummaryText.textContent = '';
                    if (quizReviewBox) {
                        quizReviewBox.classList.add('hidden');
                        quizReviewBox.innerHTML = '';
                    }
                    if (quizProgressBar) {
                        quizProgressBar.style.width = '0%';
                    }

                    quizContainer.classList.remove('hidden');
                    renderQuizQuestion();
                    quizContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                quizBuilderAdd.addEventListener('click', addQuizQuestion);
                quizStartBtn.addEventListener('click', startQuiz);
                quizRestartBtn.addEventListener('click', startQuiz);

                renderQuizList();
            }
        });
    </script>
</body>
</html>
