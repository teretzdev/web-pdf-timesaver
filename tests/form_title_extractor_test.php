<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/form_title_extractor.php';

use WebPdfTimeSaver\Mvp\FormTitleExtractor;

$failures = 0;
function ok(bool $cond, string $msg): void
{
    global $failures;
    if (!$cond) {
        echo "FAIL: $msg\n";
        $failures++;
    } else {
        echo '.';
    }
}

function assertIdentity(string $raw, string $expectedNumber, string $expectedName, string $templateId = 'fl100_12731de81e'): void
{
    $identity = FormTitleExtractor::parseFormIdentityFromTitle($raw, $templateId, 'fl100_12731de81e.pdf');
    ok(
        ($identity['formNumber'] ?? '') === $expectedNumber,
        "formNumber for \"$raw\" expected \"$expectedNumber\", got \"{$identity['formNumber']}\""
    );
    ok(
        ($identity['formName'] ?? '') === $expectedName,
        "formName for \"$raw\" expected \"$expectedName\", got \"{$identity['formName']}\""
    );
}

assertIdentity(
    'FL-100 Petition—Marriage/Domestic Partnership',
    'FL-100',
    'Petition - Marriage/Domestic Partnership'
);

assertIdentity(
    'FL-100 - Petition - Marriage/Domestic Partnership',
    'FL-100',
    'Petition - Marriage/Domestic Partnership'
);

assertIdentity(
    'Petition - Marriage/Domestic Partnership',
    'FL-100',
    'Petition - Marriage/Domestic Partnership'
);

assertIdentity(
    'FL-100 - Petition',
    'FL-100',
    'Petition'
);

assertIdentity(
    'FL-200 Petition to Determine Parental Relationship',
    'FL-200',
    'Petition to Determine Parental Relationship',
    'fl-200_6c6a534d3a'
);

$dashNormalized = FormTitleExtractor::normalizeUnicodeDashes('Petition—Marriage/Domestic Partnership');
ok(
    $dashNormalized === 'Petition - Marriage/Domestic Partnership',
    "normalizeUnicodeDashes expected spaced hyphens, got \"$dashNormalized\""
);

$catalogIdentity = FormTitleExtractor::parseFormIdentityFromTitle('', 'fl100_12731de81e', 'fl100_12731de81e.pdf');
ok(
    ($catalogIdentity['formNumber'] ?? '') === 'FL-100',
    'Catalog fallback should derive FL-100 from template id'
);
ok(
    ($catalogIdentity['formName'] ?? '') === 'Petition - Marriage/Domestic Partnership',
    'Catalog fallback should return full catalog name'
);

$fl200CatalogIdentity = FormTitleExtractor::parseFormIdentityFromTitle('', 'fl-200_6c6a534d3a', 'FL-200.pdf');
ok(
    ($fl200CatalogIdentity['formNumber'] ?? '') === 'FL-200',
    'Catalog fallback should derive FL-200 from template id'
);
ok(
    ($fl200CatalogIdentity['formName'] ?? '') === 'Petition to Determine Parental Relationship',
    'Catalog fallback should return FL-200 petition title'
);

$fl170CatalogIdentity = FormTitleExtractor::parseFormIdentityFromTitle('', 'fl-170_deadbeef00', 'FL-170.pdf');
ok(
    ($fl170CatalogIdentity['formNumber'] ?? '') === 'FL-170',
    'Catalog fallback should derive FL-170 from template id'
);
ok(
    ($fl170CatalogIdentity['formName'] ?? '') === 'Declaration for Default or Uncontested Dissolution',
    'Catalog fallback should keep FL-170 declaration title'
);

echo "\n\n" . ($failures === 0 ? 'FORM TITLE EXTRACTOR TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);
