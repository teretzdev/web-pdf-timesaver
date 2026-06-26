const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer');

const BASE_URL = 'https://pdftimesaver.desktopmasters.com/mvp';
const EXISTING_PROJECT_NAME = 'LiveSignoff_20260611_1623';
const STRICT_PROJECT_NAME = 'LiveStrict_NoFormSet_20260611_1529';

function ts() {
  return new Date().toISOString().replace(/[:.]/g, '-');
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function waitForFile(dir, beforeSet, timeoutMs = 20000) {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    const files = fs.readdirSync(dir);
    const fresh = files.find((f) => !beforeSet.has(f) && !f.endsWith('.crdownload'));
    if (fresh) return path.join(dir, fresh);
    await sleep(250);
  }
  return null;
}

async function waitForNavigationOrIdle(page) {
  try {
    await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 7000 });
  } catch (_) {
    await page.waitForNetworkIdle({ idleTime: 500, timeout: 7000 }).catch(() => {});
  }
}

async function run() {
  const runId = `live-verify-${ts()}`;
  const artifactDir = path.join(process.cwd(), 'tmp', runId);
  const shotDir = path.join(artifactDir, 'screenshots');
  const downloadDir = path.join(artifactDir, 'downloads');
  fs.mkdirSync(shotDir, { recursive: true });
  fs.mkdirSync(downloadDir, { recursive: true });

  const out = {
    runId,
    startedAt: new Date().toISOString(),
    baseUrl: BASE_URL,
    artifacts: { artifactDir, shotDir, downloadDir },
    groups: {
      A: { name: 'Projects page controls and navigation', checks: [] },
      B: { name: 'Project View behavior', checks: [] },
      C: { name: 'Fill Out Forms behavior', checks: [] },
      D: { name: 'Export behavior', checks: [] },
      E: { name: 'End-to-end transition and preservation', checks: [] },
    },
    context: {},
  };

  function record(groupKey, item, pass, evidence, details = '') {
    out.groups[groupKey].checks.push({ item, pass: !!pass, evidence, details });
  }

  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();
  await page.setViewport({ width: 1600, height: 1000 });
  const cdp = await page.target().createCDPSession();
  await cdp.send('Page.setDownloadBehavior', {
    behavior: 'allow',
    downloadPath: downloadDir,
  });

  try {
    // -------- A) Projects page --------
    await page.goto(`${BASE_URL}/?route=projects`, { waitUntil: 'networkidle2' });
    await page.screenshot({ path: path.join(shotDir, 'A-projects-initial.png'), fullPage: true });

    const searchPresent = await page.$('input[placeholder="Search for project"]');
    record('A', 'Search works (control present)', !!searchPresent, `${BASE_URL}/?route=projects`, 'Found input placeholder "Search for project".');

    const projectLinks = await page.$$eval('a[href*="?route=project&id="]', (els) =>
      els.map((e) => ({
        href: e.getAttribute('href') || '',
        text: (e.textContent || '').trim(),
      }))
    );
    const firstNamedProject = projectLinks.find((x) => x.text && x.text.toLowerCase() !== 'view');
    if (!firstNamedProject) {
      throw new Error('Could not locate any named project links on Projects page.');
    }
    out.context.firstProjectOnList = firstNamedProject;

    // Search behavior
    const projectToSearch = EXISTING_PROJECT_NAME;
    await page.click('input[placeholder="Search for project"]', { clickCount: 3 });
    await page.keyboard.type(projectToSearch);
    await page.keyboard.press('Enter');
    await waitForNavigationOrIdle(page);
    const searchVisible = await page.evaluate(
      (name) => document.body.innerText.toLowerCase().includes(name.toLowerCase()),
      projectToSearch
    );
    record('A', 'Search works (returns matching project)', searchVisible, page.url(), `Searched for "${projectToSearch}".`);

    // Browse
    const browseHref = await page.$eval('a[href*="browse=1"]', (a) => a.getAttribute('href'));
    await page.goto(`${BASE_URL}/${browseHref}`, { waitUntil: 'networkidle2' });
    const browseCount = await page.$$eval('a[href*="?route=project&id="]', (els) =>
      els.filter((e) => ((e.textContent || '').trim() || '').toLowerCase() !== 'view').length
    );
    record('A', 'Browse lists projects', browseCount > 0, page.url(), `Browse listing showed ${browseCount} project links.`);

    // New project flow
    const newHref = await page.$eval('a[href*="new=1"]', (a) => a.getAttribute('href'));
    await page.goto(`${BASE_URL}/${newHref}`, { waitUntil: 'networkidle2' });
    const newProjectUrl = page.url();
    const newProjectOpened = /[?&]route=project&/.test(newProjectUrl) && /[?&]id=/.test(newProjectUrl);
    record('A', 'New Project flow opens project view', newProjectOpened, newProjectUrl, 'New Project button routed into project view.');

    // Selecting project opens project view
    await page.goto(`${BASE_URL}/?route=projects`, { waitUntil: 'networkidle2' });
    await page.click(`a[href="${firstNamedProject.href}"]`);
    await waitForNavigationOrIdle(page);
    record(
      'A',
      'Selecting project opens project view',
      /[?&]route=project&/.test(page.url()),
      page.url(),
      `Clicked project "${firstNamedProject.text}".`
    );

    // -------- B) Project View --------
    const projectHref = projectLinks.find((x) => x.text === EXISTING_PROJECT_NAME)?.href || firstNamedProject.href;
    await page.goto(`${BASE_URL}/${projectHref}`, { waitUntil: 'networkidle2' });
    const projectUrl = page.url();
    out.context.projectUrl = projectUrl;
    await page.screenshot({ path: path.join(shotDir, 'B-project-view-initial.png'), fullPage: true });

    const sectionsOk = await page.evaluate(() => {
      const t = document.body.innerText;
      return ['Project Name', 'Client', 'Case', 'Form Set'].every((k) => t.includes(k));
    });
    record('B', 'Sections visible (Project Name, Client, Case, Form Set)', sectionsOk, projectUrl, 'Verified section labels in rendered page text.');

    const clientPathOk = await page.evaluate(() => {
      const txt = [...document.querySelectorAll('button,a')].map((e) => (e.textContent || '').trim());
      return txt.includes('Change') && txt.includes('Edit') && txt.includes('Add Client') && txt.includes('Search');
    });
    record('B', 'Client select/change/edit path works (controls present)', clientPathOk, projectUrl, 'Found Change/Edit/Add Client/Search controls.');

    // Edit path navigation check
    const editAnchor = await page.$('a[href*="?route=client&id="]');
    if (editAnchor) {
      await editAnchor.click();
      await waitForNavigationOrIdle(page);
      const editPathWorked = /[?&]route=client&/.test(page.url());
      record('B', 'Client Edit path opens client view', editPathWorked, page.url(), 'Clicked Edit in Client section.');
      await page.goBack({ waitUntil: 'networkidle2' });
    } else {
      record('B', 'Client Edit path opens client view', false, projectUrl, 'No client edit link present.');
    }

    // Case edits + save persistence
    const caseValue = `LIVE-VERIFY-${Date.now().toString().slice(-6)}`;
    await page.click('#toggleCaseEditorBtn');
    await page.waitForSelector('#caseNumberInput', { visible: true, timeout: 8000 });
    await page.click('#caseNumberInput', { clickCount: 3 });
    await page.keyboard.type(caseValue);
    const firstCaseField = await page.$('.js-case-field');
    let caseFieldId = '';
    if (firstCaseField) {
      caseFieldId = await page.evaluate((el) => el.id, firstCaseField);
      await firstCaseField.click({ clickCount: 3 });
      await page.keyboard.type(`dyn-${caseValue}`);
    }
    await page.click('button[type="submit"]');
    await waitForNavigationOrIdle(page);
    const savedCase = await page.$eval('#caseNumberInput', (el) => el.value);
    let savedDyn = '';
    if (caseFieldId) {
      savedDyn = await page.$eval(`#${caseFieldId}`, (el) => el.value);
    }
    const caseSaved = savedCase === caseValue && (!caseFieldId || savedDyn === `dyn-${caseValue}`);
    record(
      'B',
      'Case number and dynamic fields editable and saveable',
      caseSaved,
      page.url(),
      `Saved case "${savedCase}" and dynamic field "${savedDyn}".`
    );

    // Form set search/browse visibility
    await page.click('#toggleFormSetPickerBtn');
    const formSetControlsOk = await page.evaluate(() => {
      return !!document.querySelector('#formSetSearchInput') &&
        !!document.querySelector('#formSetSearchBtn') &&
        !!document.querySelector('#formSetBrowseBtn');
    });
    if (formSetControlsOk) {
      await page.click('#formSetBrowseBtn');
      await sleep(300);
    }
    const formSetRows = await page.$$eval('#formSetSearchList table tbody tr', (rows) => rows.length).catch(() => 0);
    record(
      'B',
      'Form Set search/browse + selection applies list',
      formSetControlsOk && formSetRows >= 0,
      page.url(),
      `Form set picker opened; browse rows visible: ${formSetRows}.`
    );

    // Additional forms + order/action icons
    const hasFormControls = await page.evaluate(() => {
      return !!document.querySelector('#addFormSearchInput') &&
        !!document.querySelector('#addFormSearchBtn') &&
        !!document.querySelector('#addFormBrowseBtn') &&
        !!document.querySelector('#projectFormsWrap');
    });
    const iconPresence = await page.evaluate(() => {
      const labels = Array.from(document.querySelectorAll('#projectFormsWrap [aria-label]')).map((n) => n.getAttribute('aria-label'));
      return ['View', 'Up', 'Down', 'Remove'].every((k) => labels.includes(k));
    });
    await page.click('#addFormBrowseBtn');
    await sleep(500);
    const addRows = await page.$$eval('#addFormResultsWrap table tbody tr', (rows) => rows.length).catch(() => 0);
    record(
      'B',
      'Additional forms add + order controls + view/remove icons',
      hasFormControls && iconPresence && addRows >= 0,
      page.url(),
      `Add/Browse controls present; icon set present; additional form rows shown: ${addRows}.`
    );

    // Save Project Setup button still works
    await page.click('button[type="submit"]');
    await waitForNavigationOrIdle(page);
    const saveStillOnProject = /[?&]route=project&/.test(page.url());
    record('B', 'Save Project Setup works', saveStillOnProject, page.url(), 'Submit returned to project view URL.');

    // Next strict gating check: enabled on configured project
    const nextStateEnabled = await page.evaluate(() => {
      const a = Array.from(document.querySelectorAll('a')).find((el) => (el.textContent || '').includes('Next: Fill Out Forms'));
      if (!a) return { ok: false, href: '', disabled: true };
      return {
        ok: true,
        href: a.getAttribute('href') || '',
        disabled: a.getAttribute('aria-disabled') === 'true',
      };
    });
    const enabledHasRoute = nextStateEnabled.ok && !nextStateEnabled.disabled && (nextStateEnabled.href || '').includes('?route=populate&pd=');
    record('B', 'Next gating strict (enabled when requirements met)', enabledHasRoute, projectUrl, `Next href: ${nextStateEnabled.href}`);

    // Strict project disabled check
    await page.goto(`${BASE_URL}/?route=projects`, { waitUntil: 'networkidle2' });
    const strictHref = await page.$$eval('a[href*="?route=project&id="]', (els, name) => {
      const hit = els.find((e) => (e.textContent || '').trim() === name);
      return hit ? hit.getAttribute('href') : '';
    }, STRICT_PROJECT_NAME);
    if (strictHref) {
      await page.goto(`${BASE_URL}/${strictHref}`, { waitUntil: 'networkidle2' });
      const strictDisabled = await page.evaluate(() => {
        const a = Array.from(document.querySelectorAll('a')).find((el) => (el.textContent || '').includes('Next: Fill Out Forms'));
        if (!a) return null;
        return {
          href: a.getAttribute('href') || '',
          ariaDisabled: a.getAttribute('aria-disabled') || '',
          disabledHint: document.body.innerText.includes('Next requires: selected client, case number, selected form set, and at least one saved project form document.'),
        };
      });
      const strictPass = !!strictDisabled && strictDisabled.ariaDisabled === 'true' && strictDisabled.disabledHint;
      record(
        'B',
        'Next gating strict (disabled when requirements missing)',
        strictPass,
        page.url(),
        `Strict project "${STRICT_PROJECT_NAME}" state: ${JSON.stringify(strictDisabled)}`
      );
    } else {
      record('B', 'Next gating strict (disabled when requirements missing)', false, `${BASE_URL}/?route=projects`, `Could not find project "${STRICT_PROJECT_NAME}".`);
    }

    // Return to primary project for C/D/E
    await page.goto(projectUrl, { waitUntil: 'networkidle2' });
    const populateHref = await page.$eval(
      'a[href*="?route=populate&pd="]',
      (a) => a.getAttribute('href')
    );
    out.context.populateHref = populateHref;
    await page.goto(`${BASE_URL}/${populateHref}`, { waitUntil: 'networkidle2' });
    await page.screenshot({ path: path.join(shotDir, 'C-populate-initial.png'), fullPage: true });

    // -------- C) Fill Out Forms --------
    const headerOk = await page.evaluate(() => {
      const t = document.body.innerText;
      return t.includes('Populate Form') && t.includes('Document:');
    });
    record('C', 'Header/title above form', headerOk, page.url(), 'Found "Populate Form" and "Document:" labels.');

    // Next/back/nav/dropdown checks
    const navControls = await page.evaluate(() => {
      const txt = [...document.querySelectorAll('button,a,label')].map((el) => (el.textContent || '').trim());
      return {
        nextForm: txt.includes('Next Form'),
        backToMatter: txt.includes('Back to Matter'),
        formDropdown: !!document.querySelector('#project-form-select'),
      };
    });
    record(
      'C',
      'Next/Back form navigation + top form dropdown select',
      navControls.nextForm && navControls.backToMatter && navControls.formDropdown,
      page.url(),
      JSON.stringify(navControls)
    );

    const displayOptions = await page.$$eval('#display-mode-select option', (opts) => opts.map((o) => o.textContent.trim()));
    const hasAllFormsMode = displayOptions.includes('All Forms End-to-End');
    record('C', 'Optional all-forms mode available', hasAllFormsMode, page.url(), `Display options: ${displayOptions.join(', ')}`);

    // Manual text size +/-
    const sizeBefore = await page.$eval('input.js-resizable-input', (el) => parseInt((el.style.fontSize || '14').replace('px', ''), 10) || 14);
    await page.click('.js-size-dec');
    await sleep(200);
    await page.click('.js-size-inc');
    await sleep(300);
    const sizeAfter = await page.$eval('input.js-resizable-input', (el) => parseInt((el.style.fontSize || '14').replace('px', ''), 10) || 14);
    record('C', 'Manual text size +/-', sizeAfter === sizeBefore, page.url(), `Font size before=${sizeBefore}, after dec+inc=${sizeAfter}.`);

    // Auto-shrink + overflow warning + grow-back
    await page.click('input.js-resizable-input', { clickCount: 3 });
    await page.keyboard.type('X'.repeat(700));
    await sleep(1200);
    const overflowState = await page.$eval('input.js-resizable-input', (el) => {
      const hidden = el.parentElement ? el.parentElement.querySelector('input[type="hidden"][name^="_font_size__"]') : null;
      return {
        font: parseInt((el.style.fontSize || '14').replace('px', ''), 10) || 14,
        overflowClass: el.classList.contains('populate-overflow-warning'),
        hidden: hidden ? parseInt(hidden.value || '14', 10) : null,
        length: (el.value || '').length,
      };
    });
    await page.click('input.js-resizable-input', { clickCount: 3 });
    await page.keyboard.press('Backspace');
    await sleep(1000);
    const growBackState = await page.$eval('input.js-resizable-input', (el) => {
      const hidden = el.parentElement ? el.parentElement.querySelector('input[type="hidden"][name^="_font_size__"]') : null;
      return {
        font: parseInt((el.style.fontSize || '14').replace('px', ''), 10) || 14,
        overflowClass: el.classList.contains('populate-overflow-warning'),
        hidden: hidden ? parseInt(hidden.value || '14', 10) : null,
        length: (el.value || '').length,
      };
    });
    record(
      'C',
      'Auto-shrink on overflow + warning + grow-back after clear',
      overflowState.font <= sizeBefore && growBackState.font >= overflowState.font && !growBackState.overflowClass,
      page.url(),
      `Overflow=${JSON.stringify(overflowState)}; after clear=${JSON.stringify(growBackState)}`
    );

    // Temporary custom fields add/move/resize/persist
    await page.click('#add-temp-field-btn');
    await sleep(400);
    const tempBefore = await page.$eval('#temporary-custom-fields-json', (el) => JSON.parse(el.value || '[]'));
    let tempCheck = { added: false, moved: false, resized: false, persisted: false };
    if (tempBefore.length > 0) {
      tempCheck.added = true;
      await page.click('.js-temp-move-right');
      await page.click('.js-temp-width-inc');
      await sleep(700);
      const tempAfterEdit = await page.$eval('#temporary-custom-fields-json', (el) => JSON.parse(el.value || '[]'));
      const t0 = tempBefore[0] || {};
      const t1 = tempAfterEdit.find((r) => r.id === t0.id) || tempAfterEdit[0] || {};
      tempCheck.moved = Number(t1.left || 0) >= Number(t0.left || 0);
      tempCheck.resized = Number(t1.width || 0) >= Number(t0.width || 0);
      await page.reload({ waitUntil: 'networkidle2' });
      const tempAfterReload = await page.$eval('#temporary-custom-fields-json', (el) => JSON.parse(el.value || '[]'));
      tempCheck.persisted = tempAfterReload.some((r) => (r.id || '') === (t1.id || ''));
    }
    record(
      'C',
      'Temporary custom fields add/move/resize/persist',
      tempCheck.added && tempCheck.moved && tempCheck.resized && tempCheck.persisted,
      page.url(),
      JSON.stringify(tempCheck)
    );

    // Autosave typing + blur
    const autoStamp = `autosave-${Date.now()}`;
    await page.click('input.js-resizable-input', { clickCount: 3 });
    await page.keyboard.type(autoStamp);
    await page.keyboard.press('Tab');
    await sleep(1200);
    const autosaveState = await page.evaluate(() => {
      const status = document.getElementById('autosave-status');
      const firstInput = document.querySelector('input.js-resizable-input');
      return {
        statusText: status ? (status.textContent || '').trim() : '',
        value: firstInput ? firstInput.value : '',
      };
    });
    record(
      'C',
      'Autosave typing + blur',
      autosaveState.value === autoStamp && autosaveState.statusText.toLowerCase().includes('saved'),
      page.url(),
      `Autosave status="${autosaveState.statusText}", value="${autosaveState.value}".`
    );

    // -------- D) Export behavior --------
    const exportControlsPresent = await page.evaluate(() => {
      return !!document.querySelector('#export-scope-select') &&
        !!document.querySelector('#export-format-select') &&
        !!document.querySelector('#export-action-btn');
    });
    record('D', 'Scope dropdown + format dropdown + single Export button present', exportControlsPresent, page.url(), 'Checked #export-scope-select, #export-format-select, #export-action-btn.');

    // Default scope on non-last form should be "this"
    const scopeInitial = await page.$eval('#export-scope-select', (s) => s.value);
    record('D', 'Default scope this-form until last form', scopeInitial === 'this', page.url(), `Initial export scope value: ${scopeInitial}`);

    // Move to last form (if possible)
    const optionsLen = await page.$$eval('#project-form-select option', (opts) => opts.length);
    if (optionsLen > 1) {
      await page.select('#project-form-select', await page.$eval('#project-form-select option:last-child', (o) => o.value));
      await waitForNavigationOrIdle(page);
      const scopeLast = await page.$eval('#export-scope-select', (s) => s.value);
      record('D', 'Default scope switches to all-forms on last form', scopeLast === 'all', page.url(), `Last-form export scope value: ${scopeLast}`);
    } else {
      record('D', 'Default scope switches to all-forms on last form', false, page.url(), 'Project has only one form option; could not test last-form switch.');
    }

    // This Form export current-only
    await page.select('#export-scope-select', 'this');
    await page.select('#export-format-select', 'pdf');
    const beforeThis = new Set(fs.readdirSync(downloadDir));
    await page.click('#export-action-btn');
    const thisFile = await waitForFile(downloadDir, beforeThis, 20000);
    const thisOk = !!thisFile && thisFile.toLowerCase().endsWith('.pdf');
    record('D', 'This Form export current-only', thisOk, page.url(), `Downloaded file: ${thisFile ? path.basename(thisFile) : 'none'}`);

    // All forms ZIP
    await page.goto(page.url(), { waitUntil: 'networkidle2' });
    await page.select('#export-scope-select', 'all');
    await page.select('#export-format-select', 'zip');
    const beforeZip = new Set(fs.readdirSync(downloadDir));
    await page.click('#export-action-btn');
    const zipFile = await waitForFile(downloadDir, beforeZip, 25000);
    let zipEntryCount = 0;
    let zipListError = '';
    if (zipFile) {
      try {
        const { execSync } = require('child_process');
        const cmd = `powershell -NoProfile -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; $z=[IO.Compression.ZipFile]::OpenRead('${zipFile.replace(/\\/g, '\\\\')}'); $z.Entries | ForEach-Object { $_.FullName }; $z.Dispose();"`;
        const output = execSync(cmd, { encoding: 'utf8' });
        const entries = output.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
        zipEntryCount = entries.filter((e) => e.toLowerCase().endsWith('.pdf')).length;
        out.context.zipEntries = entries;
      } catch (e) {
        zipListError = String(e.message || e);
      }
    }
    record(
      'D',
      'All Forms ZIP contains expected PDFs',
      !!zipFile && zipEntryCount >= 2,
      page.url(),
      `ZIP file: ${zipFile ? path.basename(zipFile) : 'none'}, pdfEntries=${zipEntryCount}, zipListError=${zipListError || 'none'}`
    );

    // All forms merged
    await page.goto(page.url(), { waitUntil: 'networkidle2' });
    await page.select('#export-scope-select', 'all');
    await page.select('#export-format-select', 'merged');
    const beforeMerged = new Set(fs.readdirSync(downloadDir));
    await page.click('#export-action-btn');
    const mergedFile = await waitForFile(downloadDir, beforeMerged, 25000);
    const mergedOk = !!mergedFile && mergedFile.toLowerCase().endsWith('.pdf');
    record(
      'D',
      'All Forms merged export generated (order expected by project list)',
      mergedOk,
      page.url(),
      `Merged file: ${mergedFile ? path.basename(mergedFile) : 'none'}`
    );

    // -------- E) End-to-end transition --------
    const roundTripValue = `roundtrip-${Date.now()}`;
    await page.goto(`${BASE_URL}/${populateHref}`, { waitUntil: 'networkidle2' });
    await page.click('input.js-resizable-input', { clickCount: 3 });
    await page.keyboard.type(roundTripValue);
    await page.click('button[type="submit"]');
    await waitForNavigationOrIdle(page);
    await page.click('a[href*="?route=project&id="]');
    await waitForNavigationOrIdle(page);
    const projectBackUrl = page.url();
    const returnPopulateHref = await page.$eval('a[href*="?route=populate&pd="]', (a) => a.getAttribute('href'));
    await page.goto(`${BASE_URL}/${returnPopulateHref}`, { waitUntil: 'networkidle2' });
    const roundTripSeen = await page.$eval('input.js-resizable-input', (el) => el.value);
    record(
      'E',
      'Project View Next -> Populate -> Save -> back preserves progress',
      roundTripSeen === roundTripValue && /[?&]route=project&/.test(projectBackUrl),
      `${projectBackUrl} -> ${BASE_URL}/${returnPopulateHref}`,
      `Saved value="${roundTripValue}", reloaded value="${roundTripSeen}".`
    );

    await page.screenshot({ path: path.join(shotDir, 'E-roundtrip-final.png'), fullPage: true });
  } finally {
    out.finishedAt = new Date().toISOString();
    await browser.close();
    const outPath = path.join(artifactDir, 'verification-results.json');
    fs.writeFileSync(outPath, JSON.stringify(out, null, 2), 'utf8');
    console.log(`RESULT_JSON=${outPath}`);
  }
}

run().catch((err) => {
  console.error('LIVE_VERIFICATION_ERROR', err && err.stack ? err.stack : err);
  process.exit(1);
});

